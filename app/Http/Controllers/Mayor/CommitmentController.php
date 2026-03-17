<?php

namespace App\Http\Controllers\Mayor;

use App\Http\Controllers\Controller;
use App\Models\GovernmentCommitment;
use Illuminate\Http\Request;

class CommitmentController extends Controller
{
    public function index()
    {
        $municipality = auth()->user()->municipality;
        $commitments  = $municipality->governmentCommitments()->orderByDesc('created_at')->get();

        $total     = $commitments->count();
        $delivered = $commitments->where('status', 'entregue')->count();
        $running   = $commitments->where('status', 'em_andamento')->count();
        $atRisk    = $commitments->where('status', 'em_risco')->count();
        $progress  = $total > 0 ? round(($delivered / $total) * 100) : 0;

        return view('mayor.mandato.index', compact(
            'commitments', 'total', 'delivered', 'running', 'atRisk', 'progress'
        ));
    }

    public function create()
    {
        return view('mayor.mandato.create');
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'title'                  => 'required|string|max:255',
            'description'            => 'nullable|string',
            'area'                   => 'required|string',
            'priority'               => 'required|in:alta,media,baixa',
            'deadline'               => 'nullable|date',
            'responsible_secretary'  => 'nullable|string|max:255',
            'budget_allocated'       => 'nullable|numeric',
        ]);

        auth()->user()->municipality->governmentCommitments()->create(array_merge($data, [
            'status'           => 'prometido',
            'progress_percent' => 0,
        ]));

        return redirect()->route('mayor.mandato.commitments.index')
            ->with('success', 'Compromisso registrado.');
    }

    public function show(GovernmentCommitment $commitment)
    {
        return view('mayor.mandato.show', compact('commitment'));
    }

    public function edit(GovernmentCommitment $commitment)
    {
        return view('mayor.mandato.edit', compact('commitment'));
    }

    public function update(Request $request, GovernmentCommitment $commitment)
    {
        $data = $request->validate([
            'title'            => 'required|string|max:255',
            'description'      => 'nullable|string',
            'status'           => 'required|in:prometido,em_andamento,entregue,em_risco,cancelado',
            'progress_percent' => 'required|integer|min:0|max:100',
            'priority'         => 'required|in:alta,media,baixa',
            'deadline'         => 'nullable|date',
            'responsible_secretary' => 'nullable|string|max:255',
            'budget_allocated' => 'nullable|numeric',
            'budget_spent'     => 'nullable|numeric',
        ]);

        $commitment->update($data);

        return redirect()->route('mayor.mandato.commitments.index')
            ->with('success', 'Compromisso atualizado.');
    }

    public function destroy(GovernmentCommitment $commitment)
    {
        $commitment->delete();
        return redirect()->route('mayor.mandato.commitments.index')
            ->with('success', 'Compromisso removido.');
    }
}
