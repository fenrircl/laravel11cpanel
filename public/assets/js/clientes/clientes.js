$(document).ready(function() {
    console.log('Clientes DataTable initialized');
    
    // Configuración de columnas para la tabla de clientes
    const columns = [
        {data: 'rut', name: 'rut', width: '80px'},
        {data: 'name', name: 'name'},
        {data: 'email', name: 'email'},
        {
            data: 'created_at', 
            name: 'created_at',
            width: '150px',
            render: function(data, type, row) {
                return formatTableDate(data, true);
            }
        },
        {
            data: 'action', 
            name: 'action', 
            orderable: false, 
            searchable: false,
            width: '120px',
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
        order: [[0, 'desc']] // Ordenar por ID descendente (más recientes primero)
    };
    
    // Inicializar DataTable usando la función reutilizable
    initDataTable('clientes-table', null, columns, tableOptions);

    // Los eventos de eliminación ahora se manejan automáticamente 
    // a través de initActionButtonEvents() en main.js
    
    console.log('Clientes module loaded with new action buttons system');
    
    // Funciones específicas de clientes que usan el sistema de almacenamiento
    
    // Ver detalles de cliente
    window.verCliente = function(id) {
        const cliente = EntityHelpers.getCliente(id);
        if (cliente) {
            // Crear modal con los datos almacenados
            const modalContent = `
                <div class="row">
                    <div class="col-md-6">
                        <p><strong>ID:</strong> ${cliente.id}</p>
                        <p><strong>Nombre:</strong> ${cliente.name}</p>
                        <p><strong>Email:</strong> ${cliente.email}</p>
                    </div>
                    <div class="col-md-6">
                        <p><strong>Teléfono:</strong> ${cliente.phone || 'No especificado'}</p>
                        <p><strong>Dirección:</strong> ${cliente.address || 'No especificada'}</p>
                        <p><strong>Fecha de registro:</strong> ${formatTableDate(cliente.created_at, true)}</p>
                    </div>
                </div>
            `;
            
            Swal.fire({
                title: 'Detalles del Cliente',
                html: modalContent,
                width: '600px',
                showCloseButton: true,
                showConfirmButton: false
            });
        } else {
            console.error('Cliente no encontrado:', id);
            Swal.fire({
                icon: 'error',
                title: 'Error',
                text: 'No se encontraron los datos del cliente.'
            });
        }
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