{{-- Componente de tabla de auditoría reutilizable --}}
@props(['logs' => [], 'title' => 'Últimas actividades', 'module' => null, 'entity_id' => null])

<div class="row mt-4">
    <div class="col-12">
        <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h5 class="mb-0">
                    <i class="fas fa-history me-2"></i>{{ $title }}
                </h5>
                <small class="text-muted">
                    @if($logs && $logs->isNotEmpty())
                        Últimos {{ $logs->count() }} registros
                    @else
                        Sin registros recientes
                    @endif
                </small>
            </div>
            <div class="card-body p-0">
                @if($logs && $logs->isNotEmpty())
                <div class="table-responsive">
                    <table class="table table-sm table-hover mb-0">
                        <thead class="table-light">
                            <tr>
                                <th width="120">Fecha</th>
                                <th width="100">Usuario</th>
                                <th width="80">Acción</th>
                                <th width="120">Entidad</th>
                                <th>Descripción</th>
                                <th width="80">Acciones</th>
                        </thead>
                        <tbody>
                            @foreach($logs as $log)
                            <tr>
                                <td class="text-muted small">
                                    {{ $log->created_at?->timezone('America/Santiago')->format('d/m H:i') }}
                                </td>
                                <td class="small">
                                    {{ $log->user?->name ?? '—' }}
                                </td>
                                <td>
                                    @php
                                        $actionText = match($log->action) {
                                            'create' => 'Creado',
                                            'update' => 'Editado',
                                            'delete' => 'Eliminado',
                                            'restore' => 'Restaurado',
                                            'upload' => 'Subido',
                                            'download' => 'Descargado',
                                            'login' => 'Ingreso',
                                            'logout' => 'Salida',
                                            default => ucfirst($log->action)
                                        };
                                        $badgeClass = match($log->action) {
                                            'create' => 'bg-success',
                                            'update' => 'bg-warning text-dark',
                                            'delete' => 'bg-danger',
                                            'restore' => 'bg-info',
                                            'upload' => 'bg-primary',
                                            'download' => 'bg-secondary',
                                            'login' => 'bg-success',
                                            'logout' => 'bg-secondary',
                                            default => 'bg-secondary'
                                        };
                                    @endphp
                                    <span class="badge {{ $badgeClass }} small">{{ $actionText }}</span>
                                </td>
                                <td class="small">
                                    @if(!empty($log->entity_display_info))
                                        @if($log->entity_display_info['type'] === 'cliente')
                                            <span class="text-info" title="{{ $log->entity_display_info['name'] }}">
                                                <i class="fas fa-user me-1"></i>{{ $log->entity_display_info['display'] }}
                                            </span>
                                        @elseif($log->entity_display_info['type'] === 'proveedor')
                                            <span class="text-warning" title="{{ $log->entity_display_info['name'] }}">
                                                <i class="fas fa-truck me-1"></i>{{ $log->entity_display_info['display'] }}
                                            </span>
                                        @elseif($log->entity_display_info['type'] === 'factura')
                                            <span class="text-primary">
                                                <i class="fas fa-file-invoice me-1"></i>#{{ $log->entity_display_info['display'] }}
                                            </span>
                                        @elseif($log->entity_display_info['type'] === 'cotizacion')
                                            <span class="text-success" title="Cliente: {{ $log->entity_display_info['cliente_name'] ?? '' }}">
                                                <i class="fas fa-file-alt me-1"></i>COT-{{ $log->entity_display_info['display'] }}
                                            </span>
                                        @else
                                            {{ $log->entity_display_info['display'] ?? '—' }}
                                        @endif
                                    @elseif($log->module === 'archivos' && !empty($log->entity_id))
                                        <span class="text-secondary">
                                            <i class="fas fa-file me-1"></i>Archivo
                                        </span>
                                    @else
                                        —
                                    @endif
                                </td>
                                <td class="small" style="">
                                    @php
                                        // Limpiar y mejorar la descripción
                                        $description = $log->description;
                                        // Corregir caracteres mal codificados comunes
                                        $replacements = [
                                            'Ã±' => 'ñ',
                                            'Ã\'' => 'Ñ',
                                            'Ã³' => 'ó',
                                            'Ã©' => 'é',
                                            'Ã­' => 'í',
                                            'Ãº' => 'ú',
                                            'Ã¡' => 'á',
                                            'CreÃ³' => 'Creó',
                                            'EditÃ³' => 'Editó',
                                            'EliminÃ³' => 'Eliminó',
                                            'COMPAÃ\'IA' => 'COMPAÑÍA'
                                        ];
                                        $description = str_replace(array_keys($replacements), array_values($replacements), $description);
                                        // Limpiar otros caracteres problemáticos
                                        $description = html_entity_decode($description, ENT_QUOTES, 'UTF-8');
                                    @endphp
                                    <span class="d-block" title="{{ $description }}">
                                        {{ $description }}
                                    </span>
                                </td>
                                <td class="text-center">
                                    @if($log->module == 'facturas' && !empty($log->reversible) && $log->action === 'delete')
                                        <button type="button" class="btn btn-sm btn-outline-primary" 
                                                onclick="confirmRestore({{ $log->id }}, '{{ addslashes($log->description) }}')"
                                                title="Restaurar factura eliminada">
                                            <i class="fas fa-undo"></i>
                                        </button>
                                    @else
                                        <span class="text-muted">—</span>
                                    @endif
                                </td>
                            @endforeach
                        </tbody>
                    </table>
                </div>
                @else
                <div class="card-body text-center text-muted py-4">
                    <i class="fas fa-info-circle me-2"></i>
                    No hay registros de actividad recientes
                </div>
                @endif
            </div>
        </div>
    </div>
</div>

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function() {
    // Inicializar tooltips de Bootstrap para los detalles de cambios
    var tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
    var tooltipList = tooltipTriggerList.map(function(tooltipTriggerEl) {
        return new bootstrap.Tooltip(tooltipTriggerEl, {
            html: true,
            placement: 'left'
        });
    });
});

// Función para confirmar restauración con SweetAlert
function confirmRestore(logId, description) {
    Swal.fire({
        title: '¿Restaurar factura?',
        text: 'Se creará una nueva factura con los datos originales. ' + description,
        icon: 'question',
        showCancelButton: true,
        confirmButtonColor: '#3085d6',
        cancelButtonColor: '#d33',
        confirmButtonText: 'Sí, restaurar',
        cancelButtonText: 'Cancelar'
    }).then((result) => {
        if (result.isConfirmed) {
            // Enviar petición AJAX para restaurar
            restoreFactura(logId);
        }
    });
}

// Función para restaurar factura vía AJAX
function restoreFactura(logId) {
    // Mostrar loading
    Swal.fire({
        title: 'Restaurando...',
        text: 'Por favor espera',
        allowOutsideClick: false,
        showConfirmButton: false,
        willOpen: () => {
            Swal.showLoading();
        }
    });

    // Construir URL usando Laravel's url helper
    const baseUrl = '{{ url("/") }}';
    const url = baseUrl + '/audit/facturas/' + logId + '/restore';

    // Enviar petición
    fetch(url, {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
            'X-Requested-With': 'XMLHttpRequest'
        },
        credentials: 'same-origin'
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            Swal.fire({
                icon: 'success',
                title: '¡Restaurada!',
                text: 'La factura ha sido restaurada correctamente',
                timer: 2000,
                showConfirmButton: false
            }).then(() => {
                // Recargar la página para mostrar los cambios
                location.reload();
            });
        } else {
            Swal.fire({
                icon: 'error',
                title: 'Error',
                text: data.message || 'No se pudo restaurar la factura'
            });
        }
    })
    .catch(error => {
        console.error('Error:', error);
        Swal.fire({
            icon: 'error',
            title: 'Error',
            text: 'Ocurrió un error al restaurar la factura'
        });
    });
}
</script>
@endpush
