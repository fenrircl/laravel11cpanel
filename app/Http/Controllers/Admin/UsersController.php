<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\Role;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class UsersController extends Controller
{
    public function index()
    {
        $users = User::with('roles')->orderBy('id','desc')->get();
        $roles = Role::orderBy('name')->get();
        $data = [
            'users' => $users,
            'roles' => $roles,
            'asset_css' => ['comun/tablas'],
            'asset_js' => ['admin/users']
        ];
        return view('admin.users.index', $data);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:users,email',
            'password' => 'required|string|min:6|confirmed',
            'roles' => 'array',
            'roles.*' => 'exists:roles,id'
        ]);

        $user = User::create([
            'name' => $validated['name'],
            'email' => $validated['email'],
            'password' => Hash::make($validated['password'])
        ]);
        if (!empty($validated['roles'])) {
            $user->roles()->sync($validated['roles']);
        }
        return back()->with('success','Usuario creado');
    }

    public function update(Request $request, User $user)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:users,email,' . $user->id,
            'password' => 'nullable|string|min:6|confirmed',
            'roles' => 'array',
            'roles.*' => 'exists:roles,id'
        ]);

        $user->name = $validated['name'];
        $user->email = $validated['email'];
        if (!empty($validated['password'])) {
            $user->password = Hash::make($validated['password']);
        }
        $user->save();
        $user->roles()->sync($validated['roles'] ?? []);
        return back()->with('success','Usuario actualizado');
    }

    public function destroy(User $user)
    {
        $user->delete();
        return back()->with('success','Usuario eliminado');
    }

    public function resetPassword(Request $request, User $user)
    {
        $validated = $request->validate([
            'password' => 'required|string|min:6|confirmed'
        ]);
        $user->password = Hash::make($validated['password']);
        $user->save();
        return back()->with('success','Contraseña reseteada');
    }
}
