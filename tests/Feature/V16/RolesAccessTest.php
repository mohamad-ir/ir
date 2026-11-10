<?php

use App\Enums\AccessLevel;
use App\Filament\Resources\Roles\Pages\CreateRole;
use App\Filament\Resources\Roles\Pages\EditRole;
use App\Filament\Resources\Roles\RoleResource;
use App\Filament\Resources\Users\Pages\EditUser;
use App\Http\Middleware\RequireStaffMfa;
use App\Models\Article;
use App\Models\ClinicalCase;
use App\Models\Course;
use App\Models\Enrollment;
use App\Models\Page;
use App\Models\User;
use App\Services\Access\ContentAccess;
use App\Services\Learning\LearningService;
use App\Support\Settings;
use Filament\Facades\Filament;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use Livewire\Livewire;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

/*
 * Dashboard access is the "dashboard.access" permission; free access to paid content is a
 * separate, super-administrator-only permission for custom roles. The five built-in team roles
 * behave exactly as before.
 */

beforeEach(fn () => $this->seedRoles());

function customRole(string $name, array $permissions): Role
{
    $role = Role::findOrCreate($name, 'web');
    $role->syncPermissions($permissions);
    app(PermissionRegistrar::class)->forgetCachedPermissions();

    return $role;
}

function userWithRole(string $role, array $attributes = []): User
{
    $user = User::factory()->create(['is_active' => true, 'email_verified_at' => now(), ...$attributes]);
    $user->assignRole($role);

    return $user;
}

const VIEW_ONLY = ['cases.view', 'articles.view', 'videos.view', 'questions.view', 'exam_topics.view', 'pages.view', 'courses.view', 'webinars.view', 'galleries.view', 'media.view'];

it('explains clearly why a signed-in user without dashboard access cannot open the dashboard', function () {
    customRole('demo', VIEW_ONLY);
    $this->actingAs(userWithRole('demo', ['name' => 'Demo Viewer']));

    $this->get('/admin')->assertForbidden()
        ->assertSee('This account cannot open the dashboard')
        ->assertSee('Demo Viewer')
        ->assertSee('Sign in to the dashboard')
        ->assertSee(route('account.dashboard'), false)
        ->assertSee('Sign out and use another account');
    $this->get('/admin/cases')->assertForbidden()->assertSee('This account cannot open the dashboard');
    $this->get('/admin', ['X-Livewire' => 'true'])->assertForbidden()->assertDontSee('Go to my account');
    $this->get('/_preview/case/1')->assertForbidden()->assertSee('This account cannot open the dashboard');

    // The rest of the site works as usual.
    $this->get('/account')->assertOk();
});

it('lets a custom role sign in to the dashboard with “Sign in to the dashboard”, limited to its permissions', function () {
    customRole('demo', ['dashboard.access', ...VIEW_ONLY]);
    $user = userWithRole('demo');
    $this->actingAs($user);
    Filament::setCurrentPanel(Filament::getPanel('admin'));
    $case = ClinicalCase::factory()->create();

    expect($user->canAccessPanel(Filament::getPanel('admin')))->toBeTrue()
        ->and($user->isStaff())->toBeFalse();
    $this->get('/admin')->assertOk();
    $this->get('/admin/cases')->assertOk()->assertSee($case->title);
    $this->get('/admin/cases/create')->assertForbidden();
    $this->get('/admin/cases/'.$case->id.'/edit')->assertForbidden();
    $this->get('/admin/users')->assertForbidden();
    $this->get('/admin/roles')->assertForbidden();
    $this->get('/admin/settings/site')->assertForbidden();
});

it('applies two-step verification to custom roles with dashboard access, like every team member', function () {
    customRole('demo', ['dashboard.access', ...VIEW_ONLY]);
    $user = userWithRole('demo');
    Filament::setCurrentPanel(Filament::getPanel('admin'));
    Filament::auth()->login($user);
    Route::get('/admin/multi-factor-authentication/set-up', fn () => 'setup')->name('filament.admin.auth.multi-factor-authentication.set-up-required');
    app('router')->getRoutes()->refreshNameLookups();

    Settings::set('security.require_staff_mfa', true);
    $response = (new RequireStaffMfa)->handle(Request::create('/admin'), fn () => response('dashboard'));

    expect($response->isRedirect())->toBeTrue()->and($response->headers->get('Location'))->toContain('multi-factor-authentication');
});

it('sends custom roles with dashboard access through the secure dashboard sign-in, and others to their account', function () {
    customRole('demo', ['dashboard.access']);
    customRole('reader', ['cases.view']);
    userWithRole('demo', ['email' => 'demo@example.org', 'password' => bcrypt('Str0ngPassword9')]);
    userWithRole('reader', ['email' => 'reader@example.org', 'password' => bcrypt('Str0ngPassword9')]);

    $this->post('/login', ['email' => 'demo@example.org', 'password' => 'Str0ngPassword9'])->assertRedirect(url('admin/login'));
    $this->assertGuest();
    $this->post('/login', ['email' => 'reader@example.org', 'password' => 'Str0ngPassword9'])->assertRedirect(route('account.dashboard'));
});

it('keeps dashboard access and free access to paid content exactly as before for the five built-in roles', function () {
    $article = Article::factory()->create(['access_level' => AccessLevel::Members, 'status' => 'published', 'published_at' => now()->subDay()]);
    $course = Course::factory()->create(['status' => 'published', 'published_at' => now()->subDay()]);
    $access = app(ContentAccess::class);

    foreach (User::STAFF_ROLES as $role) {
        $user = $this->staff($role);
        expect($user->canAccessPanel(Filament::getPanel('admin')))->toBeTrue("{$role} dashboard")
            ->and($user->hasFreeContentAccess())->toBeTrue("{$role} free access")
            ->and($access->canView($article, $user))->toBeTrue()
            ->and($access->canAccessCourse($course, $user))->toBeTrue()
            ->and(Role::findByName($role)->hasPermissionTo('dashboard.access'))->toBeTrue()
            ->and(Role::findByName($role)->hasPermissionTo('content.free_access'))->toBeFalse();   // comes from the role itself
    }

    $member = userWithRole('member');
    expect($member->canAccessPanel(Filament::getPanel('admin')))->toBeFalse()
        ->and($access->canView($article, $member))->toBeFalse()
        ->and($access->canAccessCourse($course, $member))->toBeFalse();
});

it('gives custom roles free access to paid content only with the separate permission, off by default', function () {
    $article = Article::factory()->create(['access_level' => AccessLevel::Members, 'status' => 'published', 'published_at' => now()->subDay()]);
    $course = Course::factory()->withLessons(2, 0)->create(['status' => 'published', 'published_at' => now()->subDay()]);
    $access = app(ContentAccess::class);
    $role = customRole('demo', ['dashboard.access', ...VIEW_ONLY]);
    $user = userWithRole('demo');

    expect($user->hasFreeContentAccess())->toBeFalse()
        ->and($access->canView($article, $user))->toBeFalse()
        ->and($access->canAccessCourse($course, $user))->toBeFalse();
    $this->actingAs($user)->get($article->url())->assertOk()->assertSee('included with membership');

    $role->givePermissionTo('content.free_access');
    app(PermissionRegistrar::class)->forgetCachedPermissions();
    $user = $user->fresh();
    expect($access->canView($article, $user))->toBeTrue()->and($access->canAccessCourse($course, $user))->toBeTrue();

    // Starting a course records a "complimentary" enrolment that lasts only while the permission does.
    $this->actingAs($user)->post(route('courses.enroll', $course->slug))->assertRedirect();
    expect(Enrollment::where('user_id', $user->id)->value('source'))->toBe('complimentary');

    $role->revokePermissionTo('content.free_access');
    app(PermissionRegistrar::class)->forgetCachedPermissions();
    expect($access->canAccessCourse($course, $user->fresh()))->toBeFalse();

    // A later purchase turns it into a permanent enrolment.
    app(LearningService::class)->enroll($user, $course, 'purchase');
    expect(Enrollment::where('user_id', $user->id)->value('source'))->toBe('purchase')
        ->and($access->canAccessCourse($course, $user->fresh()))->toBeTrue();
});

it('lets only a super administrator give or remove free access to paid content', function () {
    customRole('role_manager', ['dashboard.access', 'roles.manage']);
    $target = customRole('demo', ['dashboard.access', 'cases.view']);
    $manager = userWithRole('role_manager');
    $this->actingAs($manager);
    Filament::setCurrentPanel(Filament::getPanel('admin'));

    // A role manager who is not a super administrator: the option is not offered, and a forged request is refused.
    Livewire::test(EditRole::class, ['record' => $target->getRouteKey()])
        ->assertDontSee('Free access to paid and members-only content')
        ->assertSee('Only a super administrator can change it.')
        ->set('data.permissions_access', ['dashboard.access', 'content.free_access'])
        ->call('save')->assertHasFormErrors(['permissions_access.1']);
    expect($target->fresh()->hasPermissionTo('content.free_access'))->toBeFalse()
        ->and(RoleResource::isLocked('content.free_access', $target))->toBeTrue();

    // …and an existing grant survives their edits.
    $target->givePermissionTo('content.free_access');
    Livewire::test(EditRole::class, ['record' => $target->getRouteKey()])
        ->set('data.permissions_access', ['dashboard.access'])
        ->call('save');
    expect($target->fresh()->hasPermissionTo('content.free_access'))->toBeTrue();

    // A super administrator can change it.
    $this->actingAsStaff('super_admin');
    Livewire::test(EditRole::class, ['record' => $target->getRouteKey()])
        ->set('data.permissions_access', ['dashboard.access'])
        ->call('save');
    expect($target->fresh()->hasPermissionTo('content.free_access'))->toBeFalse();
    Livewire::test(CreateRole::class)
        ->fillForm(['name' => 'guest_lecturer'])
        ->set('data.permissions_access', ['content.free_access'])
        ->call('create')->assertHasNoFormErrors();
    expect(Role::findByName('guest_lecturer')->hasPermissionTo('content.free_access'))->toBeTrue();
});

it('keeps the fixed access of built-in roles whatever is ticked', function () {
    $this->actingAsStaff('super_admin');
    $editor = Role::findByName('editor');
    $member = Role::findByName('member');

    Livewire::test(EditRole::class, ['record' => $editor->getRouteKey()])
        ->set('data.permissions_access', ['content.free_access'])   // dashboard.access unticked, free access ticked
        ->call('save');
    Livewire::test(EditRole::class, ['record' => $member->getRouteKey()])
        ->set('data.permissions_access', ['dashboard.access'])
        ->call('save');

    expect($editor->fresh()->hasPermissionTo('dashboard.access'))->toBeTrue()
        ->and($editor->fresh()->hasPermissionTo('content.free_access'))->toBeFalse()
        ->and($member->fresh()->hasPermissionTo('dashboard.access'))->toBeFalse();
});

it('warns when a role has dashboard permissions but cannot sign in to the dashboard', function () {
    $this->actingAsStaff('super_admin');

    Livewire::test(CreateRole::class)
        ->fillForm(['name' => 'demo'])
        ->set('data.permissions_oct_cases', ['cases.view'])
        ->call('create')
        ->assertNotified(__('This role cannot sign in to the dashboard'));
});

it('lets only a super administrator assign or remove roles that include free access', function () {
    customRole('vip', ['content.free_access']);
    $member = userWithRole('member');
    $vip = Role::findByName('vip');
    $this->actingAsStaff('administrator');

    Livewire::test(EditUser::class, ['record' => $member->getRouteKey()])
        ->fillForm(['roles' => [Role::findByName('member')->id, $vip->id]])
        ->call('save')->assertHasFormErrors(['roles']);
    expect($member->fresh()->hasRole('vip'))->toBeFalse();

    $this->actingAsStaff('super_admin');
    Livewire::test(EditUser::class, ['record' => $member->getRouteKey()])
        ->fillForm(['roles' => [Role::findByName('member')->id, $vip->id]])
        ->call('save')->assertHasNoFormErrors();
    expect($member->fresh()->hasRole('vip'))->toBeTrue();
});

it('only shows draft previews to people who may view that type of content', function () {
    customRole('case_reviewer', ['dashboard.access', 'cases.view']);
    $this->actingAs(userWithRole('case_reviewer'));
    $case = ClinicalCase::factory()->create(['status' => 'draft', 'published_at' => null]);
    $page = Page::create(['title' => 'Draft page', 'slug' => 'draft-page', 'status' => 'draft', 'blocks' => []]);

    $this->get('/_preview/case/'.$case->id)->assertOk();
    $this->get('/_preview/page/'.$page->id)->assertForbidden();

    // Built-in roles keep previewing what they can edit.
    $this->actingAs($this->staff('contributor'));
    $this->get('/_preview/case/'.$case->id)->assertOk();
});

it('updates existing sites: built-in roles get dashboard access, custom roles and free access are unchanged', function () {
    customRole('demo', VIEW_ONLY);
    Role::findByName('editor')->revokePermissionTo('dashboard.access');
    Permission::where('name', 'content.free_access')->delete();
    app(PermissionRegistrar::class)->forgetCachedPermissions();

    (require database_path('migrations/2026_11_13_100000_add_dashboard_access_permissions.php'))->up();
    app(PermissionRegistrar::class)->forgetCachedPermissions();

    expect(Role::findByName('editor')->hasPermissionTo('dashboard.access'))->toBeTrue()
        ->and(Role::findByName('demo')->hasPermissionTo('dashboard.access'))->toBeFalse()
        ->and(Permission::where('name', 'content.free_access')->exists())->toBeTrue()
        ->and(Role::whereHas('permissions', fn ($q) => $q->where('name', 'content.free_access'))->count())->toBe(0);
});

it('keeps built-in team members able to reach the dashboard before the database update is applied', function () {
    $editor = $this->staff('editor');
    Permission::where('name', 'dashboard.access')->delete();
    app(PermissionRegistrar::class)->forgetCachedPermissions();

    expect($editor->fresh()->canAccessPanel(Filament::getPanel('admin')))->toBeTrue()
        ->and(userWithRole('member')->canAccessPanel(Filament::getPanel('admin')))->toBeFalse();
});

it('explains an inactive account and lists custom dashboard users as possible authors', function () {
    customRole('demo', ['dashboard.access']);
    $inactive = userWithRole('editor', ['is_active' => false]);
    $this->actingAs($inactive)->get('/admin')->assertForbidden()->assertSee('Your account is not active')->assertDontSee('Go to my account');

    $demo = userWithRole('demo', ['name' => 'Guest Editor']);
    $member = userWithRole('member', ['name' => 'Plain Member']);
    $names = User::dashboardUsers()->pluck('name');
    expect($names)->toContain('Guest Editor')->not->toContain('Plain Member');
});
