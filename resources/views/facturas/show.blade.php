@extends('layouts.app')

@section('title', 'Factura #' . $factura->invoice)

@section('content')
<main class="invoice-detail container-fluid" aria-labelledby="invoice-title">
    <meta name="factura-id" content="{{ $factura->id }}">

    <nav class="invoice-breadcrumb" aria-label="Navegación">
        <a href="{{ url()->previous() }}">Facturas</a>
        <span aria-hidden="true">/</span>
        <span>Detalle</span>
    </nav>

    <header class="invoice-hero">
        <div class="invoice-hero-main">
            <div class="invoice-identity">
                <span class="invoice-mark" aria-hidden="true"><i class="fas fa-file-invoice"></i></span>
                <div>
                    <p class="invoice-kicker">Documento de venta</p>
                    <h1 id="invoice-title">Factura #{{ $factura->invoice }}</h1>
                    <p class="invoice-counterparty">
                        {{ $factura->cliente?->name ?? $factura->proveedor?->name ?? 'Sin contraparte asociada' }}
                    </p>
                </div>
            </div>

            <div class="invoice-total-block">
                <span class="invoice-total-label">Monto total</span>
                <strong class="invoice-total-value">$ {{ number_format($factura->amount, 0, ',', '.') }}</strong>
                <span class="invoice-status {{ $factura->status === 1 ? 'is-paid' : 'is-pending' }}">
                    <span class="invoice-status-dot" aria-hidden="true"></span>
                    {{ $factura->status === 1 ? 'Pagada' : 'Pendiente de pago' }}
                </span>
            </div>
        </div>

        <div class="invoice-hero-footer">
            <span>Registro interno <strong>#{{ $factura->id }}</strong></span>
            <div class="invoice-actions">
                <a href="{{ url()->previous() }}" class="btn invoice-button invoice-button-light">
                    <i class="fas fa-arrow-left" aria-hidden="true"></i> Volver
                </a>
                <a href="{{ route('facturas.edit', $factura) }}" class="btn invoice-button invoice-button-primary">
                    <i class="fas fa-pen" aria-hidden="true"></i> Editar factura
                </a>
            </div>
        </div>
    </header>

    <section class="invoice-facts" aria-label="Fechas y pago">
        <div class="invoice-fact">
            <span class="invoice-fact-icon"><i class="far fa-calendar" aria-hidden="true"></i></span>
            <div><span class="invoice-fact-label">Fecha de emisión</span><strong>{{ $factura->date?->format('d/m/Y') ?? '—' }}</strong></div>
        </div>
        <div class="invoice-fact">
            <span class="invoice-fact-icon"><i class="far fa-clock" aria-hidden="true"></i></span>
            <div><span class="invoice-fact-label">Vencimiento</span><strong>{{ $factura->expiry?->format('d/m/Y') ?? '—' }}</strong></div>
        </div>
        <div class="invoice-fact">
            <span class="invoice-fact-icon"><i class="fas fa-check" aria-hidden="true"></i></span>
            <div><span class="invoice-fact-label">Fecha de pago</span><strong>{{ $factura->pay_date?->format('d/m/Y') ?? 'Aún no pagada' }}</strong></div>
        </div>
        <div class="invoice-fact">
            <span class="invoice-fact-icon"><i class="far fa-credit-card" aria-hidden="true"></i></span>
            <div><span class="invoice-fact-label">Método de pago</span><strong>{{ $factura->metodoPago?->name ?? 'No especificado' }}</strong></div>
        </div>
    </section>

    <div class="invoice-content-grid">
        <section class="invoice-section" aria-labelledby="invoice-detail-heading">
            <div class="invoice-section-heading">
                <div>
                    <span class="invoice-section-index">01</span>
                    <h2 id="invoice-detail-heading">Detalle de la factura</h2>
                </div>
            </div>
            <div class="invoice-text-panel">
                {{ $factura->detail ?: 'Esta factura no tiene un detalle adicional registrado.' }}
            </div>

            <div class="invoice-section-heading invoice-extra-heading">
                <div>
                    <span class="invoice-section-index">02</span>
                    <h2>Información adicional</h2>
                </div>
            </div>
            <div class="invoice-text-panel {{ $factura->extra ? '' : 'is-empty' }}">
                {{ $factura->extra ?: 'No hay información adicional.' }}
            </div>
        </section>

        <aside class="invoice-reference" aria-label="Resumen">
            <div class="invoice-reference-title">
                <i class="fas fa-receipt" aria-hidden="true"></i>
                <h2>Resumen</h2>
            </div>
            <dl>
                <div><dt>Tipo</dt><dd>{{ $factura->proveedor ? 'Compra a proveedor' : 'Venta a cliente' }}</dd></div>
                <div><dt>Contraparte</dt><dd>{{ $factura->cliente?->name ?? $factura->proveedor?->name ?? 'Sin asignar' }}</dd></div>
                <div><dt>RUT</dt><dd>{{ $factura->cliente?->rut ?? $factura->proveedor?->rut ?? '—' }}</dd></div>
                <div><dt>Estado</dt><dd>{{ $factura->status === 1 ? 'Pagada' : 'Pendiente' }}</dd></div>
            </dl>
        </aside>
    </div>

    <section class="invoice-files" aria-labelledby="invoice-files-heading">
        <div class="invoice-files-heading">
            <div>
                <span class="invoice-section-index">03</span>
                <h2 id="invoice-files-heading">Documentos adjuntos</h2>
                <p>Archivos asociados a esta factura.</p>
            </div>
            <span class="invoice-file-count">{{ $factura->archivos->count() }} {{ $factura->archivos->count() === 1 ? 'archivo' : 'archivos' }}</span>
        </div>

        @if($factura->archivos->isNotEmpty())
            <div class="invoice-file-list">
                @foreach($factura->archivos as $archivo)
                    <article class="invoice-file-row">
                        <span class="invoice-file-icon" aria-hidden="true"><i class="far fa-file-pdf"></i></span>
                        <div class="invoice-file-info">
                            <strong>{{ $archivo->file_name ?: basename((string) $archivo->path) }}</strong>
                            <span>
                                {{ $archivo->size ? number_format($archivo->size / 1024, 0, ',', '.') . ' KB' : 'Tamaño no registrado' }}
                                <span aria-hidden="true"> · </span>
                                {{ $archivo->created_at?->format('d/m/Y') ?? 'Fecha no registrada' }}
                            </span>
                        </div>
                        <a class="btn invoice-file-open" target="_blank" rel="noopener"
                           href="{{ route('files.registry.download', ['file' => $archivo->id]) }}">
                            Abrir PDF <i class="fas fa-arrow-up-right-from-square" aria-hidden="true"></i>
                        </a>
                    </article>
                @endforeach
            </div>
        @else
            <div class="invoice-files-empty">
                <i class="far fa-folder-open" aria-hidden="true"></i>
                <div><strong>No hay documentos adjuntos</strong><span>Los archivos vinculados a la factura aparecerán aquí.</span></div>
            </div>
        @endif
    </section>
</main>
@endsection

@push('styles')
<style>
.invoice-detail {
    --invoice-ink: #1b2a36;
    --invoice-steel: #426176;
    --invoice-muted: #6a7882;
    --invoice-line: #dce4e9;
    --invoice-paper: #fff;
    --invoice-ground: #f3f6f8;
    --invoice-amber: #d59b45;
    max-width: 1440px;
    margin: 0 auto;
    padding: 1.25rem clamp(.5rem, 1.5vw, 1.5rem) 2.5rem;
    color: var(--invoice-ink);
}
.invoice-detail * { box-sizing: border-box; }
.invoice-breadcrumb { display:flex; align-items:center; gap:.6rem; margin:0 0 1rem; color:#81909a; font-size:.82rem; }
.invoice-breadcrumb a { color:var(--invoice-steel); text-decoration:none; }
.invoice-breadcrumb a:hover { text-decoration:underline; }
.invoice-hero { overflow:hidden; border-radius:14px; color:#fff; background:var(--invoice-ink); box-shadow:0 8px 24px rgba(27,42,54,.12); }
.invoice-hero-main { display:flex; justify-content:space-between; align-items:center; gap:2rem; padding:clamp(1.25rem,3vw,2.25rem); border-left:4px solid var(--invoice-amber); }
.invoice-identity { display:flex; align-items:flex-start; gap:1rem; min-width:0; }
.invoice-mark { display:grid; place-items:center; width:48px; height:48px; flex:0 0 48px; border:1px solid rgba(255,255,255,.2); border-radius:11px; color:#e5b767; background:rgba(255,255,255,.07); font-size:1.15rem; }
.invoice-kicker { margin:0 0 .35rem; color:#b7c5ce; font-size:.78rem; }
.invoice-hero h1 { margin:0; color:#fff; font-size:clamp(1.45rem,3vw,2.1rem); font-weight:650; letter-spacing:-.035em; }
.invoice-counterparty { margin:.45rem 0 0; color:#c4d0d7; font-size:.95rem; }
.invoice-total-block { display:flex; flex-direction:column; align-items:flex-end; min-width:max-content; }
.invoice-total-label { color:#b7c5ce; font-size:.8rem; }
.invoice-total-value { margin:.1rem 0 .65rem; color:#fff; font-size:clamp(1.6rem,3.2vw,2.45rem); font-weight:650; letter-spacing:-.04em; }
.invoice-status { display:inline-flex; align-items:center; gap:.45rem; padding:.32rem .65rem; border:1px solid rgba(255,255,255,.16); border-radius:999px; font-size:.78rem; }
.invoice-status-dot { width:7px; height:7px; border-radius:50%; background:#e4b456; }
.invoice-status.is-paid .invoice-status-dot { background:#63c49a; }
.invoice-hero-footer { display:flex; justify-content:space-between; align-items:center; gap:1rem; padding:.85rem clamp(1.25rem,3vw,2.25rem); border-top:1px solid rgba(255,255,255,.12); color:#b7c5ce; font-size:.8rem; }
.invoice-hero-footer strong { color:#fff; font-weight:600; }
.invoice-actions { display:flex; gap:.55rem; }
.invoice-button { display:inline-flex; align-items:center; gap:.45rem; padding:.45rem .75rem; border-radius:7px; font-size:.82rem; }
.invoice-button-light { border:1px solid rgba(255,255,255,.24); color:#e8edf0; }
.invoice-button-light:hover { border-color:#fff; color:#fff; background:rgba(255,255,255,.08); }
.invoice-button-primary { border:1px solid #e1ad58; color:#1b2a36; background:#e1ad58; font-weight:600; }
.invoice-button-primary:hover { border-color:#edc274; color:#1b2a36; background:#edc274; }
.invoice-facts { display:grid; grid-template-columns:repeat(4,minmax(0,1fr)); margin:1rem 0 1.4rem; border:1px solid var(--invoice-line); border-radius:12px; background:var(--invoice-paper); }
.invoice-fact { display:flex; align-items:center; gap:.8rem; min-width:0; padding:1rem 1.1rem; }
.invoice-fact + .invoice-fact { border-left:1px solid var(--invoice-line); }
.invoice-fact-icon { display:grid; place-items:center; width:34px; height:34px; flex:0 0 34px; border-radius:9px; color:var(--invoice-steel); background:#edf2f5; }
.invoice-fact-label { display:block; margin-bottom:.15rem; color:var(--invoice-muted); font-size:.74rem; }
.invoice-fact strong { display:block; color:var(--invoice-ink); font-size:.88rem; font-weight:600; }
.invoice-content-grid { display:grid; grid-template-columns:minmax(0,1.65fr) minmax(250px,.85fr); gap:1.2rem; align-items:start; }
.invoice-section, .invoice-reference, .invoice-files { border:1px solid var(--invoice-line); border-radius:12px; background:var(--invoice-paper); }
.invoice-section { padding:1.25rem 1.4rem; }
.invoice-section-heading, .invoice-files-heading { display:flex; justify-content:space-between; align-items:flex-start; gap:1rem; }
.invoice-section-heading > div, .invoice-files-heading > div { display:flex; align-items:baseline; gap:.65rem; }
.invoice-section-index { color:#a0adb5; font-size:.72rem; font-variant-numeric:tabular-nums; }
.invoice-section h2, .invoice-files h2 { margin:0; color:var(--invoice-ink); font-size:1rem; font-weight:650; }
.invoice-text-panel { margin-top:.85rem; padding:.9rem 1rem; border-left:2px solid #9aafbd; border-radius:0 7px 7px 0; color:#3d4d58; background:#f6f8f9; line-height:1.55; white-space:pre-wrap; }
.invoice-text-panel.is-empty { color:#85929b; font-style:italic; }
.invoice-extra-heading { margin-top:1.5rem; padding-top:1.15rem; border-top:1px solid var(--invoice-line); }
.invoice-reference { padding:1.2rem 1.3rem; }
.invoice-reference-title { display:flex; align-items:center; gap:.65rem; padding-bottom:.9rem; border-bottom:1px solid var(--invoice-line); color:var(--invoice-steel); }
.invoice-reference-title h2 { margin:0; color:var(--invoice-ink); font-size:.98rem; font-weight:650; }
.invoice-reference dl { margin:0; }
.invoice-reference dl > div { display:flex; justify-content:space-between; gap:1rem; padding:.78rem 0; border-bottom:1px solid #edf1f3; }
.invoice-reference dl > div:last-child { padding-bottom:0; border-bottom:0; }
.invoice-reference dt { color:var(--invoice-muted); font-size:.8rem; font-weight:400; }
.invoice-reference dd { margin:0; color:var(--invoice-ink); font-size:.82rem; font-weight:600; text-align:right; }
.invoice-files { margin-top:1.2rem; padding:1.2rem 1.4rem; }
.invoice-files-heading { align-items:center; margin-bottom:.8rem; }
.invoice-files-heading > div { flex-wrap:wrap; }
.invoice-files-heading p { flex-basis:100%; margin:.05rem 0 0 2rem; color:var(--invoice-muted); font-size:.8rem; }
.invoice-file-count { padding:.25rem .55rem; border-radius:6px; color:var(--invoice-steel); background:#edf2f5; font-size:.75rem; white-space:nowrap; }
.invoice-file-row { display:flex; align-items:center; gap:.8rem; padding:.85rem .2rem; border-top:1px solid #e9eef1; }
.invoice-file-icon { display:grid; place-items:center; width:38px; height:42px; flex:0 0 38px; border-radius:7px; color:#bb5d54; background:#fbefee; font-size:1.05rem; }
.invoice-file-info { display:flex; flex:1; flex-direction:column; min-width:0; gap:.15rem; }
.invoice-file-info strong { overflow:hidden; color:var(--invoice-ink); font-size:.87rem; font-weight:600; text-overflow:ellipsis; white-space:nowrap; }
.invoice-file-info > span { color:var(--invoice-muted); font-size:.75rem; }
.invoice-file-open { display:inline-flex; align-items:center; gap:.45rem; padding:.4rem .65rem; border:1px solid #c8d5dc; border-radius:7px; color:var(--invoice-steel); font-size:.78rem; white-space:nowrap; }
.invoice-file-open:hover { border-color:var(--invoice-steel); color:#fff; background:var(--invoice-steel); }
.invoice-files-empty { display:flex; align-items:center; gap:.8rem; padding:1rem .2rem .25rem; border-top:1px solid #e9eef1; color:#8b989f; }
.invoice-files-empty > i { font-size:1.2rem; }
.invoice-files-empty div { display:flex; flex-direction:column; gap:.15rem; }
.invoice-files-empty strong { color:#50616c; font-size:.85rem; font-weight:600; }
.invoice-files-empty span { font-size:.78rem; }
.invoice-detail a:focus-visible { outline:3px solid #dfad59; outline-offset:3px; }
@media (max-width: 900px) {
    .invoice-facts { grid-template-columns:repeat(2,minmax(0,1fr)); }
    .invoice-fact:nth-child(3) { border-left:0; border-top:1px solid var(--invoice-line); }
    .invoice-fact:nth-child(4) { border-top:1px solid var(--invoice-line); }
    .invoice-content-grid { grid-template-columns:1fr; }
}
@media (max-width: 560px) {
    .invoice-detail { padding:1rem .35rem 1.5rem; }
    .invoice-hero-main { align-items:flex-start; flex-direction:column; gap:1.1rem; }
    .invoice-total-block { align-items:flex-start; padding-left:3.9rem; }
    .invoice-hero-footer { align-items:flex-start; flex-direction:column-reverse; }
    .invoice-actions { width:100%; }
    .invoice-actions .btn { flex:1; justify-content:center; }
    .invoice-facts { grid-template-columns:1fr; }
    .invoice-fact + .invoice-fact, .invoice-fact:nth-child(3), .invoice-fact:nth-child(4) { border-top:1px solid var(--invoice-line); border-left:0; }
    .invoice-section, .invoice-reference, .invoice-files { padding:1rem; }
    .invoice-file-row { align-items:flex-start; flex-wrap:wrap; }
    .invoice-file-info { flex-basis:calc(100% - 52px); }
    .invoice-file-open { margin-left:48px; }
}
</style>
@endpush
