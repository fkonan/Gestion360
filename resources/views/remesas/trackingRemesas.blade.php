@extends('layouts.app') 

@section('title', 'Tracking Remesas') 

@section('content')
<div class="container-fluid mt-5">
    <div class="row text-center">
         <div class="text-start" style="padding-left: 20%;">
            <a class="btn fw-bold my-2 text-light border" style="background-color: #0E2146;" href="{{ route('index') }}">
                <i class="fa fa-arrow-left me-2"></i> 
            </a>
        </div>
        <div class="col-xs-1 col-sm-1 col-md-3 col-lg-3 col-xl-4 col-xxl-4"></div>
        <div class="col-xs-10 col-sm-10 col-md-6 col-lg-6 col-xl-4 col-xxl-4 text-center">
            
            <img src="{{ asset('img/CopeCan-R.png') }}" alt="CopeCan-R">
            <h4 class="title">Consulta la ubicación de tu envío</h4>
            <form action="{{  route('trackingRemesas.consultar')  }}" method="POST">
                @csrf   
                <div class="input-group border rounded border-primary">
                    <div class="input-group-text">
                        <span class="fas fa-truck-moving"></span>
                    </div>
                    <input id="numeroRemision" name="numeroRemision" type="number" class="form-control" min="0" placeholder="Numero de remisión o factura" maxlength="20" required="required" autocomplete="off">
                    <button class="btn btn-primary" type="submit" id="Boton" name="Boton" value="Boton" style="background-color: #0E2146; color:#FFFFFF; border:1px solid #0E2146;">Buscar</button>
                </div>
            </form>
        </div>
        <div class="col-xs-1 col-sm-1 col-md-3 col-lg-3 col-xl-4 col-xxl-4"></div>
    </div>
</div>
@endsection