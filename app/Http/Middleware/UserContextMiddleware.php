<?php
namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class UserContextMiddleware
{
    public function handle(Request $request, Closure $next)
    {
        if (Auth::check()) {
            $user = Auth::user();

            // Roles e is_admin desde sesión (o calcular y guardar)
            $roles = $request->session()->get('user_roles');
            if ($roles === null) {
                $roles = $user->roles()->pluck('slug')->toArray();
                $request->session()->put('user_roles', $roles);
            }

            $isAdmin = $request->session()->get('is_admin');
            if ($isAdmin === null) {
                $isAdmin = in_array('admin', $roles, true);
                $request->session()->put('is_admin', $isAdmin);
            }

            // Guardar id en sesión para reutilizar
            if (!$request->session()->has('user_id')) {
                $request->session()->put('user_id', (int) $user->id);
            }

            // Contexto por request y para vistas
            $context = [
                'id' => (int) $user->id,
                'name' => (string) $user->name,
                'email' => (string) $user->email,
                'roles' => $roles,
                'is_admin' => (bool) $isAdmin,
            ];
            $request->attributes->set('userContext', $context);
            view()->share('userContext', $context);
        }

        return $next($request);
    }
}
