<?php

namespace App\Modules\Camara\Http\Controllers;

use App\Http\Controllers\Controller;

class CamaraController extends Controller
{
  public function index()
  {
    return view('camara.index');
  }

  public function enroll()
  {
    return view('camara.enroll');
  }

  public function recognize()
  {
    return view('camara.recognize');
  }
}
