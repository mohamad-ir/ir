<?php

namespace App\Services\Analytics;

use Illuminate\Http\Request;

/**
 * Anonymous visitor identifier: a hash of IP + user agent + a salt that
 * rotates daily. It cannot be reversed or linked across days, so no
 * cookie or consent is required (GDPR / PECR friendly).
 */
class VisitorHash
{
    public function for(Request $request): string
    {
        $salt = hash_hmac('sha256', now()->format('Y-m-d'), (string) config('app.key'));

        return substr(hash('sha256', $salt.'|'.$request->ip().'|'.$request->userAgent()), 0, 16);
    }
}
