<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Municipality;

class UsageLogController extends Controller
{
    public function index(Municipality $municipality)
    {
        return view('admin.municipalities.logs', compact('municipality'));
    }
}
