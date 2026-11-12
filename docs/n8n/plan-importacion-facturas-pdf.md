# Plan: importar PDFs, guardar detalles JSON y consultarlos desde el chatbot

Fecha: 12 de noviembre de 2026. Estado: implementación en curso. Las marcas reflejan el código del repositorio; no implican que se haya desplegado en cPanel ni probado contra producción.

## Seguimiento de implementación

| Área | Estado | Evidencia / pendiente |
|---|---|---|
| Migración de tablas de importación, extracción JSON y líneas | Implementado en código | `database/migrations/2026_11_12_000001_create_invoice_extraction_tables.php`. Falta SQL equivalente para phpMyAdmin y validar contra el esquema de producción. |
| Autenticación de endpoints de ingesta | Implementado en código | Reutiliza `auth.n8n` y `N8N_INGEST_BEARER` en el grupo `/api/v1/automation`. Falta probar con la credencial real de n8n. |
| Reserva idempotente por fuente/ID/versión | Parcial | `POST /api/v1/automation/ingestions/reserve`. Falta robustecer concurrencia, leases/reintentos y política cuando cambia el hash con la misma versión. |
| URL temporal de carga R2 | Implementado en código, sin validar en R2 | `POST /api/v1/automation/ingestions/{id}/upload-url`. Depende de que `r2` esté configurado con credenciales aptas para firmar. Falta prueba PUT real y verificación criptográfica del SHA-256 del objeto. |
| Recepción de JSON del extractor y mapeo de líneas | Parcial | `PUT /api/v1/automation/ingestions/{id}/result`; distingue el JSON real de `api-pdf` (incluye su propiedad `extraction` de metadatos) del formato envuelto anterior. Mapea `code`, `description`, `quantity`, `unit_price`, `total_amount`. Falta validar en integración y auditar importes por línea. |
| Asociación automática en `files_registry` | Parcial | Se crea el registro cuando el objeto R2 existe y se resuelve una factura. PDF registrado puede procesarse desde su clave existente; falta asegurar recuperación idempotente ante fallos intermedios. |
| Coincidencia/creación de factura | Parcial y requiere validación de producción | Exige RUT propio, contraparte, fecha, monto, confianza mínima y ausencia de advertencias; PDF debe estar asociado o cargado. Ambigüedad va a revisión. Falta revisar esquema/reglas en producción y flujo administrativo de aprobación. |
| Mantenedor de jobs y revisión de excepciones | Primera versión implementada | Lista jobs, muestra JSON/warnings, permite corregir factura/proveedor/cliente/folio/fecha/monto, aprobar con auditoría y encolar reintento. Falta prueba integrada y edición detallada de líneas individuales. |
| Crear proveedor ausente | Implementado en primera versión | Con RUT chileno válido/no duplicado, issuer, confianza aceptada y sin warnings; toggle `INVOICE_AUTO_CREATE_PROVIDERS`. Alta y creación de factura ocurren en transacción y se auditan. Falta prueba con BD real. Clientes siguen a revisión. |
| Completar datos de contacto de contraparte | Parcial | Laravel completa solo campos vacíos si el JSON incluye correo/teléfono/dirección explícitos; la API PDF actual no garantiza estos campos. No se espera que se rellenen hasta ampliar extractor. |
| Consulta de productos por chatbot | Implementado como primera versión | `search_invoice_items` busca código/descripción, contraparte, folio y año; `summarize_invoice_items` agrega en SQL agrupado por moneda y unidad y reporta cobertura. Falta paginación con cursor e historial comparable; muestra datos extraídos activos únicamente. |
| Workflow Drive | Borrador avanzado creado | Incluye filtro por carpeta, resultados completos, rama quote antes de R2, comprobación de acción idempotente y merge para conservar el binario. Requiere credenciales, variables y prueba de nodo/expresiones en instancia. |
| Workflow de PDFs ya asociados | Borrador avanzado creado | Procesa PDFs sin extracción y jobs reenviados; usa enlace temporal sin volver a cargar el objeto. Requiere importación y prueba en versión real de n8n. |
| Adjuntos de correo | Pendiente | El workflow existente de búsqueda sigue de solo lectura; falta entrada de adjuntos y su identidad IMAP estable. |
| Workflow de búsqueda de correo | Ya existe previamente | `docs/n8n/email-search-cpanel.json`; no se ha modificado para descargar ni procesar adjuntos. |
| SQL manual y guía de despliegue cPanel | Parcial | Añadido `docs/sql/invoice-extraction-schema.sql` idempotente. Falta compararlo contra la versión MySQL de producción e integrarlo con el procedimiento de pull/backup/reconciliación de migraciones. |
| API extractora | Contrato existente, mapeo inicial confirmado | Revisado `api-pdf/app/main.py`: campos de extracción en nivel superior y líneas en `data.line_items`. No necesita cambios para la integración base. Unidad y base de impuestos quedan como mejora futura del extractor, necesaria para comparar líneas en unidades y bases tributarias homogéneas. |
| Exclusión de cotizaciones de la intranet | Implementado en endpoint; falta filtrarlas antes de la subida n8n | Si el extractor clasifica `quote`, Laravel responde `ignored`, sin crear factura ni líneas. El workflow debe clasificar antes de reservar URL/subir a R2 y detener esa rama. Las cotizaciones existentes se consultan desde sus registros y partidas actuales. |
| Descarga de PDFs antiguos desde plataforma | Fix implementado en código; requiere prueba con claves cPanel/R2 | Añadida descarga por ID de `files_registry`, que lee la clave guardada exacta y evita doble decodificación/normalización Unicode. Las respuestas de facturas ahora incluyen `file_id` y el botón usa la ruta nueva. La ruta antigua queda como fallback y ya no fuerza NFD ni decodifica otra vez. |

Las rutas implementadas usan estas formas:

```text
POST /api/v1/automation/ingestions/reserve
POST /api/v1/automation/ingestions/{ingestion}/upload-url
PUT  /api/v1/automation/ingestions/{ingestion}/result
```

Se verificó sintaxis PHP de migración, controladores/servicios, rutas y `config/services.php`; ambos JSON se parsean sin claves duplicadas y `git diff --check` está limpio. No se han ejecutado pruebas de integración ni se ha probado contra R2, n8n o una base de datos real. La pantalla admin de jobs y el alta automática de proveedores están en primera versión. No importar aún a producción hasta validar workflows y SQL con respaldo/copia de esquema.

## 1. Resultado esperado

Importar automáticamente PDFs desde Google Drive, extraer sus datos con la API existente, conservar el original en R2 y vincularlo a la factura correcta. Si la factura no existe, crearla únicamente cuando su identidad y campos obligatorios sean verificables. Guardar la extracción como JSON, con una tabla separada de productos/precios para búsquedas y cálculos rápidos.

El chatbot podrá responder, por ejemplo:

- «¿Qué materiales compramos a KUPFER este mes?»
- «¿Cuánto costó este tubo en las últimas compras y quién lo vendió más barato?»
- «Muéstrame los productos de la factura 10471587».
- «Compara los materiales de esta cotización con compras anteriores».
- «Encuentra el correo donde recibimos esa factura».

La importación ocurre fuera del chat. Una pregunta no debe volver a procesar el PDF ni enviar todo el documento al modelo.

## 2. Qué existe actualmente y qué falta

| Componente | Estado observado en el repositorio | Trabajo propuesto |
|---|---|---|
| Facturas | `Factura`, tabla `invoices`; cliente/proveedor, folio, fechas, monto y pago | Añadir identidad fiscal y reglas de importación sin alterar pagos existentes |
| Archivos R2 | `files_registry`; asociación histórica `model_type = App\\Invoice` | Reutilizar la asociación y registrar hash/procedencia |
| Contexto de PDF | `invoice_document_contexts`, Markdown por archivo | Mantener lectura histórica; usar JSON para extracciones nuevas |
| Automatización | GET de documentos y PUT de Markdown bajo `/api/v1/automation` | Añadir reserva, carga, resultado estructurado y seguimiento |
| Chatbot | `AssistantDataService`: búsquedas, detalles, sumas y búsqueda textual de documentos | Añadir consultas estructuradas de productos/precios y procedencia |
| Correo | `docs/n8n/email-search-cpanel.json`, webhook IMAP de cPanel | Conservar búsqueda; ampliar filtros combinados y referencias a adjuntos |
| API PDF | `POST https://pdf.solidtec.cl/v1/extractions` | Consumir el contrato facilitado, sin desarrollar otro extractor |

Actualmente `PDF procesado` significa que al menos un PDF de la factura tiene Markdown. No significa que todos los PDFs tengan productos extraídos. El endpoint actual tampoco crea facturas nuevas ni sus líneas de detalle.

Referencias internas: `app/Models/Factura.php`, `app/Models/FilesRegistry.php`, `app/Models/InvoiceDocumentContext.php`, `app/Http/Controllers/InvoiceAutomationController.php`, `app/Services/AssistantDataService.php`, `docs/n8n-invoice-pdf-workflow.md` y `docs/n8n/README.md`.

## 3. Decisiones de arquitectura

### JSON como fuente estructurada

Guardar la respuesta original de la API y una representación normalizada versionada. **No convertir las facturas nuevas a Markdown como paso obligatorio.** El JSON conserva cabecera, productos, importes, advertencias y procedencia; las columnas y líneas normalizadas permiten calcular sin interpretar texto con un LLM.

`raw_text` será respaldo opcional para fragmentos que no se hayan estructurado; no se enviará por defecto al chatbot. Los contextos Markdown existentes seguirán disponibles mientras se reprocesan sus PDFs. No convertir automáticamente Markdown antiguo en datos contables fiables.

### Subida directa a R2, controlada por Laravel

Recomendación: n8n descarga el PDF de Drive, Laravel reserva la importación y entrega una URL PUT firmada de corta duración para una clave concreta. n8n sube el binario directamente a R2. Laravel verifica el objeto antes de asociarlo y guardar el resultado. Así el PDF no atraviesa PHP/cPanel y las claves de escritura permanentes de R2 no necesitan quedar en el workflow.

R2 admite URLs firmadas para PUT mediante su API S3; son credenciales temporales y deben mantenerse fuera de logs y del chatbot. [Documentación oficial de R2](https://developers.cloudflare.com/r2/api/s3/presigned-urls/).

Guardar bucket/clave, tamaño y hash; no guardar una URL firmada como enlace permanente. La aplicación generará enlaces autorizados de lectura cuando corresponda. Verificar la privacidad real del bucket actual antes de reutilizarlo.

Alternativa compatible: credencial S3/R2 propia en n8n, limitada al bucket de importación. Laravel debe seguir asignando/verificando la clave; no aceptar URLs arbitrarias ni descargar rutas suministradas sin validación. Un endpoint multipart en Laravel queda como alternativa si no puede firmarse la carga, condicionado por límites de PHP/cPanel.

### Responsabilidades

- **n8n:** calendario, lectura de fuentes, binarios, llamadas al extractor, reintentos y seguimiento.
- **Laravel:** autenticación, validación, coincidencias, transacciones, auditoría, exposición de datos y permisos del chat.
- **R2:** originales PDF y, si el volumen lo exige, respuestas originales grandes con referencia desde la BD.
- **MySQL:** JSON validado, relaciones, líneas normalizadas, estados e índices. No hace falta incorporar otra base de datos para esta «colección».

## 4. Contrato de la API extractora

Usar el contrato aportado por el usuario:

```http
POST https://pdf.solidtec.cl/v1/extractions
Authorization: Bearer <credencial de la API PDF>
Content-Type: multipart/form-data; boundary=<generado por n8n>
```

Campos: `file` binario PDF, `document_type=auto`, `locale=es-CL`. En n8n usar HTTP Request con Form-Data y campo de tipo binario; no enviar base64 ni fijar manualmente el boundary.

Conservar los siguientes campos del resultado:

| Campo | Uso |
|---|---|
| `request_id`, `schema_version` | Trazabilidad y selección del adaptador de formato |
| `document_type`, `document_subtype` | Distinguir facturas, boletas y documentos fuera del alcance |
| `data.invoice_number` | Folio conservado como texto, sin eliminar ceros o prefijos automáticamente |
| `data.supplier_rut`, `data.customer_rut` | Identificar emisor/receptor y resolver contraparte |
| `data.date` | Fecha de emisión, distinta de la fecha de importación |
| `data.amount`, `currency`, `net_amount`, `tax_amount` | Totales y validación monetaria |
| `data.issuer`, `data.recipient` | Nombres de respaldo; no son identidad única |
| `data.line_items` | Líneas originales y posterior normalización |
| `raw_text` | Texto auxiliar opcional |
| `confidence`, `warnings` | Reglas de revisión; confianza no equivale a garantía |
| `source.filename`, `mime_type`, `sha256`, `pages` | Verificar origen y correspondencia con el PDF |
| `extraction` | Proveedor/modelo, páginas OCR, fallos y duración |

Un HTTP 200 no basta: `data` puede estar vacío, faltar campos o existir advertencias. Exigir estructura válida y tratar `null` como desconocido, nunca como cero. Validar versión antes de normalizar; una versión desconocida queda para revisión.

La muestra real confirma `code`, `description`, `quantity`, `unit_price` y `total_amount`. La API no entrega unidad, descuento ni base de impuestos en esa muestra; esos valores quedan desconocidos y no se infieren.

Validar también cómo distingue la API las boletas y notas de crédito mediante `document_subtype`. No transformar automáticamente cotizaciones, guías de despacho o notas de crédito en facturas de compra.

## 5. Modelo de datos propuesto

Todas las tablas nuevas deben respetar exactamente el tipo y signo de los IDs existentes. Revisar el esquema real de producción antes de crear claves foráneas, considerando el error anterior de incompatibilidad con `users.id`.

### `document_ingestions`: control de procesamiento

Campos principales: UUID, fuente (`drive`, `registered_pdf`, `email_attachment`), cuenta/origen, ID externo, versión externa, hash SHA-256 del PDF, clave R2 reservada, factura/archivo asociados cuando existan, estado, intentos, error resumido, próxima ejecución, lease de procesamiento y timestamps.

Estados: `discovered`, `processing`, `uploaded`, `extracted`, `needs_review`, `completed`, `failed`. Definir transiciones permitidas y expiración del lease para recuperar ejecuciones interrumpidas.

Unicidad por origen + ID externo + versión + versión del procesamiento. La idempotencia debe considerar también el hash: un PDF copiado a otra carpeta o recibido por correo puede ser el mismo archivo. Conservar las distintas procedencias sin duplicar la factura.

### `invoice_extractions`: JSON y versiones

Campos: ID, `ingestion_id`, `invoice_id` y `file_registry_id` (pueden estar pendientes durante revisión), `request_id`, `schema_version`, versión del adaptador, `source_sha256`, `document_type`, `document_subtype`, `payload` JSON original, `normalized_data` JSON, `raw_text` opcional, confianza, advertencias, estado de validación y fecha.

Guardar versiones inmutables y señalar cuál está aprobada. Una respuesta corregida crea una nueva versión; no elimina la evidencia anterior. Si se separa el JSON original hacia R2 por tamaño, conservar en BD clave y hash, y los datos normalizados necesarios para consultar.

### `invoice_line_items`: nueva tabla separada de detalles

| Campo propuesto | Propósito |
|---|---|
| `invoice_id`, `extraction_id`, `line_number` | Factura, versión y orden; unicidad por extracción + línea |
| `description_original`, `description_normalized` | Evidencia y búsqueda normalizada |
| `supplier_sku`, `product_id` nullable | Código del proveedor y catálogo opcional |
| `quantity`, `unit_original`, `unit_normalized` | Cantidad/unidad sin inventar equivalencias |
| `unit_price`, `discount_amount`, `net_amount`, `tax_amount`, `total_amount` | Importes con semántica explícita |
| `currency`, `price_tax_basis` | Moneda y si el precio incluye impuesto o no se sabe |
| `source_line` JSON | Línea original y campos todavía no mapeados |

Usar DECIMAL para cantidades/precios (propuesta: `DECIMAL(20,6)`), no floats. Campos desconocidos nullable. El modelo actual convierte `invoices.amount` a entero: mantener CLP dentro del rango permitido y enviar moneda extranjera a revisión hasta adaptar el modelo; nunca redondear o convertir moneda silenciosamente.

Elegir una extracción activa por factura para calcular sus productos, aunque tenga varios PDFs o copias del mismo documento. No sumar las líneas de todos los adjuntos: duplicaría compras. Los anexos que agreguen líneas requerirán una política explícita.

### Identidad fiscal y origen

Añadir campos o una relación de identidad con emisor RUT, receptor RUT, tipo documental y folio normalizado. La identidad única propuesta incluye esos campos. Preservar el folio original y revisar colisiones antes de aplicar índices únicos sobre históricos.

Registrar fecha de ingreso/importación separada de emisión. Para facturas existentes no alterar `created_at`. Conservar cuenta/carpeta/ID de Drive o correo/carpeta/UIDVALIDITY/UID/parte del adjunto, sin guardar secretos.

## 6. Reglas para actualizar o crear una factura

1. Validar PDF, hash, tamaño y tipo; poner límites configurables de páginas y bytes. Excluir cotizaciones generadas por la intranet antes de subir a R2: sus datos están en `quotations` y sus partidas. En Drive, primero descargar y clasificar sin persistir en R2; al recibir `document_type=quote`, terminar la ejecución sin subir el PDF ni crear factura. Si el archivo ya está asociado en R2 a la cotización, usar el registro existente.
2. Comparar RUT emisor/receptor con el RUT de la empresa configurado. Receptor propio = compra/proveedor; emisor propio = venta/cliente. Si no coincide o es ambiguo, revisión.
3. Buscar por identidad fiscal completa. El número de factura solo no basta: distintos proveedores o clientes pueden compartir folio.
4. Para registros históricos sin identidad completa, buscar candidato por contraparte fiscal + folio y verificar fecha/monto/tipo. Varios candidatos o contradicciones implican revisión, nunca elegir el primero.
5. Si hay coincidencia única, asociar PDF y extracción; conservar estado pagado, fecha de pago, medio de pago, cheques y correcciones manuales. Diferencias de cabecera se muestran para aprobación; inicialmente actualizar automáticamente solo campos vacíos fiables.
6. Si no existe, crear con contraparte, folio, fecha y total válidos. Un vencimiento desconocido permanece vacío si el esquema lo permite; no inventar plazo. No inferir pago a partir de un PDF. Revisar qué valor inicial admite el sistema para boletas antes de crearlas como pendientes.
7. Contraparte inexistente: revisión por defecto; creación automática por RUT validado solo si se habilita expresamente. Un nombre parecido no autoriza unir registros.
8. Si faltan datos mínimos, guardar la extracción para revisión sin crear una factura incompleta. Un documento con cabecera válida y sin líneas puede registrarse como «sin detalle extraído», sin fingir que tiene productos.
9. En una transacción, guardar factura/identidad, archivo, extracción aprobada, líneas y auditoría. Usar restricciones únicas y bloqueo apropiado para impedir duplicados con ejecuciones simultáneas.

### Mantenedor, alta de proveedores y actualización de contacto

La revisión se implementará en Administración para que un administrador pueda ver jobs pendientes, fallidos, en revisión y completados; abrir el PDF y el JSON, cambiar contraparte o factura, corregir valores, aprobar/reintentar/descartar y consultar quién modificó cada asociación. La extracción original se conserva inmutable y las correcciones se auditan.

Si un PDF es una factura recibida y el proveedor no existe, se puede dar de alta automáticamente si el RUT se valida y no está duplicado, hay nombre legible y el resultado supera las reglas de confianza. Si alguno falla, el job requiere aprobación. La misma regla para clientes debe esperar a identificar inequívocamente una factura emitida.

Para correo, teléfono y dirección: actualizar automáticamente solo valores vacíos, con procedencia y campo explícito en el JSON; no sobrescribir información existente basándose en OCR. El contrato inspeccionado de `api-pdf` entrega issuer/recipient, RUT, importes y líneas, pero no garantiza datos de contacto. Antes de completar esos campos, ampliar y versionar la API para devolver `supplier`/`customer` con campos opcionales, y validar el resultado con PDFs reales. Un valor contradictorio va al mantenedor para decisión humana.

Auditar origen `n8n`, importación, valores anteriores/nuevos y administrador que aprueba correcciones. No atribuir una importación automática a un usuario que no la hizo.

## 7. Endpoints Laravel propuestos

Prefijo `/api/v1/automation`. Mantener la credencial de servicio `N8N_INGEST_BEARER` separada del Bearer de usuarios/ChatGPT. Limitar el token de automatización a sus rutas y registrar sus acciones. Las APIs externas de consulta siguen restringidas a administradores; el chatbot interno aplica sus propios permisos por usuario.

| Método y ruta propuesta | Entrada y resultado |
|---|---|
| `POST /ingestions/reserve` | Fuente, ID/version externos, nombre, MIME, bytes y hash. Devuelve `ingestion_id`, estado y acción: procesar, omitir o revisar |
| `POST /ingestions/{id}/upload-url` | Solo para reserva vigente. Devuelve PUT firmado, clave asignada, headers requeridos y expiración; reemitible al caducar |
| `PUT /ingestions/{id}/result` | Envelope de resultado con JSON original del extractor y versión del adaptador. Verifica archivo, valida y aplica reglas de actualización/creación |
| `GET /ingestions/{id}` | Estado, factura/archivo resultantes, advertencias y error resumido |
| `POST /ingestions/{id}/failure` | Etapa y error acotado; programa reintento o revisión sin retroceder una importación completada |
| `GET /documents/pending?cursor=...&limit=...` | Backfill paginado de PDFs registrados sin extracción estructurada vigente |
| `POST /documents/{file}/download-url` | Lectura temporal del PDF registrado, solo para automatización autorizada |

Ejemplo de reserva (contrato futuro):

```json
{
  "source": "drive",
  "external_id": "drive-file-id",
  "external_version": "version-del-archivo",
  "filename": "factura.pdf",
  "mime_type": "application/pdf",
  "size_bytes": 123456,
  "sha256": "<64-caracteres-hexadecimales>",
  "processing_version": "invoice-json-v1"
}
```

Ejemplo de entrega del resultado:

```json
{
  "adapter_version": "pdf-api-1.0-to-invoice-v1",
  "extraction": {
    "request_id": "uuid-del-extractor",
    "schema_version": "1.0",
    "document_type": "invoice",
    "data": {},
    "source": {},
    "warnings": []
  }
}
```

El contenido de `extraction` debe ser la respuesta completa real; los objetos vacíos anteriores son solo una abreviación, no un resultado aceptable. Laravel hace la normalización final para no duplicar reglas de negocio en n8n.

Respuesta estable: `ingestion_id`, `status`, `action` (`created`, `updated`, `unchanged`, `needs_review`), `invoice_id`, `file_id`, `extraction_id`, `warnings`. Repetir una entrega completada devuelve los mismos IDs; contenido diferente con la misma clave genera conflicto, no sobrescritura silenciosa.

Errores: 401/403 credencial o permisos, 409 conflicto, 413 tamaño, 422 contrato inválido, 429 límite, 5xx transitorio. `needs_review` es un resultado de negocio válido y no debe disparar reintentos infinitos.

Verificar objeto en la clave reservada mediante metadatos y tamaño; contrastar el hash calculado sobre el binario con `source.sha256` y definir verificación de checksum soportada por la carga real. ETag no se considera SHA-256. No confiar en una clave R2 arbitraria remitida en el resultado.

La escritura a R2 no es parte de la transacción SQL: conservar objetos reservados tras un fallo de BD para reintentar; limpiar huérfanos mediante política de retención y comprobación de referencias. No borrar originales como compensación automática.

## 8. Workflows n8n

### A. Drive → extracción → R2 → Laravel

Archivo futuro: `docs/n8n/invoice-drive-import.json`.

Secuencia: Schedule Trigger + disparador manual → listar PDFs → paginar → procesar lote → descargar PDF → calcular SHA-256 → invocar API PDF → si es `quote`, registrar/terminar sin R2 → si es factura, reservar en Laravel → omitir si ya completado → subir con PUT firmado → enviar JSON a Laravel → guardar estado → siguiente archivo.

- Propuesta inicial: cada 15 minutos, zona `America/Santiago`, lote de 10, concurrencia 1; ajustar con tiempos reales y límites de API.
- Carpeta por ID configurable; decidir explícitamente si incluye subcarpetas y unidades compartidas. Usar consultas de Drive, no coincidencia por nombre solamente. [Operaciones de búsqueda de Drive en n8n](https://github.com/n8n-io/n8n-docs/blob/main/docs/integrations/builtin/app-nodes/n8n-nodes-base.googledrive/file-folder-operations.md).
- Primera ejecución: backfill paginado. Incrementales: checkpoint persistente en BD y ventana de solapamiento; confirmar avance cuando cada archivo descubierto quede registrado. No perder pendientes por avanzar la fecha global tras un fallo.
- Versiones/fechas de modificación para detectar cambios; hash para evitar repetir extracción de contenido idéntico. El checkpoint no depende únicamente de memoria de la ejecución n8n.
- Reintentos limitados con espera creciente para 429/5xx y cortes de conexión; respetar `Retry-After`. Errores de contrato o credencial requieren revisión.
- Timeout del extractor acorde a su duración (su documentación propone hasta 300 segundos); esa espera ocurre en n8n, nunca en una petición web larga de Laravel.
- Persistir el resultado recibido antes de repetir pasos posteriores cuando sea posible. Si la API no ofrece idempotencia/consulta por solicitud, un timeout de extracción puede implicar volver a pagar el procesamiento: documentar esta limitación.
- No mover, borrar ni renombrar PDFs de Drive por defecto. Un archivo fallido no bloquea todo el lote.

### B. Consulta de correos de cPanel

Conservar `docs/n8n/email-search-cpanel.json` y el webhook de producción `/webhook/assistant/email/search`.

El chatbot decide filtros, Laravel resuelve nombres a correos de clientes/proveedores y n8n consulta IMAP. Devolver fecha, remitente/destinatario, asunto, IDs, nombres de adjuntos y fragmentos limitados. Mantener el paso de obtener cada correo tras buscar sus UID.

Para combinar remitente/asunto/fechas, validar Advanced Search contra la versión instalada del nodo comunitario; el flujo documentado actual usa Simple Search. No asumir nombres de operaciones ni compatibilidad de parámetros sin probar una exportación de esa instancia.

Este workflow permanece de lectura: buscar un correo no debe importar sus PDFs ni modificar su estado de leído. Los PDFs se procesan en el flujo C.

### C. PDFs existentes y adjuntos de correo (segunda etapa)

Archivo futuro: `docs/n8n/invoice-existing-pdf-import.json`.

Prioridad inicial: PDFs **ya asociados a facturas** en R2. Schedule/manual → consultar `/documents/pending` con cursor → reservar con `invoice_id/file_id` conocidos → obtener PDF → extractor → resultado JSON. Verificar que la identidad extraída coincida con la factura vinculada; no reasignarla automáticamente. Omitir carga R2 porque el archivo ya existe.

Añadir entrada webhook protegida para nuevas subidas desde Laravel, reutilizando el aviso existente al workflow PDF. El webhook confirma recepción rápidamente; la orquestación continúa en n8n. El cron recupera avisos perdidos mediante la consulta de pendientes.

Extensión opcional: adjuntos PDF de correos actuales/nuevos. Buscar desde un checkpoint persistente, obtener adjunto binario y entrar al mismo contrato de reserva/subida/extracción. Identificar por cuenta + carpeta + UIDVALIDITY + UID + parte del adjunto; guardar `Message-ID` como referencia adicional y SHA-256 para deduplicar entre Drive/correo/R2.

No depender solo de «no leídos»: un usuario puede leer un mensaje antes del cron. No modificar mensajes ni descargar archivos de enlaces externos arbitrarios. Los PDFs cifrados, documentos no admitidos o adjuntos ilegibles van a revisión.

Los tres flujos comparten reglas en Laravel. Si el flujo C crece, separar el origen correo en un cuarto workflow o un subworkflow reutilizable; no duplicar lógica de facturas en cada JSON.

## 9. Búsqueda rápida y ahorro de tokens del chatbot

Ampliar `AssistantDataService` y las herramientas declaradas al modelo, manteniendo autorización en el servidor:

| Herramienta propuesta | Uso |
|---|---|
| `search_invoice_items` | Producto, proveedor/cliente, folio, rango de fechas, moneda, precio y paginación |
| `invoice_item_details` | Líneas de una factura concreta y su fuente, por páginas |
| `summarize_invoice_items` | SUM/COUNT/AVG/MIN/MAX por producto, mes o contraparte sobre todo el conjunto filtrado |
| `product_price_history` | Historial y comparación con unidad/moneda/base de impuestos compatibles |
| `document_extraction_details` | Campos JSON seleccionados o fragmentos del texto auxiliar cuando faltan datos |

Reutilizar las herramientas existentes de facturas y cotizaciones para cabeceras/totales. El total de compras se calcula desde facturas; no volver a sumar esos totales por cada línea de producto. Distinguir promedio simple de precios del promedio ponderado por cantidad y explicitar cuál se usa.

Índices candidatos: identidad fiscal; contraparte + fecha; fecha de modificación; `invoice_line_items(invoice_id, extraction_id)`; producto normalizado + factura; SKU dentro de proveedor; FULLTEXT de descripción cuando el motor lo soporte. Revisar los existentes y comprobar con EXPLAIN antes de añadir índices redundantes. El FULLTEXT de Markdown actual queda como respaldo histórico.

Búsqueda en dos pasos: devolver IDs, campos breves y conteo; recuperar detalles solo de coincidencias pertinentes. Límites iniciales propuestos: 10 resultados y 20 líneas por página, con cursor y `has_more`. Los cálculos agregados jamás se hacen sobre esa página limitada; se ejecutan en SQL sobre todas las filas filtradas.

Cada resultado debe informar filtros/periodo, número de coincidencias, moneda, cobertura y procedencia. Si de 100 facturas solo 30 tienen líneas extraídas, no presentar sus productos como todas las compras. Añadir cobertura por documentos procesados/sin detalle/en revisión, sin afirmar que la extracción es completa solo por existir JSON.

Fechas relativas resueltas por el servidor en `America/Santiago`; enviar al modelo fecha actual y filtros efectivos. Consultas operativas sin periodo usan el año actual según la regla acordada; «histórico», «últimas compras» o un folio específico pueden requerir otros años y no deben quedar ocultos por ese valor predeterminado.

Mantener normalización de montos (`10.990`/`10990`) y folio en ambas contrapartes salvo filtro explícito. Los códigos/dimensiones como «tubo 1/2» necesitan búsqueda exacta/normalizada complementaria: FULLTEXT por sí solo puede descartarlos.

Normalizar sinónimos y unidades mediante alias revisables en BD. No comparar precio por caja con precio por unidad, ni mezclar monedas, impuestos o medidas diferentes. Inicialmente no es necesario un motor vectorial; evaluar búsqueda semántica después de medir consultas reales que el catálogo y FULLTEXT no resuelvan.

El contenido de PDFs/correos es dato no confiable, nunca una instrucción para ejecutar herramientas. El modelo solo consulta herramientas acotadas; no obtiene SQL libre, secretos, URLs de carga o el JSON completo por defecto. Reutilizar auditoría, cuota y persistencia del chat. Separar costes de extracción del consumo del chatbot.

## 10. Administración y configuración

En Información de sistema añadir estado de integración: última ejecución, pendientes, completados, fallidos, revisión, duración y cobertura de detalles. Vista de revisión con PDF, campos extraídos, diferencias y acciones aprobar/reintentar/rechazar; solo administradores.

Indicadores por archivo: PDF disponible, JSON extraído, detalle validado, sin productos, pendiente/error. En la lista de facturas mostrar cobertura, por ejemplo «2/3 PDFs procesados», y cuál es la extracción activa para productos. Conservar compatibilidad con la etiqueta histórica de Markdown, sin confundir ambos estados.

Configuración editable en BD: activación de importación, límites, reglas de revisión, versión de procesamiento y RUT de la empresa. El horario efectivo y carpeta usados en n8n deben tener una única fuente definida; si se exponen en BD, n8n debe leerlos, no mantener copias contradictorias.

Configurar en `.env` de Laravel `INVOICE_OWN_RUT` y opcionalmente `INVOICE_MIN_EXTRACTION_CONFIDENCE` (por defecto `0.80`). El RUT no se debe incluir en el JSON del workflow. Tras editar `.env` en cPanel, refrescar la caché de configuración con el procedimiento disponible.

Secretos en credenciales de n8n y `.env` del servidor: Google OAuth, Bearer extractor, Bearer automatización, R2 e IMAP. No incluirlos en JSON exportados, Git, URLs visibles ni mensajes del chatbot. El switch del chat en `.env` no debe detener importaciones salvo decisión explícita.

## 11. Implementación por fases y entregables

1. **Contratos y muestra — parcial:** ya se recibió una muestra real de líneas. Pendiente versión n8n/nodo IMAP, carpeta Drive, RUT empresa, esquema MySQL real, PDFs de tipos distintos y campos de unidad/impuesto.
2. **BD y API — en curso:** migración, SQL manual y endpoints base ya están en el repositorio. Pendientes reglas robustas de matching, auditoría, revisión de baja confianza, verificación del hash, idempotencia concurrente y pruebas.
3. **Workflow A — borrador creado:** importar inactivo, asignar credenciales y validar nodos/expresiones con la versión instalada; probar duplicados y lote controlado antes del cron.
4. **Chatbot — pendiente:** herramientas de líneas/historial/estadísticas, cobertura y fuentes; actualizar prompt configurable sin sobrescribir personalizaciones.
5. **Workflow C y backfill — borrador creado:** validar reproceso de PDFs registrados; luego evaluar adjuntos de correo.
6. **Operación — pendiente:** revisión administrativa, métricas, alertas y recuperación de fallos.

Entregables pendientes: workflow de adjuntos de correo, actualización controlada de `email-search-cpanel.json`, pruebas y manual de recuperación. La migración, SQL manual, endpoints base y borradores Drive/backfill están en el repositorio, pero aún no listos para desplegar sin validación.

## 12. Despliegue cPanel sin SSH

1. Respaldar BD y archivos; probar primero con copia de datos.
2. Preparar SQL idempotente compatible con el esquema real y guía de versión. Importar SQL no registra automáticamente migraciones en la tabla `migrations`; documentar reconciliación para que una ejecución posterior no intente crear todo nuevamente.
3. Hacer pull desde Git de cPanel siguiendo el procedimiento del README. Coordinar SQL y código compatible para evitar vistas/endpoints que dependan de tablas aún ausentes.
4. Importar SQL desde phpMyAdmin. No recrear ni vaciar tablas existentes.
5. Solo si cambian dependencias, generar `vendor.zip` y extraer según el procedimiento vigente; no requiere nuevas librerías por el mero hecho de guardar JSON.
6. Configurar variables y refrescar cachés con el mecanismo disponible/documentado en el proyecto; no asumir acceso a terminal ni habilitar rutas públicas para ejecutar comandos.
7. Importar workflows inactivos, asignar credenciales, probar URLs de producción y habilitar cron después de la verificación.

n8n ejecuta las tareas largas; Laravel recibe peticiones breves. No depender de workers persistentes de colas ni de cron de Laravel no disponibles en este cPanel. Ante problemas, desactivar los workflows y mantener el sistema anterior; no borrar extracciones ni originales para revertir.

## 13. Pruebas de aceptación

- Una factura existente recibe PDF/JSON/productos sin perder pago ni correcciones manuales.
- Una factura nueva válida se crea con contraparte correcta, fecha de emisión y fecha de ingreso diferenciadas.
- Mismo folio en proveedores distintos o en compra/venta no mezcla registros.
- Mismo PDF desde Drive y correo no duplica factura, archivo activo ni líneas.
- Dos ejecuciones simultáneas y un callback repetido producen una sola importación efectiva.
- PDF actualizado o extracción corregida conserva versiones y reemplaza la selección activa sin sumar duplicados.
- Falta de RUT, cabecera contradictoria, moneda no admitida, boleta ambigua o confianza insuficiente quedan en revisión.
- Un 200 del extractor sin datos no marca detalle validado.
- R2 cargado + fallo de BD, URL caducada, 429, timeout y reinicio n8n permiten recuperación documentada.
- Backfill paginado procesa más de un lote y no omite documentos al avanzar checkpoint.
- Consultas de precios y sumas coinciden con consultas SQL de control, informan cobertura y respetan unidades/moneda.
- Buscar un folio muestra ambas contrapartes; «este año» usa el año actual; correo y productos respetan permisos.
- Usuarios no autorizados no acceden a la API administrativa ni pueden invocar importación con tokens del chat.
- PDFs ya asociados usan su objeto R2 existente; no se vuelven a subir por rutina.

## 14. Datos pendientes antes de generar los JSON definitivos

- ID de carpeta Drive, inclusión de subcarpetas/unidades compartidas y credencial Google con acceso.
- Respuesta real de la API con `line_items` poblado y ejemplos de boleta/nota de crédito.
- RUT o RUTs propios del taller y política para documentos no reconocidos.
- Versión de n8n y exportación del nodo IMAP que funciona actualmente.
- Bucket/prefijo R2 permitido y privacidad del almacenamiento actual.
- Volumen de PDFs, tamaños máximos, ritmo deseado y presupuesto de extracción.
- Confirmar si «adjuntos actuales» comprende PDFs registrados, adjuntos de correo o ambos; el plan cubre ambos y prioriza los registrados.
- Esquema real de producción, contrapartes duplicadas y reglas para aprobar altas/correcciones.

Estos datos no impiden preparar el diseño, pero sí condicionan una importación automática fiable y la configuración final de los workflows.
