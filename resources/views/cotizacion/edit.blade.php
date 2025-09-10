@extends('layouts.app')

@section('content')
<div class="container">
    <h4 class="mb-3">Editar Cotización #{{ $cotizacion->id }}</h4>
    <form action="{{ route('cotizaciones.update', $cotizacion) }}" method="POST" id="cotizacionForm">
        @csrf
        @method('PUT')
        <div class="row g-3">
            <div class="col-md-4">
                <label class="form-label">Fecha</label>
                <input type="date" name="date" class="form-control" value="{{ old('date', optional($cotizacion->date)->format('Y-m-d')) }}" required>
            </div>
            <div class="col-md-4">
                <label class="form-label">Agente</label>
                <input type="text" name="agent" class="form-control" value="{{ old('agent', $cotizacion->agent) }}" required>
            </div>
            <div class="col-md-4">
                <label class="form-label">Cliente</label>
                <select name="client_id" class="form-select" required>
                    <option value="">Seleccionar</option>
                    @foreach($clientes as $c)
                        <option value="{{ $c->id }}" {{ (int) old('client_id', $cotizacion->client_id) === (int) $c->id ? 'selected' : '' }}>{{ $c->name }} ({{ $c->rut }})</option>
                    @endforeach
                </select>
            </div>
            <div class="col-12">
                <label class="form-label">Trabajo</label>
                <input type="text" name="work" class="form-control" value="{{ old('work', $cotizacion->work) }}">
            </div>
        </div>

        <hr>
        <h5>Ítems</h5>
        <div id="itemsContainer">
            @foreach($cotizacion->items as $idx => $it)
            <div class="row g-2 align-items-end mb-2 item-row">
                <input type="hidden" name="items[{{ $idx }}][id]" value="{{ $it->id }}">
                <div class="col-md-6">
                    <label class="form-label">Descripción</label>
                    <input type="text" name="items[{{ $idx }}][description]" class="form-control" value="{{ $it->description }}" required>
                </div>
                <div class="col-md-2">
                    <label class="form-label">Cantidad</label>
                    <input type="number" name="items[{{ $idx }}][amount]" class="form-control item-amount" value="{{ $it->amount }}" min="1" required>
                </div>
                <div class="col-md-3">
                    <label class="form-label">Precio Neto</label>
                    <input type="text" name="items[{{ $idx }}][price]" class="form-control clp item-price" value="{{ number_format($it->price,0,',','.') }}" required>
                </div>
                <div class="col-md-1 text-end">
                    <button type="button" class="btn btn-outline-danger btn-sm remove-item">&times;</button>
                </div>
            </div>
            @endforeach
        </div>
        <button type="button" class="btn btn-outline-primary btn-sm" id="addItemBtn">Añadir ítem</button>

        <div class="mt-4 p-3 border rounded bg-light" id="totalsBox">
            <div class="row">
                <div class="col-md-4"><strong>Subtotal (neto):</strong> <span id="subtotalDisplay">0</span></div>
                <div class="col-md-4"><strong>IVA (19%):</strong> <span id="ivaDisplay">0</span></div>
                <div class="col-md-4"><strong>Total (bruto):</strong> <span id="totalDisplay">0</span></div>
            </div>
            <small class="text-muted">El total mostrado incluye IVA y es el que se guardará.</small>
        </div>

        <div class="mt-3">
            <button class="btn btn-primary">Guardar cambios</button>
            <button type="button" id="btnCancel" class="btn btn-secondary">Cancelar</button>
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
  function parseCLP(val){
    if(!val) return 0;
    return parseInt((val+'').replace(/[^0-9]/g,''))||0;
  }
  function recalcTotals(){
    let subtotal = 0;
    document.querySelectorAll('#itemsContainer .item-row').forEach(row=>{
      const amount = parseInt(row.querySelector('.item-amount')?.value || '0');
      const price = parseCLP(row.querySelector('.item-price')?.value || '0');
      if(amount>0 && price>=0){
        subtotal += amount * price;
      }
    });
    const iva = Math.round(subtotal * 0.19);
    const total = subtotal + iva;
    const fmt = n => n.toString().replace(/\B(?=(\d{3})+(?!\d))/g, '.');
    document.getElementById('subtotalDisplay').textContent = fmt(subtotal);
    document.getElementById('ivaDisplay').textContent = fmt(iva);
    document.getElementById('totalDisplay').textContent = fmt(total);
  }

  // Inicializar masking y listeners existentes
  document.querySelectorAll('.clp').forEach(e=>{
    e.addEventListener('input',()=>{ maskCLP(e); recalcTotals(); });
  });
  document.querySelectorAll('.item-amount').forEach(e=>{
    e.addEventListener('input', recalcTotals);
  });
  document.querySelectorAll('.remove-item').forEach(btn=>btn.addEventListener('click', function(){ this.closest('.item-row').remove(); recalcTotals(); }));

  document.getElementById('addItemBtn').addEventListener('click', function(){
    const idx = document.querySelectorAll('#itemsContainer .item-row').length;
    const row = document.createElement('div');
    row.className = 'row g-2 align-items-end mb-2 item-row';
    row.innerHTML = `
      <div class="col-md-6">
        <label class="form-label">Descripción</label>
        <input type="text" name="items[${idx}][description]" class="form-control" required>
      </div>
      <div class="col-md-2">
        <label class="form-label">Cantidad</label>
        <input type="number" name="items[${idx}][amount]" class="form-control item-amount" min="1" value="1" required>
      </div>
      <div class="col-md-3">
        <label class="form-label">Precio Neto</label>
        <input type="text" name="items[${idx}][price]" class="form-control clp item-price" required>
      </div>
      <div class="col-md-1 text-end">
        <button type="button" class="btn btn-outline-danger btn-sm remove-item">&times;</button>
      </div>`;
    document.getElementById('itemsContainer').appendChild(row);
    row.querySelector('.clp').addEventListener('input', function(){ maskCLP(this); recalcTotals(); });
    row.querySelector('.item-amount').addEventListener('input', recalcTotals);
    row.querySelector('.remove-item').addEventListener('click', function(){ row.remove(); recalcTotals(); });
    recalcTotals();
  });

  // Calcular al cargar
  recalcTotals();

  // Al final del script existente añadimos la lógica de Cancelar
  const cancelBtn = document.getElementById('btnCancel');
  if(cancelBtn){
    cancelBtn.addEventListener('click', function(){
      if(typeof buildApiUrl === 'function') {
        window.location.href = buildApiUrl('cotizaciones');
      } else {
        window.location.href = '/cotizaciones';
      }
    });
  }
})();
</script>
@endpush
