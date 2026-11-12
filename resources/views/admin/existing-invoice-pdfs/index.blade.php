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
                            <a class="small" target="_blank" rel="noopener" href="{{ route('files.registry.download', ['file'=>$file->file_id]) }}">Abrir PDF</a>
                        </td>
                        <td>{{ $file->provider_name ?: $file->client_name ?: 'Sin contraparte' }}</td>
                        <td>
                            @php($statusLabel = ['pending'=>'Pendiente','processing'=>'Procesando','needs_review'=>'Revisión','failed'=>'Fallido','processed'=>'Procesado'][$file->processing_status] ?? $file->processing_status)
                            <span class="badge {{ $file->processing_status==='processed'?'bg-success':($file->processing_status==='needs_review'?'bg-warning text-dark':($file->processing_status==='failed'?'bg-danger':($file->processing_status==='processing'?'bg-info text-dark':'bg-secondary'))) }}">{{ $statusLabel }}</span>
                            @if($file->last_error)<div class="small text-danger mt-1">{{ $file->last_error }}</div>@endif
                        </td>
                        <td>
                            @if($file->extraction_id)
                                <div>{{ $file->document_type ?: 'Tipo sin identificar' }}</div>
                                <div class="small text-muted">Confianza: {{ $file->confidence ?? '—' }}</div>
                                @if($file->warnings)
                                    @php($warnings = json_decode($file->warnings, true) ?: [])
                                    <details class="small"><summary>Advertencias ({{ count($warnings) }})</summary><ul class="mb-0">@foreach($warnings as $warning)<li>{{ $warning }}</li>@endforeach</ul></details>
                                @endif
                            @else
                                <span class="text-muted">Aún no procesado</span>
                            @endif
                        </td>
                        <td>{{ (int)($file->line_count ?? 0) }}</td>
                        <td>{{ $file->uploaded_at }}</td>
                        <td>
                            @if($file->processing_status !== 'processed' && $file->processing_status !== 'processing')
                                <form method="POST" action="{{ route('admin.existing-invoice-pdfs.process', ['file'=>$file->file_id]) }}">
                                    @csrf
                                    <button class="btn btn-sm {{ $file->processing_status==='pending'?'btn-primary':'btn-outline-primary' }}" type="submit">{{ $file->processing_status==='pending'?'Procesar':'Reintentar' }}</button>
                                </form>
                            @else
                                <span class="text-muted small">—</span>
                            @endif
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="7" class="text-center text-muted py-4">No hay PDFs en este filtro.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
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
