# Reporte de actualizaciones y seguridad

**Proyecto:** AcerosEra — aplicación Laravel desplegada en cPanel  
**Fecha del reporte:** 8 de noviembre de 2026  
**Base del inventario:** `composer.json`, `composer.lock`, `package.json`, `package-lock.json` y cambios versionados en Git.

## Resumen

Se revisaron los 10 avisos de seguridad compartidos para Laravel y dependencias de JavaScript. Las versiones actualmente bloqueadas superan las versiones corregidas indicadas por los avisos. Nueve avisos tienen identificador CVE y uno está identificado por GitHub como GHSA sin CVE publicado en el aviso consultado.

Este reporte describe las versiones del repositorio. No constituye un escaneo ejecutado contra el servidor de cPanel ni confirma por sí solo qué versión está activa allí: eso se comprueba después del `pull` y de extraer `vendor.zip`.

## CVE y avisos corregidos

| Alerta | Severidad | Componente y riesgo | Versión corregida en el repositorio | Identificador y referencia |
|---|---|---|---|---|
| #13 | Crítica | Laravel `TokenGuard`: bypass de autenticación mediante credenciales API que no son texto. | `laravel/framework` **12.71.0**; corregido desde Laravel 12.68.0. | CVE-2026-114509 · [GHSA-f9x3-7vq9-cph6](https://github.com/advisories/GHSA-f9x3-7vq9-cph6) |
| #12 | Crítica | `shell-quote`: inyección de comandos en `quote()` con saltos de línea después de un token de comentario. | `shell-quote` **1.12.0**; corregido desde 1.11.0. | CVE-2026-102422 · [GHSA-pqg4-j6r4-53mv](https://github.com/advisories/GHSA-pqg4-j6r4-53mv) |
| #3 | Alta | Laravel: inyección CRLF en la regla de validación de correo predeterminada. | `laravel/framework` **12.71.0**; corregido desde 12.60.0. | CVE-2026-48019 · [GHSA-5vg9-5847-vvmq](https://github.com/advisories/GHSA-5vg9-5847-vvmq) |
| #5 | Alta | `shell-quote`: complejidad cuadrática en `parse()`, susceptible a denegación de servicio. | `shell-quote` **1.12.0**; corregido desde 1.9.0. | CVE-2026-13311 · [GHSA-395f-4hp3-45gv](https://github.com/advisories/GHSA-395f-4hp3-45gv) |
| #6 | Alta | PostCSS: traversal mediante `sourceMappingURL` que puede revelar archivos `.map`. | `postcss` **8.5.32**; corregido desde 8.5.18. | CVE-2026-73646 · [GHSA-r28c-9q8g-f849](https://github.com/advisories/GHSA-r28c-9q8g-f849) |
| #15 | Moderada | Vite: exposición de archivos fuera de `server.fs.allow` bajo ciertas rutas y condiciones del servidor de desarrollo. | `vite` **8.3.6**; corregido desde 8.3.3 para la rama 8.3.x. | GHSA-rq7h-c2jc-7f22 · [Aviso de Vite](https://github.com/vitejs/vite/security/advisories/GHSA-rq7h-c2jc-7f22). El aviso consultado no indica CVE. |
| #9 | Moderada | PostCSS: corrección incompleta del aviso anterior si `from` no está definido. | `postcss` **8.5.32**; corregido desde 8.5.23. | CVE-2026-69153 · [GHSA-fxqj-rqcc-2cmp](https://github.com/postcss/postcss/security/advisories/GHSA-fxqj-rqcc-2cmp) |
| #14 | Moderada | `launch-editor`: falta escapar `%` en argumentos de comandos en Windows. | `vite` **8.3.6**, posterior a la versión corregida 8.3.3; `launch-editor` ya no aparece como paquete independiente en `package-lock.json`. | CVE-2026-112943 · [GHSA-698v-4r3x-3vcv](https://github.com/vitejs/launch-editor/security/advisories/GHSA-698v-4r3x-3vcv) |
| #4 | Moderada | Laravel: ambigüedad de rutas en URL temporales firmadas. | `laravel/framework` **12.71.0**; corregido desde 12.61.1. | CVE-2026-48041 · [GHSA-crmm-hgp2-wgrp](https://github.com/advisories/GHSA-crmm-hgp2-wgrp) |
| #11 | Baja | Laravel: XSS en la información de la página de depuración cuando `APP_DEBUG=true`. | `laravel/framework` **12.71.0**; corregido desde 12.69.0. | CVE-2026-102279 · [GHSA-jh5r-qr3c-85q8](https://github.com/advisories/GHSA-jh5r-qr3c-85q8) |

**Alcance de la conclusión:** las versiones del lockfile están corregidas para estas diez alertas. Esto no significa que todos los paquetes del proyecto estén libres de cualquier vulnerabilidad futura ni reemplaza una nueva ejecución de Dependabot o `npm audit`.

## Versiones relevantes del proyecto

| Componente | Valor registrado |
|---|---|
| PHP requerido por Composer | `^8.2` |
| Laravel requerido por Composer | `^12.68` |
| Laravel bloqueado en `composer.lock` | **12.71.0** |
| `laravel/tinker` | **2.11.1** |
| `league/flysystem-aws-s3-v3` | **3.35.3** |
| Vite | **8.3.6** |
| Laravel Vite Plugin | **3.1.0** |
| PostCSS | **8.5.32** |
| shell-quote | **1.12.0** |
| Dependencias PHP para cPanel | Empaquetadas en `vendor.zip` |

En el reporte de error compartido desde producción, Laravel informó **12.71.0** y PHP **8.5.12**. El requisito de Composer del repositorio es `^8.2`; la versión real de PHP se debe verificar en **Administración → Información del sistema** después del despliegue.

## Cambios funcionales incluidos

- Asistente de chat integrado para usuarios autenticados; las conversaciones y mensajes quedan asociados al usuario.
- Consultas sobre facturas, clientes, proveedores, cotizaciones, saldos y vencimientos mediante herramientas de datos específicas.
- El filtro de año para vencimientos usa el año actual por defecto; el usuario puede pedir otro año explícitamente.
- Las respuestas se muestran como Markdown convertido a HTML seguro, con formato para tablas, listas, encabezados y negritas. Emojis funcionales seleccionados se presentan como iconos SVG consistentes.
- El ancho y alto del chat se ajustan arrastrando zonas transparentes en los bordes izquierdo y superior. El tamaño elegido queda guardado en el navegador.
- Configuración del modelo, prompt y límites del asistente en la base de datos; clave de OpenAI y habilitación del chat mediante `.env`.
- Integración de lectura para ChatGPT Actions con tokens Bearer revocables asociados a usuarios.
- Auditoría del chat y de la API con usuario, mensajes, modelo, tokens informados, llamadas a funciones, duración y resultado.
- Endpoints preparados para recibir Markdown y metadatos del procesamiento de PDFs con n8n. La aplicación identifica PDFs adjuntos y PDFs procesados.
- Pantalla **Administración → Información del sistema** con versiones de entorno y dependencias, estado de tablas e información de auditoría.
- Documentación de operación en cPanel y de la integración n8n en `README.md` y `docs/n8n-invoice-pdf-workflow.md`.

## Funciones que aún requieren trabajo externo

- El workflow de n8n debe ser creado y configurado por separado, con sus credenciales de Cloudflare R2 y el Bearer de callback.
- La búsqueda del Markdown de facturas procesadas está preparada. La comparación fiable de precios históricos requiere que n8n extraiga las líneas de producto y que el sistema las almacene de forma normalizada.
- La conexión autorizada al correo electrónico no está configurada ni implementada en esta entrega.
- ChatGPT Actions permite consultas de lectura autenticadas; no entrega a Laravel el detalle de los tokens que ChatGPT factura a esa cuenta externa.

## Despliegue recomendado en cPanel

1. Respaldar archivos y base de datos.
2. En **Git Version Control**, hacer `Pull` de `master`.
3. En el Administrador de archivos, extraer `vendor.zip` en la raíz de la aplicación y sobrescribir `vendor/`. Esto sincroniza los paquetes PHP con `composer.lock` sin ejecutar Composer ni usar SSH.
4. Revisar `.env`: mantener `APP_DEBUG=false` en producción y configurar las variables del asistente y n8n que correspondan. No subir secretos a Git.
5. Si la base de datos no tiene el esquema más reciente, importar `docs/sql/assistant-chat-schema.sql` desde phpMyAdmin. La importación SQL es manual en este flujo; un `pull` no ejecuta migraciones.
6. Abrir **Administración → Información del sistema** para confirmar PHP, Laravel, tablas del asistente y auditoría.
7. Probar inicio de sesión, chat, historial, búsquedas y respuestas; luego verificar el estado de los endpoints protegidos.

Los cambios de CSS y JavaScript usan `filemtime` en la URL de los assets para reducir problemas de caché del navegador después del `pull`.

## Historial de commits relacionado

- `29d3873` — añade override inicial de `shell-quote` para una alerta de seguridad previa; la versión bloqueada actual es **1.12.0** y corrige los dos avisos de la lista.
- `825c90b` — actualiza Vite y Laravel Vite Plugin; la resolución actual es Vite **8.3.6** y Laravel Vite Plugin **3.1.0**.
- `ddf4fda` — actualiza PostCSS; la resolución actual es **8.5.32**.
- `d7c8350` — integra las actualizaciones de dependencias JavaScript de los commits anteriores.
- `eb7170c` — integra el asistente, API, auditoría, configuración, endpoints n8n, documentación, `composer.lock` y `vendor.zip`.
- `7a7c1b4` — corrige la entrega del estado de la tabla de auditoría a la vista de información del sistema.
- `128786a` — mejora la legibilidad del chat y aplica el año actual por defecto a consultas de vencimientos.
- `6bc5fbb` — compacta la interfaz y añade ajuste de ancho del chat.
- `23ab9a3` — limita el uso de emojis funcionales en las instrucciones del modelo.
- `1a68072` — transforma esos emojis en iconos SVG seguros en la interfaz.
- `585dcbf` y `e27276f` — añade ajuste de ancho/alto y deja los controles de borde sin indicadores visibles.

