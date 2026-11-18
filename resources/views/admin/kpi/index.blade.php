@extends('layouts.app')

@section('title', 'KPI · Administración')

@push('styles')
<style>
    .kpi-page { --kpi-ink:#172b4d; --kpi-muted:#68788d; --kpi-line:#e5eaf0; color:var(--kpi-ink); }
    .kpi-heading { display:flex; justify-content:space-between; align-items:flex-end; gap:1rem; margin-bottom:1rem; }
    .kpi-heading h1 { font-size:1.7rem; font-weight:750; letter-spacing:-.035em; margin:0; }
    .kpi-heading p { color:var(--kpi-muted); margin:.35rem 0 0; }
    .kpi-filter { display:flex; align-items:end; gap:.6rem; }
    .kpi-grid { display:grid; grid-template-columns:repeat(3,minmax(0,1fr)); gap:1rem; margin-bottom:1rem; }
    .kpi-card { background:#fff; border:1px solid var(--kpi-line); border-radius:.85rem; padding:1.1rem; min-height:132px; box-shadow:0 2px 8px rgba(23,43,77,.035); }
    .kpi-card-top { display:flex; align-items:center; justify-content:space-between; gap:1rem; }
    .kpi-card-icon { width:40px; height:40px; border-radius:.7rem; display:grid; place-items:center; color:#245a91; background:#edf4fb; }
    .kpi-card-label { color:var(--kpi-muted); font-size:.88rem; font-weight:600; }
    .kpi-card-value { font-size:1.8rem; font-weight:750; letter-spacing:-.04em; line-height:1.15; margin-top:.8rem; }
    .kpi-card-value.money { font-size:1.42rem; }
    .kpi-card-note { color:var(--kpi-muted); font-size:.8rem; margin-top:.25rem; }
    .kpi-panel { background:#fff; border:1px solid var(--kpi-line); border-radius:.85rem; overflow:hidden; margin-bottom:1rem; }
    .kpi-panel-head { padding:1rem 1.2rem; border-bottom:1px solid var(--kpi-line); display:flex; align-items:center; justify-content:space-between; gap:1rem; }
    .kpi-panel-head h2 { font-size:1rem; font-weight:700; margin:0; }
    .kpi-chart { height:330px; width:100%; }
    .kpi-status-list { display:grid; grid-template-columns:repeat(4,minmax(0,1fr)); gap:.7rem; padding:1rem 1.2rem; }
    .kpi-status-item { border:1px solid var(--kpi-line); background:#fafbfd; border-radius:.65rem; padding:.8rem; }
    .kpi-status-item span { color:var(--kpi-muted); font-size:.82rem; display:block; }
    .kpi-status-item strong { display:block; font-size:1.25rem; margin-top:.15rem; }
    .kpi-note { color:var(--kpi-muted); font-size:.84rem; }
    @media(max-width:991.98px) { .kpi-grid { grid-template-columns:repeat(2,minmax(0,1fr)); } .kpi-status-list { grid-template-columns:repeat(2,minmax(0,1fr)); } }
    @media(max-width:575.98px) { .kpi-heading { align-items:stretch; flex-direction:column; } .kpi-filter { width:100%; } .kpi-filter .form-select { flex:1; } .kpi-grid { gap:.65rem; } .kpi-card { padding:.85rem; min-height:120px; } .kpi-card-value { font-size:1.45rem; } .kpi-card-value.money { font-size:1.18rem; } .kpi-card-icon { width:34px; height:34px; } .kpi-chart { height:280px; } }
</style>
@endpush

@section('content')
@php
    $money = static fn ($value) => '$' . number_format((float) $value, 0, ',', '.') . ' CLP';
    $cards = [
        ['Usuarios', $metrics['users'], $metrics['active_users'] . ' habilitados', 'fa-users'],
        ['Clientes', $metrics['clients'], 'Registros del sistema', 'fa-user-group'],
        ['Proveedores', $metrics['providers'], 'Registros del sistema', 'fa-truck'],
        ['Cotizaciones · ' . $year, $metrics['quotations'], $money($metrics['quotation_amount']) . ' total', 'fa-file-signature'],
        ['Facturas de clientes · ' . $year, $metrics['client_invoices'], $money($metrics['client_invoice_amount']) . ' total', 'fa-file-invoice'],
        ['Facturas de proveedores · ' . $year, $metrics['provider_invoices'], $money($metrics['provider_invoice_amount']) . ' total', 'fa-file-invoice-dollar'],
        ['Facturas con PDF · ' . $year, $metrics['invoice_pdfs'], $metrics['pdf_files'] . ' archivos PDF vinculados', 'fa-file-pdf'],
        ['PDF procesados con ítems', $metrics['pdf_processed_invoices'], number_format($metrics['pdf_line_items']) . ' líneas extraídas', 'fa-list-check'],
        ['PDF por revisar', $metrics['pdf_pending_items'], 'Facturas con PDF sin extracción validada con ítems', 'fa-magnifying-glass'],
        ['Consultas al chatbot · ' . $year, $metrics['assistant_requests'], number_format($metrics['assistant_total_tokens']) . ' tokens · ' . number_format($metrics['assistant_failed']) . ' fallidas', 'fa-comments'],
    ];
@endphp

<main class="container-fluid py-3 kpi-page">
    <header class="kpi-heading">
        <div>
            <h1>Indicadores del sistema</h1>
            <p>Resumen de actividad, documentos y uso del chatbot.</p>
        </div>
        <form class="kpi-filter" method="GET" action="{{ route('admin.kpi.index') }}">
            <label class="form-label mb-0" for="kpi-year">Año de actividad</label>
            <select class="form-select" id="kpi-year" name="year" onchange="this.form.submit()">
                @foreach($yearOptions as $option)
                    <option value="{{ $option }}" @selected($year === $option)>{{ $option }}</option>
                @endforeach
            </select>
            <noscript><button class="btn btn-primary" type="submit">Ver</button></noscript>
        </form>
    </header>

    @include('admin._quick-nav', ['active' => 'kpi'])

    <section class="kpi-grid" aria-label="Indicadores principales">
        @foreach($cards as [$label, $value, $note, $icon])
            <article class="kpi-card">
                <div class="kpi-card-top">
                    <span class="kpi-card-label">{{ $label }}</span>
                    <span class="kpi-card-icon"><i class="fa-solid {{ $icon }}" aria-hidden="true"></i></span>
                </div>
                <div class="kpi-card-value {{ str_starts_with($note, '$') ? 'money' : '' }}">{{ number_format((float) $value, 0, ',', '.') }}</div>
                <div class="kpi-card-note">{{ $note }}</div>
            </article>
        @endforeach
    </section>

    <div class="row g-3">
        <div class="col-xl-6">
            <section class="kpi-panel h-100" aria-labelledby="kpi-count-chart-title">
                <div class="kpi-panel-head"><h2 id="kpi-count-chart-title">Documentos por mes · {{ $year }}</h2></div>
                <div id="kpi-count-chart" class="kpi-chart" role="img" aria-label="Gráfico mensual de cotizaciones y facturas"></div>
            </section>
        </div>
        <div class="col-xl-6">
            <section class="kpi-panel h-100" aria-labelledby="kpi-amount-chart-title">
                <div class="kpi-panel-head"><h2 id="kpi-amount-chart-title">Montos por mes · {{ $year }}</h2></div>
                <div id="kpi-amount-chart" class="kpi-chart" role="img" aria-label="Gráfico mensual de montos de cotizaciones y facturas"></div>
            </section>
        </div>
    </div>

    <section class="kpi-panel mt-3" aria-labelledby="kpi-queue-title">
        <div class="kpi-panel-head"><h2 id="kpi-queue-title">Estado de importaciones de documentos · {{ $year }}</h2></div>
        <div class="kpi-status-list">
            @forelse(['queued'=>'En cola', 'processing'=>'Procesando', 'completed'=>'Completadas', 'needs_review'=>'Revisión', 'failed'=>'Fallidas', 'ignored'=>'Omitidas', 'discovered'=>'Detectadas'] as $status => $label)
                <div class="kpi-status-item"><span>{{ $label }}</span><strong>{{ number_format((int) ($metrics['ingestion_statuses'][$status] ?? 0)) }}</strong></div>
            @empty
                <p class="kpi-note mb-0">No hay registros de importación.</p>
            @endforelse
        </div>
    </section>

    <p class="kpi-note">Los conteos de usuarios, clientes y proveedores son acumulados. Cotizaciones, facturas, PDFs e importaciones se filtran por el año seleccionado. Las cifras se actualizan cada cinco minutos.</p>
</main>
@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', () => {
    if (!window.echarts) return;
    const data = @js($monthly);
    const countElement = document.getElementById('kpi-count-chart');
    const amountElement = document.getElementById('kpi-amount-chart');
    const money = value => '$' + new Intl.NumberFormat('es-CL', { maximumFractionDigits: 0 }).format(value) + ' CLP';
    const countChart = echarts.init(countElement);
    countChart.setOption({
        color: ['#315f91', '#26a69a', '#e1a13a'],
        tooltip: { trigger: 'axis' },
        legend: { bottom: 4 },
        grid: { left: 50, right: 18, top: 22, bottom: 52 },
        xAxis: { type: 'category', data: data.labels, axisTick: { alignWithLabel: true } },
        yAxis: { type: 'value', minInterval: 1 },
        series: [
            { name: 'Cotizaciones', type: 'bar', data: data.quotation_count, barMaxWidth: 22 },
            { name: 'Facturas cliente', type: 'bar', data: data.client_invoice_count, barMaxWidth: 22 },
            { name: 'Facturas proveedor', type: 'bar', data: data.provider_invoice_count, barMaxWidth: 22 }
        ]
    });
    const amountChart = echarts.init(amountElement);
    amountChart.setOption({
        color: ['#315f91', '#26a69a', '#e1a13a'],
        tooltip: { trigger: 'axis', valueFormatter: money },
        legend: { bottom: 4 },
        grid: { left: 72, right: 18, top: 22, bottom: 52 },
        xAxis: { type: 'category', data: data.labels },
        yAxis: { type: 'value', axisLabel: { formatter: value => '$' + new Intl.NumberFormat('es-CL', { notation: 'compact', maximumFractionDigits: 1 }).format(value) } },
        series: [
            { name: 'Cotizaciones', type: 'line', smooth: true, data: data.quotation_amount },
            { name: 'Facturas cliente', type: 'line', smooth: true, data: data.client_invoice_amount },
            { name: 'Facturas proveedor', type: 'line', smooth: true, data: data.provider_invoice_amount }
        ]
    });
    window.addEventListener('resize', () => { countChart.resize(); amountChart.resize(); });
});
</script>
@endpush
