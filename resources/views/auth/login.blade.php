@extends('layouts.app')

@section('title','Login')

@section('content')

<br><br><br><br><br><br>
<div class="container-fluid">
    <div class="row" style="margin: auto;">
        <div class="col-xs-12 col-sm-2 col-md-3 col-lg-4 col-xl-4 col-xxl-4"></div>
            <div class="col-xs-12 col-sm-8 col-md-6 col-lg-4 col-xl-4 col-xxl-4">
                <h4>Sistema: <b>Gestión</b></h4>
                <div class="card">
                <div class="card-body login-card-body">
                    <p class="login-box-msg" style="text-align: left; font-size: 30px;"><b>!Hola de nuevo¡</b></p>
                    <form method="POST" action="{{ route('login') }}">
                    @csrf
                    <div class="d-flex flex-column mb-3">
                        <div class="input-group">
                            <input type="text" class="form-control" id="documento" name="documento" placeholder="Usuario" value="{{ old('documento') }}" required>
                            <div class="input-group-text">
                                <span class="fas fa-user"></span>
                            </div>
                        </div>   
                        @error('documento')          
                        <small class="text-danger">{{ $message }}</small>
                        @enderror
                    </div>

                    <div class="d-flex flex-column mb-3">
                        <div class="input-group">
                            <input type="password" class="form-control" id="password" name="password" placeholder="Password" required>
                            <div class="input-group-text">
                                <a href="#"><span class="fas fa-eye" onclick="togglePasswordVisibility()"></span></a>
                            </div>
                        </div>
                        @error('password')
                                <small class="text-danger">{{ $message }}</small>
                        @enderror
                    </div>

                    <div class="row"></div>
                        <div class="col-4">
                        <button type="submit" name="Boton" value="Boton" id="Boton" class="btn btn-warning btn-block">Ingresar</button>
                        </div>
                    </div>
                    </form>
                </div>
            </div>
        </div>    
    </div>
</div>
@endsection

<!--
<div style="padding-top:5%;">
    <script src="https://autogestion.copetran.com.co/cdn/scripts/HeaderFooter.js" defer></script>
</div>
-->

