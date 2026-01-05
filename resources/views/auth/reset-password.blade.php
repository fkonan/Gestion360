@extends('layouts.app')

@section('title','Restablecer contraseña')

@section('content')
<br><br><br><br>
<div class="container login-container">
    <div class="row" style="margin: auto;">
        <div class="col-xs-12 col-sm-2 col-md-3 col-lg-4 col-xl-4 col-xxl-4"></div>
            <div class="col-xs-12 col-sm-8 col-md-6 col-lg-4 col-xl-4 col-xxl-4">
                <h4>Sistema: <b>Autogestión</b></h4>
                <div class="card p-1">
                <div class="card-body p-4">
                    <p class="mb-3 text-muted">
                        Por favor, ingresa tu nueva contraseña y confírmala para restablecer el acceso a tu cuenta.
                    </p>
                    <form action="{{ route('password.update') }}"
                        method="POST"
                        onsubmit="deshabilitarSubmit(this)">

                        @csrf
                        <input type="hidden" name="token" value="{{ request()->route('token') }}">
                        <input type="hidden" name="email" value="{{ request()->query('email') }}">

                        <div class="input-group">
                            <input type="password" id="password" class="form-control" name="password" placeholder="Nueva contraseña" required>
                            <div class="input-group-text">
                                <a href="#"><span class="fas fa-eye" onclick="togglePasswordVisibility('password')"></span></a>
                            </div>
                        </div>

                        <div class="input-group mt-3">
                            <input type="password" id="passwordConfirm" class="form-control password" name="password_confirmation" placeholder="Confirmar contraseña" required>
                            <div class="input-group-text">
                                <a href="#"><span class="fas fa-eye" onclick="togglePasswordVisibility('passwordConfirm')"></span></a>
                            </div>
                        </div>

                        @error('password')
                            <small class="text-danger">{{ $message }}</small>
                        @enderror

                        <button class="btn btn-warning btn-block mt-3" type="submit" name="Boton" value="Boton" id="Boton">Cambiar contraseña</button>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@pushOnce('script')
<script>
function togglePasswordVisibility(id) {
    let passwordField = document.getElementById(id);
    if (passwordField.type === "password") {
        passwordField.type = "text";
    } else {
        passwordField.type = "password";
    }
}
</script>
@endpushOnce
