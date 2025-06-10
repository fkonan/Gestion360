@extends('layouts.app')

@section('title','Login')

@section('content')
<div class="container login-container">
    <div class="row" style="margin: auto;">
        <div class="col-xs-12 col-sm-2 col-md-3 col-lg-4 col-xl-4 col-xxl-4"></div>
            <div class="col-xs-12 col-sm-8 col-md-6 col-lg-4 col-xl-4 col-xxl-4">
                <h4>Sistema: <b>Gestión</b></h4>
                <div class="card p-1">
                <div class="card-body login-card-body">
                     <div class="d-flex justify-content-between align-items-center mb-3">
                        <p class="login-box-msg" style="text-align: left; font-size: 30px;"><b>!Hola de nuevo¡</b></p>
                        <button id="toggleDarkMode" class="btn btn-outline-secondary rounded-circle" title="Cambiar modo oscuro" style="border-color:rgba(0, 0, 0, 0.23);">
                            <i class="bi bi-sun" id="darkModeIcon" style="color:rgb(0, 0, 0);"></i>
                        </button>
                    </div>
                    <form method="POST" action="{{ route('login') }}" onsubmit="deshabilitarSubmit(this)">
                    @csrf
                    <div class="d-flex flex-column mb-3">
                        <div class="input-group">
                            <input type="email" class="form-control" id="email" name="email" placeholder="Correo" value="{{ old('email') }}" required>
                            <div class="input-group-text">
                                <span class="fas fa-user"></span>
                            </div>
                        </div>   
                        @error('email')          
                         <small class="text-danger fw-bold">{{ $message }}</small>
                        @enderror
                    </div>

                    <div class="d-flex flex-column mb-3">
                        <div class="input-group">
                            <input type="password" class="form-control" id="password" name="password" placeholder="Contraseña" required>
                            <div class="input-group-text">
                                <a href="#"><span class="fas fa-eye" onclick="togglePasswordVisibility()"></span></a>
                            </div>
                        </div>
                        @error('password')
                            <small class="text-danger">{{ $message }}</small>
                        @enderror
                    </div>

                    <div class="d-flex flex-column my-4 align-items-center">
                        <div class="captcha-container">
                            {!! NoCaptcha::display(['data-theme' => $darkMode ? 'dark' : 'light']) !!}
                        </div>

                        @if ($errors->has('g-recaptcha-response'))
                            <small class="text-danger">{{ $errors->first('g-recaptcha-response') }}</small>
                        @endif
                    </div>

                    <div class="row">
                        <div class="col-12">
                            <button type="submit" name="Boton" value="Boton" id="Boton" class="btn btn-warning w-100 fw-medium">Ingresar</button>
                        </div>
                    </div>

                    <div class="mt-4 text-end">
                        <a href="{{ route('password.request') }}">¿Olvidaste tu contraseña?</a>
                    </div>
                    </form>
                </div>
            </div>
        </div>    
    </div>
</div>

<!-- Sweet alert -->
<x-alert /> 
@endsection

@pushOnce('script')
<script>
function togglePasswordVisibility() {
    let passwordField = document.getElementById("password");
    if (passwordField.type === "password") {
        passwordField.type = "text";
    } else {
        passwordField.type = "password";
    }
}
</script>
@endpushOnce



