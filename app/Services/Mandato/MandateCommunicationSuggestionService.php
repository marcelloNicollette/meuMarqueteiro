<?php

namespace App\Services\Mandato;

use App\Models\Demand;
use App\Models\DemandEvent;
use App\Models\MandateAction;
use App\Models\User;

class MandateCommunicationSuggestionService
{
    public function syncCompletedAction(MandateAction $action, User $actor): ?Demand
    {
        if ((string) $action->status !== 'concluido') {
            return null;
        }

        $action->loadMissing('axis');

        $demand = $this->findExistingDemand($action);

        if ($demand) {
            $before = [
                'title' => $demand->title,
                'raw_input' => $demand->raw_input,
                'description' => $demand->description,
                'area' => $demand->area,
                'locality' => $demand->locality,
            ];

            $demand->fill([
                'title' => $action->title,
                'raw_input' => $this->buildRawInput($action),
                'description' => $action->description,
                'area' => $action->secretaria ?: ($action->axis?->name ?: 'Mandato'),
                'locality' => $action->region,
            ]);

            if ($demand->isDirty()) {
                $demand->save();

                $this->recordEvent(
                    demand: $demand,
                    actor: $actor,
                    eventType: 'mandate_communication_suggestion_updated',
                    message: 'Sugestao de pauta do Mandato atualizada com o contexto mais recente da acao concluida.',
                    metadata: [
                        'source_module' => 'mandato',
                        'mandate_action_id' => $action->id,
                        'before' => $before,
                    ],
                );
            }

            return $demand;
        }

        $demand = Demand::create([
            'municipality_id' => $action->municipality_id,
            'registered_by' => $actor->id,
            'input_type' => 'mandato_action_completed',
            'raw_input' => $this->buildRawInput($action),
            'title' => $action->title,
            'description' => $action->description,
            'area' => $action->secretaria ?: ($action->axis?->name ?: 'Mandato'),
            'locality' => $action->region,
            'priority' => 'media',
            'status' => 'registered',
            'is_urgent' => false,
        ]);

        $this->recordEvent(
            demand: $demand,
            actor: $actor,
            eventType: 'mandate_communication_suggestion_created',
            message: 'Sugestao automatica criada a partir de acao concluida do Mandato para o Nucleo de Operacao.',
            metadata: [
                'source_module' => 'mandato',
                'mandate_action_id' => $action->id,
            ],
        );

        return $demand;
    }

    private function findExistingDemand(MandateAction $action): ?Demand
    {
        return Demand::query()
            ->where('municipality_id', $action->municipality_id)
            ->where('input_type', 'mandato_action_completed')
            ->whereHas('events', function ($query) use ($action) {
                $query->whereIn('event_type', [
                    'mandate_communication_suggestion_created',
                    'mandate_communication_suggestion_updated',
                ])->where('metadata->source_module', 'mandato')
                    ->where('metadata->mandate_action_id', $action->id);
            })
            ->latest('id')
            ->first();
    }

    private function buildRawInput(MandateAction $action): string
    {
        $lines = [
            'Sugestao automatica do Mandato para pauta no módulo Comunicação.',
            'Título da acao: ' . $action->title,
        ];

        if ($action->axis?->name) {
            $lines[] = 'Eixo tematico: ' . $action->axis->name;
        }

        if ($action->description) {
            $lines[] = 'Descrição: ' . trim($action->description);
        }

        if ($action->beneficiaries) {
            $lines[] = 'Beneficiários: ' . $action->beneficiaries;
        }

        if ($action->region) {
            $lines[] = 'Regiao: ' . $action->region;
        }

        if ($action->proof_url) {
            $lines[] = 'Evidencia: ' . $action->proof_url;
        }

        return implode("\n", $lines);
    }

    private function recordEvent(Demand $demand, User $actor, string $eventType, string $message, array $metadata): void
    {
        DemandEvent::create([
            'demand_id' => $demand->id,
            'user_id' => $actor->id,
            'event_type' => $eventType,
            'message' => $message,
            'metadata' => $metadata,
        ]);
    }
}
