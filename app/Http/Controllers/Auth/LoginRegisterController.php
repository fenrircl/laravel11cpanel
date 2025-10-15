<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Session;
use Illuminate\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;
use App\Models\AuditLog;
use App\Services\AuditLogger;

class LoginRegisterController extends Controller implements HasMiddleware
{
    public static function middleware(): array
    {
        return [
            new Middleware('guest', except: ['home', 'logout']),
            new Middleware('auth', only: ['home', 'logout']),
        ];
    }

    private function putUserRolesInSession(User $user, Request $request): void
    {
        $roles = $user->roles()->pluck('slug')->toArray();
        $isAdmin = in_array('admin', $roles, true);
        $request->session()->put('user_roles', $roles);
        $request->session()->put('is_admin', $isAdmin);
    }

    public function register(): RedirectResponse
    {
        return redirect('/');
    }
    
    public function store(Request $request): RedirectResponse
    {
        $request->validate([
            'name' => 'required|string|max:250',
            'email' => 'required|string|email:rfc,dns|max:250|unique:users,email',
            'password' => 'required|string|min:8|confirmed'
        ]);

        $user = User::create([
            'name' => $request->name,
            'email' => $request->email,
            'password' => Hash::make($request->password)
        ]);

        $credentials = $request->only('email', 'password');
        Auth::attempt($credentials);
        $request->session()->regenerate();
        if (Auth::check()) {
            $this->putUserRolesInSession(Auth::user(), $request);
            // Guardar también user_id para auditoría
            $request->session()->put('user_id', Auth::id());
        }
        return redirect()->route('home')
            ->withSuccess('¡Te has registrado e iniciado sesión exitosamente!');
    }

    public function login(): View
    {
        return view('auth.login');
    }

    public function authenticate(Request $request): RedirectResponse
    {
        $credentials = $request->validate([
            'email' => 'required|email',
            'password' => 'required'
        ]);

        if(Auth::attempt($credentials))
        {
            $request->session()->regenerate();
            $this->putUserRolesInSession(Auth::user(), $request);

            // Persistir también user_id en sesión para auditoría
            $request->session()->put('user_id', Auth::id());

            // Log de auditoría usando sesión
            AuditLogger::log($request, 'login', 'auth', null, 'Ingreso al sistema');
            return redirect()->route('home');
        }

        return back()->withErrors([
            'email' => 'Las credenciales proporcionadas no coinciden con nuestros registros.',
        ])->onlyInput('email');

    }
    
    public function home(): View
    {
        return view('auth.home');
    } 
    
    public function logout(Request $request): RedirectResponse
    {
        if (Auth::check()) {
            // Log de auditoría antes de cerrar sesión
            AuditLogger::log($request, 'logout', 'auth', null, 'Salida del sistema');
        }
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();
        return redirect()->route('login')
            ->withSuccess('¡Has cerrado sesión exitosamente!');
    }
}