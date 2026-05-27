<?php

namespace App\Services\AI;

use App\Models\Conversation;
use App\Models\ConversationMemory;
use App\Models\Message;
use App\Models\User;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class ConversationMemoryService
{
    private const MAX_MEMORY_LENGTH = 1800;

    public function __construct(
        private AIProviderService $ai,
    ) {}

    public function isVectorSearchAvailable(): bool
    {
        return DB::getDriverName() === 'pgsql';
    }

    public function rememberTurn(
        Conversation $conversation,
        User $mayor,
        Message $userMessage,
        Message $assistantMessage
    ): ?ConversationMemory {
        $existing = ConversationMemory::query()
            ->where('assistant_message_id', $assistantMessage->id)
            ->first();

        if ($existing) {
            return $existing;
        }

        $content = $this->buildMemoryContent($conversation, $userMessage, $assistantMessage);
        if ($content === '') {
            return null;
        }

        $embeddingValue = null;

        try {
            $vector = $this->ai->embed($content);
            $vectorStr = '[' . implode(',', $vector) . ']';
            $embeddingValue = $this->isVectorSearchAvailable()
                ? DB::raw("'{$vectorStr}'::vector")
                : $vectorStr;
        } catch (\Throwable $e) {
            report($e);
        }

        return ConversationMemory::create([
            'conversation_id' => $conversation->id,
            'municipality_id' => $conversation->municipality_id,
            'user_id' => $mayor->id,
            'user_message_id' => $userMessage->id,
            'assistant_message_id' => $assistantMessage->id,
            'memory_type' => $this->detectMemoryType($conversation),
            'source' => 'chat',
            'content' => $content,
            'embedding' => $embeddingValue,
            'metadata' => [
                'conversation_title' => $conversation->title,
                'origin_module' => $conversation->origin_module,
                'intent' => $conversation->context['intent'] ?? null,
                'tags' => $conversation->auto_tags ?? [],
                'user_excerpt' => Str::limit(trim($userMessage->content), 280),
                'assistant_excerpt' => Str::limit(trim($assistantMessage->content), 320),
            ],
            'token_count' => str_word_count($content),
            'importance_score' => $this->calculateImportanceScore($conversation, $userMessage, $assistantMessage),
        ]);
    }

    public function retrieve(string $query, User $mayor, ?Conversation $conversation = null, int $limit = 4): Collection
    {
        if ($this->isVectorSearchAvailable()) {
            try {
                $results = $this->retrieveVectorMemories($query, $mayor, $conversation, $limit);
                if ($results->isNotEmpty()) {
                    $this->touchMemories($results->pluck('id')->all());
                    return $results;
                }
            } catch (\Throwable $e) {
                report($e);
            }
        }

        $fallback = $this->retrieveFallbackMemories($query, $mayor, $conversation, $limit);
        if ($fallback->isNotEmpty()) {
            $this->touchMemories($fallback->pluck('id')->all());
            return $fallback;
        }

        if ($conversation) {
            $recentConversationMemories = $this->retrieveRecentConversationMemories($mayor, $conversation, min($limit, 2));

            if ($recentConversationMemories->isNotEmpty()) {
                $this->touchMemories($recentConversationMemories->pluck('id')->all());
                return $recentConversationMemories;
            }
        }

        return collect();
    }

    public function buildContext(Collection $memories): string
    {
        if ($memories->isEmpty()) {
            return '';
        }

        $lines = ["### Memorias relevantes de conversas anteriores"];

        foreach ($memories as $memory) {
            $metadata = is_array($memory->metadata ?? null)
                ? $memory->metadata
                : json_decode($memory->metadata ?? '[]', true);

            $title = $metadata['conversation_title'] ?? 'Conversa anterior';
            $intent = $metadata['intent'] ?? null;
            $tags = array_filter((array) ($metadata['tags'] ?? []));
            $relevance = isset($memory->similarity)
                ? ' | Similaridade: ' . round(((float) $memory->similarity) * 100) . '%'
                : '';

            $header = "- {$title}";
            if ($intent) {
                $header .= " | foco: {$intent}";
            }
            if (!empty($tags)) {
                $header .= ' | tags: ' . implode(', ', array_slice($tags, 0, 4));
            }
            $header .= $relevance;

            $lines[] = $header;
            $lines[] = '  ' . preg_replace('/\s+/', ' ', trim((string) $memory->content));
        }

        return implode("\n", $lines);
    }

    private function retrieveVectorMemories(string $query, User $mayor, ?Conversation $conversation, int $limit): Collection
    {
        $queryVector = $this->ai->embed($query);
        $vectorStr = '[' . implode(',', $queryVector) . ']';
        $threshold = 0.58;

        $rows = DB::select(
            "
            SELECT
                id,
                conversation_id,
                municipality_id,
                user_id,
                user_message_id,
                assistant_message_id,
                memory_type,
                source,
                content,
                metadata,
                token_count,
                importance_score,
                last_used_at,
                created_at,
                updated_at,
                1 - (embedding <=> :vector::vector) AS similarity
            FROM conversation_memories
            WHERE
                user_id = :user_id
                AND embedding IS NOT NULL
                AND 1 - (embedding <=> :vector2::vector) > :threshold
            ORDER BY
                " . ($conversation ? "CASE WHEN conversation_id = :conversation_id THEN 1 ELSE 0 END ASC," : '') . "
                ((1 - (embedding <=> :vector3::vector)) * 0.85) + (importance_score * 0.15) DESC,
                created_at DESC
            LIMIT :limit
            ",
            array_filter([
                'vector' => $vectorStr,
                'vector2' => $vectorStr,
                'vector3' => $vectorStr,
                'user_id' => $mayor->id,
                'conversation_id' => $conversation?->id,
                'threshold' => $threshold,
                'limit' => $limit,
            ], fn($value) => $value !== null)
        );

        return collect($rows);
    }

    private function retrieveFallbackMemories(string $query, User $mayor, ?Conversation $conversation, int $limit): Collection
    {
        $keywords = $this->extractKeywords($query);

        $builder = ConversationMemory::query()
            ->where('user_id', $mayor->id)
            ->orderByDesc('importance_score')
            ->orderByDesc('created_at');

        if (!empty($keywords)) {
            $builder->where(function ($queryBuilder) use ($keywords) {
                foreach ($keywords as $keyword) {
                    $queryBuilder->orWhere('content', 'like', '%' . $keyword . '%');
                }
            });
        }

        return $builder
            ->limit($limit)
            ->get()
            ->map(function (ConversationMemory $memory) {
                $memory->similarity = null;
                return $memory;
            })
            ->sortBy(fn(ConversationMemory $memory) => $conversation && $memory->conversation_id === $conversation->id ? 1 : 0)
            ->values();
    }

    private function retrieveRecentConversationMemories(User $mayor, Conversation $conversation, int $limit): Collection
    {
        return ConversationMemory::query()
            ->where('user_id', $mayor->id)
            ->where('conversation_id', $conversation->id)
            ->orderByDesc('last_used_at')
            ->orderByDesc('importance_score')
            ->orderByDesc('created_at')
            ->limit(max(1, $limit))
            ->get()
            ->map(function (ConversationMemory $memory) {
                $memory->similarity = null;

                return $memory;
            });
    }

    private function buildMemoryContent(Conversation $conversation, Message $userMessage, Message $assistantMessage): string
    {
        $parts = [];

        if (!empty($conversation->title)) {
            $parts[] = 'Conversa: ' . $conversation->title;
        }

        if (!empty($conversation->context['intent'])) {
            $parts[] = 'Objetivo: ' . $conversation->context['intent'];
        }

        if (!empty($conversation->auto_tags)) {
            $parts[] = 'Tags: ' . implode(', ', array_slice($conversation->auto_tags, 0, 6));
        }

        $parts[] = 'Prefeito pediu: ' . trim($userMessage->content);
        $parts[] = 'Orientacao dada: ' . trim($assistantMessage->content);

        if (!empty($conversation->context['last_summary'])) {
            $parts[] = 'Resumo operacional: ' . $conversation->context['last_summary'];
        }

        return Str::limit(implode("\n", array_filter($parts)), self::MAX_MEMORY_LENGTH, '');
    }

    private function detectMemoryType(Conversation $conversation): string
    {
        $intent = $conversation->context['intent'] ?? null;

        return match ($intent) {
            'captação' => 'opportunity',
            'gestao_de_crise' => 'risk',
            'demanda_operacional' => 'commitment',
            default => 'turn',
        };
    }

    private function calculateImportanceScore(Conversation $conversation, Message $userMessage, Message $assistantMessage): float
    {
        $score = 0.50;

        if (!empty($conversation->auto_tags)) {
            $score += min(count($conversation->auto_tags) * 0.05, 0.20);
        }

        if (!empty($conversation->context['intent'])) {
            $score += 0.10;
        }

        if (Str::length($userMessage->content) > 180 || Str::length($assistantMessage->content) > 320) {
            $score += 0.08;
        }

        if (preg_match('/\b(crise|risco|prazo|federal|recurso|mandato|demanda)\b/i', $userMessage->content . ' ' . $assistantMessage->content)) {
            $score += 0.12;
        }

        return min($score, 1.00);
    }

    private function extractKeywords(string $query): array
    {
        $stopwords = [
            'para', 'com', 'sobre', 'esse', 'essa', 'isso', 'quero', 'preciso', 'agora',
            'uma', 'uns', 'umas', 'que', 'como', 'qual', 'quais', 'meu', 'minha', 'suas',
            'hoje', 'ontem', 'amanha', 'município', 'prefeito', 'assistente',
        ];

        return collect(preg_split('/[^[:alnum:]]+/u', Str::lower($query)) ?: [])
            ->filter(fn($token) => mb_strlen($token) >= 4)
            ->reject(fn($token) => in_array($token, $stopwords, true))
            ->unique()
            ->take(6)
            ->values()
            ->all();
    }

    private function touchMemories(array $ids): void
    {
        if (empty($ids)) {
            return;
        }

        ConversationMemory::query()
            ->whereIn('id', $ids)
            ->update(['last_used_at' => now()]);
    }
}
