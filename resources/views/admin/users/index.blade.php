@extends('layouts.app')

@section('content')
<div class="container py-4">
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h3 class="mb-0"><i class="fas fa-user-shield me-2"></i>Administración de usuarios</h3>
    </div>

    <div class="card mb-4">
        <div class="card-header bg-primary text-white">Crear usuario</div>
        <div class="card-body">
            <form method="POST" action="{{ route('admin.users.store') }}" class="row g-3">
                @csrf
                <div class="col-md-4">
                    <label class="form-label">Nombre</label>
                    <input type="text" name="name" class="form-control" required>
                </div>
                <div class="col-md-4">
                    <label class="form-label">Email</label>
                    <input type="email" name="email" class="form-control" required>
                </div>
                <div class="col-md-4">
                    <label class="form-label">Rol(es)</label>
                    <select name="roles[]" class="form-select" multiple>
                        @foreach($roles as $r)
                            <option value="{{ $r->id }}">{{ $r->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-4">
                    <label class="form-label">Contraseña</label>
                    <input type="password" name="password" class="form-control" required>
                </div>
                <div class="col-md-4">
                    <label class="form-label">Confirmar contraseña</label>
                    <input type="password" name="password_confirmation" class="form-control" required>
                </div>
                <div class="col-12">
                    <button class="btn btn-primary"><i class="fas fa-plus me-1"></i>Crear</button>
                </div>
            </form>
        </div>
    </div>

    <div class="card">
        <div class="card-header bg-light fw-bold">Usuarios</div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-hover align-middle">
                    <thead class="table-primary">
                        <tr>
                            <th style="width:40px;"></th>
                            <th>ID</th>
                            <th>Nombre</th>
                            <th>Email</th>
                            <th>Roles</th>
                            <th style="width:220px;">Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($users as $u)
                        <tr>
                            <td>
                                <div class="rounded-circle bg-secondary d-flex align-items-center justify-content-center" style="width:36px;height:36px;">
                                    <span class="text-white fw-bold">{{ strtoupper(substr($u->name,0,1)) }}</span>
                                </div>
                            </td>
                            <td class="fw-bold">{{ $u->id }}</td>
                            <td>
                                <form method="POST" action="{{ route('admin.users.update', $u) }}" class="row g-2 align-items-center">
                                    @csrf
                                    @method('PUT')
                                    <div class="col-auto">
                                        <input type="text" name="name" class="form-control form-control-sm" value="{{ $u->name }}" style="min-width:120px;">
                                    </div>
                                    <div class="col-auto">
                                        <input type="email" name="email" class="form-control form-control-sm" value="{{ $u->email }}" style="min-width:160px;">
                                    </div>
                                    <div class="col-auto">
                                        <select name="roles[]" class="form-select form-select-sm" multiple style="min-width:120px;">
                                            @foreach($roles as $r)
                                                <option value="{{ $r->id }}" {{ $u->roles->pluck('id')->contains($r->id) ? 'selected' : '' }}>{{ $r->name }}</option>
                                            @endforeach
                                        </select>
                                    </div>
                                    <div class="col-auto">
                                        <button class="btn btn-sm btn-success"><i class="fas fa-save"></i></button>
                                    </div>
                                </form>
                            </td>
                            <td>{{ $u->email }}</td>
                            <td>
                                @foreach($u->roles as $role)
                                    <span class="badge bg-{{ $role->slug === 'admin' ? 'danger' : ($role->slug === 'manager' ? 'info' : 'secondary') }} me-1">{{ $role->name }}</span>
                                @endforeach
                            </td>
                            <td class="text-nowrap">
                                <div class="d-flex flex-column gap-1">
                                    <form method="POST" action="{{ route('admin.users.reset', $u) }}" class="d-flex gap-1 align-items-center">
                                        @csrf
                                        <input type="password" name="password" class="form-control form-control-sm" placeholder="Nueva clave" required style="max-width:110px;">
                                        <input type="password" name="password_confirmation" class="form-control form-control-sm" placeholder="Confirmar" required style="max-width:110px;">
                                        <button class="btn btn-warning btn-sm" title="Resetear clave"><i class="fas fa-key"></i></button>
                                    </form>
                                    <form method="POST" action="{{ route('admin.users.destroy', $u) }}" onsubmit="return confirm('¿Eliminar usuario?')">
                                        @csrf
                                        @method('DELETE')
                                        <button class="btn btn-sm btn-danger w-100" title="Eliminar"><i class="fas fa-trash"></i></button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
@endsection
