$(document).ready(function() {
    console.log('Provider DataTable initialized');
    
    // Configuración de columnas para la tabla de proveedores
    const columns = [
        {data: 'rut', name: 'rut',width: '80px'},
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
                // Reutilizar el generador de botones estándar
                return generateActionButtons(row.id, 'proveedores');
            }
        }
    ];
    
    // Configuración específica para obtener datos de la API
    const tableOptions = {
        ajax: {
            url: buildApiUrl('proveedores/data'),
            type: 'GET',
            dataSrc: function(json) {
                // Almacenar los datos en el sistema global
                EntityDataManager.setEntityData('proveedores', json.data);
                return json.data;
            },
            error: function(xhr, error, code) {
                console.error('Error loading proveedores data:', error);
                console.log('Response:', xhr.responseText);
                Swal.fire({
                    icon: 'error',
                    title: 'Error',
                    text: 'No se pudieron cargar los datos de proveedores. Verifique la conexión.'
                });
            }
        },
        // Ordenar por fecha de creación descendente
        order: [[3, 'desc']]
    };
    
    // Inicializar DataTable usando la función reutilizable
    initDataTable('proveedores-table', null, columns, tableOptions);

    // Los eventos de eliminación ahora se manejan automáticamente 
    // a través de initActionButtonEvents() en main.js
    
    console.log('Proveedores module loaded with new action buttons system');
    
    // Funciones específicas de proveedores que usan el sistema de almacenamiento
    
    // Ver detalles de proveedor -> redirigir a la vista
    window.verProveedor = function(id) {
        window.location.href = buildApiUrl('proveedores/' + id);
    };
    
    // Editar proveedor
    window.editarProveedor = function(id) {
        if (typeof openEditModalProveedor === 'function') {
            openEditModalProveedor(id);
            return;
        }
        const proveedor = EntityHelpers.getProveedor(id);
        if (proveedor) {
            // Poblar formulario y abrir modal (fallback)
            $('#proveedorId').val(proveedor.id);
            $('#rut_prov').val(proveedor.rut || '');
            $('#name_prov').val(proveedor.name || '');
            $('#email_prov').val(proveedor.email || '');
            $('#phone_prov').val(proveedor.phone || '');
            $('#address_prov').val(proveedor.address || '');
            $('#proveedorModalLabel').text('Editar Proveedor');
            new bootstrap.Modal(document.getElementById('proveedorModal')).show();
        } else {
            // Intentar cargar por AJAX si no está en cache
            $.get(buildApiUrl('proveedores/' + id))
                .done(function(res){
                    var p = res && res.proveedor ? res.proveedor : res;
                    $('#proveedorId').val(p.id);
                    $('#rut_prov').val(p.rut || '');
                    $('#name_prov').val(p.name || '');
                    $('#email_prov').val(p.email || '');
                    $('#phone_prov').val(p.phone || '');
                    $('#address_prov').val(p.address || '');
                    $('#proveedorModalLabel').text('Editar Proveedor');
                    new bootstrap.Modal(document.getElementById('proveedorModal')).show();
                })
                .fail(function(){
                    Swal.fire({ icon: 'error', title: 'Error', text: 'No se pudo cargar el proveedor.' });
                });
        }
    };
    
    // Eliminar proveedor
    window.eliminarProveedor = function(id) {
        const proveedor = EntityHelpers.getProveedor(id);
        if (proveedor) {
            Swal.fire({
                title: '¿Estás seguro?',
                text: `¿Deseas eliminar el proveedor "${proveedor.name}"?`,
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#d33',
                cancelButtonColor: '#3085d6',
                confirmButtonText: 'Sí, eliminar',
                cancelButtonText: 'Cancelar'
            }).then((result) => {
                if (result.isConfirmed) {
                    // Aquí harías la petición AJAX para eliminar
                    console.log('Eliminando proveedor:', proveedor);
                    // Después de eliminar exitosamente, actualizar el almacenamiento
                    // EntityDataManager.removeItem('proveedores', id);
                    
                    Swal.fire(
                        '¡Eliminado!',
                        `El proveedor "${proveedor.name}" ha sido eliminado.`,
                        'success'
                    );
                }
            });
        }
    };
}); // Fin de document ready