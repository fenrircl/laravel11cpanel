<nav class="admin-quick-nav mb-4" aria-label="Accesos rápidos de administración">
    <ul class="nav gap-2 flex-wrap">
        <li class="nav-item"><a class="nav-link {{ $active === 'system' ? 'active' : '' }}" @if($active === 'system') aria-current="page" @endif href="{{ route('admin.system-info.index') }}">Sistema y configuración</a></li>
        <li class="nav-item"><a class="nav-link {{ $active === 'users' ? 'active' : '' }}" @if($active === 'users') aria-current="page" @endif href="{{ route('admin.users.index') }}">Usuarios</a></li>
        <li class="nav-item"><a class="nav-link {{ $active === 'roles' ? 'active' : '' }}" @if($active === 'roles') aria-current="page" @endif href="{{ route('admin.roles.index') }}">Roles</a></li>
        <li class="nav-item"><a class="nav-link {{ $active === 'audit' ? 'active' : '' }}" @if($active === 'audit') aria-current="page" @endif href="{{ route('admin.audit.index') }}">Auditoría</a></li>
        <li class="nav-item"><a class="nav-link {{ $active === 'jobs' ? 'active' : '' }}" @if($active === 'jobs') aria-current="page" @endif href="{{ route('admin.document-ingestions.index') }}">Jobs de importación</a></li>
        <li class="nav-item"><a class="nav-link {{ $active === 'pdfs' ? 'active' : '' }}" @if($active === 'pdfs') aria-current="page" @endif href="{{ route('admin.existing-invoice-pdfs.index') }}">PDF de facturas</a></li>
    </ul>
</nav>
