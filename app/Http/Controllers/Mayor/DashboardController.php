<?php

namespace App\Http\Controllers\Mayor;

use App\Http\Controllers\Controller;

class DashboardController extends Controller
{
    public function index()
    {
        return redirect()->route('mayor.situacao');
    }
}
