<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="ie=edge">
    <meta name="base-url" content="{{ url('/') }}">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', config('app.name', 'Laravel'))</title>

    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">

    <!-- DataTables CSS -->
    <link href="https://cdn.datatables.net/1.13.6/css/dataTables.bootstrap5.min.css" rel="stylesheet">

    <!-- SweetAlert2 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.css" rel="stylesheet">

    <!-- Font Awesome para iconos -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">

    <!-- Select2 -->
    <link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
    
    <!-- Estilos principales y comunes -->
    <link href="{{ asset('assets/css/principal/layout.css') }}" rel="stylesheet">
    <link href="{{ asset('assets/css/comun/tablas.css') }}" rel="stylesheet">
    <link href="{{ asset('assets/css/comun/buscador.css') }}" rel="stylesheet">

    <!-- Cargar CSS específicos dinámicamente -->
    @if(isset($asset_css))
        @foreach($asset_css as $css)
            <link href="{{ asset('assets/css/' . $css . '.css') }}" rel="stylesheet">
        @endforeach
    @endif

    @stack('styles')
</head>

<body>
    @if (auth()->check() && !request()->is('login'))
    <div id="wrapper">
        <div id="sidebar">
            <div class="sidebar-header d-flex justify-content-between align-items-center">
                <h3 class="h5 mb-0">AcerosEra</h3>
                <button id="sidebarToggle" class="btn btn-link text-white p-0">
                    <i class="fas fa-bars"></i>
                </button>
            </div>
            @include('layouts.sidebar')
        </div>

        <div id="content" class="d-flex flex-column">
            <div class="header">
                @include('layouts.header')
            </div>

            <div class="container-fluid py-3 flex-1">
                @yield('content')
            </div>
        </div>
    </div>
    @else
        <div class="container-fluid py-3 flex-1">
            @yield('content')
        </div>
    @endif

    <!-- jQuery -->
    <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>

    <!-- Bootstrap Bundle JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>

    <!-- DataTables JS -->
    <script src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.6/js/dataTables.bootstrap5.min.js"></script>

    <!-- SweetAlert2 JS -->
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

    <!-- Select2 -->
    <script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>


    <script src="https://cdnjs.cloudflare.com/ajax/libs/html2pdf.js/0.8.0/html2pdf.bundle.min.js"></script>
    <!-- JavaScript principal y común -->
    <script src="{{ asset('assets/js/principal/layout.js') }}"></script>
    <script src="{{ asset('assets/js/comun/main.js') }}"></script>
    <script src="{{ asset('assets/js/comun/cache-local.js') }}"></script>
    <script src="{{ asset('assets/js/comun/buscador-global.js') }}"></script>

    <!-- Cargar JS específicos dinámicamente -->
    @if(isset($asset_js))
        @foreach($asset_js as $js)
            <script src="{{ asset('assets/js/' . $js . '.js') }}"></script>
        @endforeach
    @endif

    @stack('scripts')
</body>

<!-- Modal global: Vista rápida de Factura (ubicado fuera del header) -->
<div class="modal fade" id="invoiceQuickViewModal" tabindex="-1" aria-labelledby="invoiceQuickViewLabel" aria-hidden="true">
  <div class="modal-dialog modal-lg modal-dialog-scrollable">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title" id="invoiceQuickViewLabel"><i class="fas fa-file-invoice"></i> Factura</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <div class="modal-body">
        <div id="invoiceQuickViewBody">
          <div class="text-muted">Cargando factura...</div>
        </div>
      </div>
      <div class="modal-footer">
        <a id="invoiceFullViewLink" href="#" class="btn btn-primary" target="_self">
          Ver factura completa
        </a>
        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cerrar</button>
      </div>
    </div>
  </div>
</div>

<!-- Modal global: Vista rápida de Cliente -->
<div class="modal fade" id="clienteQuickViewModal" tabindex="-1" aria-labelledby="clienteQuickViewLabel" aria-hidden="true">
  <div class="modal-dialog modal-md modal-dialog-scrollable">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title" id="clienteQuickViewLabel"><i class="fas fa-user"></i> Cliente</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <div class="modal-body">
        <div id="clienteQuickViewBody">
          <div class="text-muted">Cargando cliente...</div>
        </div>
      </div>
      <div class="modal-footer">
        <a id="clienteFullViewLink" href="#" class="btn btn-primary" target="_self">
          Ver más
        </a>
        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cerrar</button>
      </div>
    </div>
  </div>
</div>

<!-- Modal global: Vista rápida de Proveedor -->
<div class="modal fade" id="proveedorQuickViewModal" tabindex="-1" aria-labelledby="proveedorQuickViewLabel" aria-hidden="true">
  <div class="modal-dialog modal-md modal-dialog-scrollable">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title" id="proveedorQuickViewLabel"><i class="fas fa-truck"></i> Proveedor</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <div class="modal-body">
        <div id="proveedorQuickViewBody">
          <div class="text-muted">Cargando proveedor...</div>
        </div>
      </div>
      <div class="modal-footer">
        <a id="proveedorFullViewLink" href="#" class="btn btn-primary" target="_self">
          Ver más
        </a>
        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cerrar</button>
      </div>
    </div>
  </div>
</div>
</html>