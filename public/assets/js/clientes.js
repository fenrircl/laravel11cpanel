    $(document).ready(function() {
        console.log('Clientes DataTable initialized');
        $('#clientes-table').DataTable({
            data: CLIENTES, // Usar los datos de la variable CLIENTES
            processing: true, // Puede mantenerse para mostrar un indicador de carga si los datos son muchos
            serverSide: false, // Cambiar a false ya que los datos son locales
            columns: [
                {data: 'id', name: 'id'},
                {data: 'name', name: 'nombre'},
                {data: 'email', name: 'email'},
                {data: 'phone', name: 'telefono'},
                {data: 'address', name: 'direccion'},
                {
                    data: 'created_at', 
                    name: 'created_at',
                    render: function(data, type, row) {
                        // Formatear la fecha si es necesario, ejemplo:
                        if (data) {
                            let date = new Date(data);
                            return date.toLocaleDateString('es-ES', { year: 'numeric', month: '2-digit', day: '2-digit' }) + ' ' + date.toLocaleTimeString('es-ES');
                        }
                        return '';
                    }
                },
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
                url: 'assets/js/plugins/datatable/es-ES.json'
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