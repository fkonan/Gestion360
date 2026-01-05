<?php

namespace App\Modules\GestionWeb\Http\Controllers;

use App\Http\Controllers\Controller;


class GestionWebController extends Controller
{
  public function loginChatBot()
  {
    return redirect()->away("https://chat.copetran.com/login");
  }
}
