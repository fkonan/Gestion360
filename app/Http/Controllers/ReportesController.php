<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\Auth;
use Illuminate\Http\Request;

class ReportesController extends Controller
{
    public function reportesConductores(){
        return view("reportes.conductores.main");
    }

    public function reportesPasajes(){
        return view("reportes.pasajes.main");
    }
}
