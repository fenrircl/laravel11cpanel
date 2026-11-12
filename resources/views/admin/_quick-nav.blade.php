<nav class="admin-quick-nav mb-4" aria-label="Accesos rápidos de administración">
    <ul class="nav gap-2 flex-wrap">
        <li class="nav-item"><a class="nav-link {{ $active === 'system' ? 'active' : '' }}" @if($active === 'system') aria-current="page" @endif href="{{ route('admin.system-info.index') }}">Sistema y configuración</a></li>
        <li class="nav-item"><a class="nav-link" href="{{ route('admin.system-info.index') }}#assistant-config">IA</a></li>
        <li class="nav-item"><a class="nav-link" href="{{ route('admin.system-info.index') }}#chat-audit">Uso de IA</a></li>
        <li class="nav-item"><a class="nav-link" href="{{ route('admin.system-info.index') }}#php-runtime">PHP y servidor</a></li>
        <li class="nav-item"><a class="nav-link" href="{{ route('admin.system-info.index') }}#r2-status">R2</a></li>
        <li class="nav-item"><a class="nav-link {{ $active === 'jobs' ? 'active' : '' }}" @if($active === 'jobs') aria-current="page" @endif href="{{ route('admin.document-ingestions.index') }}">Jobs de importación</a></li>
        <li class="nav-item"><a class="nav-link" href="{{ route('admin.existing-invoice-pdfs.index') }}">PDF históricos</a></li>
    </ul>
</nav>
