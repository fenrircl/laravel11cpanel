@extends('layouts.app')

@section('content')
<div class="container py-4">
    <meta name="cliente-id" content="{{ $cliente->id }}">
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h3 class="mb-0">Cliente #{{ $cliente->id }}</h3>
        <div>
            <a href="{{ route('clientes.index') }}" class="btn btn-secondary btn-sm">Volver</a>
            <a href="{{ route('clientes.edit', $cliente) }}" class="btn btn-primary btn-sm">Editar</a>
        </div>
    </div>


    <div class="card shadow-sm mb-3">
        <div class="card-body">
            <div class="row g-3">
                <div class="col-md-6">
                    <div class="mb-2"><strong>RUT:</strong> {{ $cliente->rut ?? '—' }}</div>
                    <div class="mb-2"><strong>Nombre:</strong> {{ $cliente->name ?? '—' }}</div>
                    <div class="mb-2"><strong>Email:</strong> {{ $cliente->email ?? '—' }}</div>
                </div>
                <div class="col-md-6">
                    <div class="mb-2"><strong>Teléfono:</strong> {{ $cliente->phone ?? '—' }}</div>
                    <div class="mb-2"><strong>Dirección:</strong> {{ $cliente->address ?? '—' }}</div>
                    <div class="mb-2"><strong>Creado:</strong> {{ $cliente->created_at }}</div>
                </div>
            </div>
        </div>
    </div>

    <div class="card shadow-sm mb-3">
        <div class="card-header d-flex justify-content-between align-items-center">
            <strong><i class="fas fa-folder-open me-2"></i>Archivos asociados</strong>
            <button id="filesToggle" class="btn btn-outline-secondary btn-sm" type="button" data-bs-toggle="collapse" data-bs-target="#filesCollapse" aria-expanded="false" aria-controls="filesCollapse">
                Mostrar/Ocultar
            </button>
        </div>
        
        <div id="filesCollapse" class="collapse">
            <div class="card-body">
                @include('clientes.partials.adjuntos')

            </div>
        </div>
    </div>

    <div class="card shadow-sm">
        <div class="card-header">
            <strong><i class="fas fa-file-invoice me-2"></i>Facturas del cliente</strong>
        </div>
        <div class="card-body">
            <table id="cliente-facturas-table" class="table table-striped table-bordered w-100">
                <thead></thead>
                <tbody></tbody>
            </table>
        </div>
    </div>
</div>
<!-- Modal para CRUD de Facturas de Cliente -->
<div class="modal fade" id="facturaModal" tabindex="-1" aria-labelledby="facturaModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-xl">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="facturaModalLabel">Factura de Cliente</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <form id="facturaForm">
                    @csrf
                    <input type="hidden" id="factura_id" name="id" value="">
                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="invoice" class="form-label">Número de Factura <span class="text-danger">*</span></label>
                                <input type="text" class="form-control" id="invoice" name="invoice" required>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="date" class="form-label">Fecha <span class="text-danger">*</span></label>
                                <input type="date" class="form-control" id="date" name="date" required data-format="date-cl">
                            </div>
                        </div>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-12">
                            <div class="mb-3">
                                <label for="client_id" class="form-label">Cliente <span class="text-danger">*</span></label>
                                <select class="form-select" id="client_id" name="client_id" required>
                                    <option value="">Seleccionar cliente...</option>
                                    <!-- Se llenarán dinámicamente -->
                                </select>
                            </div>
                        </div>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="expiry" class="form-label">Fecha de Vencimiento</label>
                                <input type="date" class="form-control" id="expiry" name="expiry" data-format="date-cl">
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="amount" class="form-label">Monto <span class="text-danger">*</span></label>
                                <input class="form-control" type="text" id="amount" name="amount" inputmode="numeric" required data-format="clp" placeholder="$ 0">  
                            </div>
                        </div>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="payment_method_id" class="form-label">Método de Pago</label>
                                <select class="form-select" id="payment_method_id" name="payment_method_id">
                                    <option value="">Seleccionar método...</option>
                                    <!-- Se llenarán dinámicamente -->
                                </select>
                                <small class="text-muted">Opcional al crear. Se puede definir al editar la factura.</small>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="status" class="form-label">Estado <span class="text-danger">*</span></label>
                                <select class="form-select" id="status" name="status" required>
                                    <option value="0">Pendiente</option>
                                    <option value="1">Pagado</option>
                                </select>
                            </div>
                        </div>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="pay_date" class="form-label">Fecha de Pago</label>
                                <input type="date" class="form-control" id="pay_date" name="pay_date">
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="check" class="form-label">Número de Cheque</label>
                                <input type="text" class="form-control" id="check" name="check">
                            </div>
                        </div>
                    </div>
                    
                    <div class="mb-3">
                        <label for="detail" class="form-label">Detalles</label>
                        <textarea class="form-control" id="detail" name="detail" rows="3"></textarea>
                    </div>
                    
                    <div class="mb-3">
                        <label for="extra" class="form-label">Extra</label>
                        <textarea class="form-control" id="extra" name="extra" rows="3" placeholder="Información adicional"></textarea>
                    </div>
                    
                    <!-- Sección de gestión de archivos (solo para edición) -->
                    <div id="file-management-section" style="display: none;">
                        <!-- Acordeón para gestión de archivos -->
                        <div class="accordion mt-3" id="filesAccordion">
                            <div class="accordion-item">
                                <h2 class="accordion-header">
                                    <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#filesSection" aria-expanded="false" aria-controls="filesSection">
                                        <i class="fas fa-file-upload me-2"></i>Gestión de Archivos
                                    </button>
                                </h2>
                                <div id="filesSection" class="accordion-collapse collapse" data-bs-parent="#filesAccordion">
                                    <div class="accordion-body">
                                        <!-- Subida de archivos -->
                                        <div class="mb-3">
                                            <label for="file-upload" class="form-label">Subir Archivo</label>
                                            <div class="input-group">
                                                <input type="file" class="form-control" id="file-upload" accept=".pdf,.doc,.docx,.xls,.xlsx,.jpg,.jpeg,.png">
                                                <button class="btn btn-outline-primary" type="button" onclick="uploadFile()">
                                                    <i class="fas fa-upload"></i> Subir
                                                </button>
                                            </div>
                                            <small class="text-muted">Formatos permitidos: PDF, DOC, DOCX, XLS, XLSX, JPG, PNG. Máximo 10MB.</small>
                                        </div>
                                        
                                        <!-- Lista de archivos -->
                                        <div class="mb-3">
                                            <label class="form-label">Archivos Asociados</label>
                                            <div id="files-list" class="border rounded p-3 bg-light">
                                                <div class="text-center text-muted" id="no-files-message">
                                                    <i class="fas fa-folder-open fa-2x mb-2"></i>
                                                    <p>No hay archivos asociados a esta factura</p>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                <button type="button" class="btn btn-primary" onclick="saveFactura()">Guardar</button>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
// Helpers UI
function formatCurrency(value){
    try { const n = Number(value||0); return n.toLocaleString('es-CL',{style:'currency',currency:'CLP',maximumFractionDigits:0}); } catch(e){ return value; }
}
</script>
@endpush
