<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Services\Seo\SeoManager;
use Illuminate\Foundation\Auth\EmailVerificationRequest;
use Illuminate\Http\Request;

class VerificationController extends Controller
{
    public function notice(Request $request, SeoManager $seo)
    {
        if ($request->user()->hasVerifiedEmail()) {
            return redirect()->route('account.dashboard');
        }
        $seo->title('Verify your email')->noindex();

        return view('auth.verify-email');
    }

    public function verify(EmailVerificationRequest $request)
    {
        $request->fulfill();

        return redirect()->route('account.dashboard')->with('status', 'Thank you — your email address is verified.');
    }

    public function send(Request $request)
    {
        $request->user()->sendEmailVerificationNotification();

        return back()->with('status', 'A new verification link has been sent.');
    }
}
