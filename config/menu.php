<?php

return [
    [
        'section' => 'DASHBOARD',
        'items' => [
            [
                'title' => 'Menú principal',
                'icon' => 'fas fa-home',
                'route' => 'home',
                'tooltip' => 'Página principal',
                'auth_required' => false
            ]
        ]
    ],
    [
        'section' => 'SISTEMA DE INVENTARIO',
        'items' => [      
            [
                'title' => 'Clientes',
                'icon' => 'fas fa-users',
                'route' => 'clientes.index',
                'tooltip' => 'Gestión de clientes',
                'auth_required' => true
            ],
            [
                'title' => 'Proveedores',
                'icon' => 'fas fa-truck',
                'route' => 'proveedores.index',
                'tooltip' => 'Gestión de proveedores',
                'auth_required' => true
            ],
            // [
            //     'title' => 'Facturas',
            //     'icon' => 'fas fa-file-invoice',
            //     'route' => 'facturas.index',
            //     'tooltip' => 'Todas las facturas',
            //     'auth_required' => true
            // ],
            [
                'title' => 'Facturas Clientes',
                'icon' => 'fas fa-file-invoice-dollar',
                'route' => 'facturas.clientes.index',
                'tooltip' => 'Facturas de clientes',
                'auth_required' => true
            ],
            [
                'title' => 'Facturas Proveedores',
                'icon' => 'fas fa-file-invoice',
                'route' => 'facturas.proveedores.index',
                'tooltip' => 'Facturas de proveedores',
                'auth_required' => true
            ],
            [
                'title' => 'Cotizaciones',
                'icon' => 'fas fa-file-alt',
                'route' => 'cotizaciones.index',
                'url' => '#',
                'tooltip' => 'Gestión de cotizaciones',
                'auth_required' => true
            ]
            // [
            //     'title' => 'Métodos de Pago',
            //     'icon' => 'fas fa-credit-card',
            //     'route' => 'metodos-pago.index',
            //     'tooltip' => 'Gestión de métodos de pago',
            //     'auth_required' => true
            // ],
            // [
            //     'title' => 'Archivos',
            //     'icon' => 'fas fa-folder',
            //     'route' => null,
            //     'url' => '#',
            //     'tooltip' => 'Gestión de archivos',
            //     'auth_required' => true
            // ],
            // [
            //     'title' => 'Pagos',
            //     'icon' => 'fas fa-money-bill-wave',
            //     'route' => null,
            //     'url' => '#',
            //     'tooltip' => 'Gestión de pagos',
            //     'auth_required' => true
            // ],
            // [
            //     'title' => 'Reportes',
            //     'icon' => 'fas fa-chart-pie',
            //     'route' => null,
            //     'url' => '#',
            //     'tooltip' => 'Reportes del sistema',
            //     'auth_required' => true
            // ]
        ]
    ]
];
