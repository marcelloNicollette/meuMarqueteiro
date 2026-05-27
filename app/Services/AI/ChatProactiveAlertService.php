<?php

namespace App\Services\AI;

use App\Enums\ResourceOpportunityStatus;
use App\Models\Conversation;
use App\Models\Demand;
use App\Models\FederalProgramAlert;
use App\Models\MorningBriefing;
use App\Models\Municipality;
use App\Models\SocialMention;
use App\Models\User;
use App\Services\Mandato\MandateProjectionService;
use App\Services\Radar\HybridRadarReadService;
use Illuminate\Support\Str;

class ChatProactiveAlertService
{
    public function buildFor(User $mayor, Municipality $municipality, ?Conversation $conversation = null): array
    {
        $conversationProfile = $this->buildConversationProfile($conversation);

        $alerts = collect()
            ->push($this->buildDemandAlert($municipality))
            ->push($this->buildCommitmentRiskAlert($municipality))
            ->push($this->buildFederalOpportunityAlert($municipality))
            ->push($this->buildMentionAlert($municipality))
            ->push($this->buildMorningBriefingAlert($municipality))
            ->filter()
            ->map(fn(array $alert) => $this->attachConversationRelevance($alert, $conversationProfile))
            ->sortByDesc(fn(array $alert) => $alert['weight'])
            ->take(4)
            ->values()
            ->map(fn(array $alert) => $this->stripWeight($alert))
            ->all();

        return $alerts;
    }

    private function buildDemandAlert(Municipality $municipality): ?array
    {
        $openDemands = Demand::query()
            ->where('municipality_id', $municipality->id)
            ->whereIn('status', ['pending', 'in_progress']);

        $overdueCount = (clone $openDemands)
            ->whereNotNull('due_date')
            ->whereDate('due_date', '<', today())
            ->count();

        $urgentCount = (clone $openDemands)
            ->where('is_urgent', true)
            ->count();

        if ($overdueCount === 0 && $urgentCount === 0) {
            return null;
        }

        $topDemand = (clone $openDemands)
            ->orderByDesc('is_urgent')
            ->orderBy('due_date')
            ->latest('created_at')
            ->first(['title', 'due_date', 'is_urgent']);

        $summary = [];
        if ($overdueCount > 0) {
            $summary[] = "{$overdueCount} demanda(s) com prazo vencido";
        }
        if ($urgentCount > 0) {
            $summary[] = "{$urgentCount} urgente(s)";
        }
        if ($topDemand?->title) {
            $summary[] = "Prioridade atual: {$topDemand->title}";
        }

        return [
            'key' => 'demands',
            'severity' => $overdueCount > 0 ? 'high' : 'medium',
            'title' => 'Demandas pedem atencao agora',
            'summary' => implode(' | ', $summary),
            'topic_tags' => ['demandas', 'gestao', 'prioridades'],
            'action_label' => 'Abrir conversa de acao',
            'action_type' => 'prefill_new',
            'action_value' => 'Quero um plano rapido para destravar as demandas mais urgentes e atrasadas de hoje.',
            'weight' => $overdueCount > 0 ? 100 : 80,
        ];
    }

    private function buildCommitmentRiskAlert(Municipality $municipality): ?array
    {
        $projection = app(MandateProjectionService::class)->calculate($municipality);

        if (!($projection['needs_alert'] ?? false) || !($projection['significant_deviation'] ?? false)) {
            return null;
        }

        $axisSummary = collect($projection['axis_alerts'] ?? [])
            ->take(3)
            ->map(fn (array $axis) => trim(($axis['axis_name'] ?? 'Eixo') . ' (' . ($axis['gap'] ?? 0) . ')'))
            ->implode(', ');

        $summary = $projection['alert_message'] ?? 'O ritmo do mandato esta abaixo do necessário.';
        if ($axisSummary !== '') {
            $summary .= ' | Eixos mais atrasados: ' . $axisSummary;
        }

        return [
            'key' => 'mandate_projection_risk',
            'severity' => 'high',
            'title' => 'Ritmo do mandato abaixo do necessário',
            'summary' => $summary,
            'topic_tags' => ['mandato', 'projecao', 'ritmo', 'eixos'],
            'action_label' => 'Abrir analise do mandato',
            'action_type' => 'prefill_new',
            'action_value' => 'No ritmo atual do mandato, quais eixos estao mais atrasados e quais acoes devo priorizar agora para reduzir esse desvio?',
            'weight' => 92,
        ];
    }

    private function buildFederalOpportunityAlert(Municipality $municipality): ?array
    {
        $program = app(HybridRadarReadService::class)
            ->municipalityRadarPrograms($municipality, visibleOnly: false)
            ->filter(fn (FederalProgramAlert $item) => in_array($item->status, ResourceOpportunityStatus::activeForRadar(), true))
            ->filter(fn (FederalProgramAlert $item) => (float) ($item->match_score ?? 0) >= 0.8)
            ->sortBy(fn (FederalProgramAlert $item) => $item->deadline ? 0 : 1)
            ->sortBy(fn (FederalProgramAlert $item) => $item->deadline?->timestamp ?? PHP_INT_MAX)
            ->sortByDesc(fn (FederalProgramAlert $item) => (float) ($item->match_score ?? 0))
            ->first();

        if (!$program) {
            return null;
        }

        $deadlineLabel = $program->deadline
            ? 'Prazo: ' . $program->deadline->format('d/m/Y')
            : 'Prazo não informado';

        return [
            'key' => 'federal_program',
            'severity' => $program->deadline && $program->deadline->isBefore(today()->copy()->addDays(10)) ? 'medium' : 'low',
            'title' => 'Oportunidade no radar de recursos',
            'summary' => "{$program->program_name} | {$program->area} | Match " . round($program->match_score * 100) . "% | {$deadlineLabel}",
            'topic_tags' => ['captação', 'recursos', 'radar_recursos'],
            'action_label' => 'Abrir estrategia',
            'action_type' => 'prefill_new',
            'action_value' => "Me explique como aproveitar a oportunidade {$program->program_name} do radar de recursos e qual deve ser nosso proximo passo.",
            'weight' => 60,
        ];
    }

    private function buildMorningBriefingAlert(Municipality $municipality): ?array
    {
        $todayBriefing = MorningBriefing::query()
            ->where('municipality_id', $municipality->id)
            ->whereDate('date', today())
            ->first(['id', 'read_at', 'date']);

        if (!$todayBriefing) {
            return [
                'key' => 'briefing_missing',
                'severity' => 'low',
                'title' => 'Seu briefing de hoje ainda não existe',
                'summary' => 'Gere o resumo executivo do dia para entrar no chat com contexto atualizado.',
                'topic_tags' => ['briefing', 'prioridades', 'agenda'],
                'action_label' => 'Gerar briefing',
                'action_type' => 'generate_briefing',
                'action_value' => route('mayor.mandato.briefings.generate'),
                'weight' => 40,
            ];
        }

        if ($todayBriefing->read_at) {
            return null;
        }

        $briefingSummary = $this->extractBriefingSummary($todayBriefing);

        return [
            'key' => 'briefing_unread',
            'severity' => 'low',
            'title' => 'Seu briefing de hoje esta pronto',
            'summary' => $briefingSummary ?: 'O resumo executivo do dia ja foi gerado e pode ajudar a priorizar as decisoes de agora.',
            'topic_tags' => ['briefing', 'prioridades', 'agenda'],
            'action_label' => 'Ler briefing',
            'action_type' => 'link',
            'action_value' => route('mayor.mandato.briefings.show', $todayBriefing),
            'weight' => 45,
        ];
    }

    private function buildMentionAlert(Municipality $municipality): ?array
    {
        $mentions = SocialMention::query()
            ->where('municipality_id', $municipality->id)
            ->whereIn('sentiment', ['urgent', 'negative'])
            ->where('created_at', '>=', now()->subHours(24))
            ->latest('created_at')
            ->limit(5)
            ->get(['id', 'author', 'content', 'sentiment', 'source', 'created_at']);

        if ($mentions->isEmpty()) {
            return null;
        }

        $urgentCount = $mentions->where('sentiment', 'urgent')->count();
        $negativeCount = $mentions->where('sentiment', 'negative')->count();
        $topMention = $mentions->first();
        $summary = [];

        if ($urgentCount > 0) {
            $summary[] = "{$urgentCount} menção(ões) urgente(s)";
        }

        if ($negativeCount > 0) {
            $summary[] = "{$negativeCount} negativa(s)";
        }

        if ($topMention?->content) {
            $summary[] = 'Destaque: ' . Str::limit((string) $topMention->content, 90);
        }

        return [
            'key' => 'communication_mentions',
            'severity' => $urgentCount > 0 ? 'high' : 'medium',
            'title' => 'Menções sensíveis pedem resposta',
            'summary' => implode(' | ', $summary),
            'topic_tags' => ['comunicação', 'mencoes', 'crise', 'reputacao'],
            'action_label' => 'Abrir Comunicação',
            'action_type' => 'link',
            'action_value' => route('mayor.content.index', [
                'area' => 'mentions',
                'mention_filter' => $urgentCount > 0 ? 'urgent' : 'negative',
                'mention_days' => 1,
            ]),
            'weight' => $urgentCount > 0 ? 92 : 74,
        ];
    }

    private function attachConversationRelevance(array $alert, array $conversationProfile): array
    {
        $alertTags = collect($alert['topic_tags'] ?? [])
            ->map(fn(string $tag) => Str::lower($tag))
            ->values()
            ->all();

        $matches = array_values(array_intersect($alertTags, $conversationProfile['tags']));

        $isRelatedByIntent = isset($conversationProfile['intent']) && in_array($conversationProfile['intent'], $alertTags, true);
        $isRelated = !empty($matches) || $isRelatedByIntent;

        if ($isRelated) {
            $alert['weight'] += 12;
            $alert['related_to_active_conversation'] = true;
            $alert['relevance_label'] = 'Relacionado com esta conversa';
        } else {
            $alert['related_to_active_conversation'] = false;
        }

        if (!empty($conversationProfile['summary']) && $isRelated && ($alert['action_type'] ?? null) === 'prefill_new') {
            $alert['action_value'] .= ' Considere tambem o contexto desta conversa: ' . $conversationProfile['summary'];
        }

        return $alert;
    }

    private function buildConversationProfile(?Conversation $conversation): array
    {
        if (!$conversation) {
            return [
                'tags' => [],
                'intent' => null,
                'summary' => null,
            ];
        }

        $context = $conversation->context ?? [];
        $tags = collect($conversation->auto_tags ?? [])
            ->push($context['intent'] ?? null)
            ->push($conversation->origin_module)
            ->filter()
            ->map(fn(string $tag) => Str::lower($tag))
            ->values()
            ->all();

        return [
            'tags' => $tags,
            'intent' => isset($context['intent']) ? Str::lower($context['intent']) : null,
            'summary' => Str::limit((string) ($context['last_summary'] ?? $conversation->title ?? ''), 180),
        ];
    }

    private function extractBriefingSummary(MorningBriefing $briefing): ?string
    {
        $sections = $briefing->sections ?? [];

        foreach (['alertas', 'agenda', 'comunicação', 'contexto_politico'] as $key) {
            $section = $sections[$key] ?? null;
            if (is_string($section) && trim($section) !== '') {
                return Str::limit(trim(preg_replace('/\s+/', ' ', strip_tags($section))), 180);
            }

            if (is_array($section)) {
                $flattened = collect($section)
                    ->flatten()
                    ->filter(fn($item) => is_string($item) && trim($item) !== '')
                    ->implode(' ');

                if (trim($flattened) !== '') {
                    return Str::limit(trim(preg_replace('/\s+/', ' ', strip_tags($flattened))), 180);
                }
            }
        }

        $content = trim(preg_replace('/\s+/', ' ', strip_tags((string) $briefing->content)));

        return $content !== '' ? Str::limit($content, 180) : null;
    }

    private function stripWeight(array $alert): array
    {
        unset($alert['weight']);
        return $alert;
    }
}
