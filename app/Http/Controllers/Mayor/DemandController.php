<?php

namespace App\Http\Controllers\Mayor;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class DemandController extends Controller
{
    public function index()
    {
        return view('mayor.demands.index');
    }

    public function create()
    {
        return view('mayor.demands.create');
    }

    public function store(Request $request)
    {
        return redirect()->route('mayor.mandato.demands.index')
            ->with('success', 'Demanda registrada.');
    }

    public function show($id)
    {
        return view('mayor.demands.show');
    }

    public function edit($id)
    {
        return view('mayor.demands.edit');
    }

    public function update(Request $request, $id)
    {
        return redirect()->route('mayor.mandato.demands.index');
    }

    public function destroy($id)
    {
        return redirect()->route('mayor.mandato.demands.index');
    }

    public function storeVoice(Request $request)
    {
        return response()->json(['ok' => true]);
    }
}
