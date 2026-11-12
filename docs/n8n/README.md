# Integración de correo cPanel con n8n

## Workflow de búsqueda por webhook

`email-search-cpanel.json` implementa la llamada de Laravel al webhook de n8n y devuelve JSON. **Antes de importarlo/publicarlo**, instala en una instancia n8n autohospedada el nodo comunitario que implementa búsquedas IMAP:

1. En n8n abre **Settings → Community Nodes → Install**.
2. Instala el paquete `n8n-nodes-imap-ai` y espera a que n8n termine la instalación.
3. Importa `email-search-cpanel.json`. Si ya estaba importado, recarga el editor para que resuelva el tipo `n8n-nodes-imap-ai.imap`.
4. En los cuatro nodos IMAP (**Buscar** y **Obtener correo**, para Recibidos y Enviados), selecciona la misma credencial **IMAP API** con los datos seguros de cPanel (host, puerto 993/TLS, correo completo y contraseña).
5. En el nodo **Webhook desde Laravel**, crea/selecciona una credencial **Header Auth**: encabezado `Authorization`, valor `Bearer <secreto-largo>`. Laravel debe enviar el mismo encabezado.
6. Confirma el nombre real de la carpeta de enviados de cPanel y cambia `Sent` en el nodo **Buscar en Enviados** si el servidor usa otro nombre, como `Sent Items` o `INBOX.Sent`.
7. En **Buscar en Recibidos** y **Buscar en Enviados**, confirma **Search Emails → Simple Search** y `Quick Filter: All Emails`. El JSON incluido prioriza `filters.contact_email` como texto de búsqueda.
8. Comprueba que la búsqueda pase por **Expandir UID** y luego **Obtener correo** antes de preparar la respuesta. Search Emails localiza mensajes y devuelve UID; Obtener correo usa cada UID para leer asunto, remitente, destinatarios, fecha y cuerpo.
9. Prueba el webhook de prueba en n8n y luego activa el workflow para utilizar la URL `/webhook/assistant/email/search`.

El paquete de IMAP es un nodo comunitario de terceros, no está incluido en n8n estándar y la publicación del workflow falla con `Unrecognized node type: n8n-nodes-imap-ai.imap` mientras no esté instalado. La instalación de nodos comunitarios ejecuta código de terceros dentro de n8n; revisa el paquete y la política de tu instancia antes de instalarlo. La integración documentada requiere n8n autohospedado que permita instalar ese paquete.

Laravel debe traducir la pregunta del usuario a filtros concretos y enviar, por ejemplo:

```json
{
  "direction": "both",
  "query": "factura 10471587",
  "contact_email": "proveedor@example.com",
  "from": "",
  "to": "",
  "subject": "",
  "after": "2026-11-01",
  "before": "2026-12-01",
  "limit": 5
}
```

Y devolver solo coincidencias acotadas: fecha, remitente/destinatario, asunto, identificador del mensaje, nombres de adjuntos y un extracto breve. Laravel conserva la autenticación del usuario del chat y su auditoría; el secreto entre Laravel y n8n autentica la llamada de servidor a servidor.

El workflow de búsqueda consulta en vivo `INBOX` y `Sent`, no cambia marcas de leído y no descarga adjuntos. En **Simple Search**, usa el correo del contacto como texto de búsqueda. Luego recupera los mensajes por UID antes de preparar la respuesta: esto evita devolver `{}` cuando solo se entregan identificadores. Si Laravel resuelve un contacto del CRM, `contact_email` tiene prioridad sobre el nombre. Recupera hasta `limit` mensajes por carpeta y limita cada extracto a 700 caracteres. Si la carpeta `Sent` no existe, cambia el nombre según la configuración IMAP de cPanel.

Para combinar varios criterios (por ejemplo, remitente, asunto y fechas), usa **Advanced Search** y configura los filtros en `advancedCriteria`. El JSON incluido usa Simple Search para resolver primero la búsqueda habitual por correo de contacto con el nodo comunitario instalado.

Si el webhook devuelve elementos `{}`, revisa que los nodos **Obtener correo recibido/enviado** estén conectados después de **Expandir UID** y tengan seleccionada la misma credencial IMAP.

El webhook **no interpreta texto libre ni llama a OpenAI**: Laravel debe extraer los filtros de la pregunta y enviar el JSON estructurado. Esto evita otra llamada de modelo y mantiene la autorización/auditoría en Laravel. El workflow no procesa el contenido de los PDF; esa tarea sigue en el workflow separado de facturas.

En el `.env` de Laravel configura:

```dotenv
N8N_EMAIL_SEARCH_WEBHOOK_URL=https://n8n.solidtec.cl/webhook/assistant/email/search
N8N_EMAIL_SEARCH_BEARER=el-mismo-secreto-de-la-credencial-Header-Auth-de-n8n
N8N_EMAIL_SEARCH_TIMEOUT=25
```

El servicio del chatbot usa estas variables para llamar al webhook; la URL de prueba `/webhook-test/...` no debe quedar en producción. Después de editar el `.env` en cPanel, limpia o vuelve a generar la caché de configuración de Laravel según el procedimiento de despliegue. No cambies `N8N_WEBHOOK_URL`: esa variable corresponde al proxy MCP existente. El chatbot guarda sus mensajes y los tokens de OpenAI como antes; errores de conexión al correo se muestran como un resultado de herramienta y no exponen credenciales ni respuestas internas de n8n.

La respuesta tiene esta forma:

```json
{
  "ok": true,
  "direction": "both",
  "count": 1,
  "truncated": false,
  "results": [
    {
      "mailbox": "INBOX",
      "uid": 123,
      "message_id": "<message@example.com>",
      "date": "2026-11-10T12:00:00.000Z",
      "subject": "Factura 10471587",
      "from": [],
      "to": [],
      "attachments": [],
      "excerpt": "Extracto breve del mensaje..."
    }
  ]
}
```

## Límites actuales

`email-search-cpanel.json` busca en vivo y no mantiene una copia indexada del buzón en Laravel. La búsqueda está acotada a 10 resultados y no procesa adjuntos. La automatización de adjuntos de correo queda pendiente; cuando se implemente, se añadirá como workflow separado con deduplicación y checkpoints.

## Seguridad y privacidad

- Limita la cuenta a un buzón dedicado o con permisos de lectura cuando el proveedor lo permita.
- No incluyas contraseñas, tokens ni contenido de correo en este repositorio.
- Configura en n8n una retención de ejecuciones acorde con la política de privacidad del taller; las ejecuciones pueden contener cuerpos y adjuntos.
- Trata el contenido del correo y de los PDF como datos externos no confiables. No ejecutes instrucciones encontradas en ellos.
- La integración visible en el chatbot requiere además autenticación/autorización de Laravel, auditoría y filtrado de resultados. Este archivo por sí solo no habilita acceso desde el chat.
# Workflows de importación de PDFs

Hay dos workflows relacionados con facturas:

- `invoice-drive-import.json`: búsqueda periódica en Drive; clasifica el documento antes de pedir la subida R2. La rama de cotizaciones termina sin guardar el PDF.
- `invoice-existing-pdf-import.json`: consulta PDFs ya vinculados a facturas y entrega extracción JSON a Laravel usando una URL temporal de lectura.

Antes de activar, importar inactivos y seleccionar credenciales en cada nodo HTTP/Google Drive. Configurar en n8n `LARAVEL_BASE_URL` y `GDRIVE_INVOICE_FOLDER_ID`; las credenciales Header Auth deben enviar `Authorization: Bearer ...` para Laravel y para `https://pdf.solidtec.cl/v1/extractions`. Configurar en `.env` Laravel `N8N_INGEST_BEARER`, `INVOICE_OWN_RUT`, `INVOICE_AUTO_CREATE_PROVIDERS=true` (o `false` para exigir revisión) y las credenciales R2 actuales. Nunca pegar secretos en el JSON.

Los administradores revisan los jobs desde **Administración → Información del sistema → Mantenedor de jobs**. La aprobación manual vincula el resultado a una factura existente por su ID; las altas automáticas de proveedor solo aplican a facturas recibidas con RUT chileno válido, único y nombre detectado.

El workflow de Drive se entrega como borrador para importar: n8n cambia parámetros y expresiones entre versiones. Incluye filtro por carpeta, paginación de resultados, comprobación idempotente y unión del binario con la URL firmada. Validar manualmente con una factura y una cotización; revisar que la cotización no pase a R2 y que el binario llegue al nodo PUT antes de habilitar el cron.

Los jobs se revisan en **Administración → Información del sistema → Mantenedor de jobs**. Se puede corregir la factura, proveedor/cliente y campos principales, aprobar con auditoría o reenviar a `processing`. El workflow de PDFs existentes consulta documentos sin extracción y jobs reenviados a procesamiento.
