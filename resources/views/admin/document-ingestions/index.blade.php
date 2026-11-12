@extends('layouts.app')

@section('content')
<div class="container-fluid py-4">
    <div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mb-3">
        <div><h1 class="h3 mb-1">Importaciones de documentos</h1><p class="text-muted mb-0">Revisa la extracción y asocia los jobs pendientes a la factura correcta.</p></div>
        <a class="btn btn-outline-secondary" href="{{ route('admin.system-info.index') }}">Información del sistema</a>
    </div>
    @if(session('success'))<div class="alert alert-success">{{ session('success') }}</div>@endif
    @if($errors->any())<div class="alert alert-danger"><ul class="mb-0">@foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul></div>@endif
    <div class="d-flex gap-2 flex-wrap mb-3">
        @foreach(['needs_review'=>'Revisión','failed'=>'Fallidos','processing'=>'Procesando','completed'=>'Completados','ignored'=>'Omitidos'] as $key=>$label)
            <a class="btn btn-sm {{ $status===$key?'btn-primary':'btn-outline-primary' }}" href="{{ route('admin.document-ingestions.index',['status'=>$key]) }}">{{ $label }}</a>
        @endforeach
        <a class="btn btn-sm {{ !$status?'btn-primary':'btn-outline-primary' }}" href="{{ route('admin.document-ingestions.index') }}">Todos</a>
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
                @if($job->warnings)<div class="alert alert-warning py-2"><strong>Advertencias:</strong> {{ implode(' · ', (array)json_decode($job->warnings, true)) }}</div>@endif
                @if($job->last_error)<div class="alert alert-danger py-2">{{ $job->last_error }}</div>@endif
                <details class="mb-3"><summary>Ver datos extraídos</summary><pre class="bg-light border rounded p-3 mt-2 small" style="white-space:pre-wrap;max-height:360px;overflow:auto">{{ json_encode(json_decode($job->extraction_payload ?: '{}', true), JSON_PRETTY_PRINT|JSON_UNESCAPED_UNICODE) }}</pre></details>
                @if($job->file_registry_id)<a class="btn btn-sm btn-outline-primary mb-2" target="_blank" rel="noopener" href="{{ route('files.registry.download', ['file' => $job->file_registry_id]) }}">Abrir PDF</a>@endif
                @if(in_array($job->status,['needs_review','failed']) && $job->extraction_payload)
                    <form method="POST" action="{{ route('admin.document-ingestions.approve',$job->id) }}" class="row g-2 align-items-end">
                        @csrf
                        <div class="col-md-4"><label class="form-label" for="invoice-{{ $job->id }}">ID de factura existente</label><input class="form-control" type="number" min="1" name="invoice_id" id="invoice-{{ $job->id }}" required></div>
                        <div class="col-md-4"><label class="form-label" for="provider-{{ $job->id }}">Corregir proveedor (opcional)</label><select class="form-select" name="provider_id" id="provider-{{ $job->id }}"><option value="">Sin cambio</option>@foreach($providers as $provider)<option value="{{ $provider->id }}">{{ $provider->name }} · {{ $provider->rut }}</option>@endforeach</select></div>
                        <div class="col-md-4"><label class="form-label" for="client-{{ $job->id }}">Corregir cliente (opcional)</label><select class="form-select" name="client_id" id="client-{{ $job->id }}"><option value="">Sin cambio</option>@foreach($clients as $client)<option value="{{ $client->id }}">{{ $client->name }} · {{ $client->rut }}</option>@endforeach</select></div>
                        <div class="col-md-4"><label class="form-label">Número de factura corregido</label><input class="form-control" name="invoice_number" maxlength="80" value="{{ data_get(json_decode($job->normalized_data ?: '{}', true), 'invoice_number') }}"></div>
                        <div class="col-md-4"><label class="form-label">Fecha corregida</label><input class="form-control" type="date" name="invoice_date" value="{{ data_get(json_decode($job->normalized_data ?: '{}', true), 'date') }}"></div>
                        <div class="col-md-4"><label class="form-label">Monto corregido</label><input class="form-control" type="number" min="0" step="1" name="invoice_amount" value="{{ data_get(json_decode($job->normalized_data ?: '{}', true), 'amount') }}"></div>
                        <div class="col-12"><button class="btn btn-primary" type="submit">Guardar cambios, asociar y aprobar</button></div>
                    </form>
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
