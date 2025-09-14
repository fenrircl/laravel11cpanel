@extends('layouts.app')

@section('content')
<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h4 class="mb-0">Clientes</h4>
                    <div class="btn-group" role="group">
                        <a href="{{ route('proveedores.index') }}" class="btn btn-secondary btn-sm">Ver Proveedores</a>
                        <button type="button" class="btn btn-primary btn-sm" data-bs-toggle="modal" data-bs-target="#clienteModal" onclick="openCreateModal()">
                            Nuevo Cliente
                        </button>
                    </div>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table id="clientes-table" class="table table-striped table-hover w-100">
                            <thead>
                                <tr>
                                    <th>RUT</th>
                                    <th>Nombre</th>
                                    <th>Email</th>
                                    <th>Creado</th>
                                    <th>Acciones</th>
                                </tr>
                            </thead>
                            <tbody></tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <div class="row">
        
    </div>
</div>

<!-- Modal para CRUD de Clientes -->
<div class="modal fade" id="clienteModal" tabindex="-1" aria-labelledby="clienteModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="clienteModalLabel">Nuevo Cliente</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <form id="clienteForm">
                    @csrf
                    <input type="hidden" id="clienteId" name="id" value="">
                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="rut" class="form-label">RUT</label>
                                <input type="text" class="form-control" id="rut" name="rut" data-format="rut" placeholder="12.345.678-9" required>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="name" class="form-label">Nombre</label>
                                <input type="text" class="form-control" id="name" name="name" placeholder="Nombre del cliente" required>
                            </div>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="email" class="form-label">Email</label>
                                <input type="email" class="form-control" id="email" name="email" data-format="email" placeholder="correo@dominio.cl">
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="phone" class="form-label">Teléfono</label>
                                <input type="text" class="form-control" id="phone" name="phone" data-format="phone-cl" placeholder="9 dígitos" maxlength="9">
                            </div>
                        </div>
                    </div>
                    <div class="mb-3">
                        <label for="address" class="form-label">Dirección</label>
                        <input type="text" class="form-control" id="address" name="address" placeholder="Dirección">
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                <button type="button" class="btn btn-primary" id="saveClienteBtn">Guardar</button>
            </div>
        </div>
    </div>
</div>

<!-- Modal para Ver Detalles -->
<div class="modal fade" id="viewClienteModal" tabindex="-1" aria-labelledby="viewClienteModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="viewClienteModalLabel">Detalle del Cliente</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body" id="viewClienteContent">
                <!-- Contenido dinámico -->
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

// Abrir modal de creación
function openCreateModal() {
    isEdit = false;
    currentCliente = null;
    document.getElementById('clienteModalLabel').textContent = 'Nuevo Cliente';
    document.getElementById('clienteForm').reset();
    document.getElementById('clienteId').value = '';
}

// Abrir modal de edición
function openEditModal(id) {
    isEdit = true;
    document.getElementById('clienteModalLabel').textContent = 'Editar Cliente';
    $.get(buildApiUrl('clientes/' + id))
        .done(function(res){
            var c = res && res.cliente ? res.cliente : res;
            currentCliente = c;
            // Poblar formulario
            $('#clienteId').val(c.id);
            $('#rut').val(c.rut || '');
            $('#name').val(c.name || '');
            $('#email').val(c.email || '');
            $('#phone').val(c.phone || '');
            $('#address').val(c.address || '');
            // Mostrar modal
            var modalEl = document.getElementById('clienteModal');
            var m = new bootstrap.Modal(modalEl);
            m.show();
            // Formatear y validar RUT al abrir y en blur
            if (window.CLInputFormatter) {
                const rutEl = document.getElementById('rut');
                if (rutEl) {
                    window.CLInputFormatter.bind(rutEl);
                    // Forzar validación/actualización inmediata
                    if (typeof window.CLInputFormatter.onRutBlur === 'function') {
                        window.CLInputFormatter.onRutBlur(rutEl);
                    } else {
                        window.CLInputFormatter.updateHint(rutEl);
                    }
                }
            }
        })
        .fail(function(){
            Swal.fire({ icon: 'error', title: 'Error', text: 'No se pudo cargar el cliente.' });
        });
}

// Ver detalles
function viewCliente(id) {
    $.get(buildApiUrl('clientes/' + id))
        .done(function(res){
            var c = res && res.cliente ? res.cliente : res;
            var html = '<ul class="list-group">'
                + '<li class="list-group-item"><strong>RUT:</strong> ' + (c.rut||'') + '</li>'
                + '<li class="list-group-item"><strong>Nombre:</strong> ' + (c.name||'') + '</li>'
                + '<li class="list-group-item"><strong>Email:</strong> ' + (c.email||'') + '</li>'
                + '<li class="list-group-item"><strong>Teléfono:</strong> ' + (c.phone||'') + '</li>'
                + '<li class="list-group-item"><strong>Dirección:</strong> ' + (c.address||'') + '</li>'
                + '</ul>';
            $('#viewClienteContent').html(html);
            var m = new bootstrap.Modal(document.getElementById('viewClienteModal'));
            m.show();
        })
        .fail(function(){
            Swal.fire({ icon: 'error', title: 'Error', text: 'No se pudo cargar el cliente.' });
        });
}

// Guardar (crear/editar)
$('#saveClienteBtn').on('click', function(){
    var form = $('#clienteForm');
    if (!form[0].checkValidity()) {
        form[0].reportValidity();
        return;
    }
    var id = $('#clienteId').val();
    var isEditMode = !!id;
    var url = isEditMode ? buildApiUrl('clientes/' + id) : buildApiUrl('clientes');
    var data = form.serialize();
    if (isEditMode) { data += '&_method=PUT'; }

    $.ajax({
        url: url,
        type: 'POST',
        data: data,
        success: function(response){
            showSuccessMessage('Cliente guardado correctamente', function(){
                $('#clienteModal').modal('hide');
                if ($('#clientes-table').length) {
                    $('#clientes-table').DataTable().ajax.reload();
                }
                // Notificar a otros módulos
                document.dispatchEvent(new CustomEvent('clientes:updated'));
            });
        },
        error: function(xhr){
            var msg = 'Error al guardar el cliente';
            if (xhr.responseJSON && xhr.responseJSON.message) msg = xhr.responseJSON.message;
            Swal.fire({ icon: 'error', title: 'Error', text: msg });
        }
    });
});
</script>
@endpush

@push('styles')
<style>
</style>
@endpush
