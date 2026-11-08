@extends('layouts.app')

@section('title', 'Acceso API | ' . config('app.name'))

@section('content')
<div class="container-fluid py-2">
    <div class="row justify-content-center">
        <div class="col-xl-9 col-lg-11">
            <div class="d-flex flex-wrap justify-content-between align-items-start gap-3 mb-4">
                <div>
                    <h1 class="h3 mb-1">Acceso a la API</h1>
                    <p class="text-muted mb-0">Solo administradores. Crea un Bearer de solo lectura para conectar una Action de ChatGPT con tu cuenta.</p>
                </div>
            </div>

            @if(session('success'))
                <div class="alert alert-success" role="status">{{ session('success') }}</div>
            @endif

            @if(session('plainToken'))
                <div class="alert alert-warning" role="alert">
                    <strong>Copia el token ahora.</strong> No volverá a mostrarse después de salir de esta página.
                    <div class="input-group mt-2">
                        <input id="new-api-token" class="form-control font-monospace" readonly value="{{ session('plainToken') }}">
                        <button class="btn btn-outline-dark" type="button" id="copy-api-token">Copiar</button>
                    </div>
                </div>
            @endif

            <div class="card mb-4">
                <div class="card-header fw-semibold">Crear token de solo lectura</div>
                <div class="card-body">
                    <form method="POST" action="{{ route('account.api-tokens.store') }}" class="row g-3 align-items-end">
                        @csrf
                        <div class="col-md-6">
                            <label for="token-name" class="form-label">Nombre</label>
                            <input id="token-name" name="name" class="form-control" maxlength="80" required placeholder="GPT de consultas">
                            @error('name')<div class="text-danger small mt-1">{{ $message }}</div>@enderror
                        </div>
                        <div class="col-md-3">
                            <label for="token-expiry" class="form-label">Vence en</label>
                            <select id="token-expiry" name="expires_in_days" class="form-select" required>
                                <option value="30">30 días</option>
                                <option value="90" selected>90 días</option>
                                <option value="365">1 año</option>
                            </select>
                        </div>
                        <div class="col-md-3 d-grid">
                            <button class="btn btn-primary" type="submit">Crear token</button>
                        </div>
                    </form>
                </div>
            </div>

            <div class="card">
                <div class="card-header fw-semibold">Tokens activos</div>
                <div class="table-responsive">
                    <table class="table align-middle mb-0">
                        <thead><tr><th>Nombre</th><th>Creado</th><th>Último uso</th><th>Vence</th><th></th></tr></thead>
                        <tbody>
                            @forelse($tokens as $token)
                                <tr>
                                    <td>{{ $token->name }}</td>
                                    <td>{{ $token->created_at?->format('d-m-Y') }}</td>
                                    <td>{{ $token->last_used_at?->format('d-m-Y H:i') ?? 'Sin uso' }}</td>
                                    <td>{{ $token->expires_at?->format('d-m-Y') }}</td>
                                    <td class="text-end">
                                        <form method="POST" action="{{ route('account.api-tokens.destroy', $token) }}" onsubmit="return confirm('¿Revocar este token?');">
                                            @csrf
                                            @method('DELETE')
                                            <button class="btn btn-sm btn-outline-danger" type="submit">Revocar</button>
                                        </form>
                                    </td>
                                </tr>
                            @empty
                                <tr><td colspan="5" class="text-center text-muted py-4">Todavía no has creado tokens.</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>

            <p class="small text-muted mt-3">La API solo acepta tokens cuyo usuario asociado conserve el rol de administrador. El token permite consultas de lectura. Trátalo como una contraseña y revócalo si deja de ser necesario.</p>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
document.getElementById('copy-api-token')?.addEventListener('click', async function () {
    const token = document.getElementById('new-api-token');
    await navigator.clipboard.writeText(token.value);
    this.textContent = 'Copiado';
});
</script>
@endpush
