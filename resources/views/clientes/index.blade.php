@extends('layouts.app')

@section('content')
<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header">
                    <div class="d-flex justify-content-between align-items-center">
                        <h4 class="mb-0">Listado de Clientes</h4>
                        <a href="#" class="btn btn-primary">
                            <i class="fas fa-plus me-1"></i> Nuevo Cliente
                        </a>
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
                                    <th>Teléfono</th>
                                    <th>Dirección</th>
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
@endsection

@push('scripts')
<script>
    $(document).ready(function() {
        $('#clientes-table').DataTable({
            processing: true,
            serverSide: true,
            ajax: "{{ route('clientes.data') }}", // Ruta que retornará los datos en formato JSON
            columns: [
                {data: 'id', name: 'id'},
                {data: 'nombre', name: 'nombre'},
                {data: 'email', name: 'email'},
                {data: 'telefono', name: 'telefono'},
                {data: 'direccion', name: 'direccion'},
                {data: 'created_at', name: 'created_at'},
                {
                    data: 'action', 
                    name: 'action', 
                    orderable: false, 
                    searchable: false,
                    render: function(data, type, row) {
                        return `
                        <div class="btn-group" role="group">
                            <a href="/clientes/${row.id}" class="btn btn-sm btn-info">
                                <i class="fas fa-eye"></i>
                            </a>
                            <a href="/clientes/${row.id}/edit" class="btn btn-sm btn-warning">
                                <i class="fas fa-edit"></i>
                            </a>
                            <button type="button" class="btn btn-sm btn-danger delete-cliente" data-id="${row.id}">
                                <i class="fas fa-trash"></i>
                            </button>
                        </div>
                        `;
                    }
                },
            ],
            language: {
                url: '//cdn.datatables.net/plug-ins/1.13.6/i18n/es-ES.json'
            }
        });

        // Manejo de eliminación con SweetAlert2
        $(document).on('click', '.delete-cliente', function() {
            const id = $(this).data('id');
            
            Swal.fire({
                title: '¿Estás seguro?',
                text: "¡No podrás revertir esta acción!",
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#3085d6',
                cancelButtonColor: '#d33',
                confirmButtonText: 'Sí, eliminar',
                cancelButtonText: 'Cancelar'
            }).then((result) => {
                if (result.isConfirmed) {
                    // Enviar solicitud de eliminación mediante AJAX
                    $.ajax({
                        url: `/clientes/${id}`,
                        type: 'DELETE',
                        data: {
                            "_token": "{{ csrf_token() }}"
                        },
                        success: function(response) {
                            Swal.fire(
                                '¡Eliminado!',
                                'El cliente ha sido eliminado correctamente.',
                                'success'
                            );
                            // Recargar la tabla
                            $('#clientes-table').DataTable().ajax.reload();
                        },
                        error: function(xhr) {
                            Swal.fire(
                                'Error',
                                'Ocurrió un error al eliminar el cliente.',
                                'error'
                            );
                        }
                    });
                }
            });
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
