$(document).ready(function() {
    console.log('Provider DataTable initialized');
    
    // Configuración de columnas para la tabla de proveedores
    const columns = [
        {data: 'id', name: 'id'},
        {data: 'name', name: 'name'},
        {data: 'email', name: 'email'},
        {data: 'phone', name: 'phone'},
        {data: 'address', name: 'address'},
        {
            data: 'created_at', 
            name: 'created_at',
            render: function(data, type, row) {
                return formatTableDate(data, true);
            }
        },
        {
            data: 'action', 
            name: 'action', 
            orderable: false, 
            searchable: false,
            render: function(data, type, row) {
                return generateActionButtons(row.id, 'proveedores');
            }
        }
    ];
    
    // Inicializar DataTable usando la función reutilizable
    initDataTable('proveedores-table', PROVEEDORES, columns);

    // Manejo de eliminación usando la función reutilizable
    $(document).on('click', '.delete-proveedor', function() {
        const id = $(this).data('id');
        handleDelete('proveedor', id, `/proveedores/${id}`);
    });
});