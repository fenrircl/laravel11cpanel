# 🐛 DEBUG - BADGES DE ESTADO EN BUSCADOR

## 🔍 **PROBLEMA IDENTIFICADO**
Los badges de estado (Pagada/Pendiente/Vencida) no se muestran en los resultados del buscador de facturas.

## 📋 **ACCIONES REALIZADAS**

### 1. **Verificación de Código**
- ✅ Método `getItemMeta()` implementado correctamente
- ✅ Método `renderResultItem()` usa la propiedad `result.meta`  
- ✅ CSS para badges implementado correctamente
- ✅ Lógica de estados funcional

### 2. **Debug Agregado**
Se agregaron console.logs temporales en:
- `getItemMeta()` - Para ver qué datos llegan de las facturas
- `renderResultItem()` - Para verificar que el meta se está generando
- Logs específicos para verificar el campo `status`

### 3. **Archivos de Prueba Creados**
- **`debug-estados-facturas.html`** - Test aislado de la lógica
- **`test-buscador-completo.html`** - Simulación completa del buscador

## 🔎 **POSIBLES CAUSAS**

### **Causa Más Probable: Datos del Servidor**
El problema más probable es que las facturas que llegan del servidor **no tengan el campo `status`** o lo tengan con un nombre diferente.

**Campos que el código busca:**
- `item.status` (principal)
- `item.amount` o `item.total` (para el monto)
- `item.expiry` o `item.fecha_vencimiento` (para vencimiento)

### **Verificaciones Necesarias:**
1. **¿El API de búsqueda incluye el campo `status`?**
2. **¿El campo se llama `status` o tiene otro nombre?**
3. **¿Los datos llegan correctamente desde el servidor?**

## 🧪 **CÓMO VERIFICAR**

### **Opción 1: Console del Navegador**
1. Abre el buscador en el navegador
2. Busca facturas
3. Revisa la consola para ver los logs de debug
4. Verifica qué campos tienen las facturas

### **Opción 2: Archivos de Prueba**
1. Abre `/test-buscador-completo.html`
2. Hace clic en "Generar Resultados de Prueba"
3. Verifica que los badges se muestran correctamente
4. Revisa el console output

## 🔧 **SOLUCIONES PROBABLES**

### **Si falta el campo `status`:**
```javascript
// Modificar getItemMeta para buscar otros campos
const status = item.status || item.estado || item.paid || item.payment_status;
```

### **Si el campo tiene otro nombre:**
```javascript
// Agregar mapeo de campos
const status = item.status || item.estado || item.is_paid || item.payment_status;
```

### **Si el valor no es 0/1:**
```javascript
// Agregar conversión de valores
const isPagada = Number(item.status) === 1 || 
                 item.status === 'paid' || 
                 item.status === 'pagada' ||
                 item.status === true;
```

## 📝 **PRÓXIMOS PASOS**

1. **Ejecutar una búsqueda real** y revisar los console.logs
2. **Verificar la estructura** de los datos de facturas del servidor
3. **Ajustar el código** según los campos reales disponibles
4. **Remover los console.logs** una vez resuelto el problema

## 🎯 **ARCHIVOS MODIFICADOS**
- `/public/assets/js/comun/buscador-global.js` (debug agregado)
- `/debug-estados-facturas.html` (creado)  
- `/test-buscador-completo.html` (creado)

Una vez identificada la causa real, podremos hacer la corrección específica necesaria.
