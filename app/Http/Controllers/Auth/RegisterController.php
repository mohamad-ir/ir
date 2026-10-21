<?php

namespace App\Http\Controllers\Auth;

use App\Enums\CareerStage;
use App\Http\Controllers\Controller;
use App\Models\User;
use App\Services\Newsletter\NewsletterService;
use App\Services\Seo\SeoManager;
use App\Support\Settings;
use Illuminate\Auth\Events\Registered;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;

class RegisterController extends Controller
{
    public function show(SeoManager $seo)
    {
        abort_unless(Settings::get('site.registration_enabled', true), 404);
        $seo->title('Create a free account')->noindex();

        return view('auth.register');
    }

    public function register(Request $request, NewsletterService $newsletter)
    {
        abort_unless(Settings::get('site.registration_enabled', true), 404);

        if (filled($request->input('website'))) {
            abort(422); // honeypot
        }

        $data = $request->validate([
            'name' => ['required', 'string', 'max:120'],
            'email' => ['required', 'email:rfc', 'max:190', 'unique:users,email'],
            'career_stage' => ['required', Rule::enum(CareerStage::class)],
            'institution' => ['nullable', 'string', 'max:160'],
            'country' => ['nullable', 'string', 'size:2'],
            'password' => ['required', 'confirmed', Password::min(10)->letters()->numbers()->uncompromised()],
            'terms' => ['accepted'],
            'newsletter' => ['nullable', 'boolean'],
        ]);

        $user = User::create([...collect($data)->except(['terms', 'newsletter'])->all(), 'is_active' => true]);
        $user->assignRole('member');

        event(new Registered($user));
        Auth::login($user);

        if ($request->boolean('newsletter')) {
            $newsletter->subscribe($user->email, $user->name, ['weekly_case'], 'registration', $user->id);
        }

        return redirect()->route('verification.notice');
    }
}
