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
            <span class="text-white">Bienvenido, {{ Auth::user()->name }}</span>
            <a href="{{ route('logout') }}"
               onclick="event.preventDefault(); document.getElementById('logout-form').submit();"
               class="text-white ms-3 text-decoration-none">
                Cerrar Sesión
            </a>
            <form id="logout-form" action="{{ route('logout') }}" method="POST" class="d-none">
                @csrf
            </form>
        @else
            <a href="{{ route('login') }}" class="text-white ms-3 text-decoration-none">Iniciar Sesión</a>
        @endauth
    </nav>
</div>