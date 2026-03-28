<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureSignatureSet
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if ($user && $user->isStaff() && empty($user->signature) && !session()->has('impersonator_id')) {
            if (!$request->routeIs('signature.setup', 'signature.setup.store', 'password.change', 'password.change.update', 'logout')) {
                return redirect()->route('signature.setup');
            }
        }

        return $next($request);
    }
}
