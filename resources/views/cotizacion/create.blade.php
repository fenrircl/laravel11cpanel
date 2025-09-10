@extends('layouts.app')

@section('content')
<div class="container">
  <h4 class="mb-3">Nueva Cotización</h4>
  <form action="{{ route('cotizaciones.store') }}" method="POST" id="cotizacionCreateForm">
    @csrf
    <div class="row g-3">
      <div class="col-md-4">
        <label class="form-label">Cliente</label>
        <select id="client_id" name="client_id" class="form-select" required>
          <option value="">Seleccione...</option>
          @foreach($clientes as $c)
          <option value="{{ $c->id }}">{{ $c->name }} ({{ $c->rut }})</option>
          @endforeach
        </select>
      </div>
      <div class="col-md-3">
        <label class="form-label">Fecha</label>
        <input type="date" id="date" name="date" class="form-control"
          value="{{ date('Y-m-d') }}" required>
      </div>
      <div class="col-md-3">
        <label class="form-label">Agente</label>
        <input type="text" id="agent" name="agent" class="form-control" required>
      </div>
      <div class="col-12">
        <label class="form-label">Trabajo</label>
        <input type="text" id="work" name="work" class="form-control"
          placeholder="Descripción breve">
      </div>
    </div>
    <hr>
    <h5>Ítems</h5>
    <div id="itemsContainerCreate">
      <!-- items dinámicos -->
    </div>
    <button type="button" class="btn btn-outline-primary btn-sm" id="addItemBtnCreate">
      <i class="fas fa-plus"></i> Añadir ítem
    </button>

    <div class="mt-4 p-3 border rounded bg-light" id="totalsBoxCreate">
      <div class="row">
        <div class="col-md-4">
          <strong>Subtotal (neto):</strong>
          <span id="subtotalDisplayCreate">0</span>
        </div>
        <div class="col-md-4">
          <strong>IVA (19%):</strong>
          <span id="ivaDisplayCreate">0</span>
        </div>
        <div class="col-md-4">
          <strong>Total (bruto):</strong>
          <span id="totalDisplayCreate">0</span>
        </div>
      </div>
      <small class="text-muted">El total guardado incluye IVA.</small>
    </div>

    <div class="mt-3">
      <button class="btn btn-primary">Guardar</button>
      <a href="{{ route('cotizaciones.index') }}" class="btn btn-secondary">Cancelar</a>
    </div>
  </form>
</div>
@endsection

@push('scripts')
<script>
(function(){
  function maskCLP(el){
    const v = el.value.replace(/[^0-9]/g,'');
    el.value = v ? v.replace(/\B(?=(\d{3})+(?!\d))/g, '.') : '';
  }
  function parseCLP(val){ return parseInt((val||'').replace(/[^0-9]/g,''))||0; }
  function recalc(){
    let subtotal=0; 
    document.querySelectorAll('#itemsContainerCreate .item-row').forEach(r=>{
      const amount = parseInt(r.querySelector('.item-amount')?.value||'0');
      const price = parseCLP(r.querySelector('.item-price')?.value||'0');
      if(amount>0 && price>=0){ subtotal += amount*price; }
    });
    const iva = Math.round(subtotal*0.19);
    const total = subtotal + iva;
    const fmt = n => n.toString().replace(/\B(?=(\d{3})+(?!\d))/g,'.');
    document.getElementById('subtotalDisplayCreate').textContent = fmt(subtotal);
    document.getElementById('ivaDisplayCreate').textContent = fmt(iva);
    document.getElementById('totalDisplayCreate').textContent = fmt(total);
  }
  document.getElementById('addItemBtnCreate').addEventListener('click', function(){
    const idx = document.querySelectorAll('#itemsContainerCreate .item-row').length;
    const row = document.createElement('div');
    row.className='row g-2 align-items-end mb-2 item-row';
    row.innerHTML=`<div class="col-md-6"><label class="form-label">Descripción</label><input name="items[${idx}][description]" class="form-control" required></div>
    <div class="col-md-2"><label class="form-label">Cantidad</label><input type="number" name="items[${idx}][amount]" value="1" min="1" class="form-control item-amount" required></div>
    <div class="col-md-3"><label class="form-label">Precio Neto</label><input name="items[${idx}][price]" class="form-control clp item-price" required></div>
    <div class="col-md-1 text-end"><button type="button" class="btn btn-outline-danger btn-sm remove-item">&times;</button></div>`;
    document.getElementById('itemsContainerCreate').appendChild(row);
    row.querySelector('.item-amount').addEventListener('input', recalc);
    const priceEl = row.querySelector('.item-price');
    priceEl.addEventListener('input', ()=>{ maskCLP(priceEl); recalc(); });
    row.querySelector('.remove-item').addEventListener('click',()=>{ row.remove(); recalc(); });
    recalc();
  });
  recalc();
})();
</script>
@endpush
