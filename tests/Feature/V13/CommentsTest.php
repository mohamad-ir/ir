<?php

use App\Livewire\Comments;
use App\Mail\CommentAwaitingModeration;
use App\Mail\CommentReply;
use App\Models\Article;
use App\Models\ClinicalCase;
use App\Models\Comment;
use App\Models\User;
use App\Models\Video;
use App\Services\Comments\CommentService;
use App\Support\Settings;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\URL;
use Illuminate\Validation\ValidationException;
use Livewire\Livewire;

beforeEach(function () {
    $this->seedRoles();
    Settings::set('site.notification_email', 'team@example.com');
    Mail::fake();
});

function comments(): CommentService
{
    return app(CommentService::class);
}

it('holds a member’s first comment and publishes later ones', function () {
    $article = Article::factory()->create();
    $user = User::factory()->create();

    $first = comments()->post($user, $article, 'Very clear explanation of the OCT signs.');
    expect($first->status)->toBe('pending')->and($article->fresh()->comments_count)->toBe(0);
    Mail::assertQueued(CommentAwaitingModeration::class);

    comments()->approve($first, $this->staff());
    $second = comments()->post($user, $article, 'And a follow-up question about the follow-up interval.');
    expect($second->status)->toBe('approved')->and($article->fresh()->comments_count)->toBe(2);
});

it('respects the moderation setting', function (string $mode, string $expected) {
    Settings::set('comments.moderation', $mode);
    expect(comments()->post(User::factory()->create(), Article::factory()->create(), 'A considered comment.')->status)->toBe($expected);
})->with([['all', 'pending'], ['none', 'approved'], ['first', 'pending']]);

it('holds comments with too many links or blocked words even without moderation', function () {
    Settings::set('comments.moderation', 'none');
    Settings::set('comments.blocked_words', "casino\nbuy now");
    $article = Article::factory()->create();

    expect(comments()->post(User::factory()->create(), $article, 'See http://a.test http://b.test http://c.test')->moderation_note)->toBe('Contains 3 links')
        ->and(comments()->post(User::factory()->create(), $article, 'Great CASINO offers')->status)->toBe('pending');
});

it('refuses unverified accounts, closed discussions and content the member cannot open', function () {
    $article = Article::factory()->create();
    expect(fn () => comments()->post(User::factory()->unverified()->create(), $article, 'Hello there'))->toThrow(ValidationException::class);

    $closed = Article::factory()->create(['comments_enabled' => false]);
    expect(fn () => comments()->post(User::factory()->create(), $closed, 'Hello there'))->toThrow(ValidationException::class);

    $members = Article::factory()->create(['access_level' => 'members']);
    expect(fn () => comments()->post(User::factory()->create(), $members, 'Hello there'))->toThrow(ValidationException::class);
    expect(comments()->post(makeMember(), $members, 'Hello there')->exists)->toBeTrue();

    Settings::set('comments.types', ['case']);
    expect(fn () => comments()->post(User::factory()->create(), Article::factory()->create(), 'Hello there'))->toThrow(ValidationException::class);
});

it('blocks duplicates and rapid-fire posting', function () {
    $article = Article::factory()->create();
    $user = User::factory()->create();
    comments()->post($user, $article, 'Same text');
    expect(fn () => comments()->post($user, $article, 'Same text'))->toThrow(ValidationException::class, 'already posted');

    foreach (range(1, 3) as $i) {
        comments()->post($user, $article, "Comment number {$i}");
    }
    expect(fn () => comments()->post($user, $article, 'One too many'))->toThrow(ValidationException::class, 'very quickly');
});

it('threads replies one level deep and emails the parent author', function () {
    Settings::set('comments.moderation', 'none');
    $case = ClinicalCase::factory()->create();
    [$a, $b, $c] = User::factory()->count(3)->create();

    $root = comments()->post($a, $case, 'What about the choroidal thickness?');
    $reply = comments()->post($b, $case, 'It is increased — pachychoroid.', $root);
    $nested = comments()->post($c, $case, 'Agree with that.', $reply);

    expect($nested->parent_id)->toBe($root->id);
    Mail::assertQueued(CommentReply::class, fn ($m) => $m->hasTo($a->email));

    $a->update(['notify_replies' => false]);
    Mail::fake();
    comments()->post($c, $case, 'One more thought.', $root);
    Mail::assertNotQueued(CommentReply::class);
});

it('turns reply emails off from the signed link', function () {
    $user = User::factory()->create();
    $this->get(route('comments.unsubscribe', ['user' => $user->id]))->assertForbidden();
    $this->get(URL::signedRoute('comments.unsubscribe', ['user' => $user->id]))->assertOk()->assertSee('Reply emails turned off');
    expect($user->fresh()->notify_replies)->toBeFalse();
});

it('hides a comment after three reports and toggles likes', function () {
    Settings::set('comments.moderation', 'none');
    $video = Video::factory()->create();
    $comment = comments()->post(User::factory()->create(), $video, 'A controversial take.');

    $liker = User::factory()->create();
    expect(comments()->toggleLike($comment, $liker))->toBeTrue()->and($comment->fresh()->likes_count)->toBe(1)
        ->and(comments()->toggleLike($comment, $liker))->toBeFalse()->and($comment->fresh()->likes_count)->toBe(0);

    $reporter = User::factory()->create();
    comments()->report($comment, $reporter, 'spam');
    comments()->report($comment, $reporter, 'spam'); // same member counts once
    comments()->report($comment, User::factory()->create(), 'abuse');
    expect($comment->fresh()->status)->toBe('approved');
    comments()->report($comment, User::factory()->create(), 'patient');
    expect($comment->fresh()->status)->toBe('pending')->and($video->fresh()->comments_count)->toBe(0);
});

it('lets authors edit within the window and keeps threads when a commented comment is deleted', function () {
    Settings::set('comments.moderation', 'none');
    $article = Article::factory()->create();
    $author = User::factory()->create();
    $root = comments()->post($author, $article, 'Original text');
    comments()->post(User::factory()->create(), $article, 'A reply', $root);

    comments()->edit($root, $author, 'Corrected text');
    expect($root->fresh()->body)->toBe('Corrected text')->and($root->fresh()->edited_at)->not->toBeNull();

    $this->travel(20)->minutes();
    expect(fn () => comments()->edit($root->fresh(), $author, 'Too late'))->toThrow(\Symfony\Component\HttpKernel\Exception\HttpException::class);
    expect(fn () => comments()->delete($root->fresh(), User::factory()->create()))->toThrow(\Symfony\Component\HttpKernel\Exception\HttpException::class);

    comments()->delete($root->fresh(), $author);
    expect($root->fresh()->isRemoved())->toBeTrue()->and($root->fresh()->user_id)->toBeNull()->and($article->fresh()->comments_count)->toBe(2);
});

it('anonymises or erases comments when an account is deleted and includes them in the export', function () {
    Settings::set('comments.moderation', 'none');
    $article = Article::factory()->create();
    $user = User::factory()->create();
    $withReply = comments()->post($user, $article, 'Has a reply');
    comments()->post(User::factory()->create(), $article, 'The reply', $withReply);
    comments()->post($user, $article, 'Standalone');

    expect($this->actingAs($user)->get('/account/export')->assertOk()->streamedContent())->toContain('Standalone');

    auth()->logout(); // as in Account › Delete, which signs out first
    $user->delete();
    expect(Comment::withTrashed()->where('body', 'Standalone')->exists())->toBeFalse()
        ->and($withReply->fresh()->isRemoved())->toBeTrue()
        ->and($article->fresh()->comments_count)->toBe(2);
});

it('renders and posts through the discussion component', function () {
    $article = Article::factory()->create();
    Livewire::test(Comments::class, ['item' => $article])->assertSee('Sign in')->assertDontSee('Post comment');

    $user = User::factory()->create(['name' => 'Jane Resident']);
    Livewire::actingAs($user)->test(Comments::class, ['item' => $article])
        ->set('body', 'My first comment on this article.')->call('post')
        ->assertSee('once a moderator has approved it')->assertSee('My first comment on this article.');

    // The pending comment is invisible to others
    Livewire::actingAs(User::factory()->create())->test(Comments::class, ['item' => $article])->assertDontSee('My first comment');

    // The honeypot silently drops bots
    Livewire::actingAs(User::factory()->create())->test(Comments::class, ['item' => $article])
        ->set('body', 'Bot text here')->set('website', 'http://spam.test')->call('post');
    expect(Comment::where('body', 'Bot text here')->exists())->toBeFalse();
});

it('lets moderators approve, pin and hide from the page, but not members', function () {
    $article = Article::factory()->create();
    $comment = comments()->post(User::factory()->create(), $article, 'Needs approval');

    Livewire::actingAs(User::factory()->create())->test(Comments::class, ['item' => $article])->call('approve', $comment->id)->assertForbidden();

    $editor = $this->staff('editor');
    Livewire::actingAs($editor)->test(Comments::class, ['item' => $article])
        ->assertSee('Needs approval')->call('approve', $comment->id)->call('togglePin', $comment->id);
    expect($comment->fresh()->only(['status', 'is_pinned']))->toBe(['status' => 'approved', 'is_pinned' => true]);
});

it('shows the discussion on articles and videos, collapsed on cases, and not on locked content', function () {
    Settings::set('comments.moderation', 'none');
    $article = Article::factory()->create();
    comments()->post(User::factory()->create(['name' => 'Kim Fellow']), $article, 'Visible discussion text');

    $this->get($article->urlPath())->assertSee('Visible discussion text')->assertSee('"commentCount":1', false);
    $this->get(Video::factory()->create()->urlPath())->assertSee('id="comments"', false);
    $this->get(ClinicalCase::factory()->create()->urlPath())->assertSee('Show discussion');

    $locked = Article::factory()->create(['access_level' => 'members']);
    $this->get($locked->urlPath())->assertDontSee('id="comments"', false);
});

it('gives editors a moderation queue in the dashboard', function () {
    $comment = comments()->post(User::factory()->create(), Article::factory()->create(), 'Queue item text');
    $this->actingAsStaff('editor');
    $this->get('/admin/comments')->assertOk()->assertSee('Queue item text')->assertSee('Awaiting approval');

    Livewire::test(\App\Filament\Resources\Comments\Pages\ListComments::class)
        ->callTableAction('approve', $comment);
    expect($comment->fresh()->status)->toBe('approved');

    $this->actingAsStaff('author');
    $this->get('/admin/comments')->assertForbidden();
});

it('saves comment settings', function () {
    $this->actingAsStaff();
    $this->get('/admin/settings/comments')->assertOk()->assertSee('Approve each member');
});
