@extends('layouts.app')

@section('content')
<div class="container-fluid py-4">
    <div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mb-3">
        <div>
            <h1 class="h3 mb-1">PDF de facturas existentes</h1>
            <p class="text-muted mb-0">Extrae productos y servicios de PDFs ya asociados a facturas. Los documentos dudosos quedan en revisión.</p>
        </div>
    </div>
    @include('admin._quick-nav', ['active' => 'jobs'])
    @if(session('success'))<div class="alert alert-success" role="status">{{ session('success') }}</div>@endif
    @if($errors->any())<div class="alert alert-danger"><ul class="mb-0">@foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul></div>@endif

    <div class="d-flex gap-2 flex-wrap mb-3" aria-label="Filtrar PDFs">
        @foreach(['pending'=>'Pendientes','processing'=>'Procesando','needs_review'=>'Revisión','failed'=>'Fallidos','processed'=>'Procesados','all'=>'Todos'] as $key=>$label)
            <a class="btn btn-sm {{ $status===$key?'btn-primary':'btn-outline-primary' }}" href="{{ route('admin.existing-invoice-pdfs.index', ['status'=>$key]) }}">{{ $label }}</a>
        @endforeach
    </div>

    <div class="table-responsive">
        <table class="table table-striped table-hover align-middle">
            <thead><tr><th>PDF / factura</th><th>Contraparte</th><th>Estado</th><th>Extracción</th><th>Ítems</th><th>Subido</th><th>Acción</th></tr></thead>
            <tbody>
                @forelse($files as $file)
                    <tr>
                        <td>
                            <strong>{{ $file->file_name ?: basename((string)$file->path) }}</strong>
                            <div class="small text-muted">Factura #{{ $file->invoice_number }} · ID {{ $file->invoice_id }}</div>
                            <a class="small" href="{{ route('facturas.show', $file->invoice_id) }}">Ver factura</a>
                            <span class="small text-muted">·</span>
                            <a class="small" target="_blank" rel="noopener" href="{{ route('files.registry.download', ['file'=>$file->file_id]) }}">Abrir PDF</a>
                        </td>
                        <td>{{ $file->provider_name ?: $file->client_name ?: 'Sin contraparte' }}</td>
                        <td>
                            @php
                                $statusLabel = ['pending' => 'Pendiente', 'processing' => 'Procesando', 'needs_review' => 'Revisión', 'failed' => 'Fallido', 'processed' => 'Procesado'][$file->processing_status] ?? $file->processing_status;
                                $statusClass = match ($file->processing_status) {
                                    'processed' => 'bg-success',
                                    'needs_review' => 'bg-warning text-dark',
                                    'failed' => 'bg-danger',
                                    'processing' => 'bg-info text-dark',
                                    default => 'bg-secondary',
                                };
                            @endphp
                            <span class="badge {{ $statusClass }}">{{ $statusLabel }}</span>
                            @if($file->processing_error)<div class="small text-danger mt-1">{{ $file->processing_error }}</div>@endif
                        </td>
                        <td>
                            @if($file->extraction_id)
                                <div>{{ $file->document_type ?: 'Tipo sin identificar' }}</div>
                                <div class="small text-muted">Confianza: {{ $file->confidence ?? '—' }}</div>
                                @if($file->warnings)
                                    @php
                                        $warnings = json_decode($file->warnings, true) ?: [];
                                    @endphp
                                    <details class="small"><summary>Advertencias ({{ count($warnings) }})</summary><ul class="mb-0">@foreach($warnings as $warning)<li>{{ $warning }}</li>@endforeach</ul></details>
                                @endif
                            @else
                                <span class="text-muted">Aún no procesado</span>
                            @endif
                        </td>
                        <td>{{ (int)($file->line_count ?? 0) }}</td>
                        <td>{{ $file->uploaded_at }}</td>
                        <td>
                            @if($file->processing_status === 'processing')
                                <div class="d-flex flex-wrap gap-1">
                                    <form method="POST" action="{{ route('admin.existing-invoice-pdfs.retry', ['file'=>$file->file_id]) }}" onsubmit="return confirm('¿Iniciar otra ejecución ahora? La ejecución actual de n8n podría seguir activa.');">
                                        @csrf
                                        <button class="btn btn-sm btn-outline-primary" type="submit">Reintentar</button>
                                    </form>
                                    <form method="POST" action="{{ route('admin.existing-invoice-pdfs.cancel', ['file'=>$file->file_id]) }}" onsubmit="return confirm('¿Cancelar el job en Laravel y liberar el bloqueo? Esto no detiene automáticamente n8n.');">
                                        @csrf
                                        <button class="btn btn-sm btn-outline-danger" type="submit">Cancelar</button>
                                    </form>
                                </div>
                            @elseif($file->processing_status !== 'processed')
                                <form method="POST" action="{{ route('admin.existing-invoice-pdfs.process', ['file'=>$file->file_id]) }}">
                                    @csrf
                                    <button class="btn btn-sm {{ $file->processing_status==='pending'?'btn-primary':'btn-outline-primary' }}" type="submit">{{ $file->processing_status==='pending'?'Procesar':'Reintentar' }}</button>
                                </form>
                            @else
                                <span class="text-muted small">—</span>
                            @endif
                            @if($file->extraction_id)
                                <button class="btn btn-sm btn-outline-secondary mt-1" type="button" data-bs-toggle="modal" data-bs-target="#saved-extraction-{{ $file->file_id }}">Datos e historial</button>
                            @endif
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="7" class="text-center text-muted py-4">No hay PDFs en este filtro.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
    @foreach($files as $file)
        @if($file->extraction_id)
            @php
                $extracted = $file->extracted_data;
                $extractedParty = data_get($extracted, 'issuer') ?: data_get($extracted, 'recipient');
                $extractedRut = data_get($extracted, 'supplier_rut') ?: data_get($extracted, 'customer_rut');
                $currentParty = $file->provider_name ?: $file->client_name ?: 'Sin contraparte';
            @endphp
            <div class="modal fade" id="saved-extraction-{{ $file->file_id }}" tabindex="-1" aria-labelledby="saved-extraction-title-{{ $file->file_id }}" aria-hidden="true">
                <div class="modal-dialog modal-xl modal-dialog-scrollable">
                    <div class="modal-content">
                        <div class="modal-header">
                            <div>
                                <h2 class="modal-title fs-5" id="saved-extraction-title-{{ $file->file_id }}">Datos de la factura #{{ $file->invoice_number }}</h2>
                                <div class="small text-muted">{{ $file->file_name }} · Extracción {{ $file->extraction_id }} · {{ $file->extraction_processed_at ?: 'Sin fecha de validación' }}</div>
                            </div>
                            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
                        </div>
                        <div class="modal-body">
                            <div class="d-flex flex-wrap gap-2 mb-3">
                                <a class="btn btn-primary btn-sm" href="{{ route('facturas.show', $file->invoice_id) }}">Abrir factura</a>
                                <a class="btn btn-outline-secondary btn-sm" target="_blank" rel="noopener" href="{{ route('files.registry.download', ['file'=>$file->file_id]) }}">Abrir PDF</a>
                                <span class="badge {{ $file->validation_status === 'validated' ? 'bg-success' : 'bg-warning text-dark' }} align-self-center">{{ $file->validation_status === 'validated' ? 'Validada' : 'Requiere revisión' }}</span>
                            </div>

                            <h3 class="h6">Factura actual y datos extraídos</h3>
                            <div class="table-responsive mb-4">
                                <table class="table table-sm table-bordered align-middle">
                                    <thead class="table-light"><tr><th>Dato</th><th>Guardado en factura</th><th>Extraído del PDF</th></tr></thead>
                                    <tbody>
                                        <tr><th>Folio</th><td>{{ $file->invoice_number }}</td><td>{{ data_get($extracted, 'invoice_number') ?: 'No identificado' }}</td></tr>
                                        <tr><th>Fecha</th><td>{{ $file->invoice_date ?: 'Sin fecha' }}</td><td>{{ data_get($extracted, 'date') ?: 'No identificada' }}</td></tr>
                                        <tr><th>Monto</th><td>{{ $file->invoice_amount !== null ? '$'.number_format((float)$file->invoice_amount, 0, ',', '.') : 'Sin monto' }}</td><td>{{ data_get($extracted, 'amount') !== null ? '$'.number_format((float)data_get($extracted, 'amount'), 0, ',', '.') : 'No identificado' }}</td></tr>
                                        <tr><th>Contraparte</th><td>{{ $currentParty }}</td><td>{{ $extractedParty ?: 'No identificada' }}{{ $extractedRut ? ' · '.$extractedRut : '' }}</td></tr>
                                    </tbody>
                                </table>
                            </div>

                            <h3 class="h6">Ítems guardados</h3>
                            @if($file->saved_line_items->isNotEmpty())
                                <div class="table-responsive mb-4">
                                    <table class="table table-sm table-striped align-middle">
                                        <thead><tr><th>#</th><th>Descripción</th><th>SKU</th><th>Cantidad</th><th>Unidad</th><th>Precio unitario</th><th>Total</th></tr></thead>
                                        <tbody>
                                            @foreach($file->saved_line_items as $line)
                                                <tr><td>{{ $line->line_number }}</td><td>{{ $line->description_original }}</td><td>{{ $line->supplier_sku ?: '—' }}</td><td>{{ $line->quantity ?? '—' }}</td><td>{{ $line->unit_original ?: '—' }}</td><td>{{ $line->unit_price !== null ? number_format((float)$line->unit_price, 0, ',', '.') : '—' }}</td><td>{{ $line->total_amount !== null ? number_format((float)$line->total_amount, 0, ',', '.') : '—' }} {{ $line->currency }}</td></tr>
                                            @endforeach
                                        </tbody>
                                    </table>
                                </div>
                            @else
                                <p class="text-muted small mb-4">Esta extracción no tiene ítems guardados.</p>
                            @endif

                            <h3 class="h6">Historial de cambios de la factura</h3>
                            @if($file->audit_history->isNotEmpty())
                                <div class="table-responsive mb-4">
                                    <table class="table table-sm table-bordered">
                                        <thead class="table-light"><tr><th>Fecha</th><th>Acción</th><th>Detalle y valores modificados</th></tr></thead>
                                        <tbody>
                                            @foreach($file->audit_history as $event)
                                                @php
                                                    $changes = json_decode($event->changes ?: '{}', true) ?: [];
                                                @endphp
                                                <tr>
                                                    <td>{{ $event->created_at }}</td><td>{{ $event->action }}</td>
                                                    <td>
                                                        {{ $event->description }}
                                                        @if($changes)
                                                            <ul class="small mb-0 mt-1">@foreach($changes as $field => $change)<li><strong>{{ $field }}:</strong> {{ is_scalar($change['from'] ?? null) ? ($change['from'] ?? 'vacío') : json_encode($change['from'] ?? null) }} → {{ is_scalar($change['to'] ?? null) ? ($change['to'] ?? 'vacío') : json_encode($change['to'] ?? null) }}</li>@endforeach</ul>
                                                        @endif
                                                    </td>
                                                </tr>
                                            @endforeach
                                        </tbody>
                                    </table>
                                </div>
                            @else
                                <p class="text-muted small mb-4">No hay eventos de auditoría de cambios para esta factura.</p>
                            @endif

                            <details>
                                <summary>Ver JSON completo de la extracción</summary>
                                <pre class="bg-light border rounded p-3 mt-2 small" style="white-space:pre-wrap;max-height:320px;overflow:auto">{{ json_encode($extracted, JSON_PRETTY_PRINT|JSON_UNESCAPED_UNICODE) }}</pre>
                            </details>
                        </div>
                    </div>
                </div>
            </div>
        @endif
    @endforeach
    <div class="existing-pdf-pagination">
        {{ $files->onEachSide(1)->links('pagination::bootstrap-5') }}
    </div>
</div>
@endsection

@push('styles')
<style>
.existing-pdf-pagination .pagination svg,
.existing-pdf-pagination .pagination img {
    width: 1rem !important;
    height: 1rem !important;
    max-width: 1rem !important;
    max-height: 1rem !important;
    display: inline-block;
    vertical-align: middle;
}
.existing-pdf-pagination .pagination .page-link {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    gap: .25rem;
}
</style>
@endpush
