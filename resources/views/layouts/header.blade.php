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

{{-- Carga dinámica de assets JS y CSS --}}
@if(isset($asset_css) && is_array($asset_css))
    @foreach($asset_css as $css_file)
        <link rel="stylesheet" href="{{ asset('assets/css/' . $css_file) }}.css">
    @endforeach
@endif

@if(isset($asset_js) && is_array($asset_js))
    @foreach($asset_js as $js_file)
        <script src="{{ asset('assets/js/' . $js_file) }}.js"></script>
    @endforeach
@endif


@if(isset($asset_plugins_js) && is_array($asset_plugins_js))
    @foreach($asset_plugins_js as $js_file)
        <script src="{{ asset('assets/js/plugins/' . $js_file) }}.js"></script>
        echo "<!-- Plugin JS: $js_file -->";
    @endforeach
@endif