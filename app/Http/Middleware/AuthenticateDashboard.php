<?php

namespace App\Http\Middleware;

use App\Http\Responses\NoDashboardAccess;
use Filament\Facades\Filament;
use Filament\Http\Middleware\Authenticate;
use Filament\Models\Contracts\FilamentUser;
use Illuminate\Http\Exceptions\HttpResponseException;

/** Filament's dashboard authentication, with a clear page for signed-in users who may not enter. */
class AuthenticateDashboard extends Authenticate
{
    protected function authenticate($request, array $guards): void
    {
        $user = Filament::auth()->user();

        if ($user instanceof FilamentUser && ! $user->canAccessPanel(Filament::getCurrentOrDefaultPanel())) {
            throw new HttpResponseException(NoDashboardAccess::response($request));
        }

        parent::authenticate($request, $guards);
    }
}
