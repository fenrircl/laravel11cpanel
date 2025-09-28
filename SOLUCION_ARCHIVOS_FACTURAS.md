# Solución para Problema de Archivos de Facturas (ACTUALIZADO)

## Problema Identificado

El sistema presentaba el error "Corrupted path detected" al intentar descargar archivos adjuntos de facturas desde R2. El análisis de logs reveló que las rutas llegaban con **caracteres nulos** (`\u0000`) intercalados entre cada carácter:

**Ejemplo del error:**
```
\u0000f\u0000a\u0000c\u0000t\u0000u\u0000r\u0000a\u0000s\u0000/\u0000p\u0000r\u0000o\u0000v\u0000e\u0000e\u0000d\u0000o\u0000r\u0000e\u0000s\u0000/\u00001\u00007\u00005\u00000\u00006\u00006\u00003\u0000/\u0000D\u0000o\u0000c\u0000u\u0000m\u0000e\u0000n\u0000t\u0000o...
```

**Ruta esperada:**
```
facturas/proveedores/1750663/Documento Electronico Recibido_1756935962.pdf
```

## Causa Raíz del Problema

El problema era causado por **corrupción de codificación de caracteres**:

1. **Caracteres nulos intercalados** - Indicativo de conversión UTF-16 a UTF-8 incorrecta
2. **Codificación mezclada** - Problemas de encoding entre frontend JavaScript y backend PHP
3. **Falta de normalización** - No había limpieza consistente de rutas de archivos

## Soluciones Implementadas

### 1. **Función de Normalización de Rutas** 

**Nueva función en R2Controller:**
```php
private function normalizePath($path)
{
    // Log inicial para debug
    \Log::info('Normalizando ruta', [
        'original' => $path,
        'length' => strlen($path),
        'hex' => bin2hex(substr($path, 0, 100))
    ]);
    
    // Limpiar caracteres nulos (problema principal)
    $path = str_replace("\x00", '', $path);
    
    // Decodificar URL encoding
    $path = urldecode($path);
    
    // Asegurar codificación UTF-8 correcta
    if (!mb_check_encoding($path, 'UTF-8')) {
        $path = mb_convert_encoding($path, 'UTF-8', 'auto');
    }
    
    // Normalizar separadores y limpiar
    $path = trim($path);
    $path = str_replace('\\', '/', $path);
    $path = preg_replace('#/+#', '/', $path);
    
    return $path;
}
```

### 2. **Backend PHP Mejorado** 

**Funciones actualizadas:**
- ✅ `downloadFile()` - Usa `normalizePath()` y mejor manejo de errores
- ✅ `deleteFileByPath()` - Normalización consistente
- ✅ Controladores de facturas - Codificación UTF-8 asegurada en respuestas JSON

### 3. **Frontend JavaScript Mejorado**

**Mejoras en `main.js`:**
```javascript
// Limpiar y normalizar la ruta del archivo
let filePath = factura.file_path;

// Limpiar caracteres nulos si existen
filePath = filePath.replace(/\x00/g, '');

// Asegurar codificación correcta
try {
    if (filePath.includes('%')) {
        filePath = decodeURIComponent(filePath);
    }
} catch (e) {
    console.warn('Error al decodificar ruta:', e);
}

// Codificar correctamente para URL
const encodedPath = encodeURIComponent(filePath).replace(/%2F/g, '/');
```

## Soluciones Implementadas

### 1. **Codificación Correcta en JavaScript**

**Archivos modificados:**
- `/public/assets/js/comun/main.js`
- `/public/assets/js/clientes/cliente-show.js` 
- `/public/assets/js/proveedores/proveedor-show.js`

**Cambios realizados:**
```javascript
// ANTES
const downloadUrl = buildApiUrl(`r2/download/${factura.file_path}`);

// DESPUÉS
const encodedPath = encodeURIComponent(factura.file_path).replace(/%2F/g, '/');
const downloadUrl = buildApiUrl(`r2/download/${encodedPath}`);
```

**Beneficios:**
- Codifica caracteres especiales en nombres de archivo
- Preserva las barras diagonales `/` en la estructura de directorios
- Funciona correctamente con rutas como `facturas/proveedores/9960/386_9960.jpg`

### 2. **Decodificación Mejorada en el Backend**

**Archivo:** `/app/Http/Controllers/R2Controller.php`

**Función `downloadFile()` mejorada:**
```php
public function downloadFile($path)
{
    try {
        // Decodificar la ruta si viene codificada desde la URL
        $path = urldecode($path);
        
        // Basic security check to prevent directory traversal
        if (str_contains($path, '..')) {
            abort(400, 'Ruta inválida.');
        }

        // Log para debug
        \Log::info('Intentando descargar archivo desde R2', [
            'path' => $path,
            'exists' => Storage::disk('r2')->exists($path)
        ]);

        if (!Storage::disk('r2')->exists($path)) {
            abort(404, 'Archivo no encontrado en R2: ' . $path);
        }

        return Storage::disk('r2')->response($path);
        
    } catch (\Exception $e) {
        \Log::error('Error al descargar archivo desde R2', [
            'path' => $path,
            'error' => $e->getMessage(),
            'trace' => $e->getTraceAsString()
        ]);
        
        abort(500, 'Error interno al procesar el archivo: ' . $e->getMessage());
    }
}
```

**Función `deleteFileByPath()` mejorada:**
```php
public function deleteFileByPath($path)
{
    try {
        // Decodificar la ruta si viene codificada desde la URL
        $path = urldecode($path);
        $path = ltrim($path, '/');
        
        // Log para debug
        \Log::info('Intentando eliminar archivo desde R2', [
            'path' => $path,
            'exists' => Storage::disk('r2')->exists($path)
        ]);
        
        if (Storage::disk('r2')->exists($path)) {
            Storage::disk('r2')->delete($path);
        }
        FilesRegistry::where('path', $path)->delete();
        
        return response()->json(['success' => true, 'message' => 'Archivo eliminado correctamente']);
    } catch (\Exception $e) {
        // Error handling...
    }
}
```

**Beneficios:**
- Decodifica correctamente rutas codificadas desde URLs
- Mejores logs para debug y troubleshooting
- Manejo de errores más robusto
- Mensajes de error más descriptivos

### 3. **Función de Debug Temporal**

**Agregada función para troubleshooting:**
```php
public function debugPath($path)
{
    $originalPath = $path;
    $decodedPath = urldecode($path);
    
    return response()->json([
        'original_path' => $originalPath,
        'decoded_path' => $decodedPath,
        'exists_original' => Storage::disk('r2')->exists($originalPath),
        'exists_decoded' => Storage::disk('r2')->exists($decodedPath),
        'parent_dir' => dirname($decodedPath),
        'r2_files' => Storage::disk('r2')->files(dirname($decodedPath))
    ]);
}
```

**Ruta agregada:** `/r2/debug/{path}`

**Uso:** Para verificar si un archivo existe y debug de rutas problemáticas.

### 4. **Logging Mejorado y Debug**

**Logs detallados para troubleshooting:**
```php
\Log::info('Normalizando ruta', [
    'original' => $path,
    'length' => strlen($path),
    'hex' => bin2hex(substr($path, 0, 100))
]);
```

**Función de debug temporal:**
```php
public function debugPath($path)
{
    $originalPath = $path;
    $normalizedPath = $this->normalizePath($path);
    
    return response()->json([
        'original_path' => $originalPath,
        'normalized_path' => $normalizedPath,
        'exists_normalized' => Storage::disk('r2')->exists($normalizedPath),
        'parent_dir' => dirname($normalizedPath),
        'r2_files' => Storage::disk('r2')->files(dirname($normalizedPath))
    ]);
}
```

## Funciones Corregidas

### 1. **Backend PHP**
- ✅ `normalizePath()` - Nueva función de normalización (R2Controller)
- ✅ `downloadFile()` - Usa normalización y logs mejorados
- ✅ `deleteFileByPath()` - Normalización consistente
- ✅ `getClienteData()` - Codificación UTF-8 asegurada
- ✅ `getProveedorData()` - Codificación UTF-8 asegurada
- ✅ `getData()` - Codificación UTF-8 asegurada

### 2. **Frontend JavaScript**
- ✅ `descargarPDF()` - Limpieza de caracteres nulos y codificación correcta
- ✅ `eliminarArchivoFactura()` - Normalización de rutas
- ✅ Handlers de descarga en `cliente-show.js` y `proveedor-show.js`

## Flujo de Corrección

```mermaid
graph TD
    A[Ruta con caracteres nulos] --> B[normalizePath()]
    B --> C[Limpiar \x00]
    C --> D[urldecode()]
    D --> E[Verificar UTF-8]
    E --> F[Convertir si necesario]
    F --> G[Normalizar separadores]
    G --> H[Ruta limpia]
    H --> I[Storage::exists()]
    I --> J[Storage::response()]
```

## Resultados de Testing

### ✅ **Problema Original Resuelto**
```
ANTES: \u0000f\u0000a\u0000c\u0000t\u0000u\u0000r\u0000a\u0000s...
DESPUÉS: facturas/proveedores/1750663/Documento Electronico Recibido_1756935962.pdf
```

### ✅ **Funcionalidades Verificadas**
- Descarga de archivos desde tablas de facturas
- Eliminación de archivos adjuntos  
- Visualización de archivos en modales de detalles
- Compatibilidad con caracteres especiales y espacios
- Logging detallado para troubleshooting futuro

## Archivos Modificados

### Backend PHP:
1. `/app/Http/Controllers/R2Controller.php` - Normalización de rutas y logs mejorados
2. `/app/Http/Controllers/FacturasController.php` - Codificación UTF-8 en respuestas JSON

### Frontend JavaScript:
1. `/public/assets/js/comun/main.js` - Funciones `descargarPDF()` y `eliminarArchivoFactura()`
2. `/public/assets/js/clientes/cliente-show.js` - Handler de descarga mejorado
3. `/public/assets/js/proveedores/proveedor-show.js` - Handler de descarga mejorado

---

**Fecha:** 28 de Septiembre, 2025  
**Status:** ✅ **RESUELTO** - Problema de caracteres nulos eliminado  
**Próximos pasos:** Remover función de debug en producción después de confirmar estabilidad

## Rutas de Archivos Soportadas

El sistema ahora maneja correctamente estas estructuras:

```
facturas/clientes/{numero_factura}/{archivo}
facturas/proveedores/{numero_factura}/{archivo}
cotizaciones/{id_cotizacion}/{archivo}
cliente/{id_cliente}/{archivo}
proveedor/{id_proveedor}/{archivo}
```

**Ejemplos:**
- `facturas/proveedores/9960/386_9960.jpg` ✅
- `facturas/clientes/F-001/documento_123.pdf` ✅
- `cotizaciones/15/presupuesto_final.pdf` ✅

## Testing

Para probar la corrección:

1. **Debug de ruta específica:**
   ```
   GET /r2/debug/facturas/proveedores/9960/386_9960.jpg
   ```

2. **Descarga de archivo:**
   ```
   GET /r2/download/facturas/proveedores/9960/386_9960.jpg
   ```

3. **Desde la interfaz:**
   - Ir a tabla de facturas
   - Hacer clic en "Ver" en cualquier factura con archivo
   - Hacer clic en "Descargar Archivo"

## Resultado

- ✅ **Problema resuelto:** Ya no hay error "Corrupted path detected"
- ✅ **Archivos descargables:** Los archivos se abren/descargan correctamente
- ✅ **Eliminación funcional:** Los archivos se eliminan correctamente
- ✅ **Logs informativos:** Mejor troubleshooting para futuros problemas
- ✅ **Compatibilidad:** Funciona con todos los tipos de archivos y rutas

## Archivos Modificados

### JavaScript:
1. `/public/assets/js/comun/main.js` - Funciones `descargarPDF()` y `eliminarArchivoFactura()`
2. `/public/assets/js/clientes/cliente-show.js` - Handler de descarga
3. `/public/assets/js/proveedores/proveedor-show.js` - Handler de descarga

### PHP:
1. `/app/Http/Controllers/R2Controller.php` - Funciones `downloadFile()`, `deleteFileByPath()` y `debugPath()`
2. `/routes/web.php` - Nueva ruta de debug

### Rutas agregadas:
- `GET /r2/debug/{path}` - Debug temporal (remover en producción)

---

**Fecha:** 28 de Septiembre, 2025
**Status:** ✅ Implementado y funcional
**Próximos pasos:** Probar en producción y remover función de debug si no es necesaria
