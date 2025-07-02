@extends('layouts.app')

@section('content')
<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header">
                    <div class="d-flex justify-content-between align-items-center">
                        <h4 class="mb-0">Listado de Clientes</h4>
                        <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#clienteModal" onclick="openCreateModal()">
                            <i class="fas fa-plus me-1"></i> Nuevo Cliente
                        </button>
                    </div>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table id="clientes-table" class="table table-striped table-hover">
                            <thead>
                                <tr>
                                    <th>ID</th>
                                    <th>Nombre</th>
                                    <th>Email</th>
                                    <th>Fecha de Registro</th>
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

<!-- Modal para CRUD de Clientes -->
<div class="modal fade" id="clienteModal" tabindex="-1" aria-labelledby="clienteModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="clienteModalLabel">Cliente</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form id="clienteForm">
                <div class="modal-body">
                    @csrf
                    <input type="hidden" id="clienteId" name="id">
                    
                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="nombre" class="form-label">Nombre *</label>
                                <input type="text" class="form-control" id="nombre" name="nombre" required>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="email" class="form-label">Email</label>
                                <input type="email" class="form-control" id="email" name="email">
                            </div>
                        </div>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="telefono" class="form-label">Teléfono</label>
                                <input type="text" class="form-control" id="telefono" name="telefono">
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="direccion" class="form-label">Dirección</label>
                                <input type="text" class="form-control" id="direccion" name="direccion">
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-primary" id="saveBtn">Guardar</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Modal para Ver Detalles -->
<div class="modal fade" id="viewClienteModal" tabindex="-1" aria-labelledby="viewClienteModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="viewClienteModalLabel">Detalles del Cliente</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div class="cliente-details">
                    <div class="detail-item">
                        <strong>ID:</strong> <span id="viewId"></span>
                    </div>
                    <div class="detail-item">
                        <strong>Nombre:</strong> <span id="viewNombre"></span>
                    </div>
                    <div class="detail-item">
                        <strong>Email:</strong> <span id="viewEmail"></span>
                    </div>
                    <div class="detail-item">
                        <strong>Teléfono:</strong> <span id="viewTelefono"></span>
                    </div>
                    <div class="detail-item">
                        <strong>Dirección:</strong> <span id="viewDireccion"></span>
                    </div>
                    <div class="detail-item">
                        <strong>Fecha de Registro:</strong> <span id="viewCreatedAt"></span>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cerrar</button>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
// Variables globales
var isEdit = false;
var currentCliente = null;

// Función para abrir modal de creación
function openCreateModal() {
    isEdit = false;
    document.getElementById('clienteModalLabel').textContent = 'Nuevo Cliente';
    document.getElementById('clienteForm').reset();
    document.getElementById('clienteId').value = '';
    document.getElementById('saveBtn').textContent = 'Crear';
}

// Función para abrir modal de edición
function openEditModal(id) {
    isEdit = true;
    currentCliente = CLIENTES.find(c => c.id == id);
    
    if (currentCliente) {
        document.getElementById('clienteModalLabel').textContent = 'Editar Cliente';
        document.getElementById('clienteId').value = currentCliente.id;
        document.getElementById('nombre').value = currentCliente.name || '';
        document.getElementById('email').value = currentCliente.email || '';
        document.getElementById('telefono').value = currentCliente.phone || '';
        document.getElementById('direccion').value = currentCliente.address || '';
        document.getElementById('saveBtn').textContent = 'Actualizar';
        
        var modal = new bootstrap.Modal(document.getElementById('clienteModal'));
        modal.show();
    }
}

// Función para ver detalles
function viewCliente(id) {
    var cliente = CLIENTES.find(c => c.id == id);
    
    if (cliente) {
        document.getElementById('viewId').textContent = cliente.id;
        document.getElementById('viewNombre').textContent = cliente.name || 'N/A';
        document.getElementById('viewEmail').textContent = cliente.email || 'N/A';
        document.getElementById('viewTelefono').textContent = cliente.phone || 'N/A';
        document.getElementById('viewDireccion').textContent = cliente.address || 'N/A';
        
        if (cliente.created_at) {
            let date = new Date(cliente.created_at);
            document.getElementById('viewCreatedAt').textContent = date.toLocaleDateString('es-ES') + ' ' + date.toLocaleTimeString('es-ES');
        } else {
            document.getElementById('viewCreatedAt').textContent = 'N/A';
        }
        
        var modal = new bootstrap.Modal(document.getElementById('viewClienteModal'));
        modal.show();
    }
}

// Manejo del formulario
document.getElementById('clienteForm').addEventListener('submit', function(e) {
    e.preventDefault();
    
    var formData = new FormData(this);
    var url = isEdit ? `/clientes/${formData.get('id')}` : '/clientes';
    var method = isEdit ? 'PUT' : 'POST';
    
    // Agregar método para PUT
    if (isEdit) {
        formData.append('_method', 'PUT');
    }
    
    $.ajax({
        url: url,
        type: 'POST',
        data: formData,
        processData: false,
        contentType: false,
        success: function(response) {
            $('#clienteModal').modal('hide');
            Swal.fire({
                title: 'Éxito',
                text: isEdit ? 'Cliente actualizado correctamente' : 'Cliente creado correctamente',
                icon: 'success'
            }).then(() => {
                location.reload();
            });
        },
        error: function(xhr) {
            Swal.fire({
                title: 'Error',
                text: 'Ocurrió un error al procesar la solicitud',
                icon: 'error'
            });
        }
    });
});
</script>
@endpush

@push('styles')
<style>
    .btn-group .btn {
        margin-right: 3px;
    }
    
    .cliente-details {
        padding: 20px 0;
    }

    .detail-item {
        display: flex;
        justify-content: space-between;
        align-items: center;
        padding: 12px 0;
        border-bottom: 1px solid #f1f3f4;
        transition: background-color 0.2s ease;
    }

    .detail-item:hover {
        background-color: #f8f9fa;
        padding-left: 10px;
        padding-right: 10px;
        margin-left: -10px;
        margin-right: -10px;
        border-radius: 6px;
    }

    .detail-item:last-child {
        border-bottom: none;
    }

    .detail-item strong {
        color: #495057;
        font-weight: 600;
        min-width: 120px;
    }

    .detail-item span {
        color: #6c757d;
        text-align: right;
        flex: 1;
    }
</style>
@endpush
