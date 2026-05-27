<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Municipality;
use App\Models\MunicipalityLocality;
use Illuminate\Http\Request;

class MunicipalityLocalityController extends Controller
{
    public function index(Municipality $municipality)
    {
        $localities = $municipality->localities()->get();

        return view('admin.municipalities.localities', compact('municipality', 'localities'));
    }

    public function store(Request $request, Municipality $municipality)
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:120'],
            'type' => ['required', 'in:bairro,distrito,comunidade,zona,rural,outro'],
            'zone' => ['nullable', 'string', 'max:80'],
            'notes' => ['nullable', 'string'],
            'active' => ['nullable', 'boolean'],
        ]);

        $municipality->localities()->create([
            'name' => $data['name'],
            'type' => $data['type'],
            'zone' => $data['zone'] ?? null,
            'notes' => $data['notes'] ?? null,
            'active' => $request->boolean('active', true),
        ]);

        return redirect()->route('admin.municipalities.localities.index', $municipality)
            ->with('success', 'Localidade adicionada.');
    }

    public function update(Request $request, Municipality $municipality, MunicipalityLocality $locality)
    {
        if ($locality->municipality_id !== $municipality->id) {
            abort(403);
        }

        $data = $request->validate([
            'name' => ['required', 'string', 'max:120'],
            'type' => ['required', 'in:bairro,distrito,comunidade,zona,rural,outro'],
            'zone' => ['nullable', 'string', 'max:80'],
            'notes' => ['nullable', 'string'],
            'active' => ['nullable', 'boolean'],
        ]);

        $locality->update([
            'name' => $data['name'],
            'type' => $data['type'],
            'zone' => $data['zone'] ?? null,
            'notes' => $data['notes'] ?? null,
            'active' => $request->boolean('active'),
        ]);

        return back()->with('success', 'Localidade atualizada.');
    }

    public function destroy(Municipality $municipality, MunicipalityLocality $locality)
    {
        if ($locality->municipality_id !== $municipality->id) {
            abort(403);
        }

        $locality->delete();

        return back()->with('success', 'Localidade removida.');
    }
}
