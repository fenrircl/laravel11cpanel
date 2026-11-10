# Integración de correo cPanel con n8n

## Archivo importable

`email-inbox-cpanel.json` es una base importable en n8n. Usa el nodo oficial **Email Trigger (IMAP)** para escuchar mensajes nuevos del buzón `INBOX`. No contiene credenciales: al importarlo, selecciona una credencial IMAP creada en n8n con el host, puerto TLS, usuario y contraseña de la cuenta de correo cPanel.

El nodo está configurado para observar correos nuevos no leídos y no marcar los mensajes como leídos. Para recibir adjuntos, el formato `resolved` los entrega como datos binarios en la ejecución de n8n. Si el buzón o los adjuntos son grandes, limita el procesamiento y guarda los archivos en almacenamiento externo antes de continuar.

## Qué cubre esta base

- Conexión IMAP a un buzón propio de cPanel; no requiere Gmail.
- Recepción de mensajes nuevos que llegan a `INBOX`.
- Descarga de adjuntos para que un workflow posterior pueda detectar y procesar facturas PDF.

## Workflow de búsqueda por webhook

`email-search-cpanel.json` implementa la llamada de Laravel al webhook de n8n y devuelve JSON. **Antes de importarlo/publicarlo**, instala en una instancia n8n autohospedada el nodo comunitario que implementa búsquedas IMAP:

1. En n8n abre **Settings → Community Nodes → Install**.
2. Instala el paquete `n8n-nodes-imap-ai` y espera a que n8n termine la instalación.
3. Importa `email-search-cpanel.json`. Si ya estaba importado, recarga el editor para que resuelva el tipo `n8n-nodes-imap-ai.imap`.
4. En ambos nodos **IMAP (AI)**, crea/selecciona una credencial de tipo **IMAP API** con los datos seguros de cPanel (host, puerto 993/TLS, correo completo y contraseña).
5. En el nodo **Webhook desde Laravel**, crea/selecciona una credencial **Header Auth**: encabezado `Authorization`, valor `Bearer <secreto-largo>`. Laravel debe enviar el mismo encabezado.
6. Confirma el nombre real de la carpeta de enviados de cPanel y cambia `Sent` en el nodo **Buscar en Enviados** si el servidor usa otro nombre, como `Sent Items` o `INBOX.Sent`.
7. Prueba el webhook de prueba en n8n y luego activa el workflow para utilizar la URL `/webhook/assistant/email/search`.

El paquete de IMAP es un nodo comunitario de terceros, no está incluido en n8n estándar y la publicación del workflow falla con `Unrecognized node type: n8n-nodes-imap-ai.imap` mientras no esté instalado. La instalación de nodos comunitarios ejecuta código de terceros dentro de n8n; revisa el paquete y la política de tu instancia antes de instalarlo. La integración documentada requiere n8n autohospedado que permita instalar ese paquete.

Laravel debe traducir la pregunta del usuario a filtros concretos y enviar, por ejemplo:

```json
{
  "direction": "inbound",
  "sender": "proveedor@example.com",
  "subject_or_body": "factura 10471587",
  "after": "2026-11-01",
  "before": "2026-11-30",
  "has_attachments": true,
  "limit": 5
}
```

Y devolver solo coincidencias acotadas: fecha, remitente/destinatario, asunto, identificador del mensaje, nombres de adjuntos y un extracto breve. Laravel conserva la autenticación del usuario, verifica el rol administrador y registra la consulta; el secreto entre Laravel y n8n autentica la llamada de servidor a servidor.

El workflow de búsqueda consulta en vivo `INBOX` y `Sent`, no cambia marcas de leído y no descarga los adjuntos. Busca texto en encabezados/cuerpo y aplica remitente, destinatario, asunto y fechas como filtros IMAP combinados. Devuelve máximo 10 resultados entre ambas carpetas y limita cada extracto a 700 caracteres. Si la carpeta `Sent` no existe, esa búsqueda fallará hasta ajustar el nombre.

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

Este JSON **no crea todavía una búsqueda histórica ni un endpoint de consulta para el chatbot**. El nodo IMAP incluido es un disparador de mensajes nuevos, no una búsqueda arbitraria sobre mensajes antiguos ni sobre la carpeta `Sent`. Tampoco guarda mensajes en Laravel. La API actual de Laravel no tiene un endpoint para ingerir/indexar correos.

Para que el asistente busque mensajes antiguos, enviados y recibidos de forma eficiente, el siguiente paso es sincronizar por IMAP las carpetas permitidas a una tabla/índice en Laravel (o un almacén de búsqueda dedicado), deduplicando por `Message-ID`/UID, y exponer una búsqueda autenticada para usuarios administradores. n8n puede procesar los adjuntos y enviar metadatos/texto extraído a ese endpoint. No conviene enviar el buzón completo al modelo en cada pregunta.

## Importación y credencial

1. En n8n, importa `email-inbox-cpanel.json` desde el menú de workflows.
2. Abre el nodo **Recibir correo nuevo por IMAP** y crea/selecciona una credencial IMAP.
3. En cPanel consulta **Email Accounts → Connect Devices** para obtener el servidor y los puertos seguros. Normalmente IMAP usa TLS en el puerto 993; confirma los valores publicados por tu hosting.
4. Usa la dirección completa como usuario. Guarda la contraseña solo en credenciales de n8n; nunca en Git ni en el `.env` de Laravel.
5. Prueba con un correo controlado y activa el workflow después de revisar las ejecuciones y la retención de datos sensibles en n8n.

Las carpetas de enviados suelen llamarse `Sent`, `Sent Items` o tener otro nombre configurado por el hosting. Debe confirmarse el nombre real mediante la lista de carpetas IMAP antes de sincronizarla; el JSON no asume uno.

## Seguridad y privacidad

- Limita la cuenta a un buzón dedicado o con permisos de lectura cuando el proveedor lo permita.
- No incluyas contraseñas, tokens ni contenido de correo en este repositorio.
- Configura en n8n una retención de ejecuciones acorde con la política de privacidad del taller; las ejecuciones pueden contener cuerpos y adjuntos.
- Trata el contenido del correo y de los PDF como datos externos no confiables. No ejecutes instrucciones encontradas en ellos.
- La integración visible en el chatbot requiere además autenticación/autorización de Laravel, auditoría y filtrado de resultados. Este archivo por sí solo no habilita acceso desde el chat.
