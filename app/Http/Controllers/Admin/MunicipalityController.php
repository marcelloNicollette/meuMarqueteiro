<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Municipality;
use App\Models\ProjectThesis;
use App\Models\User;
use App\Services\Communication\CommunicationSettingsService;
use App\Services\Projects\ProjectBankLibraryService;
use App\Services\ResolveAi\ResolveAiSettingsService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class MunicipalityController extends Controller
{
    public function __construct(
        private readonly CommunicationSettingsService $communicationSettings,
        private readonly ResolveAiSettingsService $resolveAiSettings,
        private readonly ProjectBankLibraryService $projectBankLibrary,
    ) {}

    public function index()
    {
        $municipalities = Municipality::with('mayor')
            ->orderByDesc('created_at')
            ->paginate(20);

        $municipalities->getCollection()->transform(function (Municipality $municipality) {
            $municipality->setAttribute('project_bank_summary', $this->projectBankSummary($municipality));

            return $municipality;
        });

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
            'contact_areas_total'  => $municipality->contactAreas()->count(),
            'contact_areas_ready'  => $municipality->contactAreas()->where('active', true)->where(function ($query) {
                $query->whereNotNull('notification_email')->orWhereNotNull('email');
            })->count(),
            'localities_total'     => $municipality->localities()->count(),
        ];
        $communicationSettings = $this->communicationSettings->forMunicipality($municipality);
        $resolveAiSettings = $this->resolveAiSettings->forMunicipality($municipality);
        $projectBankSummary = $this->projectBankSummary($municipality);

        return view('admin.municipalities.show', compact(
            'municipality',
            'stats',
            'communicationSettings',
            'resolveAiSettings',
            'projectBankSummary'
        ));
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
            'polítical_allies'    => 'nullable|string',
            'polítical_neutral'   => 'nullable|string',
            'polítical_opposition' => 'nullable|string',
            'polítical_notes'     => 'nullable|string',
        ]);

        // Montar voice_profile
        $voiceProfile = array_filter([
            'tone'       => $data['voice_tone'] ?? null,
            'style'      => $data['voice_style'] ?? null,
            'vocabulary' => $data['voice_vocabulary'] ?? null,
            'avoid'      => $data['voice_avoid'] ?? null,
        ]);

        // Montar political_map
        $políticalMap = array_filter([
            'allies'     => $data['polítical_allies'] ?? null,
            'neutral'    => $data['polítical_neutral'] ?? null,
            'opposition' => $data['polítical_opposition'] ?? null,
            'notes'      => $data['polítical_notes'] ?? null,
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
            'political_map'       => !empty($políticalMap) ? $políticalMap : $municipality->political_map,
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

    public function refreshProjectBank(Municipality $municipality): RedirectResponse
    {
        if (!$municipality->subscription_active || $municipality->onboarding_status !== 'completed') {
            return back()->with('error', "O Banco de Projetos só pode ser recurado manualmente depois que {$municipality->name} estiver ativo e com onboarding concluído.");
        }

        try {
            $theses = $this->projectBankLibrary->ensureLibraryForMunicipality(
                $municipality,
                force: true,
                reason: 'admin_manual_refresh'
            );

            return back()->with('success', "Curadoria do Banco de Projetos reexecutada para {$municipality->name} ({$theses->count()} tese(s)).");
        } catch (\Throwable $e) {
            report($e);
            $this->projectBankLibrary->markRefreshRecommended($municipality->refresh(), 'admin_manual_refresh_failed');

            return back()->with('error', "Não foi possível reexecutar a curadoria do Banco para {$municipality->name} agora.");
        }
    }

    public function destroy(Municipality $municipality)
    {
        $municipality->delete();
        return redirect()->route('admin.municipalities.index')
            ->with('success', 'Município removido com sucesso.');
    }

    private function projectBankSummary(Municipality $municipality): array
    {
        $settings = (array) ($municipality->settings ?? []);
        $projectBank = (array) ($settings['project_bank'] ?? []);
        $librarySize = (int) data_get(
            $projectBank,
            'library_size',
            ProjectThesis::query()->where('municipality_id', $municipality->id)->count()
        );
        $needsRefresh = (bool) data_get($projectBank, 'needs_refresh', false);
        $bootstrappedAt = data_get($projectBank, 'bootstrapped_at');
        $lastCuratedAt = data_get($projectBank, 'last_curated_at');

        return [
            'library_size' => $librarySize,
            'bootstrapped_at' => $bootstrappedAt,
            'last_curated_at' => $lastCuratedAt,
            'needs_refresh' => $needsRefresh,
            'refresh_reason' => (string) data_get($projectBank, 'refresh_recommended_reason', ''),
            'status_label' => match (true) {
                $needsRefresh => 'Refresh recomendado',
                !empty($lastCuratedAt) => 'Curadoria em dia',
                !empty($bootstrappedAt) => 'Bootstrap inicial pronto',
                default => 'Pendente',
            },
            'status_tone' => match (true) {
                $needsRefresh => 'warning',
                !empty($lastCuratedAt) || !empty($bootstrappedAt) => 'success',
                default => 'neutral',
            },
        ];
    }
}
