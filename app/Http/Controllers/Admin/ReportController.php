<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Municipality;
use Illuminate\Http\Request;

class ReportController extends Controller
{
    public function generate(Request $request, Municipality $municipality)
    {
        return back()->with('success', 'Relatório em geração. Você será notificado quando estiver pronto.');
    }
}
