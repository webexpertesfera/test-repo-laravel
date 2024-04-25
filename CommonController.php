<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class CommonController extends Controller
{
    
    public function unauth()
    {
        return response()->json(['message' => "unauth"]);
    }
}
