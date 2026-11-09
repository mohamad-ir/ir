<?php

use App\Mail\EnquiryReply;
use App\Models\Enquiry;
use App\Models\User;
use App\Support\Settings;
use Illuminate\Support\Facades\Mail;

beforeEach(fn () => $this->seedRoles());

it('shows the dashboard in Persian for a team member who chose it', function () {
    $this->actingAs($this->staff('super_admin', ['admin_locale' => 'fa']));

    $this->get('/admin')->assertOk()->assertSee('lang="fa"', false)->assertSee('داشبورد')->assertSee('محتوا');
    $this->get('/admin/comments')->assertOk()->assertSee('دیدگاه‌ها')->assertSee('در انتظار تأیید');
    $this->get('/admin/settings/theme')->assertOk()->assertSee('هویت سایت');
});

it('keeps the dashboard in English by default and for others', function () {
    $this->actingAs($this->staff('super_admin', ['admin_locale' => 'fa']));
    $this->actingAs($this->staff())->get('/admin')->assertOk()->assertSee('lang="en"', false)->assertSee('Content')->assertDontSee('داشبورد');
});

it('never changes the public website, member area or stored content', function () {
    $admin = $this->staff('super_admin', ['admin_locale' => 'fa']);
    $this->actingAs($admin);

    $this->get('/')->assertOk()->assertSee('lang="en"', false)->assertDontSee('داشبورد');
    $this->get('/cases')->assertOk()->assertDontSee('کیس‌ها');
    $this->get('/account/profile')->assertOk()->assertSee('Profile photo')->assertDontSee('عکس پروفایل');
    expect(app()->getLocale())->toBe('en');
});

it('sends emails in English even when triggered from a Persian dashboard', function () {
    $this->actingAs($this->staff('super_admin', ['admin_locale' => 'fa']));
    $this->get('/admin'); // the dashboard request switches the locale for its own lifetime

    \App\Support\AdminLocale::apply('fa');
    $enquiry = Enquiry::create(['name' => 'Ana', 'email' => 'ana@example.org', 'type' => 'other', 'subject' => 'Hello', 'message' => 'Question']);
    $html = (new EnquiryReply($enquiry, 'Re: Hello', 'Thank you for your message.'))->render();
    expect($html)->not->toMatch('/[\x{0600}-\x{06FF}]/u');

    $user = User::factory()->create();
    expect($user->preferredLocale())->toBe('en');
    app()->setLocale('en');
});

it('switches language from the user menu link and remembers it', function () {
    $admin = $this->staff();
    $this->actingAs($admin)->from('/admin/comments')->get('/admin/locale/fa')->assertRedirect('/admin/comments')->assertCookie('admin_locale', 'fa');
    expect($admin->fresh()->admin_locale)->toBe('fa');

    $this->get('/admin/locale/de')->assertNotFound();
    $this->actingAs(User::factory()->create())->get('/admin/locale/fa')->assertForbidden();
});

it('saves the dashboard language from the profile page', function () {
    $admin = $this->staff();
    $this->actingAs($admin);
    Livewire\Livewire::test(\App\Filament\Pages\Auth\EditProfile::class)
        ->set('data.admin_locale', 'fa')->call('save')->assertHasNoErrors();
    expect($admin->fresh()->admin_locale)->toBe('fa');
});

it('lays the Persian dashboard out left-to-right unless the owner chooses right-to-left', function () {
    $this->actingAs($this->staff('super_admin', ['admin_locale' => 'fa']));
    $this->get('/admin')->assertSee('dir="ltr"', false);

    Settings::set('admin.fa_direction', 'rtl');
    $this->get('/admin')->assertSee('dir="rtl"', false);
});

it('translates Persian validation messages in the dashboard', function () {
    \App\Support\AdminLocale::apply('fa');
    expect(__('validation.required', ['attribute' => __('Site name')]))->toBe('پر کردن نام سایت الزامی است.');
    app()->setLocale('en');
    expect(__('validation.required', ['attribute' => 'name']))->toBe('The name field is required.');
});

it('has a Persian translation for every dashboard string with intact placeholders', function () {
    $fa = json_decode(file_get_contents(lang_path('fa.json')), true);
    expect($fa)->toBeArray()->not->toBeEmpty();
    foreach ($fa as $en => $translated) {
        preg_match_all('/:[a-z]+/', $en, $need);
        foreach ($need[0] as $placeholder) {
            expect($translated)->toContain($placeholder);
        }
    }
    // Spot checks of the shared vocabulary
    expect($fa['Published'])->toBe('منتشرشده')->and($fa['Draft'])->toBe('پیش‌نویس')->and($fa['OCT cases'])->toBe('کیس‌های OCT');
});
