@extends('layouts.app')

@section('title','Olvido contraseña')

@section('content')
<br><br><br>
<div class="container login-container">
    <div class="row" style="margin: auto;">
        <div class="col-xs-12 col-sm-2 col-md-3 col-lg-4 col-xl-4 col-xxl-4"></div>
            <div class="col-xs-12 col-sm-8 col-md-6 col-lg-4 col-xl-4 col-xxl-4">
                <h4>Sistema: <b>{{ env('APP_NAME') }}</b></h4>
                <div class="card p-1">
                <div class="card-body p-4">
                    <h4 class="pb-2"><b>Recuperar contraseña</b></h4>
                    <p class="mb-3 text-center">
                        Ingresa tu correo electrónico y te enviaremos un enlace para restablecer tu contraseña.
                    </p>
                    <form action="{{ route('password.email') }}" 
                        method="POST"
                        onsubmit="deshabilitarSubmit(this)">

                        @csrf
                        <input class="form-control" type="email" name="email" placeholder="Correo" required>
                        @error('email')          
                            <small class="text-danger fw-bold">{{ $message }}</small>
                        @enderror

                        <div class="d-flex justify-content-start mt-3 gap-2">
                            <button class="btn btn-warning" type="submit" name="Boton" value="Boton" id="Boton">Enviar enlace</button>
                            <a href="{{ route('login') }}" class="btn btn-secondary">Atras</a>
                        </div>
                    </form>
                </div>
            </div>
        </div>    
    </div>
</div>
@endsection
