<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="ie=edge">
    <meta name="base-url" content="{{ url('/') }}">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', config('app.name', 'Laravel1'))</title>

    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">

    <!-- DataTables CSS -->
    <link href="https://cdn.datatables.net/1.13.6/css/dataTables.bootstrap5.min.css" rel="stylesheet">

    <!-- SweetAlert2 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.css" rel="stylesheet">

    <!-- Font Awesome para iconos -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">

    <!-- Estilos principales y comunes -->
    <link href="{{ asset('assets/css/principal/layout.css') }}" rel="stylesheet">
    <link href="{{ asset('assets/css/comun/tablas.css') }}" rel="stylesheet">

    <!-- Cargar CSS específicos dinámicamente -->
    @if(isset($asset_css))
        @foreach($asset_css as $css)
            <link href="{{ asset('assets/css/' . $css . '.css') }}" rel="stylesheet">
        @endforeach
    @endif

    @stack('styles')
        <!-- jQuery -->
    <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>

    <!-- Bootstrap Bundle JS (incluye Popper) -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>

    <!-- DataTables JS -->
    <script src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.6/js/dataTables.bootstrap5.min.js"></script>

    <!-- SweetAlert2 JS -->
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

    <!-- JavaScript principal y común -->
    <script src="{{ asset('assets/js/principal/layout.js') }}"></script>

    <!-- Cargar JS específicos dinámicamente -->
    @if(isset($asset_js))
        @foreach($asset_js as $js)
            <script src="{{ asset('assets/js/' . $js . '.js') }}"></script>
        @endforeach
    @endif
</head>

<body>
    <div id="wrapper">
        <!-- Sidebar -->
        <div id="sidebar">
            <div class="sidebar-header d-flex justify-content-between align-items-center">
                <h3 class="h5 mb-0">AcerosEra</h3>
                <button id="sidebarToggle" class="btn btn-link text-white p-0">
                    <i class="fas fa-bars"></i>
                </button>
            </div>
            @include('layouts.sidebar')
        </div>

        <!-- Contenedor principal -->
        <div id="content" class="d-flex flex-column">
            <!-- Header -->
            <div class="header">
                @include('layouts.header')
            </div>

            <!-- Contenido principal -->
            <div class="container-fluid py-3 flex-1">
                @yield('content')
            </div>
        </div>
    </div>


    @stack('scripts')
</body>
</html>