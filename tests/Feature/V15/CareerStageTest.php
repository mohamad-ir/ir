<?php

use App\Filament\Resources\CareerStages\Pages\ManageCareerStages;
use App\Models\CareerStage;
use App\Models\User;
use Database\Seeders\RolesSeeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Livewire\Livewire;

beforeEach(fn () => $this->seedRoles());

it('provides the international list and keeps the keys of earlier versions', function () {
    $keys = array_keys(CareerStage::options());

    expect($keys)->toContain('student', 'resident', 'fellow', 'ophthalmologist', 'optometrist', 'other')   // stored by older versions
        ->toContain('other_doctor', 'orthoptist', 'ophthalmic_nurse', 'researcher')
        ->and(end($keys))->toBe('other')
        ->and(CareerStage::labelFor('resident'))->toBe('Resident / trainee')
        ->and(CareerStage::labelFor(null))->toBeNull()
        ->and(CareerStage::labelFor('legacy_value'))->toBe('Legacy Value');
});

it('uses one managed list for registration, the profile and the dashboard', function () {
    $this->seed(RolesSeeder::class);
    CareerStage::create(['name' => 'Medical physicist', 'color' => 'info']);
    CareerStage::where('key', 'fellow')->update(['is_active' => false]);
    CareerStage::flush();

    $this->get('/register')->assertOk()->assertSee('Medical physicist')->assertDontSee('>Fellow<', false);

    // An inactive stage cannot be chosen at registration…
    $this->post('/register', [
        'name' => 'Dr A', 'email' => 'a@example.org', 'career_stage' => 'fellow',
        'password' => 'Str0ngPassword9', 'password_confirmation' => 'Str0ngPassword9', 'terms' => '1',
    ])->assertSessionHasErrors('career_stage');

    // …a new one can.
    $this->post('/register', [
        'name' => 'Dr B', 'email' => 'b@example.org', 'career_stage' => 'medical_physicist',
        'password' => 'Str0ngPassword9', 'password_confirmation' => 'Str0ngPassword9', 'terms' => '1',
    ])->assertSessionHasNoErrors();
    expect(User::where('email', 'b@example.org')->value('career_stage'))->toBe('medical_physicist');
});

it('lets members keep a stage that was switched off, without offering it to others', function () {
    $member = User::factory()->create(['career_stage' => 'fellow', 'email_verified_at' => now()]);
    CareerStage::where('key', 'fellow')->first()->update(['is_active' => false]);

    expect(CareerStage::options())->not->toHaveKey('fellow')
        ->and(CareerStage::options(true, 'fellow'))->toHaveKey('fellow')
        ->and($member->careerStageLabel())->toBe('Fellow');

    $this->actingAs($member);
    $this->get('/account/profile')->assertOk()->assertSee('value="fellow" selected', false);
    $this->put('/account/profile', ['name' => $member->name, 'email' => $member->email, 'career_stage' => 'fellow'])->assertSessionHasNoErrors();
    expect($member->fresh()->career_stage)->toBe('fellow');
});

it('never changes the key of a stage, so members are not affected by renaming', function () {
    $stage = CareerStage::where('key', 'optometrist')->first();
    $stage->update(['name' => 'Optometrist (clinical)', 'key' => 'something_else']);

    expect($stage->fresh()->key)->toBe('optometrist')
        ->and(CareerStage::labelFor('optometrist'))->toBe('Optometrist (clinical)');
});

it('is managed from the dashboard by user managers only, and stages in use cannot be deleted', function () {
    User::factory()->create(['career_stage' => 'resident']);

    $this->actingAs($this->staff('author'));
    $this->get('/admin/career-stages')->assertForbidden();

    $this->actingAsStaff();
    $this->get('/admin/career-stages')->assertOk()->assertSee('Orthoptist');

    Livewire::test(ManageCareerStages::class)
        ->callAction('create', ['name' => 'Vision scientist', 'color' => 'primary', 'is_active' => true])
        ->assertHasNoActionErrors();
    $new = CareerStage::where('name', 'Vision scientist')->first();
    expect($new->key)->toBe('vision_scientist')->and($new->sort_order)->toBeGreaterThan(CareerStage::where('key', 'other')->value('sort_order'));

    $resident = CareerStage::where('key', 'resident')->first();
    Livewire::test(ManageCareerStages::class)->callTableAction('delete', $resident);
    expect(CareerStage::where('key', 'resident')->exists())->toBeTrue();

    Livewire::test(ManageCareerStages::class)->callTableAction('delete', $new);
    expect(CareerStage::where('key', 'vision_scientist')->exists())->toBeFalse();
});

it('shows the stage name wherever a member appears in the dashboard', function () {
    $member = User::factory()->create(['career_stage' => 'orthoptist']);
    $this->actingAsStaff();

    $this->get('/admin/users')->assertOk()->assertSee('Orthoptist');
    $this->get('/admin/users/'.$member->id.'/edit')->assertOk();
});

it('refreshes the cached list after drag-and-drop reordering', function () {
    $this->actingAsStaff();
    CareerStage::options(); // cached
    $ids = CareerStage::orderByDesc('sort_order')->pluck('id')->map(fn ($id) => (string) $id)->all();

    Livewire::test(ManageCareerStages::class)->call('reorderTable', $ids);

    expect(array_key_first(CareerStage::options()))->toBe('other');
});

it('counts members per stage in one query and never creates number-only keys', function () {
    User::factory()->count(2)->create(['career_stage' => 'resident']);
    $stages = CareerStage::all();

    DB::enableQueryLog();
    $counts = $stages->mapWithKeys(fn ($s) => [$s->key => $s->usersCount()]);
    expect(count(DB::getQueryLog()))->toBe(1)
        ->and($counts['resident'])->toBe(2)->and($counts['fellow'])->toBe(0);

    expect(CareerStage::create(['name' => '2024'])->key)->toBe('stage_2024');
});

it('keeps registration open when every stage is switched off', function () {
    $this->seed(RolesSeeder::class);
    CareerStage::query()->update(['is_active' => false]);
    CareerStage::flush();

    $this->get('/register')->assertOk()->assertDontSee('I am a…');
    $this->post('/register', [
        'name' => 'Dr C', 'email' => 'c@example.org',
        'password' => 'Str0ngPassword9', 'password_confirmation' => 'Str0ngPassword9', 'terms' => '1',
    ])->assertSessionHasNoErrors();
});

it('maps new stages to “other” when the update is rolled back, so older versions keep working', function () {
    $orthoptist = User::factory()->create(['career_stage' => 'orthoptist']);
    $fellow = User::factory()->create(['career_stage' => 'fellow']);

    $migration = require database_path('migrations/2026_11_11_100000_create_career_stages_table.php');
    $migration->down();
    expect($orthoptist->fresh()->career_stage)->toBe('other')->and($fellow->fresh()->career_stage)->toBe('fellow')
        ->and(Schema::hasTable('career_stages'))->toBeFalse();

    $migration->up();
    expect(CareerStage::count())->toBe(count(CareerStage::DEFAULTS));
});
