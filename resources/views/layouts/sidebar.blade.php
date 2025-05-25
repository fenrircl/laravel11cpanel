<aside class="app-sidebar">
    {{-- Incluye tu parcial de sidebar aquí si lo tienes separado --}}
    {{-- Ejemplo: @include('layouts.partials._sidebar') --}}
    {{-- O coloca el HTML del sidebar directamente --}}


    <div class="sidebar-content p-2">
        <div class="mt-2 mb-3">
            <div class="text-uppercase text-muted small fw-bold ms-2">DASHBOARD</div>
            <ul class="nav flex-column">
                <li class="nav-item">
                    <a class="nav-link text-white d-flex align-items-center" href="{{ route('home') }}">
                        <i class="fas fa-home me-2"></i> 
                        <span class="nav-text">Menú principal</span>
                    </a>
                </li>
            </ul>
        </div>

        @auth
        <div class="mt-2 mb-3">
            <div class="text-uppercase text-muted small fw-bold ms-2">SISTEMA DE INVENTARIO</div>
            <ul class="nav flex-column">
                <li class="nav-item">
                    <a class="nav-link text-white d-flex align-items-center" href="#">
                        <i class="fas fa-box me-2"></i> 
                        <span class="nav-text">Stock</span>
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link text-white d-flex align-items-center" href="#">
                        <i class="fas fa-dolly me-2"></i> 
                        <span class="nav-text">Suministros</span>
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link text-white d-flex align-items-center" href="{{ route('clientes.index') }}">
                        <i class="fas fa-users me-2"></i> 
                        <span class="nav-text">Clientes</span>
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link text-white d-flex align-items-center" href="#">
                        <i class="fas fa-truck me-2"></i> 
                        <span class="nav-text">Proveedores</span>
                    </a>
                </li>
                {{-- <li class="nav-item">
                    <a class="nav-link text-white d-flex align-items-center" href="{{ route('facturas.index') }}">
                        <i class="fas fa-file-invoice me-2"></i> 
                        <span class="nav-text">Facturas Cliente</span>
                    </a>
                </li> --}}
                <li class="nav-item">
                    <a class="nav-link text-white d-flex align-items-center" href="#">
                        <i class="fas fa-file-invoice-dollar me-2"></i> 
                        <span class="nav-text">Facturas Proveedor</span>
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link text-white d-flex align-items-center" href="#">
                        <i class="fas fa-file-alt me-2"></i> 
                        <span class="nav-text">Cotizaciones</span>
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link text-white d-flex align-items-center" href="#">
                        <i class="fas fa-folder me-2"></i> 
                        <span class="nav-text">Archivos</span>
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link text-white d-flex align-items-center" href="#">
                        <i class="fas fa-money-bill-wave me-2"></i> 
                        <span class="nav-text">Pagos</span>
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link text-white d-flex align-items-center" href="#">
                        <i class="fas fa-chart-pie me-2"></i> 
                        <span class="nav-text">Reportes</span>
                    </a>
                </li>
            </ul>
        </div>
        @endauth
    </div>
</aside>