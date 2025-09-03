@extends('layouts.app')

@section('content')
<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h4 class="mb-0">Proveedores</h4>
                    <div class="btn-group" role="group">
                        <a href="{{ route('clientes.index') }}" class="btn btn-secondary btn-sm">Ver Clientes</a>
                        <button type="button" class="btn btn-primary btn-sm" data-bs-toggle="modal" data-bs-target="#proveedorModal" onclick="openCreateModalProveedor()">
                            Nuevo Proveedor
                        </button>
                    </div>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table id="proveedores-table" class="table table-striped table-hover w-100">
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
</div>

<!-- Modal para CRUD de Proveedores -->
<div class="modal fade" id="proveedorModal" tabindex="-1" aria-labelledby="proveedorModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="proveedorModalLabel">Nuevo Proveedor</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <form id="proveedorForm">
                    @csrf
                    <input type="hidden" id="proveedorId" name="id" value="">
                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="rut_prov" class="form-label">RUT</label>
                                <input type="text" class="form-control" id="rut_prov" name="rut" data-format="rut" placeholder="12.345.678-9" required>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="name_prov" class="form-label">Nombre</label>
                                <input type="text" class="form-control" id="name_prov" name="name" placeholder="Nombre del proveedor" required>
                            </div>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="email_prov" class="form-label">Email</label>
                                <input type="email" class="form-control" id="email_prov" name="email" data-format="email" placeholder="correo@dominio.cl">
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="phone_prov" class="form-label">Teléfono</label>
                                <input type="text" class="form-control" id="phone_prov" name="phone" data-format="phone-cl" placeholder="9 dígitos" maxlength="9">
                            </div>
                        </div>
                    </div>
                    <div class="mb-3">
                        <label for="address_prov" class="form-label">Dirección</label>
                        <input type="text" class="form-control" id="address_prov" name="address" placeholder="Dirección">
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                <button type="button" class="btn btn-primary" id="saveProveedorBtn">Guardar</button>
            </div>
        </div>
    </div>
</div>

<!-- Modal para Ver Detalles -->
<div class="modal fade" id="viewProveedorModal" tabindex="-1" aria-labelledby="viewProveedorModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="viewProveedorModalLabel">Detalle del Proveedor</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body" id="viewProveedorContent">
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
var isEditProveedor = false;
var currentProveedor = null;

function openCreateModalProveedor() {
    isEditProveedor = false;
    currentProveedor = null;
    document.getElementById('proveedorModalLabel').textContent = 'Nuevo Proveedor';
    document.getElementById('proveedorForm').reset();
    document.getElementById('proveedorId').value = '';
}

function openEditModalProveedor(id) {
    isEditProveedor = true;
    document.getElementById('proveedorModalLabel').textContent = 'Editar Proveedor';
    $.get(buildApiUrl('proveedores/' + id))
        .done(function(res){
            var p = res && res.proveedor ? res.proveedor : res;
            currentProveedor = p;
            $('#proveedorId').val(p.id);
            $('#rut_prov').val(p.rut || '');
            $('#name_prov').val(p.name || '');
            $('#email_prov').val(p.email || '');
            $('#phone_prov').val(p.phone || '');
            $('#address_prov').val(p.address || '');
            var m = new bootstrap.Modal(document.getElementById('proveedorModal'));
            m.show();
        })
        .fail(function(){
            Swal.fire({ icon: 'error', title: 'Error', text: 'No se pudo cargar el proveedor.' });
        });
}

function viewProveedor(id) {
    $.get(buildApiUrl('proveedores/' + id))
        .done(function(res){
            var p = res && res.proveedor ? res.proveedor : res;
            var html = '<ul class="list-group">'
                + '<li class="list-group-item"><strong>RUT:</strong> ' + (p.rut||'') + '</li>'
                + '<li class="list-group-item"><strong>Nombre:</strong> ' + (p.name||'') + '</li>'
                + '<li class="list-group-item"><strong>Email:</strong> ' + (p.email||'') + '</li>'
                + '<li class="list-group-item"><strong>Teléfono:</strong> ' + (p.phone||'') + '</li>'
                + '<li class="list-group-item"><strong>Dirección:</strong> ' + (p.address||'') + '</li>'
                + '</ul>';
            $('#viewProveedorContent').html(html);
            var m = new bootstrap.Modal(document.getElementById('viewProveedorModal'));
            m.show();
        })
        .fail(function(){
            Swal.fire({ icon: 'error', title: 'Error', text: 'No se pudo cargar el proveedor.' });
        });
}

$('#saveProveedorBtn').on('click', function(){
    var form = $('#proveedorForm');
    if (!form[0].checkValidity()) {
        form[0].reportValidity();
        return;
    }
    var id = $('#proveedorId').val();
    var editMode = !!id;
    var url = editMode ? buildApiUrl('proveedores/' + id) : buildApiUrl('proveedores');
    var data = form.serialize();
    if (editMode) { data += '&_method=PUT'; }

    $.ajax({
        url: url,
        type: 'POST',
        data: data,
        success: function(response){
            showSuccessMessage('Proveedor guardado correctamente', function(){
                $('#proveedorModal').modal('hide');
                if ($('#proveedores-table').length) {
                    $('#proveedores-table').DataTable().ajax.reload();
                }
                document.dispatchEvent(new CustomEvent('proveedores:updated'));
            });
        },
        error: function(xhr){
            var msg = 'Error al guardar el proveedor';
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
