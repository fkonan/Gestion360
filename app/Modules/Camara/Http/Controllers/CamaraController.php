<?php

namespace App\Modules\Camara\Http\Controllers;

use App\Http\Controllers\Controller;

class CamaraController extends Controller
{
  public function index()
  {
    return view('camara::camara.index');
  }

  public function enroll()
  {
    return view('camara::camara.enroll');
  }

  public function recognize()
  {
    return view('camara::camara.recognize');
  }
}
