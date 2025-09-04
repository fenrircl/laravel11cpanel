<div class="d-flex justify-content-between align-items-center">
    <div class="d-flex align-items-center">
    </div>

    <div class="header-search-container flex-grow-1 mx-4">
        <div class="input-group">
            <select id="search-entity" class="form-select search-select" style="max-width: 140px;">
                <option value="all">Todo</option>
                <option value="clientes">Clientes</option>
                <option value="proveedores">Proveedores</option>
                <option value="facturas">Facturas</option>
            </select>
            <input type="text" 
                   id="search-input" 
                   class="form-control search-input" 
                   placeholder="Buscar en el sistema..." 
                   maxlength="100">
            <button class="btn btn-search" type="button" id="search-button">
                <i class="fas fa-search"></i>
            </button>
        </div>
        
        <div id="search-results" class="search-results-dropdown"></div>
    </div>
    
    <nav>
        @auth
            <div class="dropdown">
                <button class="btn btn-outline-light btn-sm dropdown-toggle d-flex align-items-center" type="button" id="userMenuButton" data-bs-toggle="dropdown" aria-expanded="false">
                    <i class="fas fa-user-circle me-2"></i>
                    <span class="text-truncate" style="max-width: 180px;">{{ Auth::user()->name }}</span>
                </button>
                <ul class="dropdown-menu dropdown-menu-end" aria-labelledby="userMenuButton">
                    <li class="dropdown-header small text-muted px-3">{{ Auth::user()->email }}</li>
                    <li><hr class="dropdown-divider"></li>
                    <li>
                        <a class="dropdown-item" href="{{ route('logout') }}" onclick="event.preventDefault(); document.getElementById('logout-form').submit();">
                            <i class="fas fa-sign-out-alt me-2"></i> Cerrar sesión
                        </a>
                    </li>
                </ul>
            </div>
            <form id="logout-form" action="{{ route('logout') }}" method="POST" class="d-none">
                @csrf
            </form>
        @else
            <a href="{{ route('login') }}" class="btn btn-outline-light btn-sm">Iniciar sesión</a>
        @endauth
    </nav>
</div>

{{-- Modal movido al layout principal para evitar conflictos de z-index --}}