<nav class="admin-quick-nav mb-4" aria-label="Accesos rápidos de administración">
    <ul class="nav gap-2 flex-wrap">
        @if(\Illuminate\Support\Facades\Route::has('admin.kpi.index'))
            <li class="nav-item"><a class="nav-link {{ $active === 'kpi' ? 'active' : '' }}" @if($active === 'kpi') aria-current="page" @endif href="{{ route('admin.kpi.index') }}">KPI</a></li>
        @else
            <li class="nav-item"><span class="nav-link text-muted" aria-disabled="true" title="Actualiza la caché de rutas de Laravel">KPI · actualizar rutas</span></li>
        @endif
        <li class="nav-item"><a class="nav-link {{ $active === 'system' ? 'active' : '' }}" @if($active === 'system') aria-current="page" @endif href="{{ route('admin.system-info.index') }}">Sistema y configuración</a></li>
        <li class="nav-item"><a class="nav-link {{ $active === 'users' ? 'active' : '' }}" @if($active === 'users') aria-current="page" @endif href="{{ route('admin.users.index') }}">Usuarios</a></li>
        <li class="nav-item"><a class="nav-link {{ $active === 'roles' ? 'active' : '' }}" @if($active === 'roles') aria-current="page" @endif href="{{ route('admin.roles.index') }}">Roles</a></li>
        <li class="nav-item"><a class="nav-link {{ $active === 'audit' ? 'active' : '' }}" @if($active === 'audit') aria-current="page" @endif href="{{ route('admin.audit.index') }}">Auditoría</a></li>
        <li class="nav-item"><a class="nav-link {{ $active === 'jobs' ? 'active' : '' }}" @if($active === 'jobs') aria-current="page" @endif href="{{ route('admin.document-ingestions.index') }}">Jobs de importación</a></li>
        <li class="nav-item"><a class="nav-link {{ $active === 'pdfs' ? 'active' : '' }}" @if($active === 'pdfs') aria-current="page" @endif href="{{ route('admin.existing-invoice-pdfs.index') }}">PDF de facturas</a></li>
    </ul>
</nav>

@once
    @push('scripts')
        <script>
        document.addEventListener('DOMContentLoaded', () => {
            if (!window.Swal) return;
            @if(session('r2_check'))
                Swal.fire({
                    toast: true,
                    position: 'top-end',
                    icon: @js(session('r2_check.ok') ? 'success' : 'error'),
                    title: @js(session('r2_check.message')),
                    showConfirmButton: false,
                    timer: 5000,
                    timerProgressBar: true
                });
            @elseif(session('success'))
                Swal.fire({
                    toast: true,
                    position: 'top-end',
                    icon: 'success',
                    title: @js(session('success')),
                    showConfirmButton: false,
                    timer: 4000,
                    timerProgressBar: true
                });
            @elseif($errors->any())
                Swal.fire({
                    icon: 'error',
                    title: 'No se pudo completar',
                    text: @js(implode(' · ', $errors->all())),
                    confirmButtonText: 'Entendido'
                });
            @endif
        });
        </script>
    @endpush
@endonce
