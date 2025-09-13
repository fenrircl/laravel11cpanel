@extends('layouts.app')

@section('content')
<div class="container-fluid">
  {{-- <div class="row g-3 mt-2">
    <div class="col-12">
      <div class="card">
        <div class="card-header d-flex flex-wrap gap-2 align-items-end">
          <div class="me-auto">
            <h5 class="mb-0"><i class="fas fa-chart-column me-2"></i>Facturas pendientes por Cliente y Proveedor</h5>
          </div>
          <div class="d-flex flex-wrap gap-2 align-items-end">
            <div>
              <label class="form-label mb-0 small">Desde</label>
              <input type="date" id="chart-from" class="form-control form-control-sm" />
            </div>
            <div>
              <label class="form-label mb-0 small">Hasta</label>
              <input type="date" id="chart-to" class="form-control form-control-sm" />
            </div>
            <button id="chart-apply" class="btn btn-primary btn-sm">Aplicar</button>
          </div>
        </div>
        <div class="card-body">
          <div id="home-invoices-chart" style="width:100%;height:360px"></div>
        </div>
      </div>
    </div>
  </div> --}}

  <div class="row g-4 mt-3">
    <div class="col-12 mb-4">
      <div class="card h-100">
        <div class="card-header d-flex justify-content-between align-items-center">
          <h5 class="mb-0"><i class="fas fa-users me-2"></i>Facturas vencidas - Clientes</h5>
          <a href="{{ route('facturas.clientes.index') }}" class="btn btn-sm btn-outline-secondary">Ver todas</a>
        </div>
        <div class="card-body">
          <div class="table-responsive">
            <table id="home-clientes-vencidas" class="table table-sm table-hover align-middle mb-0"></table>
          </div>
        </div>
      </div>
    </div>

    <div class="col-12">
      <div class="card h-100">
        <div class="card-header d-flex justify-content-between align-items-center">
          <h5 class="mb-0"><i class="fas fa-truck me-2"></i>Facturas vencidas - Proveedores</h5>
          <a href="{{ route('facturas.proveedores.index') }}" class="btn btn-sm btn-outline-secondary">Ver todas</a>
        </div>
        <div class="card-body">
          <div class="table-responsive">
            <table id="home-proveedores-vencidas" class="table table-sm table-hover align-middle mb-0"></table>
          </div>
        </div>
      </div>
    </div>
  </div>
</div>

<!-- Modal para CRUD de Facturas (requerido para botón Editar en Home) -->
<div class="modal fade" id="facturaModal" tabindex="-1" aria-labelledby="facturaModalLabel" aria-hidden="true">
  <div class="modal-dialog modal-xl">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title" id="facturaModalLabel">Factura</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <div class="modal-body">
        <form id="facturaForm">
          @csrf
          <input type="hidden" id="factura_id" name="id" value="">
          <div class="row">
            <div class="col-md-6">
              <div class="mb-3">
                <label for="invoice" class="form-label">Número de Factura <span class="text-danger">*</span></label>
                <input type="text" class="form-control" id="invoice" name="invoice" required>
              </div>
            </div>
            <div class="col-md-6">
              <div class="mb-3">
                <label for="date" class="form-label">Fecha <span class="text-danger">*</span></label>
                <input type="date" class="form-control" id="date" name="date" required>
              </div>
            </div>
          </div>

          <div class="row">
            <div class="col-md-6">
              <div class="mb-3">
                <label for="client_id" class="form-label">Cliente</label>
                <select class="form-select" id="client_id" name="client_id">
                  <option value="">Seleccionar cliente...</option>
                </select>
              </div>
            </div>
            <div class="col-md-6">
              <div class="mb-3">
                <label for="provider_id" class="form-label">Proveedor</label>
                <select class="form-select" id="provider_id" name="provider_id">
                  <option value="">Seleccionar proveedor...</option>
                </select>
              </div>
            </div>
          </div>

          <div class="row">
            <div class="col-md-6">
              <div class="mb-3">
                <label for="expiry" class="form-label">Fecha de Vencimiento</label>
                <input type="date" class="form-control" id="expiry" name="expiry">
              </div>
            </div>
            <div class="col-md-6">
              <div class="mb-3">
                <label for="amount" class="form-label">Monto <span class="text-danger">*</span></label>
                <input type="number" class="form-control" id="amount" name="amount" step="0.01" min="0" required>
              </div>
            </div>
          </div>

          <div class="row">
            <div class="col-md-6">
              <div class="mb-3">
                <label for="payment_method_id" class="form-label">Método de Pago</label>
                <select class="form-select" id="payment_method_id" name="payment_method_id">
                  <option value="">Seleccionar método...</option>
                </select>
                <small class="text-muted">Opcional al crear. Se puede definir al editar la factura.</small>
              </div>
            </div>
            <div class="col-md-6">
              <div class="mb-3">
                <label for="status" class="form-label">Estado <span class="text-danger">*</span></label>
                <select class="form-select" id="status" name="status" required>
                  <option value="0">Pendiente</option>
                  <option value="1">Pagado</option>
                </select>
              </div>
            </div>
          </div>

          <div class="row">
            <div class="col-md-6">
              <div class="mb-3">
                <label for="pay_date" class="form-label">Fecha de Pago</label>
                <input type="date" class="form-control" id="pay_date" name="pay_date">
              </div>
            </div>
            <div class="col-md-6">
              <div class="mb-3">
                <label for="check" class="form-label">Número de Cheque</label>
                <input type="text" class="form-control" id="check" name="check">
              </div>
            </div>
          </div>

          <div class="mb-3">
            <label for="detail" class="form-label">Detalles</label>
            <textarea class="form-control" id="detail" name="detail" rows="3"></textarea>
          </div>

          <!-- Sección de gestión de archivos (solo para edición) -->
          <div id="file-management-section" style="display: none;">
            <hr>
            <h6><i class="fas fa-file-upload me-2"></i>Gestión de Archivos</h6>

            <!-- Subida de archivos -->
            <div class="mb-3">
              <label for="file-upload" class="form-label">Subir Archivo</label>
              <div class="input-group">
                <input type="file" class="form-control" id="file-upload" accept=".pdf,.doc,.docx,.xls,.xlsx,.jpg,.jpeg,.png">
                <button class="btn btn-outline-primary" type="button" onclick="uploadFile()">
                  <i class="fas fa-upload"></i> Subir
                </button>
              </div>
              <small class="text-muted">Formatos permitidos: PDF, DOC, DOCX, XLS, XLSX, JPG, PNG. Máximo 10MB.</small>
            </div>

            <!-- Lista de archivos -->
            <div class="mb-3">
              <label class="form-label">Archivos Asociados</label>
              <div id="files-list" class="border rounded p-3 bg-light">
                <div class="text-center text-muted" id="no-files-message">
                  <i class="fas fa-folder-open fa-2x mb-2"></i>
                  <p>No hay archivos asociados a esta factura</p>
                </div>
              </div>
            </div>
          </div>
        </form>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
        <button type="button" class="btn btn-primary" onclick="saveFactura()">Guardar</button>
      </div>
    </div>
  </div>
</div>
@endsection

@push('scripts')
<script>
  window.__HOME_CLIENTES_VENCIDAS__ = @json($clientesVencidasData ?? []);
  window.__HOME_PROVEEDORES_VENCIDAS__ = @json($proveedoresVencidasData ?? []);
</script>
@endpush