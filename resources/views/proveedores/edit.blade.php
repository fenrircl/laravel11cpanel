@extends('layouts.app')

@push('styles')
    <link href="{{ asset('assets/css/comun/tablas.css') }}?v={{ assets_version() }}" rel="stylesheet">
    <link href="{{ asset('assets/css/proveedores/proveedores.css') }}?v={{ assets_version() }}" rel="stylesheet">
@endpush

@section('content')
<div class="container py-4">
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h3 class="mb-0">Editar Proveedor #{{ $proveedor->id }}</h3>
        <div>
            <a href="{{ route('proveedores.show', $proveedor) }}" class="btn btn-secondary btn-sm">Volver</a>
        </div>
    </div>

    <div class="card shadow-sm">
        <div class="card-body">
            <form action="{{ route('proveedores.update', $proveedor) }}" method="POST">
                @csrf
                @method('PUT')
                
                <div class="row g-3">
                    <div class="col-md-6">
                        <div class="mb-3">
                            <label for="rut" class="form-label">RUT <span class="text-danger">*</span></label>
                            <input type="text" 
                                   class="form-control @error('rut') is-invalid @enderror" 
                                   id="rut" 
                                   name="rut" 
                                   value="{{ old('rut', $proveedor->rut) }}" 
                                   required>
                            @error('rut')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                        
                        <div class="mb-3">
                            <label for="name" class="form-label">Nombre <span class="text-danger">*</span></label>
                            <input type="text" 
                                   class="form-control @error('name') is-invalid @enderror" 
                                   id="name" 
                                   name="name" 
                                   value="{{ old('name', $proveedor->name) }}" 
                                   required>
                            @error('name')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                        
                        <div class="mb-3">
                            <label for="email" class="form-label">Email</label>
                            <input type="email" 
                                   class="form-control @error('email') is-invalid @enderror" 
                                   id="email" 
                                   name="email" 
                                   value="{{ old('email', $proveedor->email) }}">
                            @error('email')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>
                    
                    <div class="col-md-6">
                        <div class="mb-3">
                            <label for="phone" class="form-label">Teléfono</label>
                            <input type="text" 
                                   class="form-control @error('phone') is-invalid @enderror" 
                                   id="phone" 
                                   name="phone" 
                                   value="{{ old('phone', $proveedor->phone) }}">
                            @error('phone')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                        
                        <div class="mb-3">
                            <label for="address" class="form-label">Dirección</label>
                            <textarea class="form-control @error('address') is-invalid @enderror" 
                                      id="address" 
                                      name="address" 
                                      rows="3">{{ old('address', $proveedor->address) }}</textarea>
                            @error('address')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>
                </div>
                
                <hr class="my-4">
                
                <div class="d-flex justify-content-between">
                    <div>
                        <small class="text-muted">
                            <i class="fas fa-calendar me-1"></i>
                            Creado: {{ $proveedor->created_at->format('d/m/Y H:i') }}
                            @if($proveedor->updated_at && $proveedor->updated_at != $proveedor->created_at)
                                <br>
                                <i class="fas fa-edit me-1"></i>
                                Actualizado: {{ $proveedor->updated_at->format('d/m/Y H:i') }}
                            @endif
                        </small>
                    </div>
                    
                    <div>
                        <a href="{{ route('proveedores.show', $proveedor) }}" class="btn btn-secondary me-2">Cancelar</a>
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-save me-1"></i>
                            Guardar Cambios
                        </button>
                    </div>
                </div>
            </form>
        </div>
    </div>
    
    @if($errors->any())
    <div class="alert alert-danger mt-3" role="alert">
        <strong>Por favor corrige los siguientes errores:</strong>
        <ul class="mb-0 mt-2">
            @foreach($errors->all() as $error)
                <li>{{ $error }}</li>
            @endforeach
        </ul>
    </div>
    @endif
</div>

@if(session('success'))
<script>
    document.addEventListener('DOMContentLoaded', function() {
        const alert = document.createElement('div');
        alert.className = 'alert alert-success alert-dismissible fade show position-fixed';
        alert.style.top = '20px';
        alert.style.right = '20px';
        alert.style.zIndex = '9999';
        alert.innerHTML = `
            <strong>¡Éxito!</strong> {{ session('success') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        `;
        document.body.appendChild(alert);
        
        setTimeout(function() {
            alert.remove();
        }, 5000);
    });
</script>
@endif
@endsection
