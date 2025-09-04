<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Role;
use Illuminate\Http\Request;

class RolesController extends Controller
{
    public function index()
    {
        $roles = Role::orderBy('name')->get();
        return view('admin.roles.index', compact('roles'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:100',
            'slug' => 'required|string|max:100|unique:roles,slug'
        ]);
        Role::create($validated);
        return back()->with('success','Rol creado');
    }

    public function update(Request $request, Role $role)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:100',
            'slug' => 'required|string|max:100|unique:roles,slug,' . $role->id
        ]);
        $role->update($validated);
        return back()->with('success','Rol actualizado');
    }

    public function destroy(Role $role)
    {
        $role->delete();
        return back()->with('success','Rol eliminado');
    }
}
