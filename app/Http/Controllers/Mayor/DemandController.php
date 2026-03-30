<?php

namespace App\Http\Controllers\Mayor;

use App\Http\Controllers\Controller;
use App\Models\Demand;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use Illuminate\View\View;

class DemandController extends Controller
{
    private const VALID_STATUSES = ['pending', 'in_progress', 'resolved', 'cancelled'];

    public function index(): View
    {
        $user = Auth::user();
        if (!$user instanceof User) abort(401);
        $municipality = $user->municipality;

        $contactAreas = $municipality->contactAreas()->where('active', true)->get();

        $demands = Demand::query()
            ->where('municipality_id', $municipality->id)
            ->orderByDesc('created_at')
            ->limit(20)
            ->get();

        return view('mayor.demands.index', compact('municipality', 'demands', 'contactAreas'));
    }

    public function store(Request $request)
    {
        $user = Auth::user();
        if (!$user instanceof User) abort(401);
        $municipality = $user->municipality;

        $data = $request->validate([
            'raw_input'  => ['required', 'string', 'max:4000'],
            'input_type' => ['nullable', 'in:text,voice'],
            'locality'   => ['nullable', 'string', 'max:255'],
            'area'       => ['nullable', 'string', 'max:120'],
            'contact_area_id' => ['nullable', 'exists:contact_areas,id'],
            'priority'   => ['nullable', 'in:alta,media,baixa'],
            'due_date'   => ['nullable', 'date'],
            'is_urgent'  => ['nullable', 'boolean'],
        ]);

        $areaName = $data['area'] ?? null;
        if (!empty($data['contact_area_id'])) {
            $ca = $municipality->contactAreas()->where('id', $data['contact_area_id'])->first();
            if ($ca) $areaName = $ca->name;
        }

        Demand::create([
            'municipality_id' => $municipality->id,
            'registered_by'   => $user->id,
            'input_type'      => $data['input_type'] ?? 'text',
            'raw_input'       => $data['raw_input'],
            'title'           => Str::limit(trim($data['raw_input']), 90),
            'description'     => null,
            'area'            => $areaName,
            'locality'        => $data['locality'] ?? null,
            'contact_area_id' => $data['contact_area_id'] ?? null,
            'priority'        => $data['priority'] ?? 'media',
            'due_date'        => $data['due_date'] ?? null,
            'is_urgent'       => (bool) ($data['is_urgent'] ?? false),
            'status'          => 'pending',
        ]);

        return redirect()->route('mayor.mandato.demands.index')
            ->with('success', 'Demanda registrada.');
    }

    public function show(Demand $demand): View
    {
        $user = Auth::user();
        if (!$user instanceof User) abort(401);
        if ($demand->municipality_id !== $user->municipality_id) {
            abort(403);
        }

        $municipality = $user->municipality;
        $demand->load(['comments.user']);
        return view('mayor.demands.show', compact('demand', 'municipality'));
    }

    public function updateStatus(Request $request, Demand $demand)
    {
        $user = Auth::user();
        if (!$user instanceof User) abort(401);
        if ($demand->municipality_id !== $user->municipality_id) {
            abort(403);
        }

        $data = $request->validate([
            'status' => ['required', 'in:' . implode(',', self::VALID_STATUSES)],
        ]);

        $nextStatus = $data['status'];

        $payload = ['status' => $nextStatus];
        if ($nextStatus === 'resolved') {
            $payload['resolved_at'] = now();
        } else {
            $payload['resolved_at'] = null;
        }

        $demand->update($payload);

        return redirect()->route('mayor.mandato.demands.show', $demand)
            ->with('success', 'Status da demanda atualizado.');
    }

    public function update(Request $request, Demand $demand)
    {
        $user = Auth::user();
        if (!$user instanceof User) abort(401);
        if ($demand->municipality_id !== $user->municipality_id) {
            abort(403);
        }

        $data = $request->validate([
            'priority' => ['required', 'in:alta,media,baixa'],
            'due_date' => ['nullable', 'date'],
        ]);

        $demand->update([
            'priority' => $data['priority'],
            'due_date' => $data['due_date'] ?? null,
        ]);

        return redirect()->route('mayor.mandato.demands.show', $demand)
            ->with('success', 'Dados da demanda atualizados.');
    }

    public function addComment(Request $request, Demand $demand)
    {
        $user = Auth::user();
        if (!$user instanceof User) abort(401);
        if ($demand->municipality_id !== $user->municipality_id) {
            abort(403);
        }

        $data = $request->validate([
            'comment' => ['required', 'string', 'max:2000'],
        ]);

        $demand->comments()->create([
            'user_id' => $user->id,
            'comment' => $data['comment'],
        ]);

        return redirect()->route('mayor.mandato.demands.show', $demand)
            ->with('success', 'Comentário adicionado.');
    }

    public function storeVoice(Request $request)
    {
        return response()->json(['ok' => false], 501);
    }
}
