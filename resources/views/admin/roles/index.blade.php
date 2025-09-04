@extends('layouts.app')

@section('content')
<div class="container py-4">
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h3 class="mb-0">Administración de roles</h3>
    </div>

    <div class="card mb-4">
        <div class="card-header">Crear rol</div>
        <div class="card-body">
            <form method="POST" action="{{ route('admin.roles.store') }}" class="row g-3">
                @csrf
                <div class="col-md-4">
                    <label class="form-label">Nombre</label>
                    <input type="text" name="name" class="form-control" required>
                </div>
                <div class="col-md-4">
                    <label class="form-label">Slug</label>
                    <input type="text" name="slug" class="form-control" required>
                </div>
                <div class="col-12">
                    <button class="btn btn-primary">Crear</button>
                </div>
            </form>
        </div>
    </div>

    <div class="card">
        <div class="card-header">Roles</div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-striped align-middle">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Nombre</th>
                            <th>Slug</th>
                            <th>Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($roles as $role)
                        <tr>
                            <td>{{ $role->id }}</td>
                            <td>
                                <form method="POST" action="{{ route('admin.roles.update', $role) }}" class="row g-2">
                                    @csrf
                                    @method('PUT')
                                    <div class="col-md-5">
                                        <input type="text" name="name" class="form-control form-control-sm" value="{{ $role->name }}">
                                    </div>
                                    <div class="col-md-5">
                                        <input type="text" name="slug" class="form-control form-control-sm" value="{{ $role->slug }}">
                                    </div>
                                    <div class="col-md-2 text-end">
                                        <button class="btn btn-sm btn-primary">Guardar</button>
                                    </div>
                                </form>
                            </td>
                            <td>{{ $role->slug }}</td>
                            <td>
                                <form method="POST" action="{{ route('admin.roles.destroy', $role) }}" onsubmit="return confirm('¿Eliminar rol?')">
                                    @csrf
                                    @method('DELETE')
                                    <button class="btn btn-sm btn-danger">Eliminar</button>
                                </form>
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
