<?php

namespace App\Http\Controllers\Mayor;

use App\Http\Controllers\Controller;
use App\Models\Conversation;
use App\Models\FederalProgramAlert;
use App\Models\ResourceReopenNotification;
use App\Models\ResourceUserSave;
use App\Models\User;
use App\Services\AI\AssistantService;
use App\Services\Radar\HybridRadarReadService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;

class FederalProgramController extends Controller
{
    public function __construct(
        private AssistantService $assistant,
        private HybridRadarReadService $radarRead,
    ) {}

    public function index(Request $request)
    {
        $user = Auth::user();
        if (!$user instanceof User) abort(401);
        $municipality = $user->municipality;
        $programs     = $this->radarRead->enrichProgramsForUser(
            $this->radarRead->municipalityRadarPrograms($municipality),
            $user,
        );

        $total = $programs->count();
        $savedTotal = $programs->where('is_saved', true)->count();
        $reopenActiveTotal = $programs->where('is_reopen_notifying', true)->count();
        $highlightProgram = [
            'program_id' => $request->integer('highlight_program_id') ?: null,
            'canonical_cycle_id' => $request->integer('highlight_canonical_cycle_id') ?: null,
            'canonical_opportunity_id' => $request->integer('highlight_canonical_opportunity_id') ?: null,
        ];

        return view('mayor.federal-programs.index', compact(
            'municipality',
            'programs',
            'total',
            'savedTotal',
            'reopenActiveTotal',
            'highlightProgram',
        ));
    }

    public function askAssistant(Request $request, ?FederalProgramAlert $program = null)
    {
        $user = Auth::user();
        if (!$user instanceof User) abort(401);

        $municipality = $user->municipality;
        $resolvedProgram = $program;

        if ($resolvedProgram instanceof FederalProgramAlert) {
            if ($resolvedProgram->municipality_id !== $user->municipality_id) {
                abort(403);
            }
        } else {
            $resolvedProgram = $this->radarRead->resolveProgramForMunicipality(
                municipality: $municipality,
                legacyProgramId: $request->integer('program_id') ?: null,
                canonicalCycleId: $request->integer('canonical_cycle_id') ?: null,
                canonicalOpportunityId: $request->integer('canonical_opportunity_id') ?: null,
            );
        }

        if (!$resolvedProgram instanceof FederalProgramAlert) {
            abort(404);
        }

        $conversation = $user->conversations()->create([
            'municipality_id' => $user->municipality_id,
            'origin_module'   => 'radar_recursos',
            'title'           => 'Radar de Recursos: ' . Str::limit($resolvedProgram->program_name, 60),
            'auto_tags'       => ['recursos', 'radar_recursos'],
            'is_active'       => true,
            'last_message_at' => now(),
        ]);

        $lines = [];
        $lines[] = "Quero transformar esta oportunidade do Radar de Recursos em um plano de ação executável para o município.";
        $lines[] = "";
        $lines[] = "DADOS DA OPORTUNIDADE:";
        $lines[] = "- Nome: {$resolvedProgram->program_name}";
        if ($resolvedProgram->program_code) $lines[] = "- Código: {$resolvedProgram->program_code}";
        if ($resolvedProgram->ministry) $lines[] = "- Ministério: {$resolvedProgram->ministry}";
        if ($resolvedProgram->area) $lines[] = "- Área: {$resolvedProgram->area}";
        if ($resolvedProgram->funding_type) $lines[] = "- Tipo de recurso: {$resolvedProgram->funding_type}";
        if ($resolvedProgram->max_value) $lines[] = "- Valor máximo: R$ " . number_format((float) $resolvedProgram->max_value, 2, ',', '.');
        if ($resolvedProgram->deadline) $lines[] = "- Prazo: " . $resolvedProgram->deadline->format('d/m/Y');
        if ($resolvedProgram->source_url) $lines[] = "- Link: {$resolvedProgram->source_url}";
        if ($resolvedProgram->description) {
            $lines[] = "";
            $lines[] = "DESCRIÇÃO (resumo):";
            $lines[] = Str::limit(trim($resolvedProgram->description), 900);
        }
        $lines[] = "";
        $lines[] = "ENTREGA OBRIGATÓRIA:";
        $lines[] = "1) Resumo executivo (em 5 linhas) do porquê vale a pena (ou não ).";
        $lines[] = "2) Checklist de documentos e providências (com responsáveis sugeridos).";
        $lines[] = "3) Cronograma até o prazo (marcos e datas).";
        $lines[] = "4) Riscos e pontos de atenção (o que costuma reprovar).";
        $lines[] = "5) Próximas 3 ações imediatas para hoje.";

        $this->assistant->chat(
            userMessage: implode("\n", $lines),
            mayor: $user,
            conversation: $conversation,
        );

        return redirect()->route('mayor.chat.show', $conversation);
    }

    public function detail(Request $request)
    {
        $user = Auth::user();
        if (!$user instanceof User) {
            abort(401);
        }

        $detail = $this->radarRead->canonicalDetailPayloadForMunicipality(
            municipality: $user->municipality,
            legacyProgramId: $request->integer('program_id') ?: null,
            canonicalCycleId: $request->integer('canonical_cycle_id') ?: null,
            canonicalOpportunityId: $request->integer('canonical_opportunity_id') ?: null,
            user: $user,
        );

        if (!is_array($detail)) {
            abort(404);
        }

        return response()->json($detail);
    }

    public function toggleSave(Request $request)
    {
        $user = Auth::user();
        if (!$user instanceof User) abort(401);

        $resolved = $this->radarRead->resolveCanonicalEntitiesForMunicipality(
            municipality: $user->municipality,
            legacyProgramId: $request->integer('program_id') ?: null,
            canonicalCycleId: $request->integer('canonical_cycle_id') ?: null,
            canonicalOpportunityId: $request->integer('canonical_opportunity_id') ?: null,
        );

        if (!is_array($resolved)) {
            abort(404);
        }

        $opportunity = $resolved['opportunity'];
        $cycle = $resolved['cycle'];

        $existing = ResourceUserSave::query()
            ->where('user_id', $user->id)
            ->where('municipality_id', $user->municipality_id)
            ->where('resource_opportunity_id', $opportunity->id)
            ->first();

        if ($existing instanceof ResourceUserSave) {
            $existing->delete();

            return back()->with('success', 'Oportunidade removida dos salvos.');
        }

        ResourceUserSave::query()->create([
            'user_id' => $user->id,
            'municipality_id' => $user->municipality_id,
            'resource_opportunity_id' => $opportunity->id,
            'resource_opportunity_cycle_id' => $cycle->id,
            'saved_from' => 'radar_card',
            'preferences' => [
                'read_mode' => data_get($resolved, 'program.read_mode'),
            ],
            'last_viewed_at' => now(),
        ]);

        return back()->with('success', 'Oportunidade salva com sucesso.');
    }

    public function toggleReopenNotification(Request $request)
    {
        $user = Auth::user();
        if (!$user instanceof User) abort(401);

        $resolved = $this->radarRead->resolveCanonicalEntitiesForMunicipality(
            municipality: $user->municipality,
            legacyProgramId: $request->integer('program_id') ?: null,
            canonicalCycleId: $request->integer('canonical_cycle_id') ?: null,
            canonicalOpportunityId: $request->integer('canonical_opportunity_id') ?: null,
        );

        if (!is_array($resolved)) {
            abort(404);
        }

        $program = $resolved['program'];
        $opportunity = $resolved['opportunity'];
        $cycle = $resolved['cycle'];

        if (!in_array($program->status, ['closed_recently', 'archived', 'reopened'], true)) {
            return back()->with('warning', 'A notificacao de reabertura so pode ser ativada para oportunidades encerradas ou reabertas.');
        }

        $existing = ResourceReopenNotification::query()
            ->where('user_id', $user->id)
            ->where('municipality_id', $user->municipality_id)
            ->where('resource_opportunity_id', $opportunity->id)
            ->first();

        if ($existing instanceof ResourceReopenNotification && $existing->cancelled_at === null && $existing->status === 'active') {
            $existing->update([
                'status' => 'cancelled',
                'cancelled_at' => now(),
            ]);

            return back()->with('success', 'Notificacao de reabertura desativada.');
        }

        if ($existing instanceof ResourceReopenNotification) {
            $existing->update([
                'last_cycle_id' => $cycle->id,
                'channel' => 'platform',
                'status' => 'active',
                'criteria' => [
                    'notify_on_reopen' => true,
                ],
                'subscribed_at' => $existing->subscribed_at ?? now(),
                'cancelled_at' => null,
            ]);
        } else {
            ResourceReopenNotification::query()->create([
                'user_id' => $user->id,
                'municipality_id' => $user->municipality_id,
                'resource_opportunity_id' => $opportunity->id,
                'last_cycle_id' => $cycle->id,
                'channel' => 'platform',
                'status' => 'active',
                'criteria' => [
                    'notify_on_reopen' => true,
                ],
                'subscribed_at' => now(),
            ]);
        }

        return back()->with('success', 'Notificacao de reabertura ativada.');
    }
}
