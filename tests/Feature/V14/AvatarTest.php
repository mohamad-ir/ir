<?php

use App\Models\User;
use App\Services\Users\AvatarService;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

beforeEach(function () {
    $this->seedRoles();
    Storage::fake('public');
});

function avatarFiles(User $user): array
{
    return Storage::disk('public')->allFiles('avatars/'.$user->id);
}

it('stores an uploaded photo as three square WebP sizes', function () {
    $user = User::factory()->create();

    $this->actingAs($user)->postJson('/account/avatar', ['avatar' => UploadedFile::fake()->image('me.jpg', 1200, 800)])
        ->assertOk()->assertJsonStructure(['urls' => ['sm', 'md', 'lg']]);

    $user->refresh();
    expect($user->avatar_path)->toStartWith('avatars/'.$user->id.'/')->and(avatarFiles($user))->toHaveCount(3);
    foreach (AvatarService::SIZES as $size => $px) {
        $file = Storage::disk('public')->path($user->avatar_path."-{$px}.webp");
        expect(getimagesize($file))->toMatchArray([0 => $px, 1 => $px, 'mime' => 'image/webp'])
            ->and($user->avatarUrl($size))->toEndWith("-{$px}.webp");
    }
});

it('works without JavaScript through a normal form post', function () {
    $user = User::factory()->create();
    $this->actingAs($user)->from('/account/profile')->post('/account/avatar', ['avatar' => UploadedFile::fake()->image('me.png', 400, 400)])
        ->assertRedirect('/account/profile')->assertSessionHas('status');
    expect($user->fresh()->avatar_path)->not->toBeNull();
});

it('rejects files that are not real images, too small or too large', function (UploadedFile $file) {
    $user = User::factory()->create();
    $this->actingAs($user)->postJson('/account/avatar', ['avatar' => $file])->assertStatus(422)->assertJsonValidationErrors('avatar');
    expect($user->fresh()->avatar_path)->toBeNull()->and(avatarFiles($user))->toBe([]);
})->with([
    'text renamed to .jpg' => fn () => UploadedFile::fake()->createWithContent('photo.jpg', 'not an image at all'),
    'php script' => fn () => UploadedFile::fake()->createWithContent('shell.php', '<?php echo 1;'),
    'svg' => fn () => UploadedFile::fake()->createWithContent('logo.svg', '<svg xmlns="http://www.w3.org/2000/svg"><script>alert(1)</script></svg>'),
    'gif' => fn () => UploadedFile::fake()->image('anim.gif', 300, 300),
    'too small' => fn () => UploadedFile::fake()->image('tiny.jpg', 64, 64),
    'over 5 MB' => fn () => UploadedFile::fake()->image('huge.jpg', 600, 600)->size(6000),
]);

it('re-encodes the image so nothing hidden in the upload is served', function () {
    $user = User::factory()->create();
    $path = tempnam(sys_get_temp_dir(), 'av');
    $image = imagecreatetruecolor(300, 300);
    imagejpeg($image, $path);
    file_put_contents($path, '<?php system($_GET["c"]); ?>', FILE_APPEND); // payload after the JPEG data

    $this->actingAs($user)->postJson('/account/avatar', ['avatar' => new UploadedFile($path, 'me.jpg', 'image/jpeg', null, true)])->assertOk();

    foreach (avatarFiles($user->fresh()) as $file) {
        expect(Storage::disk('public')->get($file))->not->toContain('<?php')->and($file)->toEndWith('.webp');
    }
});

it('deletes the old files when the photo is replaced or removed', function () {
    $user = User::factory()->create();
    $this->actingAs($user)->postJson('/account/avatar', ['avatar' => UploadedFile::fake()->image('a.jpg', 300, 300)])->assertOk();
    $first = $user->fresh()->avatar_path;

    $this->postJson('/account/avatar', ['avatar' => UploadedFile::fake()->image('b.jpg', 300, 300)])->assertOk();
    $second = $user->fresh()->avatar_path;
    expect($second)->not->toBe($first)
        ->and(Storage::disk('public')->exists($first.'-512.webp'))->toBeFalse()
        ->and(avatarFiles($user))->toHaveCount(3);

    $this->deleteJson('/account/avatar')->assertOk();
    expect($user->fresh()->avatar_path)->toBeNull()->and(avatarFiles($user))->toBe([]);
});

it('removes the files when the account is deleted', function () {
    $user = User::factory()->create();
    app(AvatarService::class)->store($user, UploadedFile::fake()->image('a.jpg', 300, 300));
    expect(avatarFiles($user))->toHaveCount(3);

    $user->delete();
    expect(avatarFiles($user))->toBe([]);
});

it('requires a signed-in member and only ever changes their own photo', function () {
    $this->postJson('/account/avatar', ['avatar' => UploadedFile::fake()->image('a.jpg', 300, 300)])->assertUnauthorized();

    $other = User::factory()->create();
    $this->actingAs(User::factory()->create())->postJson('/account/avatar', ['avatar' => UploadedFile::fake()->image('a.jpg', 300, 300), 'user_id' => $other->id])->assertOk();
    expect($other->fresh()->avatar_path)->toBeNull();
});

it('shows the photo, or initials when there is none, wherever the member appears', function () {
    $user = User::factory()->create(['name' => 'Jane Resident']);
    $this->actingAs($user)->get('/account/profile')->assertOk()->assertSee('Upload photo')->assertSee('>JR<', false);

    app(AvatarService::class)->store($user, UploadedFile::fake()->image('a.jpg', 300, 300));
    $this->get('/account/profile')->assertSee($user->fresh()->avatarUrl('sm'), false)->assertSee($user->fresh()->avatarUrl('lg'), false)->assertSee('Change photo');
    expect($user->fresh()->getFilamentAvatarUrl())->toBe($user->fresh()->avatarUrl('sm'));
});

it('lets administrators remove an unsuitable photo', function () {
    $member = User::factory()->create();
    app(AvatarService::class)->store($member, UploadedFile::fake()->image('a.jpg', 300, 300));

    $this->actingAsStaff('administrator');
    $this->get('/admin/users')->assertOk();
    Livewire\Livewire::test(\App\Filament\Resources\Users\Pages\ListUsers::class)->callTableAction('removePhoto', $member);

    expect($member->fresh()->avatar_path)->toBeNull()->and(avatarFiles($member))->toBe([]);
});

it('rate-limits uploads', function () {
    $this->actingAs(User::factory()->create());
    foreach (range(1, 10) as $i) {
        $this->postJson('/account/avatar', ['avatar' => UploadedFile::fake()->image('a.jpg', 200, 200)])->assertOk();
    }
    $this->postJson('/account/avatar', ['avatar' => UploadedFile::fake()->image('a.jpg', 200, 200)])->assertStatus(429);
});
