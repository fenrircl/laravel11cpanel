$(document).ready(function() {
    console.log('Provider DataTable initialized');
    
    // Configuración de columnas para la tabla de proveedores
    const columns = [
        // {data: 'id', name: 'id', width: '80px'},
        {data: 'rut', name: 'rut'},

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
                // Botones personalizados para editar factura
                return `
                    <button class="btn btn-sm btn-action btn-view" title="Ver" onclick="verProveedor(${row.id})"><i class="fas fa-eye"></i></button>
                    <button class="btn btn-sm btn-action btn-edit" title="Editar" onclick="openEditFacturaModal('proveedor', ${row.id})"><i class="fas fa-edit"></i></button>
                    <button class="btn btn-sm btn-action btn-delete" title="Eliminar" data-id="${row.id}" data-entity="proveedor"><i class="fas fa-trash"></i></button>
                `;
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
        order: [[0, 'desc']] // Ordenar por ID descendente (más recientes primero)
    };
    
    // Inicializar DataTable usando la función reutilizable
    initDataTable('proveedores-table', null, columns, tableOptions);

    // Los eventos de eliminación ahora se manejan automáticamente 
    // a través de initActionButtonEvents() en main.js
    
    console.log('Proveedores module loaded with new action buttons system');
    
    // Funciones específicas de proveedores que usan el sistema de almacenamiento
    
    // Ver detalles de proveedor
    window.verProveedor = function(id) {
        const proveedor = EntityHelpers.getProveedor(id);
        if (proveedor) {
            // Crear modal con los datos almacenados
            const modalContent = `
                <div class="row">
                    <div class="col-md-6">
                        <p><strong>ID:</strong> ${proveedor.id}</p>
                        <p><strong>Nombre:</strong> ${proveedor.name}</p>
                        <p><strong>Email:</strong> ${proveedor.email}</p>
                    </div>
                    <div class="col-md-6">
                        <p><strong>Teléfono:</strong> ${proveedor.phone || 'No especificado'}</p>
                        <p><strong>Dirección:</strong> ${proveedor.address || 'No especificada'}</p>
                        <p><strong>Fecha de registro:</strong> ${formatTableDate(proveedor.created_at, true)}</p>
                    </div>
                </div>
            `;
            
            Swal.fire({
                title: 'Detalles del Proveedor',
                html: modalContent,
                width: '600px',
                showCloseButton: true,
                showConfirmButton: false
            });
        } else {
            console.error('Proveedor no encontrado:', id);
            Swal.fire({
                icon: 'error',
                title: 'Error',
                text: 'No se encontraron los datos del proveedor.'
            });
        }
    };
    
    // Editar proveedor
    window.editarProveedor = function(id) {
        const proveedor = EntityHelpers.getProveedor(id);
        if (proveedor) {
            // Aquí podrías usar los datos almacenados para pre-llenar un formulario
            console.log('Editando proveedor:', proveedor);
            Swal.fire({
                icon: 'info',
                title: 'Función en desarrollo',
                text: `Editando proveedor: ${proveedor.name}`
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