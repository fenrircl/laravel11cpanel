# Graph Report - laravel11cpanel  (2026-11-08)

## Corpus Check
- 194 files · ~108,877 words
- Verdict: corpus is large enough that graph structure adds value.
- Unclassified: 34 file(s) not represented in the graph (top: (none) 19, .css 11, .example 1)

## Summary
- 1398 nodes · 2252 edges · 154 communities (75 shown, 79 thin omitted)
- Extraction: 89% EXTRACTED · 11% INFERRED · 0% AMBIGUOUS · INFERRED: 250 edges (avg confidence: 0.82)
- Token cost: 104,200 input · 42,400 output

## Community Hubs (Navigation)
- HTTP Middleware & Request Pipeline
- R2 File Storage Controller
- Composer Autoload & Dependencies
- Node.js Backend Patterns Guide
- Web Accessibility (WCAG) Guide
- Cotizaciones Controller CRUD
- Admin Audit Logs & Roles
- Global JS UI Helpers
- Auth Views & Home Dashboard
- Búsqueda & Facturas Controllers
- Facturas Client DataTables UI
- SEO Structured Data Reference
- Global Search Widget
- Client-Side Search Cache
- Core Users/Cache/Jobs Migrations
- DataTables Endpoints & UI Feeds
- Facturas Controller Endpoints
- Application Configuration Domains
- MCP Sessions, Roles & Audit Schema
- Métodos de Pago Controller
- In-Memory Entity Data Store
- Audit Log Controller Queries
- Vite Build & SSR
- Tailwind CSS Patterns
- Clientes Controller CRUD
- Factura Files UI Component
- Cotización PDF Populator
- Layout JS Bootstrap
- Sidebar Navigation & Proveedores View
- Laravel API Design Patterns
- Roles & Users Authorization
- Factura Days-to-Expiry UI
- MCP Controller & App Bootstrap
- Audit Restore & PDF Views
- Modern PHP 8.3+ Features
- Login & Auth Routes/Views
- Proveedores Views & Routes
- Frontend Build Dependencies
- Livewire Patterns Reference
- Laravel Testing Guide
- Búsqueda Controller Search
- Blade Helper Functions
- Config & User Factory
- Admin Roles/Audit Routes
- Queue & Horizon Guide
- Symfony & PHP Attributes Guide
- Tailwind Accessibility Patterns
- Admin Users Management
- HTTP Kernel Wiring
- Action Button Builder
- Admin Users Forms & Views
- Eloquent ORM & Performance
- Service Layer & Repository Patterns
- PHP Testing & Static Analysis
- Admin Roles Forms & Views
- Tailwind Layout & Responsive
- Aceros Era Brand Assets
- App Service Provider & Menu Config
- Chatbot Widget Script
- Métodos de Pago UI Script
- Feature Test Scaffold
- Logging Configuration
- Header Navigation & Logout
- Facturas Cliente Fixed Script
- Facturas Proveedor Script
- Facturas Clientes View
- Authenticate Middleware
- API / MCP Endpoints
- Jobs & Queue Tables
- Unit Test Scaffold
- Image Placeholders & Icons
- Cotización View Scripts
- Vite Library Mode
- Vite Config Environment
- EncryptCookies Middleware
- Maintenance Mode Middleware
- TrimStrings Middleware
- TrustProxies Middleware
- CSRF Verification Middleware
- Cache & Locks Tables
- Cotizaciones Email Column
- Layout Header & Sidebar Partials
- Facturas JS Actions
- Factura Quick View Handlers
- Proveedor Edit/Show Flow
- CSS Transitions Reference
- Clientes Attachments Partial
- Public Image Redirect Stub
- Robots Crawler Policy
- Laravel Front Controller Bootstrap
- Cotización PDF Upload Route
- Proveedores Attachments Partial
- Cotizaciones DataTable Script
- Cotización PDF Route
- Cotización R2 Export Route
- Payment Check Handler (clientes)
- Payment Check Handler (proveedores)
- Método Pago Status Toggle
- R2 Download Handler
- Proveedores DataTable Script
- Cotización Index & PDF Views
- Cotización PDF Index View
- Tailwind Typography Utilities
- Vite WebAssembly Support
- R2 Delete Route
- Ver Factura (proveedores)
- Editar Método Pago Handler
- Ver Método Pago Handler
- Eliminar Proveedor Stub
- Laravel Framework Overview
- Resources JS Entrypoint
- API Echo Route
- API Saludo Route
- Artisan Inspire Command
- Cache Clear Route
- Laravel Welcome Page

## God Nodes (most connected - your core abstractions)
1. `Factura` - 45 edges
2. `SearchCacheManager` - 37 edges
3. `AuditLogger` - 34 edges
4. `Cliente` - 33 edges
5. `GlobalSearchManager` - 33 edges
6. `User` - 29 edges
7. `FilesRegistry` - 28 edges
8. `Cotizacion` - 26 edges
9. `Proveedor` - 26 edges
10. `R2Controller` - 23 edges

## Surprising Connections (you probably didn't know these)
- `fetchWithXRequestedWith` --semantically_similar_to--> `loadAndPopulate()`  [INFERRED] [semantically similar]
  resources/js/bootstrap.js → public/assets/js/cotizaciones/cotizacion_pdf.js
- `fetchWithXRequestedWith` --semantically_similar_to--> `btnConfirmSend Handler (PDF + upload + email)`  [INFERRED] [semantically similar]
  resources/js/bootstrap.js → public/assets/js/cotizaciones/cotizaciones.js
- `VerifyApiBearerToken (auth.bearer)` --references--> `User`  [INFERRED]
  bootstrap/app.php → app/Models/User.php
- `Laravel development patterns skill` --conceptually_related_to--> `laravel/framework ^12.68 requirement`  [INFERRED]
  .agents/skills/laravel-patterns/SKILL.md → composer.json
- `MUST / MUST NOT constraints (PHP 8.2+, PSR-12, >85% coverage)` --conceptually_related_to--> `laravel/framework ^12.68 requirement`  [INFERRED]
  .agents/skills/laravel-specialist/SKILL.md → composer.json

## Import Cycles
- None detected.

## Hyperedges (group relationships)
- **cPanel deployment pipeline** — readme_cpanel_deployment, readme_vendor_zip, readme_laravel12_upgrade, composer_json_manifest, artisan_cli [INFERRED 0.85]
- **WCAG 2.2 accessibility guidance set** — agents_skills_accessibility_skill, agents_skills_accessibility_references_wcag_quick_reference, agents_skills_accessibility_references_a11y_patterns_doc, accessibility_evidence_led_audit, accessibility_conformance_levels [INFERRED 0.85]
- **Laravel architecture guidance pair** — agents_skills_laravel_patterns_skill, agents_skills_laravel_specialist_skill, laravel_patterns_layer_boundaries, laravel_specialist_core_workflow [INFERRED 0.85]
- **Layered Node.js Backend Stack** — agents_skills_nodejs_best_practices_skill_architecture_principles, agents_skills_nodejs_backend_patterns_references_details_layered_architecture, agents_skills_nodejs_backend_patterns_references_advanced_patterns_di_container [INFERRED 0.85]
- **Technical SEO Control Set** — agents_skills_seo_skill_technical_seo, agents_skills_seo_skill_crawlability, agents_skills_seo_skill_xml_sitemap, agents_skills_seo_skill_url_structure [EXTRACTED 1.00]
- **Async PHP Runtime Options** — agents_skills_php_pro_references_async_patterns_swoole_http_server, agents_skills_php_pro_references_async_patterns_reactphp_event_loop, agents_skills_php_pro_references_async_patterns_amphp_framework, agents_skills_php_pro_references_async_patterns_php_fibers [EXTRACTED 1.00]
- **Tailwind CSS-first design system (@theme, @utility, @custom-variant)** — agents_skills_tailwind_css_patterns_references_configuration_css_first_configuration, agents_skills_tailwind_css_patterns_references_reference_css_first_theme, agents_skills_tailwind_css_patterns_references_configuration_custom_utilities, agents_skills_tailwind_css_patterns_references_reference_custom_variants [INFERRED 0.85]
- **WCAG accessibility compliance concerns in Tailwind components** — agents_skills_tailwind_css_patterns_references_accessibility_focus_management, agents_skills_tailwind_css_patterns_references_accessibility_color_contrast, agents_skills_tailwind_css_patterns_references_accessibility_screen_reader_support, agents_skills_tailwind_css_patterns_references_animations_reduced_motion [INFERRED 0.85]
- **Vite 8 Rolldown/Oxc plugin and environment architecture** — agents_skills_vite_references_rolldown_migration_rolldown_oxc, agents_skills_vite_references_core_plugin_api_plugin_api, agents_skills_vite_references_environment_api_environment_api, agents_skills_vite_references_build_and_ssr_create_builder [INFERRED 0.85]
- **Invoice audit trail creation and restoration flow** — app_http_controllers_auditlogcontroller_auditlogcontroller, app_http_controllers_admin_auditlogscontroller, app_services_auditlogger, app_models_auditlog_auditlog, app_models_factura_factura [EXTRACTED 1.00]
- **R2 storage attachment flow shared by clients, providers, quotations and invoices** — app_http_controllers_r2controller_r2controller, app_models_filesregistry_filesregistry, app_http_controllers_clientescontroller_clientescontroller, app_http_controllers_proveedorescontroller_proveedorescontroller, app_http_controllers_cotizacionescontroller_cotizacionescontroller, concepts_r2_storage [EXTRACTED 1.00]
- **Invoice resource shared across invoice, search and chart controllers** — app_http_controllers_facturascontroller_facturascontroller, app_http_controllers_busquedacontroller_busquedacontroller, app_http_controllers_chartscontroller_chartscontroller, app_http_controllers_metodopagocontroller_metodopagocontroller, app_models_factura_factura, app_models_cliente_cliente, app_models_proveedor_proveedor, db_invoices [INFERRED 0.85]
- **Global + web Middleware Pipeline (proxy trust, maintenance, trimming, cookies, CSRF, UTF-8)** — app_http_kernel_middleware, app_http_middleware_trustproxies_headers, app_http_middleware_preventrequestsduringmaintenance_except, app_http_middleware_trimstrings_except, app_http_middleware_encryptcookies_except, app_http_middleware_verifycsrftoken_except, app_http_middleware_ensureutf8response_handle [EXTRACTED 1.00]
- **Session Auth Chain: guest redirect, auth redirect, user context, admin role guard** — app_http_middleware_redirectifauthenticated_handle, app_http_middleware_authenticate_redirectto, app_http_middleware_usercontextmiddleware_usercontextmiddleware_handle, app_http_middleware_adminrolemiddleware_adminrolemiddleware_handle, app_models_user_hasrole [INFERRED 0.85]
- **MCP/API Gateway: bearer token verification, CSRF exemptions, JSON UTF-8 enforcement** — app_http_middleware_verifyapibearertoken_handle, config_services_mcp_bearer, app_http_middleware_verifycsrftoken_except, app_http_middleware_ensureutf8response_handle [INFERRED 0.75]
- **Invoice data model cluster (dual client/provider ownership, payment method, file attachment)** — app_models_factura_factura, app_models_cliente_cliente, app_models_proveedor_proveedor, app_models_metodopago_metodopago, app_models_filesregistry_filesregistry [EXTRACTED 1.00]
- **Quotation data model cluster (header, line items, client)** — app_models_cotizacion_cotizacion, app_models_cotizacionitem_cotizacionitem, app_models_cliente_cliente [EXTRACTED 1.00]
- **Audit trail pipeline (log entry, user attribution, change diff)** — app_services_auditlogger_auditlogger_log, app_models_auditlog_auditlog, app_models_user_user, app_services_auditlogger_auditlogger_simplediff [INFERRED 0.85]
- **Laravel Runtime Configuration Group** — config_app_application_config, config_auth_authentication_config, config_cache_cache_config, config_database_database_config, config_filesystems_filesystem_config, config_queue_queue_config, config_session_session_config, config_logging_logging_config [INFERRED 0.95]
- **MCP Server Integration Group** — config_mcp_mcp_server_config, config_mcp_mcp_discovery, config_mcp_mcp_transports, config_mcp_mcp_session_management, config_services_mcp_bearer_token, config_cache_cache_config, config_logging_logging_config [EXTRACTED 1.00]
- **cPanel Shared-Hosting Deployment Group** — cpanel_public_index_front_controller, config_app_application_config, config_app_maintenance_mode, config_filesystems_filesystem_config, config_session_session_config [INFERRED 0.75]
- **Local cache + server global search pipeline** — public_assets_js_comun_cache_local_searchcachemanager, public_assets_js_comun_buscador_global_globalsearchmanager, public_assets_js_comun_main_buildapiurl, concept_localstorage_layer, api_entity_search_data [INFERRED 0.85]
- **DataTables to ENTITY_DATA to action buttons pipeline** — public_assets_js_comun_main_initdatatable, public_assets_js_comun_main_entitydatamanager, public_assets_js_comun_main_actionbuttonfactory, public_assets_js_comun_main_generateactionbuttons, public_assets_js_clientes_clientes_js, public_assets_js_clientes_cliente_show_js [INFERRED 0.85]
- **Audit logging schema with diff columns and user attribution** — migrations_audit_logs_table, migrations_alter_audit_logs_diff_columns, migrations_users_table, concept_audit_trail [EXTRACTED 1.00]
- **Facturas UI Script Cluster (clientes, proveedores, mixed, filters)** — public_assets_js_facturas_facturas_script, public_assets_js_facturas_facturas_cliente_script, public_assets_js_facturas_facturas_proveedor_script, public_assets_js_facturas_facturas_cliente_fixed_script, public_assets_js_facturas_filtros_attachinvoicefilters, facturas_index_view, facturas_clientes_index_view, facturas_proveedores_index_view [INFERRED 0.95]
- **Pending Invoice Filter Pipeline (Home -> URL/sessionStorage -> DataTables filters)** — public_assets_js_home_home_inithometables, public_assets_js_facturas_filtros_filterstatemanager, public_assets_js_facturas_filtros_attachinvoicefilters, public_assets_js_facturas_filtros_datatable_search_filter, public_assets_js_facturas_facturas_cliente_pending_filter, public_assets_js_facturas_facturas_proveedor_pending_filter [INFERRED 0.95]
- **Cotización PDF Generation & Email Pipeline** — public_assets_js_cotizaciones_cotizacion_pdf_populatequotationforpdf, public_assets_js_cotizaciones_cotizacion_pdf_download_handler, public_assets_js_cotizaciones_cotizaciones_confirmsend_handler, cotizacion_pdf_view, cotizacion_index_pdf_view [INFERRED 0.95]
- **Cotizacion CRUD & PDF View Set** — cotizacion_index_index_view, cotizacion_create_create_view, cotizacion_edit_edit_view, cotizacion_show_show_view, cotizacion_pdf_pdf_layout, cotizacion_index_pdf_index_pdf_view [EXTRACTED 1.00]
- **Facturas Management View Set and Shared Modal Contract** — views_facturas_index, facturas_clientes_index_clientes_index_view, facturas_proveedores_index_proveedores_index_view, facturas_edit_edit_view, facturas_show_show_view, facturas_index_factura_crud_modal_contract [INFERRED 0.85]
- **Admin Pages Consuming the Audit Log Table Component** — components_audit_log_table_audit_log_table, views_clientes_index, cotizacion_index_index_view, views_facturas_index, facturas_clientes_index_clientes_index_view, facturas_proveedores_index_proveedores_index_view [EXTRACTED 1.00]
- **Authenticated web route group (auth middleware)** — middleware_auth, routes_web_home_route, routes_web_facturas_index_route, routes_web_clientes_index_route, routes_web_proveedores_index_route, routes_web_cotizaciones_index_route, routes_web_metodos_pago_resource_group [EXTRACTED 1.00]
- **Admin prefix group (auth + admin.role)** — middleware_auth, middleware_admin_role, routes_web_admin_route_group, routes_web_admin_users_index_route, routes_web_admin_roles_index_route, routes_web_admin_audit_index_route [EXTRACTED 1.00]
- **Sidebar navigation to named routes** — resources_views_layouts_sidebar_blade, layouts_sidebar_clientes_nav_link, layouts_sidebar_proveedores_nav_link, layouts_sidebar_facturas_clientes_nav_link, layouts_sidebar_cotizaciones_nav_link, routes_web_clientes_index_route, routes_web_proveedores_index_route [INFERRED 0.75]
- **Aceros Era Logo Variant Family (full lockup, circular mark, footer mark, letterhead banner)** — public_img_logo, public_img_logo2, public_img_logo_footer, public_img_header_logo [INFERRED 0.95]
- **Brand Identity Composition (mark + ERA letter ring + wordmark + tagline)** — public_img_logo_brand_identity, img_brand_era_letters, public_img_logo, img_acero_inoxidable_tagline [INFERRED 0.85]

## Communities (154 total, 79 thin omitted)

### Community 0 - "HTTP Middleware & Request Pipeline"
Cohesion: 0.06
Nodes (38): Global HTTP Middleware Stack (Kernel::$middleware), Route Middleware Aliases (admin.role, auth.bearer, signed, guest), AdminRoleMiddleware, is_admin Session Flag Cached by AdminRoleMiddleware, Authenticate::redirectTo (login redirect / JSON passthrough), EncryptCookies Cookie Exception List, EnsureUtf8Response, EnsureUtf8Response::handle (JSON UTF-8 sanitization) (+30 more)

### Community 1 - "R2 File Storage Controller"
Cohesion: 0.08
Nodes (22): R2Controller, FilesRegistry, FilesRegistry::getFileNameAttribute (UTF-8 sanitize), FilesRegistry::getPathAttribute (UTF-8 sanitize), Cloudflare R2 object storage disk, files_registry table, GET /clientes/{cliente}/cotizaciones/files (clientes.cotizaciones.files), GET /clientes/{cliente}/files (clientes.files) (+14 more)

### Community 2 - "Composer Autoload & Dependencies"
Cohesion: 0.04
Nodes (45): pestphp/pest-plugin, php-http/discovery, autoload, autoload-dev, files, psr-4, config, allow-plugins (+37 more)

### Community 3 - "Node.js Backend Patterns Guide"
Cohesion: 0.06
Nodes (45): Queue Rate Limiting, ApiResponse Format Helper, Redis Caching Strategies, Dependency Injection Container, JWT Authentication, MongoDB with Mongoose, Node.js Advanced Patterns, PostgreSQL Connection Pool (+37 more)

### Community 4 - "Web Accessibility (WCAG) Guide"
Cohesion: 0.06
Nodes (41): Accessible authentication (WCAG 3.3.8), WCAG conformance levels A / AA / AAA, Evidence-led audit workflow (Lighthouse + accessibility tree + keyboard), POUR principles (Perceivable, Operable, Understandable, Robust), Target size 24x24 CSS px (WCAG 2.5.8), ARIA tabs pattern, Accessibility code patterns reference, Live regions and notifications pattern (+33 more)

### Community 5 - "Cotizaciones Controller CRUD"
Cohesion: 0.08
Nodes (21): CotizacionesController, Ciudad, Cotizacion, CotizacionItem, OrdenTrabajo, quotation_items table, Mailgun HTTP messages API, Illuminate\Database\Eloquent\Factories\HasFactory (+13 more)

### Community 6 - "Admin Audit Logs & Roles"
Cohesion: 0.08
Nodes (24): Admin\AuditLogsController, AuditLogsController, Admin\RolesController, RolesController, Auth\LoginRegisterController, LoginRegisterController, BackupController, ChartsController (+16 more)

### Community 7 - "Global JS UI Helpers"
Cohesion: 0.06
Nodes (18): ActionButtonPresets, clearFileInput(), ensureFacturaSelectRefreshButtons(), ensureLoaded(), fetchFromServer(), formatDateCL(), getFromLocalCache(), initializeFileInputs() (+10 more)

### Community 8 - "Auth Views & Home Dashboard"
Cohesion: 0.08
Nodes (40): AdminLTE Page Layout (adminlte::page), Home Dashboard View, Home Overdue Invoices Tables (clientes/proveedores), authenticate Route (POST login), Login View, Register View, store Route (POST registration), clientes.update Route (PUT) (+32 more)

### Community 9 - "Búsqueda & Facturas Controllers"
Cohesion: 0.10
Nodes (14): Cliente, Factura, MetodoPago, Proveedor, Illuminate\Support\Facades\Cache, Illuminate\Validation\Rule, Legacy table naming convention (clients/providers/invoices/quotations), GET /charts/facturas/pendientes (charts.facturas.pendientes) (+6 more)

### Community 10 - "Facturas Client DataTables UI"
Cohesion: 0.08
Nodes (31): GET /facturas/clientes/data, DELETE /facturas/{id}, Facturas Cliente DataTable Script (fixed variant), Pending filter from Home/URL (clientes), Facturas Cliente DataTable Script, editarFactura, GET /facturas/data, DELETE /facturas/{id} (proveedores) (+23 more)

### Community 11 - "SEO Structured Data Reference"
Cohesion: 0.08
Nodes (33): Article Schema, BreadcrumbList Schema, FAQ Schema, Organization Schema, Product Schema, Structured Data Reference, Structured Data Validation, Agentic Browsing and AI Discoverability (+25 more)

### Community 12 - "Global Search Widget"
Cohesion: 0.13
Nodes (4): GET /buscar (global search endpoint), window.GlobalSearchTempCache (prefill cache), Header global search widget (#search-input/#search-results), GlobalSearchManager

### Community 13 - "Client-Side Search Cache"
Cohesion: 0.13
Nodes (3): GET {clientes|proveedores|facturas}/search-data, Browser localStorage cache layer, SearchCacheManager

### Community 14 - "Core Users/Cache/Jobs Migrations"
Cohesion: 0.10
Nodes (3): Illuminate\Database\Migrations\Migration, Illuminate\Database\Schema\Blueprint, Illuminate\Support\Facades\Schema

### Community 15 - "DataTables Endpoints & UI Feeds"
Cohesion: 0.11
Nodes (28): GET clientes/data (DataTables feed), GET clientes/{id}, GET facturas/clientes/data (DataTables feed), GET facturas/{id}, GET proveedores/{id}, GET r2/download/{path}, DataTables Spanish (es-ES) language file, Facturas (invoices) UI feature (+20 more)

### Community 16 - "Facturas Controller Endpoints"
Cohesion: 0.15
Nodes (13): FacturasController, ProveedoresController, AuditLogger service, AuditLogger, invoices table, providers table, POST /cotizaciones/{cotizacion}/send-email (cotizaciones.send-email), POST facturas/{factura}/check (facturas.check) (+5 more)

### Community 17 - "Application Configuration Domains"
Cohesion: 0.12
Nodes (24): Application Configuration, Application Encryption Key (AES-256-CBC), Maintenance Mode Driver, Authentication Guards and Providers, Cache Stores Configuration, Database Connections Configuration, Redis Client and Databases, Filesystem Default Disk and Disks (+16 more)

### Community 18 - "MCP Sessions, Roles & Audit Schema"
Cohesion: 0.10
Nodes (21): POST /api/mcp/webhook (N8N proxy), Audit trail / change history, User authentication / accounts feature, MCP server session storage, Role-based access control (roles + role_user), Sanctum personal access tokens (API auth), HTTP session store, DatabaseSeeder (+13 more)

### Community 19 - "Métodos de Pago Controller"
Cohesion: 0.12
Nodes (14): MetodoPagoController, payment_methods table, Métodos de Pago Index View, GET /metodos-pago/create (metodos-pago.create), GET metodos-pago/data (metodos-pago.data), DELETE /metodos-pago/{metodoPago} (metodos-pago.destroy), GET /metodos-pago/{metodoPago}/edit (metodos-pago.edit), GET /metodos-pago (metodos-pago.index) (+6 more)

### Community 20 - "In-Memory Entity Data Store"
Cohesion: 0.15
Nodes (12): window.ENTITY_DATA in-memory entity store, EntityDataManager, reloadInvoiceTables(), saveFactura(), proceedWithSaving(), uploadFileBeforeSaving(), saveFacturaEdit(), showSuccessMessage() (+4 more)

### Community 21 - "Audit Log Controller Queries"
Cohesion: 0.18
Nodes (7): AuditLogController, AuditLog, Illuminate\Support\Arr, GET /api/audit-logs (api.audit-logs), cotizacion.index view, facturas.proveedores.index view, proveedores.index view

### Community 22 - "Vite Build & SSR"
Cohesion: 0.12
Nodes (20): Vite Integration (@tailwindcss/vite), Vite Skill Generation Info, createBuilder (environment-aware builds), Vite JavaScript API (createServer, build, preview), SSR Development (low-level), vite.config.ts Configuration, Asset Import Queries (?url, ?raw, ?worker), import.meta.glob (+12 more)

### Community 23 - "Tailwind CSS Patterns"
Cohesion: 0.11
Nodes (19): Custom Animations via @theme (v4.1+), React Button Component with Variants, CSS-First Configuration (@theme), Custom Utilities (@utility), JavaScript Configuration (Legacy), Tailwind Plugins, Grid Layouts, Spacing Utilities (+11 more)

### Community 24 - "Clientes Controller CRUD"
Cohesion: 0.13
Nodes (12): ClientesController, clients table, GET /clientes/create (clientes.create), GET clientes/data (clientes.data), DELETE /clientes/{cliente} (clientes.destroy), GET /clientes/{cliente}/edit (clientes.edit), Route::resource clientes (except index, param cliente), GET /clientes/{cliente} (clientes.show) (+4 more)

### Community 25 - "Factura Files UI Component"
Cohesion: 0.22
Nodes (3): createFacturaFilesComponent(), displayFacturaFiles(), FacturaFilesComponent

### Community 26 - "Cotización PDF Populator"
Cohesion: 0.14
Nodes (16): PDF Download Button Handler (btnDownloadPdf), GET /cotizaciones/{id} (JSON), getBaseUrl(), loadAndPopulate(), parseId(), populateQuotationForPdf, Cotización PDF Template Populator, abrirEnviarCotizacion (+8 more)

### Community 27 - "Layout JS Bootstrap"
Cohesion: 0.16
Nodes (11): initDataTablesResize(), initModals (reset/focus on modal events), initSidebar(), initTooltips(), isMobile(), resizeAllDataTables (global helper, defined in main.js), Layout & Sidebar Script, setupFormValidation() (+3 more)

### Community 28 - "Sidebar Navigation & Proveedores View"
Cohesion: 0.17
Nodes (14): Sidebar: Auditoria (admin), Sidebar: Clientes, Sidebar: Cotizaciones, Sidebar: Facturas Proveedores, Sidebar: Menu principal, Sidebar: Proveedores, auth Middleware, Proveedores Index View (proveedores-table) (+6 more)

### Community 29 - "Laravel API Design Patterns"
Cohesion: 0.16
Nodes (14): API Resources, API Routes, API Versioning, API Controllers, CORS Configuration, Form Requests, Route Middleware, Resource Collections (+6 more)

### Community 30 - "Roles & Users Authorization"
Cohesion: 0.24
Nodes (7): Role, role_user pivot table, User::hasRole(slug), User, Illuminate\Database\Eloquent\Relations\BelongsToMany, Illuminate\Foundation\Auth\User, Illuminate\Notifications\Notifiable

### Community 31 - "Factura Days-to-Expiry UI"
Cohesion: 0.21
Nodes (9): calculateDaysToExpiry (clientes), handleModalHidden(), initModalEvents (facturaDetailsModal), renderDaysCounter (clientes), calculateDaysToExpiry (home), getRange(), inRange(), makeOptions() (+1 more)

### Community 32 - "MCP Controller & App Bootstrap"
Cohesion: 0.15
Nodes (10): App\Http\Controllers\McpController, AdminRoleMiddleware (admin.role), VerifyApiBearerToken (auth.bearer), Illuminate\Foundation\Application, Illuminate\Foundation\Configuration\Exceptions, Illuminate\Foundation\Configuration\Middleware, Illuminate\Foundation\Inspiring, Illuminate\Support\Facades\Artisan (+2 more)

### Community 33 - "Audit Restore & PDF Views"
Cohesion: 0.18
Nodes (12): Audit logs table, route admin.audit.restore, Restore (revertir) log form, Home Dashboard View (home-* tables, invoiceQuickView), Cotización PDF Page (btnDownloadPdf), Cotización Index View (cotizaciones-table), Cotización PDF printable template (#pdfPrintable), Facturas Clientes Index View (facturas-clientes-table) (+4 more)

### Community 34 - "Modern PHP 8.3+ Features"
Cohesion: 0.23
Nodes (12): Enums with Methods, First-Class Callables, Match Expressions, Modern PHP 8.3+ Features, Never Return Type, Readonly Properties and Classes, Strict Types and Type Declarations, PHP Pro MUST DO / MUST NOT DO Constraints (+4 more)

### Community 35 - "Login & Auth Routes/Views"
Cohesion: 0.20
Nodes (9): Illuminate\View\View, Header: Iniciar sesion (login), LoginRegisterController Guest Route Group, POST /authenticate (authenticate), GET /home guest (LoginRegisterController@home), GET /login (login), POST /store (store), auth.home view (+1 more)

### Community 36 - "Proveedores Views & Routes"
Cohesion: 0.21
Nodes (9): Proveedor Show View (proveedor-facturas-table), GET facturas/proveedores/data (facturas.proveedores.data), GET /proveedores/create (proveedores.create), DELETE /proveedores/{proveedor} (proveedores.destroy), GET /proveedores/{proveedor}/edit (proveedores.edit), Route::resource proveedores (except index, param proveedor), GET /proveedores/{proveedor} (proveedores.show), POST /proveedores (proveedores.store) (+1 more)

### Community 37 - "Frontend Build Dependencies"
Cohesion: 0.21
Nodes (11): devDependencies, concurrently, laravel-vite-plugin, vite, overrides, shell-quote, private, scripts (+3 more)

### Community 38 - "Livewire Patterns Reference"
Cohesion: 0.20
Nodes (11): Eloquent Relationships, Livewire Authorization, Livewire Component Patterns, Livewire Events, Livewire Form Component, Livewire Components, Livewire Loading States, Livewire Polling (+3 more)

### Community 39 - "Laravel Testing Guide"
Cohesion: 0.18
Nodes (11): Dispatching Jobs, Authentication Testing, Database Testing, Feature Tests, Mocking Facades, Pest PHP, Running Tests, Laravel Testing (+3 more)

### Community 40 - "Búsqueda Controller Search"
Cohesion: 0.31
Nodes (4): BusquedaController, Search data cache (Laravel Cache), POST search/clear-cache (search.clear-cache), GET {entidad}/search-data (search.data)

### Community 41 - "Blade Helper Functions"
Cohesion: 0.27
Nodes (10): helper amount_int(), helper assets_version(), helper bool_badge(), helper dt_tz(), helper format_clp(), helper route_active(), helper user_id(), CLP integer money convention (no decimals) (+2 more)

### Community 42 - "Config & User Factory"
Cohesion: 0.22
Nodes (5): UserFactory, Illuminate\Database\Eloquent\Factories\Factory, Illuminate\Support\Facades\Hash, Illuminate\Support\Str, static

### Community 43 - "Admin Roles/Audit Routes"
Cohesion: 0.25
Nodes (11): Sidebar: Roles (admin), Sidebar: Usuarios (admin), admin.role Middleware, GET /admin/audit (admin.audit.index), POST /admin/audit/{log}/restore (admin.audit.restore), DELETE /admin/roles/{role} (admin.roles.destroy), GET /admin/roles (admin.roles.index), POST /admin/roles (admin.roles.store) (+3 more)

### Community 44 - "Queue & Horizon Guide"
Cohesion: 0.24
Nodes (10): Failed Jobs, Horizon Setup, Job Batching, Job Chaining, Job Middleware, Queue Monitoring, Queue Configuration, Queue System (+2 more)

### Community 45 - "Symfony & PHP Attributes Guide"
Cohesion: 0.24
Nodes (10): Event Listeners, PHP Attributes, Console Commands, Controllers with Attributes, Custom Symfony Events, Symfony Dependency Injection, DTOs with Validation, Event Subscribers (+2 more)

### Community 46 - "Tailwind Accessibility Patterns"
Cohesion: 0.22
Nodes (10): ARIA Patterns with Tailwind, Accessibility Checklist, Color Contrast, Focus Management, Motion Preferences (Accessibility), Screen Reader Support, Built-in Animations, Reduced Motion Support (+2 more)

### Community 47 - "Admin Users Management"
Cohesion: 0.20
Nodes (6): Admin\UsersController, UsersController, DELETE /admin/users/{user} (admin.users.destroy), POST /admin/users/{user}/reset-password (admin.users.reset), POST /admin/users (admin.users.store), PUT /admin/users/{user} (admin.users.update)

### Community 48 - "HTTP Kernel Wiring"
Cohesion: 0.24
Nodes (9): AdminRoleMiddleware (admin.role), api Middleware Group (Sanctum), EnsureUtf8Response Middleware, Global Middleware Stack, App Http Kernel, Route Middleware Aliases, VerifyApiBearerToken (auth.bearer), web Middleware Group (+1 more)

### Community 49 - "Action Button Builder"
Cohesion: 0.33
Nodes (4): ActionButtonBuilder, ActionButtonFactory, EntityHelpers, generateActionButtons()

### Community 50 - "Admin Users Forms & Views"
Cohesion: 0.22
Nodes (8): Create user form, route admin.users.destroy, route admin.users.reset, route admin.users.store, route admin.users.update, Reset password form, Users table, admin.users.index view

### Community 51 - "Eloquent ORM & Performance"
Cohesion: 0.28
Nodes (9): Advanced Queries, Custom Casts, Eloquent ORM, Model Events and Observers, Eloquent Performance Tips, Query Optimization, Query Scopes, Livewire Performance Tips (+1 more)

### Community 52 - "Service Layer & Repository Patterns"
Cohesion: 0.28
Nodes (9): Job Patterns, Controllers with DTOs, Form Requests with Enums, Jobs and Queues (php-pro), Laravel Patterns (php-pro), Repository Pattern, Service Layer Pattern, Symfony Messenger Message Handler (+1 more)

### Community 53 - "PHP Testing & Static Analysis"
Cohesion: 0.28
Nodes (9): Code Coverage Configuration, PHPUnit Data Providers, Mockery, PHPStan Annotations, PHPStan Configuration, PHPUnit with Strict Types, Testing and Quality Assurance, PHP Pro Core Workflow (+1 more)

### Community 54 - "Admin Roles Forms & Views"
Cohesion: 0.25
Nodes (7): Create role form, route admin.roles.destroy, route admin.roles.store, route admin.roles.update, Roles table, Inline update role form, admin.roles.index view

### Community 55 - "Tailwind Layout & Responsive"
Cohesion: 0.25
Nodes (8): Card Component Pattern, Navigation Bar Pattern, Responsive Breakpoints Reference, Flexbox Layouts, Dark Mode, Container Queries (v4.1+), Dark Mode Toggle and Best Practices, Mobile-First Responsive Layout

### Community 56 - "Aceros Era Brand Assets"
Cohesion: 0.50
Nodes (8): "ACERO INOXIDABLE - Diseno y ejecucion en acero inoxidable" Tagline, "E R A" Letter Ring Inside Circular Emblem, Company Contact Details Block (Sociedad Aceros Era Ltda., RUT, address, email, phone), Letterhead Banner with Company Details and Logo, Aceros Era Full Logo (mark + wordmark + tagline), Aceros Era Circular Emblem Only (transparent/white variant), Aceros Era Brand Identity (navy blue, circular cross emblem), Small Footer Logo (compact circular emblem)

### Community 58 - "Chatbot Widget Script"
Cohesion: 0.43
Nodes (4): addMessage(), send(), setTyping(), sleep()

### Community 59 - "Métodos de Pago UI Script"
Cohesion: 0.33
Nodes (6): eliminarMetodoPago, POST /metodos-pago & PUT|DELETE /metodos-pago/{id}, GET /metodos-pago/data, openCreateModal (método de pago), saveMetodoPago(), Métodos de Pago DataTable Script

### Community 60 - "Feature Test Scaffold"
Cohesion: 0.53
Nodes (3): Illuminate\Foundation\Testing\TestCase, ExampleTest, TestCase

### Community 61 - "Logging Configuration"
Cohesion: 0.40
Nodes (4): Monolog\Handler\NullHandler, Monolog\Handler\StreamHandler, Monolog\Handler\SyslogUdpHandler, Monolog\Processor\PsrLogMessageProcessor

### Community 62 - "Header Navigation & Logout"
Cohesion: 0.40
Nodes (4): Header: Cerrar sesion (logout), Header: global search widget, GET buscar (buscar), POST /logout (logout)

### Community 63 - "Facturas Cliente Fixed Script"
Cohesion: 0.60
Nodes (4): calculateDaysToExpiry(), handleModalHidden(), initModalEvents(), renderDaysCounter()

### Community 64 - "Facturas Proveedor Script"
Cohesion: 0.60
Nodes (4): calculateDaysToExpiry (proveedores), handleModalHidden(), initModalEvents(), renderDaysCounter (proveedores)

### Community 65 - "Facturas Clientes View"
Cohesion: 0.50
Nodes (3): Sidebar: Facturas Clientes, GET /facturas/clientes (facturas.clientes.index), facturas.clientes.index view

### Community 67 - "API / MCP Endpoints"
Cohesion: 0.50
Nodes (4): MCP Bearer Token (services.mcp.bearer), api Middleware Group, POST /api/mcp/webhook (n8n proxy), GET /api/ping

### Community 68 - "Jobs & Queue Tables"
Cohesion: 0.67
Nodes (4): Queue workers and job batches, failed_jobs table, job_batches table, jobs table

### Community 70 - "Image Placeholders & Icons"
Cohesion: 0.50
Nodes (4): Default Placeholder Avatar (default-50x50.gif), Placeholder Avatar Concept (50x50 grey fallback image for users/customers without a photo), Icon Sprite Sheet (icons.png), 3x2 Sprite Atlas of Toggle/Status Icons (checked checkboxes, empty boxes, list/document rows in blue and dark variants)

### Community 71 - "Cotización View Scripts"
Cohesion: 0.50
Nodes (3): scripts.quotations, scripts.scripts, sweet::alert

### Community 72 - "Vite Library Mode"
Cohesion: 0.67
Nodes (3): Library Mode Build, Top-level input Option (Vite 8), Vite 8 Notable Breaking Changes

### Community 73 - "Vite Config Environment"
Cohesion: 0.67
Nodes (3): Conditional and Async Config, loadEnv in Config, import.meta.env Variables

### Community 79 - "Cache & Locks Tables"
Cohesion: 1.00
Nodes (3): Application cache store, cache_locks table, cache table

### Community 80 - "Cotizaciones Email Column"
Cohesion: 0.67
Nodes (3): Quotations (cotizaciones) feature, Add email to quotations table, quotations (cotizaciones) table

### Community 83 - "Factura Quick View Handlers"
Cohesion: 0.67
Nodes (3): verFactura (clientes fixed variant), verFactura (clientes), verFactura (mixed clientes/proveedores)

### Community 84 - "Proveedor Edit/Show Flow"
Cohesion: 0.67
Nodes (3): editarProveedor, GET /proveedores/{id}, verProveedor

## Ambiguous Edges - Review These
- `.estadisticas()` → `invoices table`  [AMBIGUOUS]
  app/Http/Controllers/BusquedaController.php · relation: references
- `.handle()` → `Route Middleware Aliases (admin.role, auth.bearer, signed, guest)`  [AMBIGUOUS]
  app/Http/Middleware/UserContextMiddleware.php · relation: references
- `Ciudad` → `Cliente`  [AMBIGUOUS]
  app/Models/Ciudad.php · relation: conceptually_related_to
- `Cliente` → `OrdenTrabajo`  [AMBIGUOUS]
  app/Models/OrdenTrabajo.php · relation: conceptually_related_to
- `Cotizacion` → `OrdenTrabajo`  [AMBIGUOUS]
  app/Models/OrdenTrabajo.php · relation: conceptually_related_to
- `Clientes listing DataTable script` → `openEditModal() cliente form flow`  [AMBIGUOUS]
  public/assets/js/clientes/clientes.js · relation: calls
- `MCP server session storage` → `POST /api/mcp/webhook (N8N proxy)`  [AMBIGUOUS]
  public/assets/js/chatbot.js · relation: conceptually_related_to

## Knowledge Gaps
- **331 isolated node(s):** `Base Controller`, `$schema`, `name`, `type`, `description` (+326 more)
  These have ≤1 connection - possible missing edges or undocumented components. (Counts symbols only; 476 node(s) total have ≤1 connection when file, concept and rationale nodes are included.)
- **79 thin communities (<3 nodes) omitted from report** — run `graphify query` to explore isolated nodes.

## Suggested Questions
_Questions this graph is uniquely positioned to answer:_

- **What is the exact relationship between `.estadisticas()` and `invoices table`?**
  _Edge tagged AMBIGUOUS (relation: references) - confidence is low._
- **What is the exact relationship between `.handle()` and `Route Middleware Aliases (admin.role, auth.bearer, signed, guest)`?**
  _Edge tagged AMBIGUOUS (relation: references) - confidence is low._
- **What is the exact relationship between `Ciudad` and `Cliente`?**
  _Edge tagged AMBIGUOUS (relation: conceptually_related_to) - confidence is low._
- **What is the exact relationship between `Cliente` and `OrdenTrabajo`?**
  _Edge tagged AMBIGUOUS (relation: conceptually_related_to) - confidence is low._
- **What is the exact relationship between `Cotizacion` and `OrdenTrabajo`?**
  _Edge tagged AMBIGUOUS (relation: conceptually_related_to) - confidence is low._
- **What is the exact relationship between `Clientes listing DataTable script` and `openEditModal() cliente form flow`?**
  _Edge tagged AMBIGUOUS (relation: calls) - confidence is low._
- **What is the exact relationship between `MCP server session storage` and `POST /api/mcp/webhook (N8N proxy)`?**
  _Edge tagged AMBIGUOUS (relation: conceptually_related_to) - confidence is low._