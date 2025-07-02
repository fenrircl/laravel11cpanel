@extends('layouts.app')

@section('content')
<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header">
                    <div class="d-flex justify-content-between align-items-center">
                        <h4 class="mb-0">Listado de Proveedores</h4>
                        <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#proveedorModal" onclick="openCreateModal()">
                            <i class="fas fa-plus me-1"></i> Nuevo Proveedor
                        </button>
                    </div>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table id="proveedores-table" class="table table-striped table-hover">
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

<!-- Modal para CRUD de Proveedores -->
<div class="modal fade" id="proveedorModal" tabindex="-1" aria-labelledby="proveedorModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="proveedorModalLabel">Proveedor</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form id="proveedorForm">
                <div class="modal-body">
                    @csrf
                    <input type="hidden" id="proveedorId" name="id">
                    
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
<div class="modal fade" id="viewProveedorModal" tabindex="-1" aria-labelledby="viewProveedorModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="viewProveedorModalLabel">Detalles del Proveedor</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div class="proveedor-details">
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
var currentProveedor = null;

// Función para abrir modal de creación
function openCreateModal() {
    isEdit = false;
    document.getElementById('proveedorModalLabel').textContent = 'Nuevo Proveedor';
    document.getElementById('proveedorForm').reset();
    document.getElementById('proveedorId').value = '';
    document.getElementById('saveBtn').textContent = 'Crear';
}

// Función para abrir modal de edición
function openEditModal(id) {
    isEdit = true;
    currentProveedor = PROVEEDORES.find(p => p.id == id);
    
    if (currentProveedor) {
        document.getElementById('proveedorModalLabel').textContent = 'Editar Proveedor';
        document.getElementById('proveedorId').value = currentProveedor.id;
        document.getElementById('nombre').value = currentProveedor.name || '';
        document.getElementById('email').value = currentProveedor.email || '';
        document.getElementById('telefono').value = currentProveedor.phone || '';
        document.getElementById('direccion').value = currentProveedor.address || '';
        document.getElementById('saveBtn').textContent = 'Actualizar';
        
        var modal = new bootstrap.Modal(document.getElementById('proveedorModal'));
        modal.show();
    }
}

// Función para ver detalles
function viewProveedor(id) {
    var proveedor = PROVEEDORES.find(p => p.id == id);
    
    if (proveedor) {
        document.getElementById('viewId').textContent = proveedor.id;
        document.getElementById('viewNombre').textContent = proveedor.name || 'N/A';
        document.getElementById('viewEmail').textContent = proveedor.email || 'N/A';
        document.getElementById('viewTelefono').textContent = proveedor.phone || 'N/A';
        document.getElementById('viewDireccion').textContent = proveedor.address || 'N/A';
        
        if (proveedor.created_at) {
            let date = new Date(proveedor.created_at);
            document.getElementById('viewCreatedAt').textContent = date.toLocaleDateString('es-ES') + ' ' + date.toLocaleTimeString('es-ES');
        } else {
            document.getElementById('viewCreatedAt').textContent = 'N/A';
        }
        
        var modal = new bootstrap.Modal(document.getElementById('viewProveedorModal'));
        modal.show();
    }
}

// Manejo del formulario
document.getElementById('proveedorForm').addEventListener('submit', function(e) {
    e.preventDefault();
    
    var formData = new FormData(this);
    var url = isEdit ? `/proveedores/${formData.get('id')}` : '/proveedores';
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
            $('#proveedorModal').modal('hide');
            Swal.fire({
                title: 'Éxito',
                text: isEdit ? 'Proveedor actualizado correctamente' : 'Proveedor creado correctamente',
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
</style>
@endpush
