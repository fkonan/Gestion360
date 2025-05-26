<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\Auth;
use Illuminate\Http\Request;

class GestionWebController extends Controller
{
    public function loginChatBot(){
        return redirect()->away("https://chat.copetran.com/login");
    }
}
