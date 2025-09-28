$(document).ready(function() {
    console.log('Clientes DataTable initialized');
    
    // Configuración de columnas para la tabla de clientes
    const columns = [
        {data: 'rut', name: 'rut', width: '80px', responsivePriority: 3},
        {
            data: 'name', 
            name: 'name',
            width: '200px',  // Ancho fijo para el nombre
            responsivePriority: 1  // Máxima prioridad - nunca se oculta
        },
        {data: 'email', name: 'email', responsivePriority: 4},
        {data: 'phone', name: 'phone', width: '120px', responsivePriority: 5},
        {
            data: null, 
            name: 'action', 
            orderable: false, 
            searchable: false,
            width: '120px',
            responsivePriority: 2,  // Segunda prioridad - nunca se oculta
            render: function(data, type, row) {
                // Usar la nueva función genérica con configuración por defecto
                return generateActionButtons(row.id, 'clientes');
            }
        }
    ];
    
    // Configuración específica para obtener datos de la API
    const tableOptions = {
        ajax: {
            url: buildApiUrl('clientes/data'),
            type: 'GET',
            dataSrc: function(json) {
                // Almacenar los datos en el sistema global
                EntityDataManager.setEntityData('clientes', json.data);
                return json.data;
            },
            error: function(xhr, error, code) {
                console.error('Error loading clientes data:', error);
                console.log('Response:', xhr.responseText);
                Swal.fire({
                    icon: 'error',
                    title: 'Error',
                    text: 'No se pudieron cargar los datos de clientes. Verifique la conexión.'
                });
            }
        },
        order: [[1, 'asc']] // Ordenar por nombre ascendente
    };
    
    // Inicializar DataTable usando la función reutilizable
    initDataTable('clientes-table', null, columns, tableOptions);

    // Los eventos de eliminación ahora se manejan automáticamente 
    // a través de initActionButtonEvents() en main.js
    
    console.log('Clientes module loaded with new action buttons system');
    
    // Funciones específicas de clientes que usan el sistema de almacenamiento
    
    // Ver detalles de cliente => redirigir a la vista clientes/{id}
    window.verCliente = function(id) {
        window.location.href = buildApiUrl('clientes/' + id);
    };
    
    // Editar cliente
    window.editarCliente = function(id) {
        // Delegar al flujo real definido en la vista (abre modal y precarga datos)
        if (typeof openEditModal === 'function') {
            openEditModal(id);
        } else {
            // Fallback: cargar por AJAX similar a openEditModal
            $.get(buildApiUrl('clientes/' + id))
                .done(function(res){
                    var c = res && res.cliente ? res.cliente : res;
                    $('#clienteId').val(c.id);
                    $('#rut').val(c.rut || '');
                    $('#name').val(c.name || '');
                    $('#email').val(c.email || '');
                    $('#phone').val(c.phone || '');
                    $('#address').val(c.address || '');
                    $('#clienteModalLabel').text('Editar Cliente');
                    var m = new bootstrap.Modal(document.getElementById('clienteModal'));
                    m.show();
                })
                .fail(function(){
                    Swal.fire({ icon: 'error', title: 'Error', text: 'No se pudo cargar el cliente.' });
                });
        }
    };
    
    // Eliminar cliente
    window.eliminarCliente = function(id) {
        const cliente = EntityHelpers.getCliente(id);
        if (cliente) {
            Swal.fire({
                title: '¿Estás seguro?',
                text: `¿Deseas eliminar el cliente "${cliente.name}"?`,
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#d33',
                cancelButtonColor: '#3085d6',
                confirmButtonText: 'Sí, eliminar',
                cancelButtonText: 'Cancelar'
            }).then((result) => {
                if (result.isConfirmed) {
                    // Aquí harías la petición AJAX para eliminar
                    console.log('Eliminando cliente:', cliente);
                    // Después de eliminar exitosamente, actualizar el almacenamiento
                    // EntityDataManager.removeItem('clientes', id);
                    
                    Swal.fire(
                        '¡Eliminado!',
                        `El cliente "${cliente.name}" ha sido eliminado.`,
                        'success'
                    );
                }
            });
        }
    };
}); // Fin de document ready