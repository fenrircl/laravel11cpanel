@extends('layouts.app')

@section('content')
<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header">
                    <h4 class="mb-0">Nueva Cotización</h4>
                </div>
                <div class="card-body">
                    <form id="cotizacionForm">
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
                                <input type="date" id="date" name="date" class="form-control" value="{{ date('Y-m-d') }}" required>
                            </div>
                            <div class="col-md-3">
                                <label class="form-label">Agente</label>
                                <input type="text" id="agent" name="agent" class="form-control" required>
                            </div>
                            <div class="col-12">
                                <label class="form-label">Trabajo</label>
                                <input type="text" id="work" name="work" class="form-control" placeholder="Descripción breve">
                            </div>
                        </div>

                        <hr>
                        <div class="d-flex justify-content-between align-items-center mb-2">
                            <h5 class="mb-0">Ítems</h5>
                            <button type="button" class="btn btn-sm btn-primary" id="addItemBtn"><i class="fas fa-plus"></i> Agregar ítem</button>
                        </div>

                        <div class="table-responsive">
                            <table class="table table-bordered align-middle" id="itemsTable">
                                <thead class="table-light">
                                    <tr>
                                        <th style="width: 45%">Descripción</th>
                                        <th class="text-end" style="width: 10%">Cant.</th>
                                        <th class="text-end" style="width: 20%">Precio unitario</th>
                                        <th class="text-end" style="width: 20%">Total</th>
                                        <th style="width: 5%"></th>
                                    </tr>
                                </thead>
                                <tbody></tbody>
                                <tfoot>
                                    <tr>
                                        <th colspan="3" class="text-end">Total</th>
                                        <th class="text-end" id="grandTotal">$0</th>
                                        <th></th>
                                    </tr>
                                </tfoot>
                            </table>
                        </div>

                        <div class="text-end">
                            <button type="button" id="saveCotizacionBtn" class="btn btn-success">Guardar Cotización</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
(function(){
    const tbody = document.querySelector('#itemsTable tbody');
    const grandTotalEl = document.getElementById('grandTotal');

    function parseCLP(str){
        return parseInt(String(str||'').replace(/[^0-9]/g,''))||0;
    }
    function formatCLP(n){
        return new Intl.NumberFormat('es-CL',{style:'currency',currency:'CLP',minimumFractionDigits:0,maximumFractionDigits:0}).format(n||0);
    }

    function recalc(){
        let total = 0;
        tbody.querySelectorAll('tr').forEach(tr => {
            const qty = parseInt(tr.querySelector('.item-qty').value||'0')||0;
            const unit = parseCLP(tr.querySelector('.item-unit').value);
            const line = qty * unit;
            tr.querySelector('.item-total').textContent = formatCLP(line);
            total += line;
        });
        grandTotalEl.textContent = formatCLP(total);
    }

    function addRow(data={}){
        const tr = document.createElement('tr');
        tr.innerHTML = `
            <td><input type="text" class="form-control item-desc" placeholder="Descripción" value="${data.description||''}" required></td>
            <td><input type="number" min="1" class="form-control text-end item-qty" value="${data.quantity||1}" required></td>
            <td><input type="text" class="form-control text-end item-unit" value="${data.unit_price? (data.unit_price.toLocaleString('es-CL')):''}" placeholder="0" inputmode="numeric"></td>
            <td class="text-end item-total">$0</td>
            <td class="text-center"><button type="button" class="btn btn-sm btn-outline-danger remove-item"><i class="fas fa-trash"></i></button></td>
        `;
        tbody.appendChild(tr);
        // eventos
        tr.querySelector('.item-qty').addEventListener('input', recalc);
        const unitEl = tr.querySelector('.item-unit');
        const formatUnit = ()=>{
            const val = parseCLP(unitEl.value);
            unitEl.value = val ? val.toLocaleString('es-CL') : '';
            recalc();
        };
        unitEl.addEventListener('input', formatUnit);
        unitEl.addEventListener('blur', formatUnit);
        tr.querySelector('.remove-item').addEventListener('click', ()=>{ tr.remove(); recalc();});
        recalc();
    }

    document.getElementById('addItemBtn').addEventListener('click', ()=> addRow());
    // primera fila por defecto
    addRow();

    document.getElementById('saveCotizacionBtn').addEventListener('click', function(){
        const form = document.getElementById('cotizacionForm');
        if (!form.checkValidity()) { form.reportValidity(); return; }
        const items = [];
        tbody.querySelectorAll('tr').forEach(tr => {
            const description = tr.querySelector('.item-desc').value.trim();
            const quantity = parseInt(tr.querySelector('.item-qty').value||'0')||0;
            const unit_price = parseCLP(tr.querySelector('.item-unit').value);
            if (description && quantity>0 && unit_price>=0){
                items.push({ description, quantity, unit_price });
            }
        });
        if (!items.length){
            Swal.fire({icon:'warning', title:'Ítems', text:'Agrega al menos un ítem'});
            return;
        }
        const payload = {
            _token: document.querySelector('#cotizacionForm input[name="_token"]').value,
            client_id: document.getElementById('client_id').value,
            date: document.getElementById('date').value,
            agent: document.getElementById('agent').value,
            work: document.getElementById('work').value,
            items
        };
        $.ajax({
            url: '{{ route('cotizaciones.store') }}',
            type: 'POST',
            data: payload,
            success: function(){
                showSuccessMessage('Cotización creada', function(){
                    window.location.href = '{{ route('cotizaciones.index') }}';
                });
            },
            error: function(xhr){
                let msg = 'Error al crear la cotización';
                if (xhr.responseJSON && xhr.responseJSON.message) msg = xhr.responseJSON.message;
                Swal.fire({icon:'error', title:'Error', text: msg});
            }
        });
    });
})();
</script>
@endpush
