@extends('layouts.app')

@section('content')
<div class="container-fluid py-4">
    <div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mb-3">
        <div><h1 class="h3 mb-1">Jobs de importación</h1></div>
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
        <a class="btn btn-sm {{ !$status?'btn-primary':'btn-outline-primary' }}" href="{{ route('admin.document-ingestions.index', array_filter(['type'=>$type])) }}">Todos (sin omitidos)</a>
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
                <details class="border rounded p-3 mb-3 bg-light">
                    <summary>Datos identificados y coincidencias</summary>
                    <div class="row g-2 small mt-2">
                        <div class="col-md-3"><span class="text-muted">Emisor:</span> {{ data_get($job->extracted_data, 'issuer') ?: '—' }}</div>
                        <div class="col-md-3"><span class="text-muted">RUT proveedor:</span> {{ data_get($job->extracted_data, 'supplier_rut') ?: '—' }}</div>
                        <div class="col-md-3"><span class="text-muted">Cliente:</span> {{ data_get($job->extracted_data, 'customer_name') ?: data_get($job->extracted_data, 'recipient') ?: (data_get($job->extracted_data, 'invoices') ? 'Varias facturas' : '—') }}</div>
                        <div class="col-md-3"><span class="text-muted">RUT cliente:</span> {{ data_get($job->extracted_data, 'customer_rut') ?: '—' }}</div>
                    </div>
                    @if(!empty($job->counterparty_name_matches))
                        <div class="table-responsive mt-2">
                            <table class="table table-sm table-bordered align-middle mb-0 bg-white">
                                <thead class="table-light"><tr><th>Página</th><th>Nombre extraído</th><th>Coincidencia en sistema</th><th>Tipo</th><th>Coincidencia</th></tr></thead>
                                <tbody>
                                    @foreach($job->counterparty_name_matches as $partyMatch)
                                        <tr>
                                            <td>{{ $partyMatch['page'] ?: '—' }} @if($partyMatch['invoice_number'])<span class="text-muted">· Factura {{ $partyMatch['invoice_number'] }}</span>@endif</td>
                                            <td>{{ $partyMatch['extracted_name'] ?: '—' }}</td>
                                            <td>{{ $partyMatch['matched_name'] }} @if($partyMatch['matched_rut'])<span class="text-muted">· {{ $partyMatch['matched_rut'] }}</span>@endif</td>
                                            <td>{{ $partyMatch['type'] }}</td>
                                            <td><span class="badge bg-success">{{ $partyMatch['method'] }}</span></td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    @endif
                </details>
                @if($job->warnings)
                    @php
                        $jobWarnings = json_decode($job->warnings, true);
                        $jobWarnings = is_array($jobWarnings) ? $jobWarnings : [(string) $job->warnings];
                    @endphp
                    <details class="mb-2 text-warning-emphasis">
                        <summary>Advertencias ({{ count($jobWarnings) }})</summary>
                        <ul class="small mb-0 mt-2">@foreach($jobWarnings as $warning)<li>{{ $warning }}</li>@endforeach</ul>
                    </details>
                @endif
                @if($job->last_error)
                    <details class="mb-2 text-danger"><summary>Detalle del estado</summary><p class="small mb-0 mt-2">{{ $job->last_error }}</p></details>
                @endif
                @php
                    $invoicePreviews = data_get($job->extracted_data, 'invoices');
                    $invoicePreviews = is_array($invoicePreviews) && count($invoicePreviews)
                        ? $invoicePreviews
                        : [$job->extracted_data];
                    $invoicePreviews = array_values(array_filter($invoicePreviews, 'is_array'));
                    $hasMultipleInvoices = count($invoicePreviews) > 1 && (bool) data_get($job->extracted_data, 'invoices');
                @endphp
                <button type="button" class="btn btn-sm btn-primary mb-2" data-bs-toggle="modal" data-bs-target="#extracted-items-{{ $job->id }}">
                    {{ $hasMultipleInvoices ? 'Revisar y seleccionar facturas por página' : 'Ver datos e ítems extraídos' }} <span class="badge bg-light text-primary ms-1">{{ count($invoicePreviews) }}</span>
                </button>
                <div class="modal fade" id="extracted-items-{{ $job->id }}" tabindex="-1" aria-labelledby="extracted-items-title-{{ $job->id }}" aria-hidden="true">
                    <div class="modal-dialog modal-xl modal-dialog-scrollable">
                        <div class="modal-content">
                            <div class="modal-header">
                                <div>
                                    <h2 class="modal-title h5 mb-1" id="extracted-items-title-{{ $job->id }}">Datos e ítems extraídos</h2>
                                    <div class="small text-muted">{{ $job->filename ?: $job->external_id }} · {{ count($invoicePreviews) }} {{ count($invoicePreviews) === 1 ? 'documento' : 'facturas/páginas' }}</div>
                                </div>
                                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
                            </div>
                            <div class="modal-body">
                                @php
                                    $pageApprovals = data_get($job->extracted_data, 'page_approvals', []);
                                @endphp
                                <div class="alert {{ $job->status === 'completed' ? 'alert-success' : 'alert-info' }} py-2">
                                    {{ $job->status === 'completed' ? 'Importación completada. Estos son los datos reconocidos y guardados.' : 'Vista previa de la extracción. Los datos todavía no se han insertado en una factura.' }}
                                </div>
                                @if($hasMultipleInvoices)
                                    <div class="d-flex align-items-center gap-2 flex-wrap mb-3 extraction-stepper" data-stepper-for="{{ $job->id }}" data-current-step="0">
                                        @foreach($invoicePreviews as $stepIndex => $stepPreview)
                                            <button type="button" class="btn btn-sm {{ $stepIndex === 0 ? 'btn-primary' : 'btn-outline-primary' }} extraction-step-button" data-step-target="{{ $stepIndex }}" aria-current="{{ $stepIndex === 0 ? 'step' : 'false' }}">
                                                {{ $stepIndex + 1 }}
                                            </button>
                                        @endforeach
                                        <span class="small text-muted ms-1" data-step-status>Página 1 de {{ count($invoicePreviews) }}</span>
                                    </div>
                                    <form id="approve-pages-{{ $job->id }}" method="POST" action="{{ route('admin.document-ingestions.approve-pages', $job->id) }}">
                                        @csrf
                                        <div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mb-3">
                                            <span class="small text-muted">Selecciona páginas e ID. Los campos vacíos se conservan.</span>
                                            <button type="button" class="btn btn-sm btn-outline-primary" data-select-all-pages="{{ $job->id }}">Marcar pendientes</button>
                                        </div>
                                @endif
                                @forelse($invoicePreviews as $previewIndex => $preview)
                                    @php
                                        $previewItems = data_get($preview, 'line_items', []);
                                        $pageNumber = (int) (data_get($preview, 'page') ?: $previewIndex + 1);
                                        $pageAlreadyApproved = data_get($pageApprovals ?? [], (string) $pageNumber);
                                        $pageInvoiceSuggestion = data_get($job->page_invoice_suggestions ?? [], (string) $pageNumber);
                                        $pageCounterparty = data_get($job->page_counterparties ?? [], (string) $pageNumber, []);
                                        $pageCounterpartyName = data_get($pageCounterparty, 'name') ?: data_get($preview, 'recipient') ?: data_get($preview, 'issuer') ?: 'Contraparte no identificada';
                                    @endphp
                                    <section class="border rounded p-3 mb-3 {{ $hasMultipleInvoices ? 'extraction-page-step' : '' }}" data-step-index="{{ $previewIndex }}">
                                        <div class="d-flex justify-content-between align-items-start flex-wrap gap-2 mb-3 pb-3 border-bottom">
                                            <div class="d-flex align-items-start gap-2">
                                                @if($hasMultipleInvoices)
                                                    <input class="form-check-input mt-2 page-approval-checkbox" type="checkbox" name="pages[]" value="{{ $pageNumber }}" id="page-{{ $job->id }}-{{ $pageNumber }}" @checked(!$pageAlreadyApproved) aria-label="Aplicar factura de la página {{ $pageNumber }}">
                                                @endif
                                                <div>
                                                    <div class="small text-muted">{{ data_get($pageCounterparty, 'type', 'Contraparte') }} · {{ count($invoicePreviews) > 1 ? 'Página ' . $pageNumber : 'Factura' }}</div>
                                                    <h3 class="h4 fw-semibold mb-0">{{ $pageCounterpartyName }}</h3>
                                                    <div class="small text-muted">Factura {{ data_get($preview, 'invoice_number') ?: 'sin folio' }}</div>
                                                </div>
                                            </div>
                                            <div class="d-flex align-items-center gap-2">
                                                <span class="badge {{ $pageAlreadyApproved ? 'bg-success' : 'bg-light text-dark border' }}">{{ $pageAlreadyApproved ? 'Aplicada' : (is_array($previewItems) ? count($previewItems) : 0) . ' ítems' }}</span>
                                                @if($job->file_registry_id || $job->r2_key)
                                                    <a class="btn btn-sm btn-outline-secondary" target="_blank" rel="noopener" href="{{ route('admin.document-ingestions.pdf', ['ingestion' => $job->id]) }}#page={{ $pageNumber }}">Abrir PDF · pág. {{ $pageNumber }}</a>
                                                @endif
                                            </div>
                                        </div>
                                        <div class="row g-2 mb-3">
                                            <div class="col-md-4"><div class="border rounded p-2 h-100"><div class="small text-muted">Folio</div><strong>{{ data_get($preview, 'invoice_number') ?: '—' }}</strong></div></div>
                                            <div class="col-md-4"><div class="border rounded p-2 h-100"><div class="small text-muted">Fecha</div><strong>{{ data_get($preview, 'date') ?: '—' }}</strong></div></div>
                                            <div class="col-md-4"><div class="border rounded p-2 h-100"><div class="small text-muted">Total</div><strong>{{ data_get($preview, 'amount') !== null ? '$ ' . number_format((float)data_get($preview, 'amount'), 0, ',', '.') : 'No identificado' }}</strong></div></div>
                                        </div>
                                        <details class="small mb-3"><summary>Más datos</summary><div class="row g-2 mt-1"><div class="col-md-3">Emisor: {{ data_get($preview, 'issuer') ?: '—' }}</div><div class="col-md-3">RUT proveedor: {{ data_get($preview, 'supplier_rut') ?: '—' }}</div><div class="col-md-3">RUT cliente: {{ data_get($preview, 'customer_rut') ?: '—' }}</div><div class="col-md-3">Neto: {{ data_get($preview, 'net_amount') !== null ? '$ ' . number_format((float)data_get($preview, 'net_amount'), 0, ',', '.') : '—' }}</div><div class="col-md-3">IVA: {{ data_get($preview, 'tax_amount') !== null ? '$ ' . number_format((float)data_get($preview, 'tax_amount'), 0, ',', '.') : '—' }}</div><div class="col-md-3">Moneda: {{ data_get($preview, 'currency') ?: '—' }}</div></div></details>
                                        @if($hasMultipleInvoices)
                                            <h4 class="h6 mb-2">Vincular a factura existente</h4>
                                            <div class="row g-2 mb-3">
                                                <div class="col-md-3"><label class="form-label small" for="page-invoice-id-{{ $job->id }}-{{ $pageNumber }}">ID de factura existente</label><input class="form-control form-control-sm" type="number" min="1" name="invoice_ids[{{ $pageNumber }}]" id="page-invoice-id-{{ $job->id }}-{{ $pageNumber }}" value="{{ old('invoice_ids.' . $pageNumber, is_array($pageAlreadyApproved) ? data_get($pageAlreadyApproved, 'invoice_id') : data_get($pageInvoiceSuggestion, 'id')) }}" placeholder="Ej. 6410">@if($pageInvoiceSuggestion)<div class="form-text text-success">Coincidencia única por folio {{ $pageInvoiceSuggestion['folio'] }}.</div>@else<div class="form-text">Si el folio no coincide, ingresa el ID interno.</div>@endif</div>
                                                <div class="col-md-3"><label class="form-label small" for="page-number-{{ $job->id }}-{{ $pageNumber }}">Folio</label><input class="form-control form-control-sm" name="invoice_numbers[{{ $pageNumber }}]" id="page-number-{{ $job->id }}-{{ $pageNumber }}" value="{{ data_get($preview, 'invoice_number') }}"></div>
                                                <div class="col-md-3"><label class="form-label small" for="page-date-{{ $job->id }}-{{ $pageNumber }}">Fecha</label><input class="form-control form-control-sm" type="date" name="invoice_dates[{{ $pageNumber }}]" id="page-date-{{ $job->id }}-{{ $pageNumber }}" value="{{ data_get($preview, 'date') }}"></div>
                                                <div class="col-md-3"><label class="form-label small" for="page-amount-{{ $job->id }}-{{ $pageNumber }}">Monto total</label><input class="form-control form-control-sm" type="number" min="0" step="1" name="invoice_amounts[{{ $pageNumber }}]" id="page-amount-{{ $job->id }}-{{ $pageNumber }}" value="{{ data_get($preview, 'amount') }}"></div>
                                            </div>
                                        @endif
                                        <h4 class="h6 mb-2">Ítems extraídos</h4>
                                        @if(is_array($previewItems) && count($previewItems))
                                            <div class="table-responsive">
                                                <table class="table table-sm table-striped table-bordered align-middle mb-0">
                                                    <thead class="table-light"><tr><th>#</th><th>Descripción</th><th>Código</th><th>Cantidad</th><th>Unidad</th><th>Precio unitario</th><th>Total</th></tr></thead>
                                                    <tbody>
                                                        @foreach($previewItems as $lineIndex => $item)
                                                            <tr>
                                                                <td>{{ data_get($item, 'item') ?: $lineIndex + 1 }}</td>
                                                                <td>{{ data_get($item, 'description') ?: data_get($item, 'description_original') ?: '—' }}</td>
                                                                <td>{{ data_get($item, 'code') ?: data_get($item, 'supplier_sku') ?: '—' }}</td>
                                                                <td>{{ data_get($item, 'quantity') ?? '—' }}</td>
                                                                <td>{{ data_get($item, 'unit') ?: '—' }}</td>
                                                                <td>{{ data_get($item, 'unit_price') !== null ? '$ ' . number_format((float)data_get($item, 'unit_price'), 2, ',', '.') : '—' }}</td>
                                                                @php
                                                                    $lineTotal = data_get($item, 'total_price') ?? data_get($item, 'amount') ?? data_get($item, 'total_amount');
                                                                @endphp
                                                                <td>{{ $lineTotal !== null ? '$ ' . number_format((float)$lineTotal, 0, ',', '.') : '—' }}</td>
                                                            </tr>
                                                        @endforeach
                                                    </tbody>
                                                </table>
                                            </div>
                                        @else
                                            <div class="small text-muted">No se pudieron extraer ítems para este documento.</div>
                                        @endif
                                    </section>
                                @empty
                                    <div class="alert alert-warning mb-0">La extracción no contiene datos de factura para previsualizar.</div>
                                @endforelse
                                @if($hasMultipleInvoices)
                                    </form>
                                @endif
                            </div>
                            <div class="modal-footer justify-content-between">
                                @if($hasMultipleInvoices)
                                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cerrar</button>
                                    <div class="d-flex gap-2">
                                        <button type="button" class="btn btn-outline-primary" data-step-previous disabled>Anterior</button>
                                        <button type="button" class="btn btn-primary" data-step-next>Validar y continuar</button>
                                        <button type="submit" class="btn btn-success d-none" data-step-submit form="approve-pages-{{ $job->id }}">Aplicar facturas seleccionadas</button>
                                    </div>
                                @else
                                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cerrar</button>
                                @endif
                            </div>
                        </div>
                    </div>
                </div>
                <details class="mb-2"><summary>JSON de extracción</summary><pre class="bg-light border rounded p-3 mt-2 small" style="white-space:pre-wrap;max-height:360px;overflow:auto">{{ json_encode(json_decode($job->extraction_payload ?: '{}', true), JSON_PRETTY_PRINT|JSON_UNESCAPED_UNICODE) }}</pre></details>
                @if($job->file_registry_id || $job->r2_key)
                    <a class="btn btn-sm btn-outline-primary mb-2" target="_blank" rel="noopener" href="{{ route('admin.document-ingestions.pdf', ['ingestion' => $job->id]) }}">Ver PDF en R2</a>
                @else
                    <span class="badge bg-light text-muted border mb-2">PDF no disponible en R2</span>
                @endif
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
                @if(!$job->file_registry_id && !$job->invoice_id && $job->status !== 'ignored')
                    <form method="POST" action="{{ route('admin.document-ingestions.discard', $job->id) }}" class="mt-2" onsubmit="return confirm('Se eliminará el PDF de R2 y el job quedará como omitido. ¿Quieres continuar?')">
                        @csrf @method('DELETE')
                        <button class="btn btn-outline-danger btn-sm" type="submit">Descartar PDF y eliminar de R2</button>
                    </form>
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

document.addEventListener('click', function (event) {
    const button = event.target.closest('[data-select-all-pages]');
    if (button) {
        const modal = button.closest('.modal');
        modal?.querySelectorAll('.page-approval-checkbox').forEach((checkbox) => {
            if (!checkbox.closest('section')?.querySelector('.badge.bg-success')) checkbox.checked = true;
        });
        return;
    }

    const stepButton = event.target.closest('[data-step-target], [data-step-next], [data-step-previous]');
    if (!stepButton) return;
    const modal = stepButton.closest('.modal');
    const stepper = modal?.querySelector('.extraction-stepper');
    const steps = modal ? [...modal.querySelectorAll('.extraction-page-step')] : [];
    if (!stepper || !steps.length) return;

    const current = Number(stepper.dataset.currentStep || 0);
    let target = stepButton.hasAttribute('data-step-next')
        ? current + 1
        : stepButton.hasAttribute('data-step-previous')
            ? current - 1
            : Number(stepButton.dataset.stepTarget);
    target = Math.max(0, Math.min(target, steps.length - 1));

    if (target > current) {
        const section = steps[current];
        const checkbox = section.querySelector('.page-approval-checkbox');
        const page = checkbox?.value;
        const invoiceId = page ? section.querySelector(`[name="invoice_ids[${page}]"]`) : null;
        if (checkbox?.checked && !invoiceId?.value.trim()) {
            const status = stepper.querySelector('[data-step-status]');
            if (status) {
                status.textContent = 'Ingresa el ID de factura de esta página antes de continuar.';
                status.classList.add('text-danger');
            }
            invoiceId?.focus();
            return;
        }
    }

    stepper.dataset.currentStep = String(target);
    steps.forEach((section, index) => section.classList.toggle('d-none', index !== target));
    stepper.querySelectorAll('.extraction-step-button').forEach((button, index) => {
        button.classList.toggle('btn-primary', index === target);
        button.classList.toggle('btn-outline-primary', index !== target);
        button.setAttribute('aria-current', index === target ? 'step' : 'false');
    });

    const status = stepper.querySelector('[data-step-status]');
    if (status) {
        status.textContent = `Página ${target + 1} de ${steps.length}`;
        status.classList.remove('text-danger');
    }
    const previous = modal.querySelector('[data-step-previous]');
    const next = modal.querySelector('[data-step-next]');
    const submit = modal.querySelector('[data-step-submit]');
    if (previous) previous.disabled = target === 0;
    if (next) next.classList.toggle('d-none', target === steps.length - 1);
    if (submit) submit.classList.toggle('d-none', target !== steps.length - 1);
});

document.addEventListener('shown.bs.modal', function (event) {
    const modal = event.target;
    if (!modal.matches('[id^="extracted-items-"]')) return;
    const firstStep = modal.querySelector('.extraction-step-button');
    firstStep?.click();
});
</script>
@endpush
