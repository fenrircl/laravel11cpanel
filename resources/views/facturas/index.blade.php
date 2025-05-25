<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Listado de Facturas</title>
    {{-- Si estás usando Vite y Tailwind CSS, incluye tus assets compilados --}}
    {{-- @vite(['resources/css/app.css', 'resources/js/app.js']) --}}
    <style>
        body { font-family: sans-serif; margin: 20px; }
        table { width: 100%; border-collapse: collapse; }
        th, td { border: 1px solid #ddd; padding: 8px; text-align: left; }
        th { background-color: #f2f2f2; }
        .actions a { margin-right: 5px; text-decoration: none; }
    </style>
</head>
<body>

    <h1>Listado de Facturas</h1>

    {{-- Opcional: Enlace para crear una nueva factura --}}
    {{-- <a href="{{ route('facturas.create') }}">Crear Nueva Factura</a> --}}

    @if (session('success'))
        <div style="color: green; margin-bottom: 15px;">
            {{ session('success') }}
        </div>
    @endif

    @if ($invoices->isEmpty())
        <p>No hay facturas para mostrar.</p>
    @else
        <table>
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Número de Factura</th>
                    <th>Cliente</th> {{-- Asumiendo que tienes una relación o un campo cliente_id/nombre_cliente --}}
                    <th>Monto</th>
                    <th>Fecha de Emisión</th>
                    <th>Acciones</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($invoices as $invoice)
                    <tr>
                        <td>{{ $invoice->id }}</td>
                        <td>{{ $invoice->numero_factura ?? 'N/A' }}</td> {{-- Ajusta 'numero_factura' al nombre real de tu columna --}}
                        <td>{{ $invoice->cliente_nombre ?? ($invoice->client->name ?? 'N/A') }}</td> {{-- Ejemplo si tienes relación con Cliente --}}
                        <td>{{ number_format($invoice->monto ?? 0, 2) }}</td> {{-- Ajusta 'monto' --}}
                        <td>{{ $invoice->fecha_emision ? $invoice->fecha_emision->format('d/m/Y') : 'N/A' }}</td> {{-- Ajusta 'fecha_emision' y formatea si es Carbon --}}
                        <td class="actions">
                            {{-- Enlaces a las rutas de recursos (si las tienes definidas) --}}
                            {{-- <a href="{{ route('facturas.show', $invoice->id) }}">Ver</a> --}}
                            {{-- <a href="{{ route('facturas.edit', $invoice->id) }}">Editar</a> --}}
                            {{-- <form action="{{ route('facturas.destroy', $invoice->id) }}" method="POST" style="display:inline;"> --}}
                                {{-- @csrf --}}
                                {{-- @method('DELETE') --}}
                                {{-- <button type="submit" onclick="return confirm('¿Estás seguro?')">Eliminar</button> --}}
                            {{-- </form> --}}
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    @endif

</body>
</html>