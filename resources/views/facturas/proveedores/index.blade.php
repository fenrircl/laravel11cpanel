@extends('layouts.app')

@section('content')
<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header">
                    <div class="d-flex justify-content-between align-items-center">
                        <h4 class="mb-0">Facturas de Proveedores</h4>
                        <div class="btn-group" role="group">
                            <a href="{{ route('facturas.index') }}" class="btn btn-secondary btn-sm">
                                <i class="fas fa-arrow-left me-1"></i> Todas las Facturas
                            </a>
                            <a href="{{ route('facturas.clientes.index') }}" class="btn btn-info btn-sm">
                                <i class="fas fa-users me-1"></i> Facturas Clientes
                            </a>
                            <button type="button" class="btn btn-primary btn-sm" data-bs-toggle="modal" data-bs-target="#facturaModal" onclick="openCreateModal('proveedor')">
                                <i class="fas fa-plus me-1"></i> Nueva Factura Proveedor
                            </button>
                        </div>
                    </div>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table id="facturas-proveedores-table" class="table table-striped table-hover">
                            <thead>
                                <tr>
                                    <th>ID</th>
                                    <th>Número Factura</th>
                                    <th>Proveedor</th>
                                    <th>Fecha</th>
                                    <th>Vencimiento</th>
                                    <th>Monto</th>
                                    <th>Estado</th>
                                    <th>Método Pago</th>
                                    <th>Acciones</th>
                                </tr>
                            </thead>
                            <tbody>
                                <!-- Los datos se cargarán con DataTables -->
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Modal para CRUD de Facturas de Proveedor -->
<div class="modal fade" id="facturaModal" tabindex="-1" aria-labelledby="facturaModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="facturaModalLabel">Factura de Proveedor</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <form id="facturaForm">
                    @csrf
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
                                <input type="date" class="form-control" id="date" name="date" required>
                            </div>
                        </div>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-12">
                            <div class="mb-3">
                                <label for="provider_id" class="form-label">Proveedor <span class="text-danger">*</span></label>
                                <select class="form-select" id="provider_id" name="provider_id" required>
                                    <option value="">Seleccionar proveedor...</option>
                                    <!-- Se llenarán dinámicamente -->
                                </select>
                            </div>
                        </div>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="expiry" class="form-label">Fecha de Vencimiento</label>
                                <input type="date" class="form-control" id="expiry" name="expiry">
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="amount" class="form-label">Monto <span class="text-danger">*</span></label>
                                <input type="number" class="form-control" id="amount" name="amount" step="0.01" min="0" required>
                            </div>
                        </div>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="payment_method_id" class="form-label">Método de Pago <span class="text-danger">*</span></label>
                                <select class="form-select" id="payment_method_id" name="payment_method_id" required>
                                    <option value="">Seleccionar método...</option>
                                    <!-- Se llenarán dinámicamente -->
                                </select>
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
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                <button type="button" class="btn btn-primary" onclick="saveFactura()">Guardar</button>
            </div>
        </div>
    </div>
</div>

<!-- Modal de detalles -->
<div class="modal fade" id="facturaDetailsModal" tabindex="-1" aria-labelledby="facturaDetailsModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="facturaDetailsModalLabel">Detalles de la Factura</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body" id="facturaDetailsContent">
                <!-- Contenido se carga dinámicamente -->
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cerrar</button>
            </div>
        </div>
    </div>
</div>
@endsection
