# Workflow n8n para documentos de factura

El procesamiento de PDF queda desacoplado del chat. Laravel entrega a n8n solo los metadatos y la clave del objeto; n8n lee el PDF directamente desde R2, lo convierte a Markdown y envía el resultado de vuelta. La clave de R2 nunca se entrega al modelo ni al navegador.

## Configuración en el servidor

Configura estas variables en el `.env` de cPanel, sin subirlas a Git:

```dotenv
N8N_PDF_PROCESS_WEBHOOK_URL=https://N8N_HOST/webhook/ID_DEL_WORKFLOW
N8N_PDF_WEBHOOK_BEARER=secreto-opcional-para-proteger-el-webhook
N8N_INGEST_BEARER=secreto-largo-compartido-con-la-credencial-de-n8n
```

El webhook de procesamiento es opcional: las subidas de PDF siguen funcionando si no está configurado. Para recibir los resultados, `N8N_INGEST_BEARER` sí debe estar configurado tanto en cPanel como en la credencial HTTP de n8n.

## Flujo

1. Cuando se sube un PDF asociado a una factura, Laravel envía un POST al webhook n8n. El cuerpo lleva `invoice` (id, número, client_id, provider_id, entidad, monto y fechas), `file` (id de `files_registry`, `r2_key`, nombre, MIME y tamaño) y `callback_url`.
2. n8n usa su propia credencial de Cloudflare R2 para descargar `file.r2_key`. Laravel no envía el PDF por HTTP.
3. n8n extrae el texto y lo convierte a Markdown.
4. n8n hace PUT al `callback_url`, usando `Authorization: Bearer N8N_INGEST_BEARER` y este cuerpo:

```json
{
  "invoice_number": "F-1001",
  "markdown": "# Factura F-1001\n\nProveedor: ...",
  "source_sha256": "<sha256-del-pdf-opcional>",
  "metadata": {
    "workflow": "invoice-pdf-v1",
    "pages": 2
  }
}
```

El endpoint es `PUT /api/v1/automation/invoices/{invoice_id}/documents/{file_id}/markdown`. Laravel verifica que el archivo pertenezca a esa factura y sea PDF, y luego crea o actualiza el contexto asociado al archivo. El límite de Markdown por documento es 500 KB.

## Indicadores en las listas de facturas

Las tablas de facturas muestran automáticamente **PDF adjunto** cuando encuentran un archivo PDF en `files_registry` y **PDF procesado** cuando existe un contexto Markdown guardado para ese mismo archivo. La marca de procesado solo aparece después de que n8n complete el callback; una subida enviada a n8n o un fallo del workflow deja la factura como pendiente de procesamiento. Si una factura tiene varios PDFs, aparece como procesada cuando al menos uno tiene contexto guardado.

Para automatizarlo, configura el webhook de n8n y las credenciales indicadas arriba, y al final del workflow llama al callback con el `invoice_id` y `file_id` recibidos. Para revisar/reprocesar, consulta el endpoint GET de documentos: cada archivo incluye `processed_at` si ya fue procesado. Si el workflow se ejecuta manualmente, el indicador se actualizará al recargar la tabla después del callback.

## Endpoints de integración

- `GET /api/v1/automation/invoices/{invoice_id}/documents` devuelve factura, proveedor/cliente y claves R2 de sus PDFs. Usa la misma credencial Bearer de n8n; sirve para reprocesar los documentos de esa factura.
- `PUT /api/v1/automation/invoices/{invoice_id}/documents/{file_id}/markdown` guarda el resultado del workflow.
- El chat y la Action de ChatGPT consultan fragmentos mediante `POST /api/v1/assistant/invoice-documents/search`. Esa ruta usa un Bearer de usuario, distinto del secreto de n8n.

## Reintentos e idempotencia

El identificador del archivo es la clave de actualización. Repetir el callback reemplaza el Markdown de ese PDF en vez de duplicarlo. n8n puede comparar `source_sha256` para omitir conversiones cuando el objeto no cambió. Un error en el webhook no revierte ni elimina el archivo que ya se subió a R2.
