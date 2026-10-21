<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Services\Seo\SeoManager;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;

class LoginController extends Controller
{
    public function show(Request $request, SeoManager $seo)
    {
        self::rememberRedirect($request);
        $seo->title('Sign in')->noindex();

        return view('auth.login');
    }

    /** ?redirect=/courses/x returns the visitor to that page after signing in or registering (local paths only). */
    public static function rememberRedirect(Request $request): void
    {
        $to = (string) $request->query('redirect');
        if (str_starts_with($to, '/') && ! str_starts_with($to, '//') && ! str_contains($to, '\\')) {
            $request->session()->put('url.intended', url($to));
        }
    }

    public function login(Request $request)
    {
        $credentials = $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required', 'string'],
        ]);

        if (! Auth::attempt([...$credentials, 'is_active' => true], $request->boolean('remember'))) {
            throw ValidationException::withMessages(['email' => 'These details do not match our records.']);
        }

        $request->session()->regenerate();
        $user = $request->user();

        // Staff with two-factor authentication must use the dashboard login, which enforces it.
        if ($user->isStaff()) {
            Auth::logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();

            return redirect(url(config('cms.admin_path').'/login'))->with('status', 'Team members sign in through the secure dashboard login.');
        }

        return redirect()->intended(route('account.dashboard'));
    }

    public function logout(Request $request)
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect('/');
    }
}
