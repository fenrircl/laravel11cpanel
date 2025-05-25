<div class="d-flex justify-content-between align-items-center">
    <div class="d-flex align-items-center">
        <button id="sidebarToggle" class="btn btn-link text-white me-3">
            <i class="fas fa-bars"></i>
        </button>
        <h1 class="h4 mb-0">{{ config('app.name', 'Mi Aplicación') }}</h1>
    </div>
    
    <nav>
        {{-- Ejemplo de enlaces de navegación en el header --}}
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
            {{-- <a href="{{ route('register') }}" class="text-white ms-3 text-decoration-none">Registrarse</a> --}}
        @endauth
    </nav>
</div>