@extends('layouts.app')

@section('title', 'Iniciar sesión | ' . config('app.name', 'AcerosEra'))

@push('styles')
<style>
    body:has(.login-screen) { background:#edf1f6; }
    .login-screen {
        min-height:calc(100vh - 2rem);
        display:grid;
        place-items:center;
        padding:2rem 1rem;
        background:
            radial-gradient(ellipse at 50% 0%, rgba(49, 99, 157, .10), transparent 48%),
            #edf1f6;
    }
    .login-card {
        width:min(100%, 500px);
        overflow:hidden;
        border:1px solid #cbd5e1;
        border-radius:14px;
        background:#fff;
        box-shadow:0 18px 55px rgba(26, 49, 78, .12);
    }
    .login-brand {
        display:flex;
        justify-content:center;
        align-items:center;
        min-height:150px;
        padding:1.35rem 2rem;
        border-bottom:1px solid #e4eaf1;
        background:#fff;
    }
    .login-brand img { display:block; width:min(100%, 330px); height:auto; }
    .login-content { padding:2rem 2.25rem 2.25rem; }
    .login-content h1 { color:#183b68; font-size:1.55rem; font-weight:700; letter-spacing:-.025em; margin:0 0 .4rem; }
    .login-intro { color:#65758a; font-size:.94rem; line-height:1.55; margin:0 0 1.65rem; }
    .login-content .form-label { color:#293d56; font-size:.9rem; font-weight:600; margin-bottom:.45rem; }
    .login-content .form-control { min-height:47px; border-color:#cad4e0; border-radius:7px; padding:.65rem .8rem; }
    .login-content .form-control:focus { border-color:#3672b5; box-shadow:0 0 0 .2rem rgba(54, 114, 181, .15); }
    .login-content .btn-login { min-height:47px; border-radius:7px; background:#1e4778; border-color:#1e4778; font-weight:650; }
    .login-content .btn-login:hover { background:#17385f; border-color:#17385f; }
    .login-content :focus-visible { outline:3px solid #80b6ec; outline-offset:2px; }
    .login-password-wrap { position:relative; }
    .login-password-wrap .form-control { padding-right:3.25rem; }
    .login-password-toggle { position:absolute; top:50%; right:.65rem; transform:translateY(-50%); border:0; background:transparent; color:#64758b; padding:.3rem; }
    .login-footer { margin-top:1.2rem; color:#7a8797; font-size:.8rem; text-align:center; }
    @media (max-width:480px) {
        .login-screen { padding:1rem .25rem; }
        .login-card { border-radius:10px; }
        .login-brand { min-height:125px; padding:1.1rem 1.5rem; }
        .login-brand img { width:min(100%, 275px); }
        .login-content { padding:1.6rem 1.25rem 1.8rem; }
    }
</style>
@endpush

@section('content')
<main class="login-screen">
    <section class="login-card" aria-labelledby="login-title">
        <div class="login-brand">
            <img src="{{ asset('img/logo.png') }}" alt="AcerosEra, acero inoxidable">
        </div>

        <div class="login-content">
            <h1 id="login-title">Iniciar sesión</h1>
            <p class="login-intro">Ingresa con el correo y la contraseña asignados a tu cuenta para acceder al sistema.</p>

            @if ($message = Session::get('success'))
                <div class="alert alert-success" role="status">{{ $message }}</div>
            @endif

            @if ($errors->any())
                <div class="alert alert-danger" role="alert">
                    @foreach ($errors->all() as $error)
                        <div>{{ $error }}</div>
                    @endforeach
                </div>
            @endif

            <form action="{{ route('authenticate') }}" method="post">
                @csrf
                <div class="mb-3">
                    <label for="email" class="form-label">Correo electrónico</label>
                    <input type="email" class="form-control @error('email') is-invalid @enderror" id="email" name="email" value="{{ old('email') }}" autocomplete="username" placeholder="nombre@empresa.cl" required autofocus>
                </div>
                <div class="mb-4">
                    <label for="password" class="form-label">Contraseña</label>
                    <div class="login-password-wrap">
                        <input type="password" class="form-control @error('password') is-invalid @enderror" id="password" name="password" autocomplete="current-password" placeholder="Ingresa tu contraseña" required>
                        <button class="login-password-toggle" type="button" id="togglePassword" aria-label="Mostrar contraseña" aria-pressed="false" title="Mostrar contraseña"><i class="fas fa-eye" aria-hidden="true"></i></button>
                    </div>
                </div>
                <button type="submit" class="btn btn-primary btn-login text-white w-100">Iniciar sesión</button>
            </form>

            <p class="login-footer mb-0">Si necesitas acceso, comunícate con el administrador.</p>
        </div>
    </section>
</main>
@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function () {
    const password = document.getElementById('password');
    const button = document.getElementById('togglePassword');
    button.addEventListener('click', function () {
        const showPassword = password.type === 'password';
        password.type = showPassword ? 'text' : 'password';
        button.setAttribute('aria-pressed', String(showPassword));
        button.setAttribute('aria-label', showPassword ? 'Ocultar contraseña' : 'Mostrar contraseña');
        button.title = showPassword ? 'Ocultar contraseña' : 'Mostrar contraseña';
        button.innerHTML = showPassword ? '<i class="fas fa-eye-slash" aria-hidden="true"></i>' : '<i class="fas fa-eye" aria-hidden="true"></i>';
    });
});
</script>
@endpush
