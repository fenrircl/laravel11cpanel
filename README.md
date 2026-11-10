<p align="center"><a href="https://laravel.com" target="_blank"><img src="https://raw.githubusercontent.com/laravel/art/master/logo-lockup/5%20SVG/2%20CMYK/1%20Full%20Color/laravel-logolockup-cmyk-red.svg" width="400" alt="Laravel Logo"></a></p>

<p align="center">
<a href="https://github.com/laravel/framework/actions"><img src="https://github.com/laravel/framework/workflows/tests/badge.svg" alt="Build Status"></a>
<a href="https://packagist.org/packages/laravel/framework"><img src="https://img.shields.io/packagist/dt/laravel/framework" alt="Total Downloads"></a>
<a href="https://packagist.org/packages/laravel/framework"><img src="https://img.shields.io/packagist/v/laravel/framework" alt="Latest Stable Version"></a>
<a href="https://packagist.org/packages/laravel/framework"><img src="https://img.shields.io/packagist/l/laravel/framework" alt="License"></a>
</p>

## About Laravel

Laravel is a web application framework with expressive, elegant syntax. We believe development must be an enjoyable and creative experience to be truly fulfilling. Laravel takes the pain out of development by easing common tasks used in many web projects, such as:

- [Simple, fast routing engine](https://laravel.com/docs/routing).
- [Powerful dependency injection container](https://laravel.com/docs/container).
- Multiple back-ends for [session](https://laravel.com/docs/session) and [cache](https://laravel.com/docs/cache) storage.
- Expressive, intuitive [database ORM](https://laravel.com/docs/eloquent).
- Database agnostic [schema migrations](https://laravel.com/docs/migrations).
- [Robust background job processing](https://laravel.com/docs/queues).
- [Real-time event broadcasting](https://laravel.com/docs/broadcasting).

Laravel is accessible, powerful, and provides tools required for large, robust applications.

## Learning Laravel

Laravel has the most extensive and thorough [documentation](https://laravel.com/docs) and video tutorial library of all modern web application frameworks, making it a breeze to get started with the framework.

You may also try the [Laravel Bootcamp](https://bootcamp.laravel.com), where you will be guided through building a modern Laravel application from scratch.

If you don't feel like reading, [Laracasts](https://laracasts.com) can help. Laracasts contains thousands of video tutorials on a range of topics including Laravel, modern PHP, unit testing, and JavaScript. Boost your skills by digging into our comprehensive video library.

## Laravel Sponsors

We would like to extend our thanks to the following sponsors for funding Laravel development. If you are interested in becoming a sponsor, please visit the [Laravel Partners program](https://partners.laravel.com).

### Premium Partners

- **[Vehikl](https://vehikl.com/)**
- **[Tighten Co.](https://tighten.co)**
- **[WebReinvent](https://webreinvent.com/)**
- **[Kirschbaum Development Group](https://kirschbaumdevelopment.com)**
- **[64 Robots](https://64robots.com)**
- **[Curotec](https://www.curotec.com/services/technologies/laravel/)**
- **[Cyber-Duck](https://cyber-duck.co.uk)**
- **[DevSquad](https://devsquad.com/hire-laravel-developers)**
- **[Jump24](https://jump24.co.uk)**
- **[Redberry](https://redberry.international/laravel/)**
- **[Active Logic](https://activelogic.com)**
- **[byte5](https://byte5.de)**
- **[OP.GG](https://op.gg)**

## Contributing

Thank you for considering contributing to the Laravel framework! The contribution guide can be found in the [Laravel documentation](https://laravel.com/docs/contributions).

## Code of Conduct

In order to ensure that the Laravel community is welcoming to all, please review and abide by the [Code of Conduct](https://laravel.com/docs/contributions#code-of-conduct).

## Security Vulnerabilities

If you discover a security vulnerability within Laravel, please send an e-mail to Taylor Otwell via [taylor@laravel.com](mailto:taylor@laravel.com). All security vulnerabilities will be promptly addressed.

## License

The Laravel framework is open-sourced software licensed under the [MIT license](https://opensource.org/licenses/MIT).

## Actualización y despliegue en cPanel

La aplicación se despliega desde Git en cPanel y las dependencias de PHP se publican en `vendor.zip`, porque el servidor no tiene acceso SSH ni ejecuta Composer.

1. Antes de actualizar, crea una copia de seguridad de los archivos de la aplicación y de la base de datos.
2. En cPanel, usa Git Version Control para hacer `Pull` del repositorio.
3. En el Administrador de archivos, localiza `vendor.zip` en la raíz del proyecto y elige **Extraer** en esa misma ubicación. Confirma que se sobrescriba la carpeta `vendor` existente. El ZIP contiene la carpeta `vendor/`.
4. Comprueba que el sitio use PHP 8.2 o superior y abre la aplicación. Si el hosting ofrece una opción para limpiar la caché de Laravel, úsala después de la actualización.

`composer.json` y `composer.lock` describen las dependencias; `vendor.zip` contiene las dependencias de producción ya instaladas y optimizadas. Al actualizar dependencias en desarrollo, hay que regenerar y versionar `composer.json`, `composer.lock` y `vendor.zip` para que el despliegue por cPanel quede sincronizado.

Esta actualización sube Laravel de la rama 11 a Laravel 12.71.0 para incluir las correcciones de seguridad. Conviene revisar el funcionamiento de las pantallas principales y de los flujos de autenticación después de desplegarla.

## Asistente y conexión con ChatGPT

El widget queda apagado por defecto. Para activarlo en el `.env` del servidor, configura `ASSISTANT_CHAT_ENABLED=true` y `OPENAI_API_KEY`; para apagarlo, cambia el flag a `false`. El prompt, modelo, razonamiento y máximo de tokens de salida se editan desde **Administración → Información del sistema** y quedan guardados en la base de datos. La API key y el interruptor de activación permanecen en `.env`. El historial completo queda en la base de datos por usuario, mientras cada llamada envía como máximo los últimos cuatro turnos para controlar tokens.

La sección **Auditoría de uso del chatbot** de esa misma pantalla registra usuario, mensajes, modelo, tokens de entrada/salida, llamadas a funciones, duración y estado. Para la Action de ChatGPT, el servidor registra las consultas de datos pero no puede conocer los tokens que ChatGPT factura al usuario.

El asistente puede buscar clientes, proveedores, facturas y cotizaciones; listar las facturas/cotizaciones de un cliente; consultar el detalle completo de una factura o cotización; encontrar facturas que vencen este mes o que ya están atrasadas (por defecto, del año actual; puede consultarse otro año si el usuario lo pide); y sumar saldos pendientes por proveedor. Los resúmenes permiten filtrar por fecha de emisión, vencimiento o pago. Estas herramientas son de solo lectura y devuelven resultados acotados.

El chatbot también puede buscar correos recibidos y enviados mediante el webhook IMAP de n8n. Configura `N8N_EMAIL_SEARCH_WEBHOOK_URL`, `N8N_EMAIL_SEARCH_BEARER` y `N8N_EMAIL_SEARCH_TIMEOUT` en `.env`; el asistente pasa filtros concretos (número de factura, remitente, destinatario, asunto y fechas) y recibe un máximo de 10 resultados compactos. El workflow requiere el nodo comunitario IMAP documentado en [`docs/n8n/README.md`](docs/n8n/README.md). Los adjuntos se identifican por nombre; su contenido PDF se obtiene a través del flujo PDF separado. La búsqueda y comparación histórica de precios de productos sigue dependiendo de que n8n extraiga las líneas de factura y de que se almacenen normalizadas.

En cPanel, importa una sola vez [`docs/sql/assistant-chat-schema.sql`](docs/sql/assistant-chat-schema.sql) desde phpMyAdmin después de respaldar la base de datos. Ese SQL crea las tablas de conversaciones, tokens, configuración, auditoría de uso y contexto de documentos. Las tablas del asistente guardan `user_id` e índices, pero no agregan claves foráneas hacia `users`, porque bases heredadas de cPanel pueden definir `users.id` con tipos distintos. Al actualizar un despliegue ya existente, importa la versión actualizada del SQL; las instrucciones `IF NOT EXISTS` conservan las tablas y datos existentes y crean las nuevas. La guía del workflow n8n para PDFs está en [`docs/n8n-invoice-pdf-workflow.md`](docs/n8n-invoice-pdf-workflow.md), y la base IMAP para correo cPanel junto con sus límites actuales está en [`docs/n8n/README.md`](docs/n8n/README.md).

El menú **Acceso API** y la creación/revocación de tokens están disponibles solo para usuarios con el rol `admin`. La API también rechaza tokens si su usuario asociado no conserva ese rol. Para conectar un GPT personalizado, un administrador inicia sesión, crea un token con vencimiento y copia el secreto cuando se muestre. En la Action de ChatGPT configura autenticación **Bearer** e importa [`docs/chatgpt-actions-openapi.yaml`](docs/chatgpt-actions-openapi.yaml), reemplazando `YOUR-DOMAIN.example` por el dominio HTTPS de cPanel. El token solo permite búsquedas de lectura y actúa con los permisos del administrador asociado. Revócalo desde **Acceso API** cuando ya no lo necesites.

El asistente permite combinar criterios de facturas: número, cliente/proveedor, monto exacto o rango, fechas y estado. Los montos CLP se buscan igual con o sin separadores (por ejemplo, 10.990 y 10990). Los números de factura buscan en clientes y proveedores salvo que se especifique el tipo. Acepta fechas como hoy, ayer, 10 de noviembre, 10/11 y 10-11; si no se indica año, usa el año actual. Para fecha de última modificación, indica “modificada”; para fecha de emisión, basta con “factura de hoy”. En búsquedas de modificadas se incluye el usuario de auditoría solo cuando coincide con la fecha y hora del cambio. El endpoint es POST /api/v1/assistant/invoices/search; los filtros enviados se combinan.
