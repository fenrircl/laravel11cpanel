# Implementación de Logs de Auditoría

## Resumen
Se ha implementado un sistema completo de logs de auditoría que muestra las últimas 10 actividades realizadas en cada módulo del sistema. Los logs muestran información comprensible para el usuario final, utilizando RUTs para clientes/proveedores y números de factura en lugar de IDs internos.

## Archivos Implementados

### 1. Controlador de Auditoría
- **Archivo**: `/app/Http/Controllers/AuditLogController.php`
- **Funcionalidades**:
  - `getModuleLogs()`: Obtiene logs para cualquier módulo
  - `getClienteLogs()`: Logs específicos de clientes y sus facturas
  - `getProveedorLogs()`: Logs específicos de proveedores y sus facturas  
  - `getFacturasClientesLogs()`: Logs de facturas de clientes
  - `getFacturasProveedoresLogs()`: Logs de facturas de proveedores
  - `getFacturasGeneralLogs()`: Logs generales de facturas
  - `enrichLogsWithEntityInfo()`: Enriquece logs con RUTs y números de factura
  - `apiLogs()`: Endpoint para obtener logs vía AJAX

### 2. Componente de Vista
- **Archivo**: `/resources/views/components/audit-log-table.blade.php`
- **Características**:
  - Tabla responsive con últimas 10 actividades
  - Acciones traducidas al español (Creado, Editado, Eliminado, etc.)
  - Corrección automática de caracteres mal codificados
  - Tooltips con detalles de cambios realizados
  - Iconos diferenciados por tipo de entidad
  - Colores distintivos por tipo de acción

### 3. Integración en Controladores
Se agregaron logs de auditoría en los siguientes controladores:

#### ClientesController
```php
$auditController = new AuditLogController();
$data['auditLogs'] = $auditController->getClienteLogs();
```

#### ProveedoresController
```php
$auditController = new AuditLogController();
$data['auditLogs'] = $auditController->getProveedorLogs();
```

#### FacturasController
- **index()**: `getFacturasGeneralLogs()` - Todas las facturas
- **clienteIndex()**: `getFacturasClientesLogs()` - Solo facturas de clientes
- **proveedorIndex()**: `getFacturasProveedoresLogs()` - Solo facturas de proveedores

### 4. Integración en Vistas
Se agregó el componente en todas las vistas principales:

#### Clientes (`/resources/views/clientes/index.blade.php`)
```blade
<x-audit-log-table 
    :logs="$auditLogs ?? collect()" 
    title="Actividad reciente en clientes"
/>
```

#### Proveedores (`/resources/views/proveedores/index.blade.php`)
```blade
<x-audit-log-table 
    :logs="$auditLogs ?? collect()" 
    title="Actividad reciente en proveedores"
/>
```

#### Facturas - Vista General (`/resources/views/facturas/index.blade.php`)
```blade
<x-audit-log-table 
    :logs="$auditLogs ?? collect()" 
    title="Actividad reciente en facturas"
/>
```

#### Facturas de Clientes (`/resources/views/facturas/clientes/index.blade.php`)
```blade
<x-audit-log-table 
    :logs="$auditLogs ?? collect()" 
    title="Actividad reciente en facturas de clientes"
/>
```

#### Facturas de Proveedores (`/resources/views/facturas/proveedores/index.blade.php`)
```blade
<x-audit-log-table 
    :logs="$auditLogs ?? collect()" 
    title="Actividad reciente en facturas de proveedores"
/>
```

## Características de la Implementación

### 1. Información Comprensible para el Usuario
- **Clientes**: Muestra RUT en lugar del ID interno
- **Proveedores**: Muestra RUT en lugar del ID interno  
- **Facturas**: Muestra número de factura en lugar del ID interno
- **Archivos**: Muestra "Archivo" para uploads/downloads

### 2. Acciones Traducidas
```php
$actionText = match($log->action) {
    'create' => 'Creado',
    'update' => 'Editado', 
    'delete' => 'Eliminado',
    'restore' => 'Restaurado',
    'upload' => 'Subido',
    'download' => 'Descargado',
    'login' => 'Ingreso',
    'logout' => 'Salida',
    default => ucfirst($log->action)
};
```

### 3. Corrección de Codificación
El componente corrige automáticamente caracteres mal codificados:
```php
$replacements = [
    'Ã±' => 'ñ',
    'ÃŃ' => 'Ñ', 
    'Ã³' => 'ó',
    'Ã©' => 'é',
    'Ã­' => 'í',
    'Ãº' => 'ú',
    'Ã¡' => 'á',
    'CreÃ³' => 'Creó',
    'EditÃ³' => 'Editó',
    'EliminÃ³' => 'Eliminó',
    'COMPAÃ\'IA' => 'COMPAÑÍA'
];
```

### 4. Detalles de Cambios
Los tooltips muestran información detallada de los cambios:
- Campos modificados con traducciones al español
- Valores anteriores y nuevos (de → hacia)
- Formato HTML con estilos Bootstrap

### 5. Colores y Badges
```php
$badgeClass = match($log->action) {
    'create' => 'bg-success',      // Verde
    'update' => 'bg-warning text-dark',  // Amarillo
    'delete' => 'bg-danger',       // Rojo
    'restore' => 'bg-info',        // Azul claro
    'upload' => 'bg-primary',      // Azul
    'download' => 'bg-secondary',  // Gris
    default => 'bg-secondary'
};
```

## Rutas Implementadas

### API para AJAX
```php
Route::get('/api/audit-logs', [\App\Http\Controllers\AuditLogController::class, 'apiLogs'])
    ->name('api.audit-logs');
```

## Filtros de Consulta

### Por Módulo
- **clientes**: Logs de clientes y sus facturas relacionadas
- **proveedores**: Logs de proveedores y sus facturas relacionadas
- **facturas**: Logs de facturas (general, clientes o proveedores)
- **archivos**: Logs de subida/descarga de archivos

### Enriquecimiento de Datos
El sistema realiza consultas eficientes para obtener información adicional:
1. Recopila IDs únicos de cada módulo
2. Ejecuta consultas batch para obtener datos de entidades
3. Enriquece cada log con información comprensible

## Beneficios de la Implementación

1. **Transparencia**: Los usuarios pueden ver qué cambios se han realizado recientemente
2. **Trazabilidad**: Cada acción queda registrada con usuario, fecha y detalles
3. **Usabilidad**: La información se presenta de forma comprensible (RUTs, números de factura)
4. **Performance**: Consultas optimizadas con límite de 10 registros por vista
5. **Responsive**: Las tablas se adaptan a diferentes tamaños de pantalla
6. **Accesibilidad**: Tooltips informativos y colores distintivos

## Casos de Uso

### Vista de Clientes
Muestra los últimos cambios en:
- Creación/edición/eliminación de clientes
- Facturas creadas para clientes
- Archivos subidos relacionados con clientes

### Vista de Proveedores  
Muestra los últimos cambios en:
- Creación/edición/eliminación de proveedores
- Facturas creadas para proveedores
- Archivos subidos relacionados con proveedores

### Vista de Facturas
Muestra los últimos cambios en:
- Creación/edición/eliminación de facturas
- Cambios de estado de facturas
- Subida/descarga de archivos de facturas

## Mantenimiento

### Limpieza de Logs Antiguos
Se recomienda implementar un comando para limpiar logs antiguos:
```bash
php artisan audit:cleanup --days=30
```

### Optimización de Consultas
Las consultas están optimizadas con:
- Índices en `created_at`, `module`, `entity_id`
- Límite de 10 registros por vista
- Carga eager de relaciones (`with('user')`)
- Consultas batch para enriquecimiento de datos

Esta implementación proporciona un sistema de auditoría completo, comprensible y eficiente que mejora la transparencia y trazabilidad del sistema.
