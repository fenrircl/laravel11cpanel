$(document).ready(function() {
    console.log('Provider DataTable initialized');
    
    // Configuración de columnas para la tabla de proveedores
    const columns = [
        {data: 'id', name: 'id', width: '80px'},
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
                return generateActionButtons(row.id, 'proveedores');
            }
        }
    ];
    
    // Configuración específica para obtener datos de la API
    const tableOptions = {
        ajax: {
            url: buildApiUrl('proveedores/data'),
            type: 'GET',
            dataSrc: 'data',
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

    // Manejo de eliminación usando la función reutilizable
    $(document).on('click', '.delete-proveedor', function() {
        const id = $(this).data('id');
        handleDelete('proveedor', id, buildApiUrl(`proveedores/${id}`), function() {
            // Recargar la tabla después de eliminar
            $('#proveedores-table').DataTable().ajax.reload();
        });
    });
});