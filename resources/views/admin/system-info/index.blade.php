@extends('layouts.app')

@section('content')
<div class="container-fluid py-3">
    <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
        <div>
            <h1 class="h3 mb-1">Información del sistema</h1>
            <p class="text-muted mb-0">Versiones activas en este servidor y versiones fijadas para el próximo despliegue.</p>
        </div>
        <span class="badge {{ $phpSupported ? 'bg-success' : 'bg-danger' }}">
            PHP {{ $phpSupported ? 'cumple el requisito' : 'no cumple el requisito' }}
        </span>
    </div>

    <div class="alert alert-info" role="note">
        Las versiones fijadas provienen de <code>composer.lock</code> y <code>package-lock.json</code> subidos al repositorio.
        Esto permite corroborar qué versiones contiene el despliegue; no consulta internet para determinar si existe una versión más nueva.
        Los paquetes npm se usan durante el build y pueden no estar instalados en cPanel.
    </div>

    @if(session('success'))
        <div class="alert alert-success">{{ session('success') }}</div>
    @endif
    @if($errors->any())
        <div class="alert alert-danger"><ul class="mb-0">@foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul></div>
    @endif

    <div class="card mb-4">
        <div class="card-header d-flex justify-content-between align-items-center">
            <span class="fw-semibold">Configuración del chatbot</span>
            <span class="badge {{ $assistantEnabled ? 'bg-success' : 'bg-secondary' }}">{{ $assistantEnabled ? 'Habilitado por .env' : 'Deshabilitado por .env' }}</span>
        </div>
        <div class="card-body">
            <p class="small text-muted">El prompt, modelo y límites se guardan en la base de datos y se aplican sin deploy. La API key y el interruptor de encendido permanecen en el <code>.env</code>.</p>
            <form method="POST" action="{{ route('admin.system-info.assistant.update') }}">
                @csrf
                @method('PUT')
                <div class="mb-3">
                    <label class="form-label" for="system_prompt">Prompt del sistema</label>
                    <textarea class="form-control font-monospace" id="system_prompt" name="system_prompt" rows="7" maxlength="12000" required>{{ old('system_prompt', $assistantSettings['system_prompt']) }}</textarea>
                    <div class="form-text">Máximo 12.000 caracteres. No incluyas API keys ni instrucciones que permitan modificar información del sistema.</div>
                </div>
                <div class="row g-3 align-items-end">
                    <div class="col-md-4">
                        <label class="form-label" for="assistant_model">Modelo</label>
                        <input class="form-control" id="assistant_model" name="model" value="{{ old('model', $assistantSettings['model']) }}" maxlength="100" pattern="[A-Za-z0-9._-]+" required>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label" for="reasoning_effort">Razonamiento</label>
                        <select class="form-select" id="reasoning_effort" name="reasoning_effort" required>
                            @foreach(['none', 'low', 'medium', 'high', 'xhigh', 'max'] as $effort)
                                <option value="{{ $effort }}" @selected(old('reasoning_effort', $assistantSettings['reasoning_effort']) === $effort)>{{ $effort }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label" for="max_output_tokens">Máximo de tokens de salida</label>
                        <input class="form-control" type="number" id="max_output_tokens" name="max_output_tokens" min="100" max="3000" value="{{ old('max_output_tokens', $assistantSettings['max_output_tokens']) }}" required>
                    </div>
                    <div class="col-md-2">
                        <button class="btn btn-primary w-100" type="submit">Guardar</button>
                    </div>
                </div>
            </form>
        </div>
    </div>

    <div class="row g-3 mb-4">
        <div class="col-sm-6 col-xl-3">
            <div class="card h-100"><div class="card-body">
                <div class="text-muted small">PHP del servidor</div>
                <div class="fs-4 fw-semibold">{{ PHP_VERSION }}</div>
                <div class="small text-muted">Requisito del proyecto: {{ $phpConstraint }} · {{ $phpSapi }}</div>
            </div></div>
        </div>
        <div class="col-sm-6 col-xl-3">
            <div class="card h-100"><div class="card-body">
                <div class="text-muted small">Laravel activo</div>
                <div class="fs-4 fw-semibold">{{ $laravelVersion }}</div>
                <div class="small text-muted">Cargado desde vendor</div>
            </div></div>
        </div>
        <div class="col-sm-6 col-xl-3">
            <div class="card h-100"><div class="card-body">
                <div class="text-muted small">Base de datos configurada</div>
                <div class="fs-4 fw-semibold">{{ $databaseDriver }}</div>
                <div class="small text-muted">Se muestra el driver, no las credenciales</div>
            </div></div>
        </div>
        <div class="col-sm-6 col-xl-3">
            <div class="card h-100"><div class="card-body">
                <div class="text-muted small">Extensiones requeridas</div>
                <div class="fs-4 fw-semibold">{{ collect($extensions)->where('loaded', true)->count() }}/{{ count($extensions) }}</div>
                <div class="small text-muted">Cargadas en PHP</div>
            </div></div>
        </div>
    </div>

    <div class="card mb-4">
        <div class="card-header fw-semibold">Extensiones PHP</div>
        <div class="table-responsive">
            <table class="table table-sm table-striped mb-0">
                <thead><tr><th>Extensión</th><th>Estado</th></tr></thead>
                <tbody>
                    @foreach($extensions as $extension)
                        <tr>
                            <td><code>{{ $extension['name'] }}</code></td>
                            <td><span class="badge {{ $extension['loaded'] ? 'bg-success' : 'bg-danger' }}">{{ $extension['loaded'] ? 'Disponible' : 'Falta' }}</span></td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>

    <div class="card mb-4">
        <div class="card-header fw-semibold">Dependencias PHP de producción</div>
        <div class="table-responsive">
            <table class="table table-sm table-striped mb-0">
                <thead><tr><th>Paquete</th><th>Requisito</th><th>Fijada en lock</th><th>Instalada en vendor</th><th>Despliegue</th></tr></thead>
                <tbody>
                    @forelse($phpPackages as $package)
                        <tr>
                            <td><code>{{ $package['name'] }}</code></td>
                            <td>{{ $package['constraint'] }}</td>
                            <td>{{ $package['locked'] }}</td>
                            <td>{{ $package['installed'] }}</td>
                            <td><span class="badge {{ $package['matches'] ? 'bg-success' : 'bg-warning text-dark' }}">{{ $package['matches'] ? 'Coincide' : 'Revisar' }}</span></td>
                        </tr>
                    @empty
                        <tr><td colspan="5" class="text-muted">No se encontraron dependencias Composer.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <div class="card">
        <div class="card-header fw-semibold">Dependencias JavaScript directas (build)</div>
        <div class="table-responsive">
            <table class="table table-sm table-striped mb-0">
                <thead><tr><th>Paquete</th><th>Alcance</th><th>Requisito</th><th>Fijada en lock</th></tr></thead>
                <tbody>
                    @forelse($npmPackages as $package)
                        <tr>
                            <td><code>{{ $package['name'] }}</code></td>
                            <td>{{ $package['scope'] }}</td>
                            <td>{{ $package['constraint'] }}</td>
                            <td>{{ $package['locked'] }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="4" class="text-muted">No se encontraron dependencias npm directas.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <div class="card mt-4" id="chat-audit">
        <div class="card-header fw-semibold">Auditoría de uso del chatbot</div>
        <div class="card-body">
            @if(!$usageTableExists)
                <div class="alert alert-warning mb-0">Falta crear <code>assistant_usage_logs</code>. Importa el esquema actualizado <code>docs/sql/assistant-chat-schema.sql</code> desde phpMyAdmin para activar la auditoría.</div>
            @else
                <div class="mb-4">
                    <h3 class="h6 mb-1">Cuota y uso diario por usuario</h3>
                    <p class="small text-muted">Consultas registradas hoy. La cuota de usuarios sin rol administrador es de 5 y se reinicia a medianoche de {{ config('assistant.quota_timezone', 'America/Santiago') }}.</p>
                    <div class="table-responsive">
                        <table class="table table-sm table-striped align-middle">
                            <thead><tr><th>Usuario</th><th>Correo</th><th>Rol</th><th>Uso de hoy</th><th>Total histórico</th><th>Cuota</th><th>Disponibles</th></tr></thead>
                            <tbody>
                                @forelse($quotaUsers as $quotaUser)
                                    <tr>
                                        <td>{{ $quotaUser->name }}</td>
                                        <td>{{ $quotaUser->email }}</td>
                                        <td>{{ $quotaUser->assistant_is_admin ? 'Administrador' : 'Usuario' }}</td>
                                        <td>{{ number_format($quotaUser->assistant_requests_today) }}</td>
                                        <td>{{ number_format($quotaUser->assistant_requests_lifetime) }}</td>
                                        <td>{{ $quotaUser->assistant_is_admin ? 'Sin límite' : '5 por día' }}</td>
                                        <td>{{ $quotaUser->assistant_is_admin ? '—' : number_format(max(0, 5 - $quotaUser->assistant_requests_today)) }}</td>
                                    </tr>
                                @empty
                                    <tr><td colspan="7" class="text-muted">No hay usuarios registrados.</td></tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                    {{ $quotaUsers->links('pagination::bootstrap-5') }}
                </div>

                <div class="row g-2 mb-3">
                    <div class="col-sm-6 col-lg"><div class="border rounded p-2"><div class="small text-muted">Consultas</div><strong>{{ number_format($usageTotals['requests']) }}</strong></div></div>
                    <div class="col-sm-6 col-lg"><div class="border rounded p-2"><div class="small text-muted">Tokens entrada</div><strong>{{ number_format($usageTotals['input_tokens']) }}</strong></div></div>
                    <div class="col-sm-6 col-lg"><div class="border rounded p-2"><div class="small text-muted">Tokens salida</div><strong>{{ number_format($usageTotals['output_tokens']) }}</strong></div></div>
                    <div class="col-sm-6 col-lg"><div class="border rounded p-2"><div class="small text-muted">Tokens totales</div><strong>{{ number_format($usageTotals['total_tokens']) }}</strong></div></div>
                    <div class="col-sm-6 col-lg"><div class="border rounded p-2"><div class="small text-muted">Fallidas</div><strong>{{ number_format($usageTotals['failed']) }}</strong></div></div>
                </div>
                <p class="small text-muted">Cada fila conserva el mensaje y la respuesta del chat para auditoría, junto al consumo informado por OpenAI. Las llamadas de ChatGPT Actions externas no incluyen aquí los tokens facturados por ChatGPT.</p>
                <div class="table-responsive">
                    <table class="table table-sm table-striped align-middle">
                        <thead><tr><th>Fecha</th><th>Usuario</th><th>Pregunta y respuesta</th><th>Modelo</th><th>Tokens (in / out / total)</th><th>Funciones</th><th>Duración</th><th>Estado</th></tr></thead>
                        <tbody>
                            @forelse($usageLogs as $log)
                                <tr>
                                    <td class="text-nowrap">{{ $log->created_at?->format('Y-m-d H:i:s') }}</td>
                                    <td>{{ $log->user?->name ?? $log->user_name ?? 'Usuario eliminado' }}<div class="small text-muted">{{ $log->conversation_id ? 'Chat #' . $log->conversation_id : '' }}</div></td>
                                    <td style="min-width:260px; max-width:440px">
                                        <details><summary class="text-truncate">{{ \Illuminate\Support\Str::limit($log->user_message, 100) }}</summary><pre class="small text-wrap mt-2 mb-0">{{ $log->user_message }}</pre></details>
                                        @if($log->assistant_message)
                                            <details class="mt-1"><summary class="text-muted">Ver respuesta</summary><pre class="small text-wrap mt-2 mb-0">{{ $log->assistant_message }}</pre></details>
                                        @endif
                                        @if($log->error_type)<div class="small text-danger">{{ $log->error_type }}</div>@endif
                                    </td>
                                    <td>{{ $log->model ?? '—' }}</td>
                                    <td class="text-nowrap">{{ number_format($log->input_tokens) }} / {{ number_format($log->output_tokens) }} / {{ number_format($log->total_tokens) }}</td>
                                    <td>{{ $log->tool_calls }}</td>
                                    <td>{{ number_format($log->duration_ms) }} ms</td>
                                    <td><span class="badge {{ $log->status === 'success' ? 'bg-success' : ($log->status === 'processing' ? 'bg-warning text-dark' : 'bg-danger') }}">{{ $log->status === 'success' ? 'Correcta' : ($log->status === 'processing' ? 'En curso' : 'Fallida') }}</span></td>
                                </tr>
                            @empty
                                <tr><td colspan="8" class="text-muted">Aún no hay consultas registradas.</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
                {{ $usageLogs->links('pagination::bootstrap-5') }}
            @endif
        </div>
    </div>
</div>
@endsection
