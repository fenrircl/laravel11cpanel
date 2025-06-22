<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="ie=edge">
    <title>@yield('title', config('app.name', 'Laravel1'))</title>

    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">

    <!-- DataTables CSS -->
    <link href="https://cdn.datatables.net/1.13.6/css/dataTables.bootstrap5.min.css" rel="stylesheet">

    <!-- SweetAlert2 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.css" rel="stylesheet">

    <!-- Font Awesome para iconos -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">

    <!-- Tus estilos personalizados -->
    <link href="{{ asset('assets/css/custom.css') }}" rel="stylesheet">

    <style>
     
    </style>

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



    <script>
        $(document).ready(function() {
            $('#sidebarToggle').on('click', function() {
                $('#sidebar').toggleClass('collapsed');
                $('#content').toggleClass('expanded');
                
                // Redimensionar DataTables después de la transición
                setTimeout(function() {
                    // Redimensionar todas las DataTables existentes
                   // $.fn.dataTable.tables({ visible: true, api: true }).columns.adjust().responsive.recalc();
                }, 350); // Esperar a que termine la transición (300ms + buffer)
                
                // Guardar estado en localStorage para recordar preferencia
                if ($('#sidebar').hasClass('collapsed')) {
                    localStorage.setItem('sidebarState', 'collapsed');
                } else {
                    localStorage.setItem('sidebarState', 'expanded');
                }
            });
            
            // Restaurar estado del sidebar
            var sidebarState = localStorage.getItem('sidebarState');
            if (sidebarState === 'collapsed') {
                $('#sidebar').addClass('collapsed');
                $('#content').addClass('expanded');
                // Redimensionar DataTables después de restaurar el estado
                setTimeout(function() {
                    $.fn.dataTable.tables({ visible: true, api: true }).columns.adjust().responsive.recalc();
                }, 100);
            }
            
            // Redimensionar DataTables cuando cambie el tamaño de la ventana
            $(window).on('resize', function() {
                setTimeout(function() {
                    $.fn.dataTable.tables({ visible: true, api: true }).columns.adjust().responsive.recalc();
                }, 100);
            });
        });
    </script>

    @stack('scripts')
</body>
</html>