<?php

namespace App\Http\Controllers;

use App\Models\Article;
use App\Models\Bookmark;
use App\Models\CareerStage;
use App\Models\Certificate;
use App\Models\ClinicalCase;
use App\Models\LessonQuizAttempt;
use App\Models\Question;
use App\Models\Subscriber;
use App\Models\User;
use App\Models\Video;
use App\Services\Learning\LearningService;
use App\Services\Newsletter\NewsletterService;
use App\Services\Payments\StripeClient;
use App\Services\Seo\SeoManager;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;

class AccountController extends Controller
{
    public function dashboard(Request $request, SeoManager $seo)
    {
        $seo->title('My learning')->noindex();
        $user = $request->user();

        $bookmarks = $user->bookmarks()->latest('id')->with('bookmarkable')->get()->filter(fn ($b) => $b->bookmarkable && method_exists($b->bookmarkable, 'isPublished') && $b->bookmarkable->isPublished());
        $attempts = $user->questionAttempts();
        $answered = (clone $attempts)->distinct('question_id')->count('question_id');
        $correct = (clone $attempts)->where('is_correct', true)->count();
        $total = (clone $attempts)->whereNotNull('is_correct')->count();

        $learning = app(LearningService::class);
        $enrollments = $user->enrollments()->with(['course' => fn ($q) => $q->published()->with(['featuredMedia', 'lessons:id,course_id,slug,sort_order,course_section_id,duration_minutes', 'sections']), 'lastLesson'])
            ->latest('updated_at')->get()->filter(fn ($e) => $e->course !== null)
            ->each(fn ($e) => $e->setAttribute('progress', $learning->progress($user, $e->course)));

        return view('account.dashboard', [
            'user' => $user,
            'enrollments' => $enrollments,
            'webinars' => $user->webinarRegistrations()->whereNull('canceled_at')->with('webinar')->get()
                ->filter(fn ($r) => $r->webinar && $r->webinar->isPublished() && ! $r->webinar->hasEnded())->sortBy(fn ($r) => $r->webinar->starts_at)->values(),
            'subscription' => $user->activeSubscription(),
            'certificates' => Certificate::valid()->where('user_id', $user->id)->latest('issued_at')->get(),
            'bookmarks' => $bookmarks,
            'answered' => $answered,
            'accuracy' => $total ? round($correct / $total * 100) : null,
            'totalQuestions' => Question::published()->count(),
            'recentAttempts' => $user->questionAttempts()->with('question')->latest('id')->limit(6)->get(),
            'latestCases' => ClinicalCase::published()->with(['featuredMedia', 'topics', 'modalities'])->latest('published_at')->limit(3)->get(),
            'subscriber' => $user->hasVerifiedEmail() ? Subscriber::where('email', $user->email)->first() : null,
        ]);
    }

    public function billing(Request $request, SeoManager $seo)
    {
        $seo->title('Billing')->noindex();
        $user = $request->user();

        return view('account.billing', [
            'user' => $user,
            // The subscription that gives access, otherwise the most recent one.
            'subscription' => $user->activeSubscription()?->load('plan') ?? $user->subscriptions()->with('plan')->latest('id')->first(),
            'isMember' => $user->isMember(),
            'orders' => $user->orders()->whereIn('status', ['paid', 'partially_refunded', 'refunded'])->latest('paid_at')->latest('id')->get(),
            'paymentsEnabled' => StripeClient::configured(),
        ]);
    }

    public function profile(Request $request, SeoManager $seo)
    {
        $seo->title('Profile & settings')->noindex();

        return view('account.profile', [
            'user' => $request->user(),
            'subscriber' => $request->user()->hasVerifiedEmail() ? Subscriber::where('email', $request->user()->email)->first() : null,
        ]);
    }

    public function updateProfile(Request $request, NewsletterService $newsletter)
    {
        $user = $request->user();
        $data = $request->validate([
            'name' => ['required', 'string', 'max:120'],
            'title' => ['nullable', 'string', 'max:40'],
            'email' => ['required', 'email', 'max:190', Rule::unique('users')->ignore($user->id)],
            'career_stage' => ['nullable', Rule::in(CareerStage::selectableKeys($request->user()->career_stage))],
            'specialty' => ['nullable', 'string', 'max:120'],
            'institution' => ['nullable', 'string', 'max:160'],
            'country' => ['nullable', 'string', 'size:2'],
            'newsletter' => ['nullable', 'boolean'],
            'notify_replies' => ['nullable', 'boolean'],
            // Changing the sign-in email needs the password (someone at an unlocked computer cannot take over the account).
            'current_password' => [Rule::requiredIf(fn () => strcasecmp((string) $request->input('email'), $user->email) !== 0), 'nullable', 'current_password'],
        ], ['current_password.required' => 'Enter your current password to change your email address.']);
        unset($data['current_password']);
        $data['notify_replies'] = $request->boolean('notify_replies');

        $emailChanged = $data['email'] !== $user->email;
        $user->fill(collect($data)->except('newsletter')->all());
        if ($emailChanged) {
            $user->email_verified_at = null;
        }
        $user->save();

        if ($emailChanged) {
            $user->sendEmailVerificationNotification();
        }

        // Newsletter preferences follow a verified address only: a new, unconfirmed address may belong to someone else.
        $subscriber = $user->hasVerifiedEmail() ? Subscriber::where('email', $user->email)->first() : null;
        if (! $user->hasVerifiedEmail()) {
            // nothing to change until the new address is confirmed
        } elseif ($request->boolean('newsletter') && (! $subscriber || $subscriber->status->value !== 'subscribed')) {
            $newsletter->subscribe($user->email, $user->name, ['weekly_case'], 'account', $user->id);
        } elseif (! $request->boolean('newsletter') && $subscriber && $subscriber->status->value === 'subscribed') {
            $newsletter->unsubscribe($subscriber);
        }

        return back()->with('status', 'Your profile has been updated.');
    }

    public function updatePassword(Request $request)
    {
        $request->validate([
            'current_password' => ['required', 'current_password'],
            'password' => ['required', 'confirmed', Password::min(10)->letters()->numbers()->uncompromised()],
        ]);

        $request->user()->update(['password' => Hash::make($request->password)]);

        return back()->with('status', 'Your password has been changed.');
    }

    /** GDPR data export. */
    public function export(Request $request)
    {
        $user = $request->user();
        $data = [
            'account' => [...$user->only(['name', 'title', 'email', 'career_stage', 'specialty', 'institution', 'country', 'created_at', 'last_login_at']), 'career_stage_name' => $user->careerStageLabel()],
            'bookmarks' => $user->bookmarks()->with('bookmarkable')->get()->map(fn ($b) => ['type' => $b->bookmarkable_type, 'title' => $b->bookmarkable?->title, 'saved_at' => $b->created_at])->all(),
            'question_attempts' => $user->questionAttempts()->with('question:id,title')->get()->map(fn ($a) => ['question' => $a->question?->title, 'answer' => $a->selected_key, 'correct' => $a->is_correct, 'at' => $a->created_at])->all(),
            'newsletter' => $user->hasVerifiedEmail() ? Subscriber::where('email', $user->email)->first()?->only(['email', 'status', 'interests', 'confirmed_at']) : null,
            'certificates' => Certificate::where('user_id', $user->id)->get()->map(fn ($c) => $c->only(['code', 'course_title', 'recipient_name', 'issued_at', 'revoked_at']))->all(),
            'quiz_attempts' => LessonQuizAttempt::where('user_id', $user->id)->with('lesson:id,title')->get()->map(fn ($a) => ['lesson' => $a->lesson?->title, 'score' => $a->score, 'passed' => $a->passed, 'at' => $a->created_at])->all(),
            'courses' => $user->enrollments()->with('course:id,title')->get()->map(fn ($e) => ['course' => $e->course?->title, 'access' => $e->source, 'enrolled_at' => $e->created_at, 'completed_at' => $e->completed_at])->all(),
            'webinars' => $user->webinarRegistrations()->with('webinar:id,title,starts_at')->get()->map(fn ($r) => ['webinar' => $r->webinar?->title, 'starts_at' => $r->webinar?->starts_at, 'registered_at' => $r->created_at, 'cancelled_at' => $r->canceled_at])->all(),
            'membership' => $user->subscriptions()->get()->map(fn ($s) => $s->only(['status', 'amount', 'currency', 'interval', 'current_period_end', 'created_at', 'canceled_at']))->all(),
            'comments' => $user->comments()->withTrashed()->with('commentable')->get()->map(fn ($c) => ['on' => $c->commentable?->title, 'comment' => $c->body, 'status' => $c->status, 'posted_at' => $c->created_at, 'deleted_at' => $c->deleted_at])->all(),
            'orders' => $user->orders()->get()->map(fn ($o) => $o->only(['number', 'description', 'total', 'tax', 'currency', 'status', 'paid_at']))->all(),
        ];

        return response()->streamDownload(fn () => print (json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE)), 'my-data.json', ['Content-Type' => 'application/json']);
    }

    public function destroy(Request $request)
    {
        $request->validate(['password' => ['required', 'current_password']]);
        $user = $request->user();
        abort_if($user->isSuperAdmin(), 403, 'Super administrators cannot delete their own account here.');

        $running = $user->subscriptions()->grantingAccess()->where('cancel_at_period_end', false)->exists();
        if ($running) {
            return back()->withErrors(['password' => 'Please cancel your membership under Billing before deleting your account, so that no further payments are taken.']);
        }

        Auth::logout();
        if ($user->hasVerifiedEmail()) {
            Subscriber::where('email', $user->email)->delete();
        }
        $user->delete();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect('/')->with('status', 'Your account and personal data have been deleted.');
    }

    /** One-click opt-out from the link in reply emails (signed, so no sign-in needed). */
    public function stopReplyEmails(User $user)
    {
        $user->forceFill(['notify_replies' => false])->saveQuietly();

        return view('newsletter.message', [
            'icon' => 'heroicon-o-bell-slash',
            'title' => 'Reply emails turned off',
            'text' => 'You will no longer receive an email when someone replies to your comments. You can turn them back on in your profile at any time.',
            'cta' => ['url' => route('account.profile'), 'label' => 'Open my profile'],
        ]);
    }

    public function toggleBookmark(Request $request, string $type, int $id)
    {
        $class = ['case' => ClinicalCase::class, 'article' => Article::class, 'video' => Video::class, 'question' => Question::class][$type];
        $model = $class::published()->findOrFail($id);

        $existing = Bookmark::where('user_id', $request->user()->id)->where('bookmarkable_type', $type)->where('bookmarkable_id', $model->id)->first();
        if ($existing) {
            $existing->delete();
            $saved = false;
        } else {
            Bookmark::create(['user_id' => $request->user()->id, 'bookmarkable_type' => $type, 'bookmarkable_id' => $model->id]);
            $saved = true;
        }

        return $request->expectsJson() ? response()->json(['saved' => $saved]) : back();
    }
}
