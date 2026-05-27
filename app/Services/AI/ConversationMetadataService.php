<?php

namespace App\Services\AI;

use App\Models\Conversation;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;

class ConversationMetadataService
{
    private const MAX_TAGS = 5;

    private const DEFAULT_TITLE = 'Nova conversa';

    private const TAG_RULES = [
        'comunicação'        => ['post', 'legenda', 'discurso', 'entrevista', 'comunicação', 'pronunciamento', 'instagram', 'facebook', 'whatsapp'],
        'obras'              => ['obra', 'obras', 'paviment', 'asfalto', 'ponte', 'reforma', 'praca', 'iluminacao', 'creche'],
        'saude'              => ['saude', 'ubs', 'upa', 'medico', 'hospital', 'fisioterapia', 'medicamento', 'atendimento'],
        'educacao'           => ['educacao', 'escola', 'creche', 'aluno', 'professor', 'fundeb', 'transporte escolar'],
        'crise'              => ['crise', 'ataque', 'denuncia', 'problema', 'risco', 'escandalo', 'boato', 'reclamacao'],
        'legislacao'         => ['lei', 'licitacao', 'lrf', 'decreto', 'juridico', 'tribunal', 'ministerio publico'],
        'recursos'           => ['programa federal', 'programas federais', 'edital', 'captação', 'recurso', 'convenio', 'transferegov', 'ministerio'],
        'mandato'            => ['mandato', 'promessa', 'compromisso', 'acao', 'eixo', 'meta', 'entrega'],
        'demanda_cidada'     => ['demanda', 'cidadao', 'solicitacao', 'buraco', 'lixo', 'iluminacao publica', 'bairro'],
        'política'           => ['vereador', 'camara', 'base', 'oposicao', 'aliado', 'politico', 'partido', 'eleitoral'],
        'planejamento'       => ['plano de acao', 'cronograma', 'prioridade', 'checklist', 'proximos passos', 'estrategia'],
    ];

    public function initializeConversation(Conversation $conversation, string $originModule = 'chat', array $context = []): void
    {
        $payload = [
            'origin_module' => $conversation->origin_module ?: $originModule,
        ];

        if ($context !== []) {
            $payload['context'] = array_merge($conversation->context ?? [], $context);
        }

        $conversation->update($payload);
    }

    public function refresh(Conversation $conversation): void
    {
        $messages = $conversation->messages()
            ->whereIn('role', ['user', 'assistant'])
            ->orderBy('created_at')
            ->limit(12)
            ->get(['role', 'content']);

        if ($messages->isEmpty()) {
            return;
        }

        $title = $this->shouldReplaceTitle($conversation->title)
            ? $this->generateTitle($messages)
            : $conversation->title;

        $autoTags = $this->generateTags($messages);
        $intent = $this->detectIntent($messages, $autoTags);

        $context = array_merge($conversation->context ?? [], [
            'intent' => $intent,
            'last_topic' => $title,
            'last_summary' => $this->buildSummary($messages),
        ]);

        $conversation->update([
            'title' => $title,
            'auto_tags' => $autoTags,
            'context' => $context,
        ]);
    }

    private function shouldReplaceTitle(?string $title): bool
    {
        return empty($title) || trim($title) === self::DEFAULT_TITLE;
    }

    private function generateTitle(Collection $messages): string
    {
        $firstUserMessage = $messages->firstWhere('role', 'user');

        if (!$firstUserMessage) {
            return self::DEFAULT_TITLE;
        }

        $content = trim((string) $firstUserMessage->content);
        $content = preg_replace('/\s+/', ' ', $content) ?? $content;
        $content = preg_replace('/^(quero|preciso|me ajude a|me ajuda a|crie|fa[a-z]*|gere)\s+/i', '', $content) ?? $content;

        $parts = preg_split('/[.!?\n]/', $content) ?: [];
        $candidate = trim((string) ($parts[0] ?? $content));

        if ($candidate === '') {
            return self::DEFAULT_TITLE;
        }

        return Str::limit(Str::ucfirst($candidate), 72, '...');
    }

    private function generateTags(Collection $messages): array
    {
        $text = $this->normalizeText($messages->pluck('content')->implode(' '));
        $tags = [];

        foreach (self::TAG_RULES as $tag => $keywords) {
            foreach ($keywords as $keyword) {
                if (Str::contains($text, $this->normalizeText($keyword))) {
                    $tags[] = $tag;
                    break;
                }
            }
        }

        $tags = array_values(array_unique($tags));

        if ($tags === []) {
            $tags[] = 'geral';
        }

        return array_slice($tags, 0, self::MAX_TAGS);
    }

    private function detectIntent(Collection $messages, array $tags): string
    {
        $text = $this->normalizeText($messages->pluck('content')->implode(' '));

        if (Str::contains($text, ['plano de acao', 'cronograma', 'proximos passos', 'checklist'])) {
            return 'planejamento';
        }

        if (in_array('comunicação', $tags, true)) {
            return 'comunicação';
        }

        if (in_array('recursos', $tags, true)) {
            return 'captação';
        }

        if (in_array('crise', $tags, true)) {
            return 'gestao_de_crise';
        }

        if (in_array('demanda_cidada', $tags, true)) {
            return 'demanda_operacional';
        }

        return 'orientacao_geral';
    }

    private function buildSummary(Collection $messages): string
    {
        $firstUserMessage = trim((string) optional($messages->firstWhere('role', 'user'))->content);
        $lastAssistantMessage = trim((string) optional($messages->where('role', 'assistant')->last())->content);

        $parts = [];

        if ($firstUserMessage !== '') {
            $parts[] = 'Pedido inicial: ' . Str::limit($firstUserMessage, 140, '...');
        }

        if ($lastAssistantMessage !== '') {
            $parts[] = 'Ultima orientacao: ' . Str::limit($lastAssistantMessage, 180, '...');
        }

        return implode(' | ', $parts);
    }

    private function normalizeText(string $text): string
    {
        $text = Str::lower(Str::ascii($text));
        $text = preg_replace('/[^a-z0-9\s]/', ' ', $text) ?? $text;
        return preg_replace('/\s+/', ' ', trim($text)) ?? trim($text);
    }
}
