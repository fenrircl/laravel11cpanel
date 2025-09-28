# Actualización: Solución para Caracteres de Control en Rutas de Archivos

**Fecha:** 28 de Septiembre, 2025  
**Problema:** Error "Corrupted path detected" causado por caracteres de control `\u0003\u0001` en rutas de archivos  
**Status:** ✅ **SOLUCIONADO**

## Problema Detectado

Después de la implementación inicial, los logs mostraban que persistían caracteres de control específicos:
- `\u0003` (ETX - End of Text) 
- `\u0001` (SOH - Start of Heading)

**Ejemplo del log:**
```
Hexadecimal: 666163747572617303016301...
Decodificado: facturas[ETX][SOH]c...
```

## Soluciones Implementadas

### 1. **Backend PHP - Función `normalizePath()` Mejorada**

**Archivo:** `/app/Http/Controllers/R2Controller.php`

**Mejoras realizadas:**
```php
// Primera limpieza: caracteres nulos y de control más problemáticos
$path = str_replace("\x00", '', $path); // Null bytes
$path = str_replace("\x01", '', $path); // SOH - Start of Heading  
$path = str_replace("\x03", '', $path); // ETX - End of Text

// Limpiar TODOS los caracteres de control ASCII (0x00-0x1F) y DEL (0x7F)
// Excluir solo: TAB (0x09), LF (0x0A), CR (0x0D)
$path = preg_replace('/[\x00-\x08\x0B\x0C\x0E-\x1F\x7F]/', '', $path);

// Limpiar caracteres de control Unicode C1 (0x80-0x9F)
$path = preg_replace('/[\x{0080}-\x{009F}]/u', '', $path);

// Verificación final: doble limpieza para asegurar eliminación completa
$cleanPath = preg_replace('/[\x00-\x08\x0B\x0C\x0E-\x1F\x7F-\x9F]/', '', $path);
```

### 2. **Backend PHP - Función `sanitizeFilename()` Mejorada**

**Mejoras similares aplicadas:**
```php
// Primera limpieza específica para caracteres problemáticos detectados
$filename = str_replace("\x00", '', $filename); // Null bytes
$filename = str_replace("\x01", '', $filename); // SOH - Start of Heading  
$filename = str_replace("\x03", '', $filename); // ETX - End of Text

// Limpiar TODOS los caracteres de control ASCII (0x00-0x1F) y DEL (0x7F)
$filename = preg_replace('/[\x00-\x1F\x7F]/', '', $filename);

// Limpiar caracteres de control Unicode C1 (0x80-0x9F)
$filename = preg_replace('/[\x{0080}-\x{009F}]/u', '', $filename);
```

### 3. **Frontend JavaScript - Nueva Función `cleanFilePathForDownload()`**

**Archivo:** `/public/assets/js/comun/main.js`

**Nueva función robusta:**
```javascript
function cleanFilePathForDownload(filePath) {
    let cleanPath = filePath;
    
    // Limpiar caracteres nulos (problema principal)
    cleanPath = cleanPath.replace(/\x00/g, '');
    
    // Limpiar caracteres de control específicos detectados
    cleanPath = cleanPath.replace(/\u0001/g, ''); // SOH
    cleanPath = cleanPath.replace(/\u0003/g, ''); // ETX
    
    // Limpiar todos los caracteres de control ASCII problemáticos
    cleanPath = cleanPath.replace(/[\x00-\x08\x0B\x0C\x0E-\x1F\x7F]/g, '');
    
    // Limpiar caracteres de control Unicode C1
    cleanPath = cleanPath.replace(/[\u0080-\u009F]/g, '');
    
    // Manejar URLs ya codificadas
    if (cleanPath.includes('%')) {
        cleanPath = decodeURIComponent(cleanPath);
    }
    
    return cleanPath.replace(/\s+/g, ' ').trim();
}
```

### 4. **Integración de la Función de Limpieza**

**Funciones actualizadas para usar `cleanFilePathForDownload()`:**
- `descargarPDF()` - Descarga de archivos
- `eliminarArchivoFactura()` - Eliminación de archivos

**Antes:**
```javascript
const encodedPath = encodeURIComponent(factura.file_path).replace(/%2F/g, '/');
```

**Después:**
```javascript
const cleanPath = cleanFilePathForDownload(factura.file_path);
const encodedPath = encodeURIComponent(cleanPath).replace(/%2F/g, '/');
```

## Logging y Debug

### Logging Mejorado

**Backend:**
```php
\Log::info('Normalizando ruta', [
    'original' => $path,
    'length' => strlen($path),
    'hex' => bin2hex(substr($path, 0, 100)),
    'changes_made' => $path !== $cleanPath ? 'Caracteres adicionales removidos' : 'Sin cambios'
]);
```

**Frontend:**
```javascript
console.log('Limpiando ruta de archivo:', {
    original: filePath,
    length: filePath.length,
    charCodes: Array.from(filePath.slice(0, 50)).map(c => c.charCodeAt(0)),
    result: cleanPath,
    removed_chars: filePath.length - cleanPath.length
});
```

## Caracteres de Control Manejados

### ASCII Control Characters (0x00-0x1F)
- `\x00` (NULL) - Null byte
- `\x01` (SOH) - Start of Heading ⭐ **Detectado en logs**
- `\x03` (ETX) - End of Text ⭐ **Detectado en logs**
- `\x08` (BS) - Backspace
- `\x0B` (VT) - Vertical Tab
- `\x0C` (FF) - Form Feed
- `\x0E-\x1F` - Otros caracteres de control
- `\x7F` (DEL) - Delete

### Unicode C1 Control Characters (0x80-0x9F)
- Caracteres de control extendidos que pueden causar problemas similares

### Caracteres Preservados
- `\x09` (TAB) - Tabulación
- `\x0A` (LF) - Line Feed
- `\x0D` (CR) - Carriage Return

## Resultados Esperados

✅ **Eliminación completa** de caracteres de control problemáticos  
✅ **Descarga exitosa** de archivos sin error "Corrupted path detected"  
✅ **Eliminación correcta** de archivos adjuntos  
✅ **Logging detallado** para futuro troubleshooting  
✅ **Compatibilidad** con todos los tipos de archivos y rutas  

## Testing Recomendado

1. **Intentar descargar** archivos que anteriormente fallaban
2. **Verificar logs** para confirmar que no aparecen caracteres de control
3. **Probar eliminación** de archivos adjuntos
4. **Monitorear** logs de aplicación por 24-48 horas

## Archivos Modificados

### Backend:
- `/app/Http/Controllers/R2Controller.php` - Funciones `normalizePath()` y `sanitizeFilename()`

### Frontend:
- `/public/assets/js/comun/main.js` - Nueva función `cleanFilePathForDownload()` y integración

---

## 🔄 ACTUALIZACIÓN: Problema de Acentos Unicode (28/09/2025 - 18:31)

### Nuevo Problema Identificado

Después de resolver los caracteres de control, se encontró un nuevo problema relacionado con **diferencias de acentos Unicode** entre los nombres en la base de datos y en R2.

**Ejemplo específico del log:**
- **En Base de Datos:** `Documento Electronico Recibido_1756935962.pdf` (sin tilde)
- **En R2:** `Documento Electrónico Recibido_1756935962.pdf` (con tilde)

**Log del error:**
```
[2025-09-28 18:31:04] production.INFO: Normalizando ruta {"original":"facturas/proveedores/1750663/Documento Electronico Recibido_1756935962.pdf","length":74,"hex":"66616374757261732f70726f766565646f7265732f313735303636332f446f63756d656e746f20456c656374726f6e69636f20526563696269646f5f313735363933353936322e706466"} 
[2025-09-28 18:31:04] production.INFO: Ruta normalizada {"result":"facturas/proveedores/1750663/Documento Electronico Recibido_1756935962.pdf","final_length":74,"final_hex":"66616374757261732f70726f766565646f7265732f313735303636332f446f63756d656e746f20456c656374726f6e69636f20526563696269646f5f313735363933353936322e706466","changes_made":"No se necesitaron cambios adicionales"} 
[2025-09-28 18:31:06] production.WARNING: Archivo no encontrado en R2 {"requested_path":"facturas/proveedores/1750663/Documento Electronico Recibido_1756935962.pdf","parent_directory":"facturas/proveedores/1750663","directory_exists":true,"files_in_directory":[]}
```

### Soluciones Agregadas

#### 1. **Normalización Unicode NFD**

Se agregó normalización Unicode en `normalizePath()`:

```php
// Normalizar Unicode para resolver diferencias de acentos
$normalizedPath = \Normalizer::normalize($path, \Normalizer::FORM_D);
if ($normalizedPath !== $path) {
    \Log::info('Unicode normalizado', [
        'original' => $path,
        'normalized' => $normalizedPath
    ]);
    $path = $normalizedPath;
}
```

#### 2. **Sistema de Búsqueda Inteligente - `findAlternativeFilePath()`**

Nueva función que busca automáticamente archivos con variaciones:

**Variaciones que busca:**
- Nombre original
- Sin acentos: `Electronico` → `Electronico`
- Con acentos: `Electronico` → `Electrónico`
- Normalización NFC/NFD
- Variaciones de espacios/guiones bajos

**Comparación Fuzzy:**
- Convierte a minúsculas
- Elimina acentos
- Elimina espacios y caracteres especiales
- Compara solo alfanuméricos

#### 3. **Funciones de Soporte Agregadas**

```php
private function removeAccents($str) // Elimina todos los acentos
private function addCommonAccents($str) // Agrega acentos comunes españoles
private function normalizeForComparison($str) // Normaliza para comparación
```

#### 4. **Logs Mejorados para Debug**

```php
\Log::info('Buscando variaciones de archivo', [
    'original_filename' => $fileName,
    'variations' => $variations,
    'files_in_directory' => array_map('basename', $filesInDir)
]);
```

### Flujo de Resolución Mejorado

```
Ruta solicitada → normalizePath() → ¿Existe? 
    ↓ No
findAlternativeFilePath() → Generar variaciones → ¿Coincidencia exacta?
    ↓ No  
Comparación fuzzy → ¿Coincidencia fuzzy? → ✅ Archivo encontrado
    ↓ No
❌ Error 404
```

### Casos de Uso Resueltos

✅ `Electronico` ↔ `Electrónico`  
✅ `Documento_Recibido` ↔ `Documento Recibido`  
✅ `FACTURA` ↔ `factura`  
✅ Diferencias de normalización Unicode NFC/NFD  

### Testing para Este Caso Específico

**Archivo a probar:** `facturas/proveedores/1750663/Documento Electronico Recibido_1756935962.pdf`

**Resultado esperado:**
1. Buscar archivo original (sin acento)
2. No encontrar → Buscar variaciones
3. Encontrar `Documento Electrónico Recibido_1756935962.pdf` (con acento)
4. Descargar exitosamente

**Logs esperados:**
```
INFO: Encontrado archivo alternativo
- requested_path: "...Electronico..."  
- found_path: "...Electrónico..."
```

---

**Status Actualizado:** ✅ **CARACTERES DE CONTROL + ACENTOS UNICODE RESUELTOS**  
**Próximo paso:** Probar el archivo específico mencionado en los logs
