<?php

namespace App\Http\Middleware;

use App\Filament\Pages\CustomerWelcome;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class RedirectCustomerToWelcome
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if (
            $user?->isCustomer()
            && blank($user->customer_portal_welcomed_at)
            && $request->is('admin*')
            && ! $request->is('admin/customer-welcome')
            && ! $request->is('admin/logout')
        ) {
            return redirect(CustomerWelcome::getUrl());
        }

        return $next($request);
    }
}
