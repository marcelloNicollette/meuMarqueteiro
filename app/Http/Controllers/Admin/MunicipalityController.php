<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Municipality;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class MunicipalityController extends Controller
{
    public function index()
    {
        $municipalities = Municipality::with('mayor')
            ->orderByDesc('created_at')
            ->paginate(20);

        return view('admin.municipalities.index', compact('municipalities'));
    }

    public function create()
    {
        return view('admin.municipalities.create');
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'ibge_code'          => 'required|string|unique:municipalities',
            'name'               => 'required|string|max:255',
            'state'              => 'required|string|max:100',
            'state_code'         => 'required|string|max:2',
            'population'         => 'nullable|integer',
            'idhm'               => 'nullable|numeric',
            'subscription_tier'  => 'required|in:essencial,estrategico,parceiro',
            'mayor_name'         => 'required|string|max:255',
            'mayor_email'        => 'required|email|unique:users,email',
            'mayor_password'     => 'required|string|min:8',
        ]);

        $municipality = Municipality::create([
            'ibge_code'           => $data['ibge_code'],
            'name'                => $data['name'],
            'state'               => $data['state'],
            'state_code'          => $data['state_code'],
            'population'          => $data['population'] ?? null,
            'idhm'                => $data['idhm'] ?? null,
            'subscription_tier'   => $data['subscription_tier'],
            'subscription_active' => true,
            'onboarding_status'   => 'pending',
        ]);

        $mayor = User::create([
            'name'            => $data['mayor_name'],
            'email'           => $data['mayor_email'],
            'password'        => Hash::make($data['mayor_password']),
            'role'            => 'mayor',
            'municipality_id' => $municipality->id,
            'is_active'       => true,
        ]);
        $mayor->assignRole('mayor');

        return redirect()->route('admin.municipalities.onboarding.show', $municipality)
            ->with('success', "Município {$municipality->name} criado! Inicie o onboarding.");
    }

    public function show(Municipality $municipality)
    {
        $municipality->load('mayor', 'users', 'governmentCommitments');
        $stats = [
            'commitments_total'    => $municipality->governmentCommitments()->count(),
            'commitments_done'     => $municipality->governmentCommitments()->where('status', 'entregue')->count(),
            'commitments_at_risk'  => $municipality->governmentCommitments()->where('status', 'em_risco')->count(),
            'conversations_total'  => $municipality->conversations()->count(),
            'contents_generated'   => $municipality->generatedContents()->count(),
        ];
        return view('admin.municipalities.show', compact('municipality', 'stats'));
    }

    public function edit(Municipality $municipality)
    {
        return view('admin.municipalities.edit', compact('municipality'));
    }

    public function update(Request $request, Municipality $municipality)
    {
        $data = $request->validate([
            'name'                => 'required|string|max:255',
            'ibge_code'           => 'nullable|string|unique:municipalities,ibge_code,' . $municipality->id,
            'state'               => 'required|string|max:100',
            'state_code'          => 'required|string|max:2',
            'region'              => 'nullable|string|max:50',
            'population'          => 'nullable|integer',
            'idhm'                => 'nullable|numeric|min:0|max:1',
            'gdp'                 => 'nullable|numeric',
            'area_km2'            => 'nullable|numeric',
            'subscription_tier'   => 'required|in:essencial,estrategico,parceiro',
            'subscription_active' => 'nullable|boolean',
            'voice_tone'          => 'nullable|string|max:255',
            'voice_style'         => 'nullable|string|max:255',
            'voice_vocabulary'    => 'nullable|string|max:255',
            'voice_avoid'         => 'nullable|string|max:255',
            'political_allies'    => 'nullable|string',
            'political_neutral'   => 'nullable|string',
            'political_opposition' => 'nullable|string',
            'political_notes'     => 'nullable|string',
        ]);

        // Montar voice_profile
        $voiceProfile = array_filter([
            'tone'       => $data['voice_tone'] ?? null,
            'style'      => $data['voice_style'] ?? null,
            'vocabulary' => $data['voice_vocabulary'] ?? null,
            'avoid'      => $data['voice_avoid'] ?? null,
        ]);

        // Montar political_map
        $politicalMap = array_filter([
            'allies'     => $data['political_allies'] ?? null,
            'neutral'    => $data['political_neutral'] ?? null,
            'opposition' => $data['political_opposition'] ?? null,
            'notes'      => $data['political_notes'] ?? null,
        ]);

        $municipality->update([
            'name'                => $data['name'],
            'ibge_code'           => $data['ibge_code'] ?? $municipality->ibge_code,
            'state'               => $data['state'],
            'state_code'          => $data['state_code'],
            'region'              => $data['region'] ?? null,
            'population'          => $data['population'] ?? null,
            'idhm'                => $data['idhm'] ?? null,
            'gdp'                 => $data['gdp'] ?? null,
            'area_km2'            => $data['area_km2'] ?? null,
            'subscription_tier'   => $data['subscription_tier'],
            'subscription_active' => $request->boolean('subscription_active'),
            'voice_profile'       => !empty($voiceProfile) ? $voiceProfile : $municipality->voice_profile,
            'political_map'       => !empty($politicalMap) ? $politicalMap : $municipality->political_map,
        ]);

        return redirect()->route('admin.municipalities.show', $municipality)
            ->with('success', 'Município atualizado com sucesso.');
    }

    public function toggleActive(Municipality $municipality)
    {
        $municipality->update(['subscription_active' => !$municipality->subscription_active]);
        $status = $municipality->subscription_active ? 'ativado' : 'desativado';
        return back()->with('success', "Município {$municipality->name} {$status} com sucesso.");
    }

    public function destroy(Municipality $municipality)
    {
        $municipality->delete();
        return redirect()->route('admin.municipalities.index')
            ->with('success', 'Município removido com sucesso.');
    }
}
