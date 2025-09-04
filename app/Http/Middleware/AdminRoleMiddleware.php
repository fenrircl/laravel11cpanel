<?php
namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class AdminRoleMiddleware
{
    public function handle(Request $request, Closure $next)
    {
        $user = Auth::user();
        if (!$user) {
            abort(403, 'No tienes permisos para acceder a administración.');
        }

        $isAdmin = $request->session()->get('is_admin');
        if ($isAdmin === null) {
            $isAdmin = $user->hasRole('admin');
            $request->session()->put('is_admin', $isAdmin);
        }

        if (!$isAdmin) {
            abort(403, 'No tienes permisos para acceder a administración.');
        }
        return $next($request);
    }
}
