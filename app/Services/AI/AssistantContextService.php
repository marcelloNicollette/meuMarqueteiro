<?php

namespace App\Services\AI;

use App\Models\Demand;
use App\Models\DemandComment;
use App\Models\GeneratedContent;
use App\Models\MandateAction;
use App\Models\MandateAxis;
use App\Models\MandatePromise;
use App\Models\Municipality;
use App\Models\User;
use App\Services\Mandato\MandateProjectionService;

class AssistantContextService
{
    public function __construct(
        private readonly MandateProjectionService $projection,
    ) {}

    public function buildOperationalContext(Municipality $municipality, User $mayor): array
    {
        return [
            'strategic_profile' => $this->buildStrategicProfileContext($municipality, $mayor),
            'demands' => $this->buildDemandsContext($municipality),
            'mandate_execution' => $this->buildMandateExecutionContext($municipality),
            'recent_contents' => $this->buildRecentContentsContext($municipality),
        ];
    }

    private function buildStrategicProfileContext(Municipality $municipality, User $mayor): string
    {
        $settings = is_array($municipality->settings) ? $municipality->settings : [];
        $preferences = is_array($mayor->preferences) ? $mayor->preferences : [];

        $lines = [];

        if (!empty($preferences['preferred_name'])) {
            $lines[] = "- O prefeito prefere ser chamado de: {$preferences['preferred_name']}";
        }

        if (!empty($settings['partido'])) {
            $lines[] = "- Partido: {$settings['partido']}";
        }

        if (!empty($settings['início_mandato']) || !empty($settings['fim_mandato'])) {
            $início = $settings['início_mandato'] ?? '?';
            $fim = $settings['fim_mandato'] ?? '?';
            $lines[] = "- Mandato: {$início} a {$fim}";
        }

        if (!empty($settings['resumo_programa'])) {
            $lines[] = "- Resumo do programa de governo: {$settings['resumo_programa']}";
        }

        if (!empty($settings['lista_projetos'])) {
            $lines[] = "- Projetos prioritarios em andamento: {$settings['lista_projetos']}";
        }

        if (!empty($settings['sensibilidades'])) {
            $lines[] = "- Sensibilidades locais e políticas: {$settings['sensibilidades']}";
        }

        if (!empty($settings['histórico_comunicação'])) {
            $lines[] = "- Histórico de comunicação do mandato: {$settings['histórico_comunicação']}";
        }

        if (!empty($settings['agenda_integrada'])) {
            $lines[] = "- Agenda integrada: {$settings['agenda_integrada']}";
        }

        return $lines === []
            ? 'Contexto proprietario do onboarding ainda incompleto.'
            : implode("\n", $lines);
    }

    private function buildDemandsContext(Municipality $municipality): string
    {
        $openDemandStatuses = ['pending', 'in_progress'];

        $openDemandsQuery = Demand::query()
            ->where('municipality_id', $municipality->id)
            ->whereIn('status', $openDemandStatuses);

        $totalOpen = (clone $openDemandsQuery)->count();
        $urgentCount = (clone $openDemandsQuery)->where('is_urgent', true)->count();
        $overdueCount = (clone $openDemandsQuery)
            ->whereNotNull('due_date')
            ->whereDate('due_date', '<', today())
            ->count();

        $priorityDemands = (clone $openDemandsQuery)
            ->withCount('comments')
            ->orderByDesc('is_urgent')
            ->orderBy('due_date')
            ->orderByDesc('created_at')
            ->limit(5)
            ->get(['title', 'area', 'priority', 'status', 'due_date', 'is_urgent']);

        $recentComments = DemandComment::query()
            ->whereHas('demand', fn($query) => $query
                ->where('municipality_id', $municipality->id)
                ->whereIn('status', $openDemandStatuses))
            ->with(['demand:id,title'])
            ->latest('created_at')
            ->limit(3)
            ->get();

        if ($totalOpen === 0) {
            return 'Nenhuma demanda operacional aberta no momento.';
        }

        $lines = [
            "Demandas abertas: {$totalOpen}",
            "Urgentes: {$urgentCount}",
            "Atrasadas: {$overdueCount}",
        ];

        if ($priorityDemands->isNotEmpty()) {
            $lines[] = 'Demandas prioritarias:';
            foreach ($priorityDemands as $demand) {
                $lines[] = sprintf(
                    '- %s%s%s%s',
                    $demand->title,
                    $demand->area ? " | area: {$demand->area}" : '',
                    $demand->due_date ? " | prazo: {$demand->due_date->format('d/m/Y')}" : '',
                    $demand->is_urgent ? ' | urgente' : ''
                );
            }
        }

        if ($recentComments->isNotEmpty()) {
            $lines[] = 'Comentarios recentes em demandas abertas:';
            foreach ($recentComments as $comment) {
                $lines[] = '- ' . ($comment->demand?->title ?? 'Demanda') . ': ' . mb_substr($comment->comment, 0, 140);
            }
        }

        return implode("\n", $lines);
    }

    private function buildMandateExecutionContext(Municipality $municipality): string
    {
        $axes = MandateAxis::query()
            ->where('municipality_id', $municipality->id)
            ->where('is_active', true)
            ->with(['promises' => fn($query) => $query->where('is_active', true)->withCount('actions')->orderBy('order')])
            ->orderBy('order')
            ->get();

        $openActions = MandateAction::query()
            ->where('municipality_id', $municipality->id)
            ->whereIn('status', ['planejado', 'em_andamento', 'suspenso'])
            ->with('axis:id,name')
            ->orderByDesc('created_at')
            ->limit(5)
            ->get(['id', 'mandate_axis_id', 'title', 'status', 'physical_progress', 'end_date']);

        $pendingPromises = MandatePromise::query()
            ->where('municipality_id', $municipality->id)
            ->where('is_active', true)
            ->where('status', 'pending')
            ->withCount('actions')
            ->with('axis:id,name')
            ->orderBy('order')
            ->limit(8)
            ->get(['id', 'mandate_axis_id', 'text', 'score', 'status']);

        $pendingWithoutActions = $pendingPromises
            ->filter(fn (MandatePromise $promise) => (int) ($promise->actions_count ?? 0) === 0)
            ->values();

        $completedActions = MandateAction::query()
            ->where('municipality_id', $municipality->id)
            ->where('status', 'concluido')
            ->with('axis:id,name')
            ->orderByDesc('updated_at')
            ->limit(4)
            ->get(['id', 'mandate_axis_id', 'title', 'region', 'beneficiaries', 'proof_url', 'updated_at']);

        if ($axes->isEmpty() && $openActions->isEmpty() && $pendingPromises->isEmpty() && $completedActions->isEmpty()) {
            return 'Eixos, promessas e acoes do mandato ainda não configurados.';
        }

        $lines = [];

        if ($axes->isNotEmpty()) {
            $axisStats = $axes
                ->map(fn($axis) => [
                    'name' => $axis->name,
                    'score' => (int) round($axis->promises->avg('score') ?? 0),
                    'promise_count' => $axis->promises->count(),
                    'pending_without_actions' => $axis->promises
                        ->where('status', 'pending')
                        ->filter(fn (MandatePromise $promise) => (int) ($promise->actions_count ?? 0) === 0)
                        ->count(),
                ]);

            $weakestAxis = $axisStats
                ->sortBy('score')
                ->first();

            $strongestAxis = $axisStats
                ->sortByDesc('score')
                ->first();

            $averageScore = (int) round($axisStats->avg('score') ?? 0);
            $belowAverageAxes = $axisStats
                ->filter(fn (array $axis) => $axis['score'] < $averageScore)
                ->sortBy('score')
                ->take(3)
                ->values();

            $lines[] = 'Eixos do mandato: ' . $axes->count();
            $lines[] = "Atendimento medio entre eixos: {$averageScore}%";
            if ($weakestAxis) {
                $lines[] = "Eixo mais fragil: {$weakestAxis['name']} ({$weakestAxis['score']}% de atendimento medio)";
            }
            if ($strongestAxis) {
                $lines[] = "Eixo com melhor desempenho: {$strongestAxis['name']} ({$strongestAxis['score']}% de atendimento medio)";
            }
            if ($belowAverageAxes->isNotEmpty()) {
                $lines[] = 'Eixos abaixo da media para foco imediato:';
                foreach ($belowAverageAxes as $axis) {
                    $lines[] = sprintf(
                        '- %s | atendimento medio: %d%% | promessas sem acao: %d',
                        $axis['name'],
                        $axis['score'],
                        $axis['pending_without_actions']
                    );
                }
            }
        }

        if ($openActions->isNotEmpty()) {
            $lines[] = 'Acoes em andamento ou pendentes:';
            foreach ($openActions as $action) {
                $lines[] = sprintf(
                    '- %s%s | status: %s%s',
                    $action->title,
                    $action->axis?->name ? " | eixo: {$action->axis->name}" : '',
                    $action->status_label,
                    $action->end_date ? " | prazo: {$action->end_date->format('d/m/Y')}" : ''
                );
            }
        }

        if ($pendingWithoutActions->isNotEmpty()) {
            $lines[] = 'Compromissos pendentes sem acao vinculada:';
            foreach ($pendingWithoutActions as $promise) {
                $lines[] = sprintf(
                    '- %s%s',
                    $promise->text,
                    $promise->axis?->name ? " | eixo: {$promise->axis->name}" : ''
                );
            }
        } elseif ($pendingPromises->isNotEmpty()) {
            $lines[] = 'Promessas ainda pendentes:';
            foreach ($pendingPromises as $promise) {
                $lines[] = sprintf(
                    '- %s%s',
                    $promise->text,
                    $promise->axis?->name ? " | eixo: {$promise->axis->name}" : ''
                );
            }
        }

        $projection = $this->projection->calculate($municipality);
        if (!empty($projection['alert_message'])) {
            $lines[] = 'Projecao do mandato: ' . $projection['alert_message'];
            $lines[] = "Fim do mandato considerado: {$projection['term_end_label']}";
        }

        foreach (array_slice($projection['axis_alerts'] ?? [], 0, 3) as $axisAlert) {
            $lines[] = sprintf(
                '- Eixo em alerta: %s%s | gap projetado: %d compromisso(s)',
                $axisAlert['axis_icon'] ? $axisAlert['axis_icon'] . ' ' : '',
                $axisAlert['axis_name'],
                (int) ($axisAlert['gap'] ?? 0)
            );
        }

        if ($completedActions->isNotEmpty()) {
            $lines[] = 'Acoes concluidas que podem virar argumento de comunicação e posicionamento:';
            foreach ($completedActions as $action) {
                $lines[] = sprintf(
                    '- %s%s%s%s',
                    $action->title,
                    $action->axis?->name ? " | eixo: {$action->axis->name}" : '',
                    $action->region ? " | regiao: {$action->region}" : '',
                    $action->beneficiaries ? " | beneficiarios: {$action->beneficiaries}" : ''
                );
            }
        }

        return implode("\n", $lines);
    }

    private function buildRecentContentsContext(Municipality $municipality): string
    {
        $recentContents = GeneratedContent::query()
            ->where('municipality_id', $municipality->id)
            ->orderByDesc('created_at')
            ->limit(4)
            ->get(['title', 'type', 'tags', 'created_at']);

        if ($recentContents->isEmpty()) {
            return 'Nenhum conteudo recente salvo no módulo de comunicação.';
        }

        $lines = ['Conteudos recentes gerados:'];

        foreach ($recentContents as $content) {
            $tags = is_array($content->tags) && !empty($content->tags)
                ? ' | tags: ' . implode(', ', array_slice($content->tags, 0, 3))
                : '';

            $lines[] = sprintf(
                '- %s | tipo: %s | criado em: %s%s',
                $content->title ?: 'Conteudo sem titulo',
                $content->type,
                $content->created_at->format('d/m/Y H:i'),
                $tags
            );
        }

        return implode("\n", $lines);
    }
}
