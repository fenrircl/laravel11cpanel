@extends('layouts.app')

@push('styles')
<style>
    .users-page { --users-ink: #172b4d; --users-muted: #66758a; --users-line: #e5eaf0; color: var(--users-ink); }
    .users-heading { display:flex; align-items:flex-end; justify-content:space-between; gap:1rem; margin-bottom:1.5rem; }
    .users-heading h1 { font-size:1.65rem; font-weight:700; letter-spacing:-.035em; margin:0; }
    .users-heading p { color:var(--users-muted); margin:.35rem 0 0; }
    .users-stats { display:flex; gap:.65rem; margin-bottom:1.25rem; }
    .users-stat { border:1px solid var(--users-line); background:#fff; border-radius:.65rem; padding:.75rem 1rem; min-width:130px; }
    .users-stat strong { display:block; font-size:1.25rem; line-height:1.2; }
    .users-stat span { color:var(--users-muted); font-size:.82rem; }
    .users-panel { border:1px solid var(--users-line); border-radius:.75rem; overflow:hidden; background:#fff; }
    .users-toolbar { padding:1rem 1.15rem; border-bottom:1px solid var(--users-line); display:flex; align-items:center; justify-content:space-between; gap:1rem; }
    .users-toolbar h2 { font-size:1rem; font-weight:700; margin:0; }
    .users-search { max-width:300px; }
    .users-table { margin:0; }
    .users-table thead th { color:#69788c; font-size:.76rem; font-weight:700; letter-spacing:.035em; text-transform:uppercase; background:#f7f9fb; border-bottom:1px solid var(--users-line); padding:.8rem 1rem; }
    .users-table tbody td { padding:.85rem 1rem; border-color:#edf0f4; vertical-align:middle; }
    .user-cell { display:flex; align-items:center; gap:.75rem; min-width:210px; }
    .user-avatar { width:40px; height:40px; display:grid; place-items:center; flex:none; border-radius:50%; background:#e8eff8; color:#244b79; font-weight:700; }
    .user-name { font-weight:650; }
    .user-id, .user-email { color:var(--users-muted); font-size:.85rem; }
    .user-email { overflow-wrap:anywhere; }
    .role-badge { display:inline-block; background:#eef2f7; color:#40536d; border-radius:999px; padding:.28rem .6rem; font-size:.76rem; margin:.12rem .15rem .12rem 0; }
    .status-badge { display:inline-flex; align-items:center; gap:.4rem; border-radius:999px; padding:.32rem .62rem; font-size:.78rem; font-weight:650; white-space:nowrap; }
    .status-badge::before { content:''; width:7px; height:7px; border-radius:50%; background:currentColor; }
    .status-active { background:#e8f6ef; color:#167647; }
    .status-inactive { background:#f1f3f5; color:#687587; }
    .user-actions { display:flex; justify-content:flex-end; align-items:center; gap:.4rem; white-space:nowrap; }
    .user-actions form { display:inline; }
    .users-empty { padding:2.5rem 1rem!important; text-align:center; color:var(--users-muted); }
    .users-page .form-label { font-weight:600; font-size:.9rem; }
    .users-page .modal-header { border-bottom-color:var(--users-line); }
    .users-page .modal-footer { border-top-color:var(--users-line); }
    .users-page :focus-visible { outline:3px solid #81b5ec; outline-offset:2px; }
    @media (max-width: 767.98px) {
        .users-heading { align-items:flex-start; flex-direction:column; }
        .users-stats { width:100%; }
        .users-stat { flex:1; min-width:0; }
        .users-toolbar { align-items:stretch; flex-direction:column; }
        .users-search { max-width:none; }
        .users-table thead th, .users-table tbody td { padding:.7rem; }
        .user-actions { justify-content:flex-start; }
    }
</style>
@endpush

@section('content')
<div class="container py-4 users-page">
    <header class="users-heading">
        <div>
            <h1>Usuarios</h1>
        </div>
        <button class="btn btn-primary" type="button" data-bs-toggle="modal" data-bs-target="#createUserModal">
            <i class="fas fa-plus me-2" aria-hidden="true"></i>Crear usuario
        </button>
    </header>
    @include('admin._quick-nav', ['active' => 'users'])

    @if(session('success'))
        <div class="alert alert-success alert-dismissible fade show" role="status">
            {{ session('success') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Cerrar"></button>
        </div>
    @endif
    @if($errors->has('user'))
        <div class="alert alert-danger" role="alert">{{ $errors->first('user') }}</div>
    @endif
    @if($errors->any() && !$errors->has('user'))
        <div class="alert alert-danger" role="alert"><strong>Revisa los datos del formulario.</strong><ul class="mb-0 mt-1">@foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul></div>
    @endif

    <div class="users-stats" aria-label="Resumen de usuarios">
        <div class="users-stat"><strong>{{ $users->count() }}</strong><span>Cuentas</span></div>
        <div class="users-stat"><strong>{{ $users->where('is_active', true)->count() }}</strong><span>Habilitadas</span></div>
        <div class="users-stat"><strong>{{ $users->where('is_active', false)->count() }}</strong><span>Deshabilitadas</span></div>
    </div>

    <section class="users-panel" aria-labelledby="users-list-heading">
        <div class="users-toolbar">
            <h2 id="users-list-heading">Listado de usuarios</h2>
            <label class="users-search input-group">
                <span class="input-group-text bg-white"><i class="fas fa-search" aria-hidden="true"></i></span>
                <input type="search" id="userSearch" class="form-control" placeholder="Buscar por nombre, correo o rol" aria-label="Buscar usuarios">
            </label>
        </div>
        <div class="table-responsive">
            <table class="table users-table align-middle">
                <thead><tr><th scope="col">Usuario</th><th scope="col">Correo</th><th scope="col">Roles</th><th scope="col">Estado</th><th scope="col" class="text-end">Acciones</th></tr></thead>
                <tbody id="usersTableBody">
                    @forelse($users as $user)
                    <tr data-user-row data-search="{{ strtolower($user->name.' '.$user->email.' '.$user->roles->pluck('name')->implode(' ')) }}">
                        <td><div class="user-cell"><div class="user-avatar" aria-hidden="true">{{ mb_strtoupper(mb_substr($user->name, 0, 1)) }}</div><div><div class="user-name">{{ $user->name }}</div><div class="user-id">ID {{ $user->id }}</div></div></div></td>
                        <td class="user-email">{{ $user->email }}</td>
                        <td>@forelse($user->roles as $role)<span class="role-badge">{{ $role->name }}</span>@empty<span class="text-muted small">Sin roles</span>@endforelse</td>
                        <td><span class="status-badge {{ $user->is_active ? 'status-active' : 'status-inactive' }}">{{ $user->is_active ? 'Habilitado' : 'Deshabilitado' }}</span></td>
                        <td>
                            <div class="user-actions">
                                <button type="button" class="btn btn-sm btn-outline-primary" data-bs-toggle="modal" data-bs-target="#editUserModal"
                                    data-user-id="{{ $user->id }}" data-user-name="{{ $user->name }}" data-user-email="{{ $user->email }}"
                                    data-user-roles="{{ $user->roles->pluck('id')->implode(',') }}" aria-label="Editar {{ $user->name }}" title="Editar">
                                    <i class="fas fa-pen" aria-hidden="true"></i>
                                </button>
                                <button type="button" class="btn btn-sm btn-outline-secondary" data-bs-toggle="modal" data-bs-target="#resetPasswordModal"
                                    data-user-id="{{ $user->id }}" data-user-name="{{ $user->name }}" aria-label="Cambiar contraseña de {{ $user->name }}" title="Cambiar contraseña">
                                    <i class="fas fa-key" aria-hidden="true"></i>
                                </button>
                                <form method="POST" action="{{ route('admin.users.status', $user) }}">
                                    @csrf @method('PATCH')
                                    <button class="btn btn-sm {{ $user->is_active ? 'btn-outline-warning' : 'btn-outline-success' }}" type="submit" {{ auth()->id() === $user->id && $user->is_active ? 'disabled' : '' }} aria-label="{{ $user->is_active ? 'Deshabilitar' : 'Habilitar' }} {{ $user->name }}" title="{{ $user->is_active ? 'Deshabilitar' : 'Habilitar' }}">
                                        <i class="fas {{ $user->is_active ? 'fa-user-slash' : 'fa-user-check' }}" aria-hidden="true"></i>
                                    </button>
                                </form>
                                <form method="POST" action="{{ route('admin.users.destroy', $user) }}" onsubmit="return confirm('¿Eliminar definitivamente esta cuenta?')">
                                    @csrf @method('DELETE')
                                    <button class="btn btn-sm btn-outline-danger" type="submit" {{ auth()->id() === $user->id ? 'disabled' : '' }} aria-label="Eliminar {{ $user->name }}" title="Eliminar"><i class="fas fa-trash" aria-hidden="true"></i></button>
                                </form>
                            </div>
                        </td>
                    </tr>
                    @empty
                    <tr><td colspan="5" class="users-empty">Todavía no hay usuarios registrados.</td></tr>
                    @endforelse
                    <tr id="usersNoMatch" hidden><td colspan="5" class="users-empty">No hay usuarios que coincidan con la búsqueda.</td></tr>
                </tbody>
            </table>
        </div>
    </section>
</div>

<div class="modal fade" id="createUserModal" tabindex="-1" aria-labelledby="createUserTitle" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered"><div class="modal-content">
        <form method="POST" action="{{ route('admin.users.store') }}">
            @csrf
            <div class="modal-header"><div><h2 class="modal-title fs-5" id="createUserTitle">Crear usuario</h2><p class="text-muted small mb-0">Define sus datos de acceso y permisos.</p></div><button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button></div>
            <div class="modal-body">
                <div class="mb-3"><label for="createName" class="form-label">Nombre</label><input id="createName" name="name" class="form-control" value="{{ old('name') }}" required maxlength="255"></div>
                <div class="mb-3"><label for="createEmail" class="form-label">Correo electrónico</label><input id="createEmail" type="email" name="email" class="form-control" value="{{ old('email') }}" required></div>
                <div class="mb-3"><label for="createRoles" class="form-label">Roles</label><select id="createRoles" name="roles[]" class="form-select" multiple size="{{ min(max($roles->count(), 2), 5) }}">@foreach($roles as $role)<option value="{{ $role->id }}" @selected(collect(old('roles', []))->contains($role->id))>{{ $role->name }}</option>@endforeach</select><div class="form-text">Mantén Ctrl o Cmd para elegir más de uno.</div></div>
                <div class="row g-3"><div class="col-sm-6"><label for="createPassword" class="form-label">Contraseña</label><input id="createPassword" type="password" name="password" class="form-control" required minlength="6" autocomplete="new-password"></div><div class="col-sm-6"><label for="createPasswordConfirm" class="form-label">Confirmar contraseña</label><input id="createPasswordConfirm" type="password" name="password_confirmation" class="form-control" required minlength="6" autocomplete="new-password"></div></div>
            </div>
            <div class="modal-footer"><button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancelar</button><button class="btn btn-primary" type="submit">Crear usuario</button></div>
        </form>
    </div></div>
</div>

<div class="modal fade" id="editUserModal" tabindex="-1" aria-labelledby="editUserTitle" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered"><div class="modal-content">
        <form method="POST" id="editUserForm" action="">
            @csrf @method('PUT')
            <div class="modal-header"><div><h2 class="modal-title fs-5" id="editUserTitle">Editar usuario</h2><p class="text-muted small mb-0">Actualiza sus datos y roles.</p></div><button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button></div>
            <div class="modal-body">
                <div class="mb-3"><label for="editName" class="form-label">Nombre</label><input id="editName" name="name" class="form-control" required maxlength="255"></div>
                <div class="mb-3"><label for="editEmail" class="form-label">Correo electrónico</label><input id="editEmail" type="email" name="email" class="form-control" required></div>
                <div class="mb-3"><label for="editRoles" class="form-label">Roles</label><select id="editRoles" name="roles[]" class="form-select" multiple>@foreach($roles as $role)<option value="{{ $role->id }}">{{ $role->name }}</option>@endforeach</select><div class="form-text">Mantén Ctrl o Cmd para elegir más de uno.</div></div>
            </div>
            <div class="modal-footer"><button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancelar</button><button class="btn btn-primary" type="submit">Guardar cambios</button></div>
        </form>
    </div></div>
</div>

<div class="modal fade" id="resetPasswordModal" tabindex="-1" aria-labelledby="resetPasswordTitle" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered"><div class="modal-content">
        <form method="POST" id="resetPasswordForm" action="">
            @csrf
            <div class="modal-header"><div><h2 class="modal-title fs-5" id="resetPasswordTitle">Cambiar contraseña</h2><p class="text-muted small mb-0" id="resetPasswordUser"></p></div><button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button></div>
            <div class="modal-body"><div class="mb-3"><label for="resetPassword" class="form-label">Nueva contraseña</label><input id="resetPassword" type="password" name="password" class="form-control" required minlength="6" autocomplete="new-password"></div><div><label for="resetPasswordConfirm" class="form-label">Confirmar contraseña</label><input id="resetPasswordConfirm" type="password" name="password_confirmation" class="form-control" required minlength="6" autocomplete="new-password"></div></div>
            <div class="modal-footer"><button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancelar</button><button class="btn btn-primary" type="submit">Guardar contraseña</button></div>
        </form>
    </div></div>
</div>
@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function () {
    @if($errors->any() && !$errors->has('user'))
    bootstrap.Modal.getOrCreateInstance(document.getElementById('createUserModal')).show();
    @endif
    const editModal = document.getElementById('editUserModal');
    editModal.addEventListener('show.bs.modal', function (event) {
        const button = event.relatedTarget;
        const id = button.dataset.userId;
        document.getElementById('editUserForm').action = @json(url('/admin/users')) + '/' + id;
        document.getElementById('editName').value = button.dataset.userName;
        document.getElementById('editEmail').value = button.dataset.userEmail;
        const selectedRoles = button.dataset.userRoles.split(',').filter(Boolean);
        Array.from(document.getElementById('editRoles').options).forEach(option => option.selected = selectedRoles.includes(option.value));
    });

    document.getElementById('resetPasswordModal').addEventListener('show.bs.modal', function (event) {
        const button = event.relatedTarget;
        document.getElementById('resetPasswordForm').action = @json(url('/admin/users')) + '/' + button.dataset.userId + '/reset-password';
        document.getElementById('resetPasswordUser').textContent = button.dataset.userName;
        document.getElementById('resetPasswordForm').reset();
    });

    const search = document.getElementById('userSearch');
    search.addEventListener('input', function () {
        const query = search.value.trim().toLocaleLowerCase();
        let visible = 0;
        document.querySelectorAll('[data-user-row]').forEach(row => {
            const matches = row.dataset.search.includes(query);
            row.hidden = !matches;
            if (matches) visible++;
        });
        const noMatch = document.getElementById('usersNoMatch');
        noMatch.hidden = visible !== 0 || document.querySelectorAll('[data-user-row]').length === 0;
    });
});
</script>
@endpush
