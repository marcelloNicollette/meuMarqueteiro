<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Municipality;
use Illuminate\Http\Request;

class OnboardingController extends Controller
{
    public function show(Municipality $municipality)
    {
        return view('admin.municipalities.onboarding', compact('municipality'));
    }

    public function saveVoiceProfile(Request $request, Municipality $municipality)
    {
        $data = $request->validate([
            'tone'       => 'required|string',
            'style'      => 'required|string',
            'vocabulary' => 'required|string',
            'avoid'      => 'nullable|string',
        ]);

        $municipality->update([
            'voice_profile'     => $data,
            'onboarding_status' => 'in_progress',
        ]);

        return back()->with('success', 'Perfil de voz salvo.');
    }

    public function savePoliticalMap(Request $request, Municipality $municipality)
    {
        $data = $request->validate([
            'allies'   => 'nullable|string',
            'neutral'  => 'nullable|string',
            'opposition'=> 'nullable|string',
            'notes'    => 'nullable|string',
        ]);

        $municipality->update(['political_map' => $data]);

        return back()->with('success', 'Mapa político salvo.');
    }

    public function complete(Request $request, Municipality $municipality)
    {
        $municipality->update([
            'onboarding_status'      => 'completed',
            'onboarding_completed_at'=> now(),
        ]);

        return redirect()->route('admin.municipalities.show', $municipality)
            ->with('success', 'Onboarding concluído! O prefeito já pode acessar o sistema.');
    }

    public function uploadDocuments(Request $request, Municipality $municipality)
    {
        return back()->with('success', 'Documentos recebidos.');
    }

    public function triggerDataIngestion(Request $request, Municipality $municipality)
    {
        return back()->with('success', 'Ingestão de dados iniciada.');
    }
}
