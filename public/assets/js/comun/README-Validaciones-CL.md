# Guía rápida: Helper de formato y validación Chile (CLP, fecha, email, RUT, teléfono)

Este helper agrega formato y validaciones reutilizables para inputs en toda la app.

Incluye:
- CLFormat (utilidades): CLP, fechas, email, RUT, teléfono.
- CLInputFormatter (UI): enlaza inputs con data-attributes, muestra hints, normaliza valores antes de enviar formularios y bloquea submit si hay campos requeridos inválidos.

Se inicializa automáticamente al cargar la página y al abrir modales (no requiere código adicional).

---

## 1) Uso en HTML (data-format)

Agrega el atributo data-format al input. Opcionalmente usa data-formatted-target para indicar dónde mostrar la pista formateada.

### CLP (Pesos chilenos)

#### Opción 1: Con hint externo (comportamiento original)
```html
<label>Monto</label>
<input type="number" id="amount" name="amount" step="1" min="0" required data-format="clp">
<!-- Se crea automáticamente un <small> con la pista formateada ($ 1.234.567) -->
```

#### Opción 2: Formato dentro del input (RECOMENDADO)
```html
<label>Monto</label>
<input type="text" id="amount" name="amount" inputmode="numeric" required data-format="clp" placeholder="$ 0">
```

Comportamiento para **data-format="clp"**:
- **En focus/edición**: el valor vuelve a dígitos puros (para editar cómodamente).
- **En blur/carga**: el valor visible se muestra formateado ($ 1.234.567) directamente en el input.
- **En submit**: se normaliza automáticamente a entero en pesos (sin $ ni puntos).
- **NO se crea ningún hint externo** (span pequeño).

Recomendaciones:
- Usa `type="text"` + `inputmode="numeric"` para poder mostrar el formato visual.
- Si usas `type="number"`, los navegadores no permiten separadores, solo verás dígitos.

#### Opción 3: Modo inline explícito (equivalente a opción 2)
```html
<label>Monto</label>
<input type="text" id="amount" name="amount" inputmode="numeric" required data-format="clp-inline" placeholder="$ 0">
```
Comportamiento idéntico a `data-format="clp"` con `type="text"`.

### Fecha chilena (DD-MM-AAAA)
```html
<!-- Opción A: input text (acepta DD-MM-AAAA, normaliza a ISO al enviar) -->
<input type="text" name="date" required data-format="date-cl" placeholder="dd-mm-aaaa">

<!-- Opción B: input date (mantiene ISO, muestra pista DD-MM-AAAA) -->
<input type="date" name="expiry" data-format="date-cl">
```
Comportamiento:
- Para type=text: valida y formatea DD-MM-AAAA; al enviar convierte a yyyy-mm-dd.
- Para type=date: value sigue en ISO; la pista muestra dd-mm-aaaa.

### Email
```html
<!-- Email general -->
<input type="email" name="email" required data-format="email">

<!-- Email con TLD obligatorio (.cl por defecto, configurable) -->
<input type="email" name="email_cl" required data-format="email-cl" data-email-tld="cl">
```
Comportamiento:
- Normaliza (minúsculas, sin espacios/acentos).
- Valida; si es requerido e inválido, marca is-invalid y bloquea submit.
- Para email-cl, obliga a terminar en .cl (o el TLD que definas en data-email-tld).

### RUT
```html
<input type="text" name="rut" required data-format="rut" placeholder="12.345.678-5">
```
Comportamiento:
- Limpia a dígitos y K, valida dígito verificador.
- Hint muestra RUT con puntos y guion. En submit se envía limpio (sin puntos, con DV).
- Si es requerido e inválido, marca is-invalid y bloquea submit.

### Teléfono chileno (móvil)
```html
<input type="text" name="phone" required data-format="phone-cl" inputmode="numeric" placeholder="9 dígitos">
```
Comportamiento:
- Mantiene solo dígitos. Valida 9 dígitos. Hint muestra +56 X XXXX XXXX.
- Si es requerido e inválido, marca is-invalid y bloquea submit.

---

## 2) Uso en JS (opcional)
El helper se auto-inicializa. Si insertas inputs dinámicamente:

```js
// Enlazar un input recién insertado
theInput && window.CLInputFormatter && window.CLInputFormatter.bind(theInput);

// O refrescar las pistas de todos los inputs marcados
awindow.CLInputFormatter && window.CLInputFormatter.refreshAllHints();
```

APIs disponibles (utilidades):
```js
// CLP
CLFormat.formatCurrencyCL(7540);           // "$\u00A07.540"
CLFormat.parseCLP('7.540');                 // 7540

// Fechas
CLFormat.formatDateCL('2025-08-07');       // "07-08-2025"
CLFormat.parseDateCLToISO('07-08-2025');   // "2025-08-07"

// Email
CLFormat.normalizeEmail('Álvaro@ACME.cl'); // "alvaro@acme.cl"
CLFormat.isValidEmail('a@b.cl');           // true
CLFormat.isValidEmailTLD('a@b.cl','cl');   // true

// RUT
CLFormat.cleanRut('12.345.678-5');         // "123456785"
CLFormat.isValidRut('123456785');          // true/false
CLFormat.formatRut('123456785');           // "12.345.678-5"

// Teléfono
CLFormat.cleanPhoneCL('+56 9 1234 5678');  // "912345678"
CLFormat.isValidPhoneCL('912345678');      // true
CLFormat.formatPhoneCL('912345678');       // "+56 9 1234 5678"
```

---

## 3) Recomendaciones
- Para montos con hint: type="number", step="1", min="0" y data-format="clp".
- Para montos inline: type="text", inputmode="numeric" y data-format="clp-inline".
- Para fecha dd-mm-aaaa en texto: usa inputmode="numeric" y placeholder.
- Para RUT/teléfono en texto: usa inputmode="numeric".
- Si un campo no es requerido, no bloquea el submit, pero igual muestra el estado visual.
- Puedes dirigir la pista a un elemento específico con data-formatted-target.

---

## 4) Ejemplo completo de formulario
```html
<form id="demoForm">
  <input type="text" name="rut" required data-format="rut" placeholder="12.345.678-5">
  <input type="email" name="email" required data-format="email-cl" data-email-tld="cl">
  <input type="text" name="phone" required data-format="phone-cl" inputmode="numeric">
  <input type="text" name="fecha" required data-format="date-cl" placeholder="dd-mm-aaaa">
  <input type="number" name="amount" required data-format="clp" step="1" min="0">
  <button type="submit">Enviar</button>
</form>
```

Eso es todo: con data-format en los inputs, el helper hace el resto.
