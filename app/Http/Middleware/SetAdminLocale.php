<?php

namespace App\Http\Middleware;

use App\Support\AdminLocale;
use Closure;
use Illuminate\Http\Request;

/** Registered on the Filament panel only (persistent, so Livewire updates of dashboard pages use it too). */
class SetAdminLocale
{
    public function handle(Request $request, Closure $next)
    {
        AdminLocale::apply(AdminLocale::current());

        return $next($request);
    }
}
