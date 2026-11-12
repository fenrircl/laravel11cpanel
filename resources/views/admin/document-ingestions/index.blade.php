@extends('layouts.app')

@section('content')
<div class="container-fluid py-4">
    <div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mb-3">
        <div><h1 class="h3 mb-1">Mantenedor de jobs</h1><p class="text-muted mb-0">Revisa importaciones, corrige los datos y administra reintentos.</p></div>
    </div>
    @include('admin._quick-nav', ['active' => 'jobs'])
    @if(session('success'))<div class="alert alert-success">{{ session('success') }}</div>@endif
    @if($errors->any())<div class="alert alert-danger"><ul class="mb-0">@foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul></div>@endif
    <h2 class="h6 text-muted mb-2">Tipo de documento</h2>
    <div class="d-flex gap-2 flex-wrap mb-3">
        <a class="btn btn-sm {{ !$type?'btn-primary':'btn-outline-primary' }}" href="{{ route('admin.document-ingestions.index', array_filter(['status'=>$status])) }}">Todos los tipos</a>
        @foreach($types as $documentType)
            <a class="btn btn-sm {{ $type===$documentType?'btn-primary':'btn-outline-primary' }}" href="{{ route('admin.document-ingestions.index', array_filter(['status'=>$status, 'type'=>$documentType])) }}">{{ $documentType }}</a>
        @endforeach
        <a class="btn btn-sm {{ $type==='unclassified'?'btn-primary':'btn-outline-primary' }}" href="{{ route('admin.document-ingestions.index', array_filter(['status'=>$status, 'type'=>'unclassified'])) }}">Sin clasificar</a>
    </div>
    <h2 class="h6 text-muted mb-2">Estado</h2>
    <div class="d-flex gap-2 flex-wrap mb-3">
        @foreach(['needs_review'=>'Revisión','failed'=>'Fallidos','processing'=>'Procesando','completed'=>'Completados','ignored'=>'Omitidos'] as $key=>$label)
            <a class="btn btn-sm {{ $status===$key?'btn-primary':'btn-outline-primary' }}" href="{{ route('admin.document-ingestions.index', array_filter(['status'=>$key, 'type'=>$type])) }}">{{ $label }}</a>
        @endforeach
        <a class="btn btn-sm {{ !$status?'btn-primary':'btn-outline-primary' }}" href="{{ route('admin.document-ingestions.index', array_filter(['type'=>$type])) }}">Todos</a>
    </div>
    @forelse($jobs as $job)
        <article class="card mb-3">
            <div class="card-header d-flex justify-content-between flex-wrap gap-2"><strong>{{ $job->filename ?: $job->external_id }}</strong><span class="badge bg-secondary">{{ $job->status }}</span></div>
            <div class="card-body">
                <div class="row g-2 small mb-3">
                    <div class="col-md-3"><strong>Origen:</strong> {{ $job->source }}</div>
                    <div class="col-md-3"><strong>Tipo:</strong> {{ $job->document_type ?: 'Sin clasificar' }}</div>
                    <div class="col-md-3"><strong>Confianza:</strong> {{ $job->confidence ?? '—' }}</div>
                    <div class="col-md-3"><strong>Actualizado:</strong> {{ $job->updated_at }}</div>
                </div>
                <div class="border rounded p-3 mb-3 bg-light">
                    <strong class="d-block mb-2">Datos identificados</strong>
                    <div class="row g-2 small">
                        <div class="col-md-3"><span class="text-muted">Emisor:</span> {{ data_get($job->extracted_data, 'issuer') ?: 'No identificado' }}</div>
                        <div class="col-md-3"><span class="text-muted">RUT proveedor:</span> {{ data_get($job->extracted_data, 'supplier_rut') ?: 'No identificado' }}</div>
                        <div class="col-md-3"><span class="text-muted">Cliente:</span> {{ data_get($job->extracted_data, 'customer_name') ?: data_get($job->extracted_data, 'recipient') ?: 'No identificado' }}</div>
                        <div class="col-md-3"><span class="text-muted">RUT cliente:</span> {{ data_get($job->extracted_data, 'customer_rut') ?: 'No identificado' }}</div>
                    </div>
                </div>
                @if($job->warnings)<div class="alert alert-warning py-2"><strong>Advertencias:</strong> {{ implode(' · ', (array)json_decode($job->warnings, true)) }}</div>@endif
                @if($job->last_error)<div class="alert alert-danger py-2">{{ $job->last_error }}</div>@endif
                <details class="mb-3"><summary>Ver datos extraídos</summary><pre class="bg-light border rounded p-3 mt-2 small" style="white-space:pre-wrap;max-height:360px;overflow:auto">{{ json_encode(json_decode($job->extraction_payload ?: '{}', true), JSON_PRETTY_PRINT|JSON_UNESCAPED_UNICODE) }}</pre></details>
                @if($job->file_registry_id)<a class="btn btn-sm btn-outline-primary mb-2" target="_blank" rel="noopener" href="{{ route('files.registry.download', ['file' => $job->file_registry_id]) }}">Abrir PDF</a>@endif
                @if(in_array($job->status,['needs_review','failed']) && $job->extraction_payload)
                    @php
                        $invoiceMatch = $job->suggested_invoice;
                        $suggestedPartyName = $job->suggested_party_name ?? null;
                        $suggestedPartyRut = $job->suggested_party_rut ?? null;
                        $suggestedPartyType = $job->suggested_party_type ?? null;
                        $databaseParty = $invoiceMatch?->proveedor ?: $invoiceMatch?->cliente;
                        $databasePartyLabel = $databaseParty ? $databaseParty->name . ($databaseParty->rut ? ' · ' . $databaseParty->rut : '') : 'Sin contraparte asociada';
                        $proposedPartyLabel = $suggestedPartyName
                            ? $suggestedPartyName . ($suggestedPartyRut ? ' · ' . $suggestedPartyRut : '')
                            : 'Sin contraparte identificada';
                        $databaseDate = $invoiceMatch?->date?->format('Y-m-d') ?: '';
                        $databaseAmount = $invoiceMatch?->amount !== null ? (string) $invoiceMatch->amount : '';
                    @endphp
                    <form id="approve-job-{{ $job->id }}" method="POST" action="{{ route('admin.document-ingestions.approve',$job->id) }}" class="row g-2 align-items-end">
                        @csrf
                        <div class="col-md-4"><label class="form-label" for="invoice-{{ $job->id }}">ID de factura existente</label><input class="form-control" type="number" min="1" name="invoice_id" id="invoice-{{ $job->id }}" value="{{ old('invoice_id', $job->suggested_invoice_id) }}" required><div class="form-text">Se sugiere por ID interno o coincidencia única de folio.</div></div>
                        <div class="col-md-4"><label class="form-label" for="provider-{{ $job->id }}">Proveedor</label><select class="form-select" name="provider_id" id="provider-{{ $job->id }}"><option value="">Sin cambio</option>@foreach($providers as $provider)<option value="{{ $provider->id }}" @selected((int) old('provider_id', $job->suggested_provider_id ?? 0) === (int) $provider->id)>{{ $provider->name }} · {{ $provider->rut }}</option>@endforeach</select>@if($job->suggested_provider_id ?? null)<div class="form-text text-success">Preseleccionado desde el RUT extraído.</div>@endif</div>
                        <div class="col-md-4"><label class="form-label" for="client-{{ $job->id }}">Cliente</label><select class="form-select" name="client_id" id="client-{{ $job->id }}"><option value="">Sin cambio</option>@foreach($clients as $client)<option value="{{ $client->id }}" @selected((int) old('client_id', $job->suggested_client_id ?? 0) === (int) $client->id)>{{ $client->name }} · {{ $client->rut }}</option>@endforeach</select>@if($job->suggested_client_id ?? null)<div class="form-text text-success">Preseleccionado desde el RUT extraído.</div>@endif</div>
                        <div class="col-md-4"><label class="form-label" for="invoice-number-{{ $job->id }}">Número de factura corregido</label><input class="form-control" id="invoice-number-{{ $job->id }}" name="invoice_number" maxlength="80" value="{{ old('invoice_number', data_get($job->extracted_data, 'invoice_number')) }}"></div>
                        <div class="col-md-4"><label class="form-label" for="invoice-date-{{ $job->id }}">Fecha corregida</label><input class="form-control" id="invoice-date-{{ $job->id }}" type="date" name="invoice_date" value="{{ old('invoice_date', data_get($job->extracted_data, 'date')) }}"></div>
                        <div class="col-md-4"><label class="form-label" for="invoice-amount-{{ $job->id }}">Monto corregido</label><input class="form-control" id="invoice-amount-{{ $job->id }}" type="number" min="0" step="1" name="invoice_amount" value="{{ old('invoice_amount', data_get($job->extracted_data, 'amount')) }}"></div>
                        <div class="col-12">
                            @if($invoiceMatch)
                                <button class="btn btn-primary" type="button" data-bs-toggle="modal" data-bs-target="#invoice-match-{{ $job->id }}">Comparar datos y aprobar</button>
                            @else
                                <button class="btn btn-primary" type="submit">Asociar y aprobar</button>
                            @endif
                        </div>
                    </form>
                    @if($invoiceMatch)
                        <div class="small text-success mt-2">{{ $job->invoice_id ? 'Factura interna ya asociada' : 'Coincidencia única por folio' }}: factura ID {{ $job->suggested_invoice_id }}.</div>
                        <div class="modal fade" id="invoice-match-{{ $job->id }}" tabindex="-1" aria-labelledby="invoice-match-title-{{ $job->id }}" aria-hidden="true" data-form-id="approve-job-{{ $job->id }}" data-database-party="{{ $databasePartyLabel }}">
                            <div class="modal-dialog modal-lg modal-dialog-scrollable">
                                <div class="modal-content">
                                    <div class="modal-header">
                                        <div><h2 class="modal-title h5 mb-1" id="invoice-match-title-{{ $job->id }}">Comparar antes de aprobar</h2><div class="small text-muted">Factura interna ID {{ $invoiceMatch->id }} · PDF {{ $job->filename }}</div></div>
                                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
                                    </div>
                                    <div class="modal-body">
                                        <p class="small text-muted">La columna «Actual» muestra lo guardado en el sistema. «Se guardará» refleja los valores del formulario que se aplicarán al aprobar.</p>
                                        <div class="table-responsive">
                                            <table class="table table-sm table-bordered align-middle">
                                                <thead class="table-light"><tr><th>Dato</th><th>Factura actual</th><th>Se guardará</th><th>Resultado</th></tr></thead>
                                                <tbody>
                                                    <tr><th>Folio</th><td>{{ $invoiceMatch->invoice }}</td><td data-preview="invoice_number">{{ old('invoice_number', data_get($job->extracted_data, 'invoice_number')) ?: 'Sin cambio' }}</td><td data-comparison="invoice_number"></td></tr>
                                                    <tr><th>Fecha</th><td>{{ $databaseDate ?: 'Sin fecha' }}</td><td data-preview="invoice_date">{{ old('invoice_date', data_get($job->extracted_data, 'date')) ?: 'Sin cambio' }}</td><td data-comparison="invoice_date"></td></tr>
                                                    <tr><th>Monto</th><td>{{ $databaseAmount !== '' ? '$'.number_format((float)$databaseAmount, 0, ',', '.') : 'Sin monto' }}</td><td data-preview="invoice_amount">{{ old('invoice_amount', data_get($job->extracted_data, 'amount')) !== null ? '$'.number_format((float)old('invoice_amount', data_get($job->extracted_data, 'amount')), 0, ',', '.') : 'Sin cambio' }}</td><td data-comparison="invoice_amount"></td></tr>
                                                    <tr><th>{{ $suggestedPartyType ?: 'Contraparte' }}</th><td>{{ $databasePartyLabel }}</td><td data-preview="party">{{ $proposedPartyLabel }}</td><td data-comparison="party"></td></tr>
                                                </tbody>
                                            </table>
                                        </div>
                                        @if($suggestedPartyRut && !$job->suggested_provider_id && !$job->suggested_client_id)
                                            <div class="alert alert-warning py-2">El RUT extraído ({{ $suggestedPartyRut }}) no coincide con un registro existente; revisa la contraparte antes de aprobar.</div>
                                        @endif
                                        <details class="mt-3"><summary>Ver JSON completo de extracción</summary><pre class="bg-light border rounded p-3 mt-2 small" style="white-space:pre-wrap;max-height:300px;overflow:auto">{{ json_encode(json_decode($job->extraction_payload ?: '{}', true), JSON_PRETTY_PRINT|JSON_UNESCAPED_UNICODE) }}</pre></details>
                                    </div>
                                    <div class="modal-footer justify-content-between">
                                        <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Volver y corregir</button>
                                        <button type="submit" form="approve-job-{{ $job->id }}" class="btn btn-success">Confirmar vínculo y aprobar</button>
                                    </div>
                                </div>
                            </div>
                        </div>
                    @elseif($job->invoice_matches_count > 1)
                        <div class="small text-warning mt-2">Hay varias facturas con este folio; confirma manualmente el ID correcto.</div>
                    @endif
                @endif
                @if(in_array($job->status,['needs_review','failed']))
                    <form method="POST" action="{{ route('admin.document-ingestions.retry',$job->id) }}" class="mt-2">@csrf<button class="btn btn-outline-secondary btn-sm" type="submit">Enviar a cola de reintento</button></form>
                @endif
            </div>
        </article>
    @empty
        <div class="alert alert-info">No hay jobs en este filtro.</div>
    @endforelse
    {{ $jobs->links() }}
</div>
@endsection

@push('scripts')
<script>
document.addEventListener('show.bs.modal', function (event) {
    const modal = event.target;
    if (!modal.matches('[id^="invoice-match-"]')) return;

    const form = document.getElementById(modal.dataset.formId);
    if (!form) return;

    const provider = form.elements.namedItem('provider_id');
    const client = form.elements.namedItem('client_id');
    let party = modal.dataset.databaseParty || 'Sin contraparte asociada';
    if (provider?.value) party = provider.options[provider.selectedIndex].text;
    else if (client?.value) party = client.options[client.selectedIndex].text;

    const proposals = {
        invoice_number: form.elements.namedItem('invoice_number')?.value || 'Sin cambio',
        invoice_date: form.elements.namedItem('invoice_date')?.value || 'Sin cambio',
        invoice_amount: form.elements.namedItem('invoice_amount')?.value || 'Sin cambio',
        party
    };

    Object.entries(proposals).forEach(([field, value]) => {
        const target = modal.querySelector(`[data-preview="${field}"]`);
        if (target) target.textContent = value;
        const result = modal.querySelector(`[data-comparison="${field}"]`);
        const actualCell = target?.previousElementSibling;
        if (!result || !actualCell) return;

        const actual = actualCell.textContent.trim();
        const normalize = (text) => text.trim().toLocaleLowerCase().replace(/[.$\s]/g, '');
        const same = normalize(actual) === normalize(value);
        let label = same ? 'Coincide · sin cambio' : 'Se reemplazará';
        let badge = same ? 'bg-success' : 'bg-warning text-dark';
        if (value === 'Sin cambio') {
            label = 'Se conserva';
            badge = 'bg-secondary';
        } else if (field === 'party' && actual === 'Sin contraparte asociada') {
            label = 'Se asociará';
            badge = 'bg-info text-dark';
        }

        result.textContent = label;
        result.className = '';
        const badgeElement = document.createElement('span');
        badgeElement.className = `badge ${badge}`;
        badgeElement.textContent = label;
        result.replaceChildren(badgeElement);
    });
});
</script>
@endpush
