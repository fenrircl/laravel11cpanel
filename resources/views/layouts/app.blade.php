<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="ie=edge">
    <title>@yield('title', config('app.name', 'Laravel'))</title>

    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">

    <!-- DataTables CSS -->
    <link href="https://cdn.datatables.net/1.13.6/css/dataTables.bootstrap5.min.css" rel="stylesheet">

    <!-- SweetAlert2 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.css" rel="stylesheet">

    <!-- Font Awesome para iconos -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">

    <!-- Tus estilos personalizados -->
    <link href="{{ asset('css/custom.css') }}" rel="stylesheet">

    <style>
        body {
            overflow-x: hidden;
        }
        
        #wrapper {
            display: flex;
        }
        
        #sidebar {
            min-height: 100vh;
            width: 325px;
            background-color: #2c3e50;
            color: white;
            transition: all 0.3s;
            position: relative;
            z-index: 999;
        }
        
        #sidebar.collapsed {
            width: 20px; /* Aumentado desde 60px para dar más espacio a los iconos */
        }

        #sidebar.collapsed .app-sidebar {
            width: 50px; /* Ajusta el ancho del contenido cuando el sidebar está colapsado */
        }
        
        #sidebar.collapsed .nav-text,
        #sidebar.collapsed .sidebar-header h3,
        #sidebar.collapsed .text-uppercase {
            display: none;
        }
        
        #sidebar.collapsed .nav-link {
            padding: 10px;
            display: flex;
            justify-content: center;
        }
        
        #sidebar.collapsed .nav-item i {
            margin-right: 0 !important;
            font-size: 1.25rem; /* Ligeramente más grande */
        }
        
        #sidebar.collapsed .sidebar-content {
            padding: 10px 5px !important;
        }
        
        #content {
            width: 100%;
            transition: all 0.3s;
        }
        
        .header {
            background-color: #3498db;
            color: white;
            padding: 15px;
        }
        
        .sidebar-header {
            padding: 15px;
            border-bottom: 1px solid rgba(255,255,255,0.1);
        }
        
        /* Estiliza el hover para que sea más visible en modo colapsado */
        #sidebar.collapsed .nav-link:hover {
            background-color: rgba(255, 255, 255, 0.1);
            border-radius: 5px;
        }
        
        @media (max-width: 768px) {
            #sidebar {
                width: 70px;
            }
            
            #sidebar .nav-text,
            #sidebar .sidebar-header h3,
            #sidebar .text-uppercase {
                display: none;
            }
            
            #sidebar .nav-link {
                padding: 10px;
                display: flex;
                justify-content: center;
            }
            
            #sidebar .nav-item i {
                margin-right: 0 !important;
                font-size: 1.25rem;
            }
            
            #sidebar.active {
                width: 250px;
            }
            
            #sidebar.active .nav-text,
            #sidebar.active .sidebar-header h3,
            #sidebar.active .text-uppercase {
                display: inline-block;
            }
            
            #sidebar.active .nav-link {
                justify-content: flex-start;
                padding: .5rem 1rem;
            }
            
            #sidebar.active .nav-item i {
                margin-right: 0.5rem !important;
            }
        }
    </style>

    @stack('styles')
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
        <div id="content" class="d-flex flex-column w-100">
            <!-- Header -->
            <div class="header">
                <h2 class="h4 mb-0">Laravel</h2>
            </div>

            <!-- Contenido principal -->
            <div class="container-fluid py-3">
                @yield('content')
            </div>
        </div>
    </div>

    <!-- jQuery -->
    <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>

    <!-- Bootstrap Bundle JS (incluye Popper) -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>

    <!-- DataTables JS -->
    <script src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.6/js/dataTables.bootstrap5.min.js"></script>

    <!-- SweetAlert2 JS -->
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

    <script>
        $(document).ready(function() {
            $('#sidebarToggle').on('click', function() {
                $('#sidebar').toggleClass('collapsed');
                
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
            }
        });
    </script>

    @stack('scripts')
</body>
</html>