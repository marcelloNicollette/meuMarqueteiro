<?php

namespace App\Services\AI;

use App\Models\Conversation;
use App\Models\DocumentEmbedding;
use App\Models\Message;
use App\Models\Municipality;
use App\Models\User;
use App\Services\RAG\RAGService;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * Serviço principal do Assistente do Meu Marqueteiro.
 * Inclui memória persistente entre sessões via compressão de contexto.
 */
class AssistantService
{
    // A cada N respostas do assistente, atualiza o sumário de memória
    private const MEMORY_COMPRESS_EVERY = 8;

    // Quantas mensagens recentes manter no histórico direto (além da memória)
    private const HISTORY_RECENT_LIMIT = 8;

    public function __construct(
        private AIProviderService $ai,
        private RAGService        $rag,
    ) {}

    public function chat(
        string       $userMessage,
        User         $mayor,
        Conversation $conversation,
    ): Message {
        $municipality = $mayor->municipality;

        // 1. RAG
        $ragChunks  = collect();
        $ragContext = '';
        $hasEmbeddings = DocumentEmbedding::where(function ($q) use ($municipality) {
            $q->where('municipality_id', $municipality->id)->orWhereNull('municipality_id');
        })->exists();

        if ($hasEmbeddings) {
            try {
                $ragChunks  = $this->rag->retrieve($userMessage, $municipality);
                $ragContext = $this->rag->buildContext($ragChunks);
            } catch (\Exception $e) {
                $ragChunks  = collect();
                $ragContext = '';
            }
        }

        // 2. Salvar mensagem do usuário no banco ANTES de buscar o histórico
        Message::create([
            'conversation_id' => $conversation->id,
            'role'            => 'user',
            'content'         => $userMessage,
            'input_type'      => 'text',
        ]);

        // 3. Histórico com memória persistente (agora inclui a mensagem salva acima)
        $history = $this->buildHistoryWithMemory($conversation);

        // 4. Montar mensagens para o LLM
        // O histórico inclui a mensagem salva. Adicionamos um lembrete explícito
        // ao final para garantir que o Claude responda O QUE FOI PERGUNTADO AGORA.
        $messages = [
            [
                'role'    => 'system',
                'content' => $this->buildSystemPrompt($mayor, $municipality, $ragContext),
            ],
            ...$history,
            // Lembrete final — garante que a última pergunta seja respondida
            [
                'role'    => 'user',
                'content' => 'ATENCAO: responda ESPECIFICAMENTE isto que acabei de perguntar: [' . $userMessage . '] — nao repita respostas anteriores nem mude o assunto.',
            ],
        ];

        // 5. Chamar o LLM
        $response = $this->ai->chat($messages, [
            'temperature' => 0.7,
            'max_tokens'  => 2048,
        ]);

        // 6. Salvar resposta do assistente
        $assistantMessage = Message::create([
            'conversation_id' => $conversation->id,
            'role'            => 'assistant',
            'content'         => $response->content,
            'rag_sources'     => $ragChunks->map(fn($c) => [
                'source'     => $c->source,
                'category'   => $c->category,
                'similarity' => $c->similarity ?? null,
            ])->values()->toArray(),
            'tokens_used'     => $response->tokensUsed,
            'metadata'        => [
                'provider' => $response->provider,
                'model'    => $response->model,
            ],
        ]);

        // 7. Atualizar metadados da conversa
        $conversation->update([
            'last_message_at' => now(),
            'token_count'     => $conversation->token_count + $response->tokensUsed,
            'ai_provider'     => $response->provider,
            'ai_model'        => $response->model,
        ]);

        // 8. Comprimir memória se atingiu o limite
        $this->maybeCompressMemory($conversation, $mayor, $municipality);

        return $assistantMessage;
    }

    // ─── Memória Persistente ─────────────────────────────────────────────

    /**
     * Dispara a compressão de memória se necessário.
     */
    private function maybeCompressMemory(
        Conversation $conversation,
        User         $mayor,
        Municipality $municipality
    ): void {
        $assistantCount = $conversation->messages()
            ->where('role', 'assistant')
            ->count();

        if ($assistantCount > 0 && $assistantCount % self::MEMORY_COMPRESS_EVERY === 0) {
            try {
                $this->compressMemory($conversation, $mayor, $municipality);
            } catch (\Exception $e) {
                Log::warning("Falha ao comprimir memória da conversa {$conversation->id}: " . $e->getMessage());
            }
        }
    }

    /**
     * Comprime o histórico recente em um sumário salvo em conversations.context.
     * Isso permite que sessões futuras "lembrem" do que foi discutido.
     */
    public function compressMemory(
        Conversation $conversation,
        User         $mayor,
        Municipality $municipality
    ): void {
        $existingMemory   = $conversation->context['memory_summary'] ?? null;
        $lastCompressedAt = $conversation->context['memory_compressed_at'] ?? null;

        $messagesQuery = $conversation->messages()
            ->whereIn('role', ['user', 'assistant'])
            ->orderBy('created_at');

        if ($lastCompressedAt) {
            $messagesQuery->where('created_at', '>', $lastCompressedAt);
        }

        $messages = $messagesQuery->limit(40)->get();

        if ($messages->count() < 4) return;

        $dialogText = $messages->map(
            fn($m) => ($m->role === 'user' ? 'PREFEITO' : 'ASSISTENTE') . ': ' . mb_substr($m->content, 0, 500)
        )->implode("\n\n");

        $contextualNote = $existingMemory
            ? "MEMORIA ANTERIOR JA RESUMIDA:\n{$existingMemory}\n\nNOVAS INTERACOES A INCORPORAR:\n{$dialogText}"
            : "INTERACOES A RESUMIR:\n{$dialogText}";

        $compressPrompt = "Voce e um sistema de memoria para um assistente de IA de prefeito municipal.

Analise as interacoes abaixo entre o prefeito {$mayor->name} ({$municipality->name}/{$municipality->state}) e o assistente, e crie um SUMARIO DE MEMORIA conciso e util.

{$contextualNote}

Crie um sumario em topicos curtos que capture:
1. Temas e assuntos ja discutidos (para nao repetir explicacoes)
2. Decisoes ou intencoes manifestadas pelo prefeito
3. Informacoes fornecidas pelo prefeito sobre sua gestao
4. Preferencias de comunicacao demonstradas
5. Contexto politico ou situacoes especificas mencionadas
6. Programas ou recursos que estao sendo investigados

Regras:
- Maximo 400 palavras
- Formato de lista de topicos curtos
- Linguagem telegrafica
- Foque no que e UTIL para contexto futuro
- NUNCA invente informacoes

Retorne APENAS o sumario, sem introducao ou conclusao.";

        $response = Http::withHeaders([
            'x-api-key'         => env('ANTHROPIC_API_KEY'),
            'anthropic-version' => '2023-06-01',
            'content-type'      => 'application/json',
        ])->timeout(30)->post('https://api.anthropic.com/v1/messages', [
            'model'      => 'claude-haiku-4-5-20251001',
            'max_tokens' => 600,
            'messages'   => [['role' => 'user', 'content' => $compressPrompt]],
        ]);

        if (!$response->successful()) {
            throw new \Exception('API error: ' . $response->status());
        }

        $summary = $response->json()['content'][0]['text'] ?? '';
        if (empty(trim($summary))) return;

        $context = $conversation->context ?? [];
        $context['memory_summary']       = $summary;
        $context['memory_compressed_at'] = now()->toISOString();
        $context['memory_message_count'] = $conversation->messages()->count();

        $conversation->update(['context' => $context]);

        Log::info("Memoria comprimida para conversa {$conversation->id} ({$municipality->name})");
    }

    /**
     * Constrói o histórico incluindo memória persistente de sessões anteriores.
     * Busca o memory_summary da conversa ATUAL ou, se vazio, da conversa anterior do usuário.
     */
    private function buildHistoryWithMemory(Conversation $conversation): array
    {
        $history = [];

        // 1. Tentar pegar memória da conversa atual
        $memorySummary = $conversation->context['memory_summary'] ?? null;

        // 2. Se a conversa atual não tem memória, buscar da conversa anterior do mesmo usuário
        if (!$memorySummary) {
            $previousConversation = Conversation::where('user_id', $conversation->user_id)
                ->where('id', '!=', $conversation->id)
                ->whereNotNull('context')
                ->orderByDesc('last_message_at')
                ->first();

            if ($previousConversation) {
                $memorySummary = $previousConversation->context['memory_summary'] ?? null;
            }
        }

        // 3. Injetar a memória no início do histórico
        if ($memorySummary) {
            $history[] = [
                'role'    => 'user',
                'content' => '[MEMORIA DE CONVERSAS ANTERIORES — use para personalizar suas respostas sem precisar perguntar o que ja foi dito]:' .
                    "

{$memorySummary}",
            ];
            $history[] = [
                'role'    => 'assistant',
                'content' => 'Entendido. Tenho a memoria das nossas conversas anteriores e vou usa-la naturalmente nas minhas respostas.',
            ];
        }

        // 4. Mensagens recentes da conversa atual
        $recentMessages = $conversation->messages()
            ->latest()
            ->limit(self::HISTORY_RECENT_LIMIT)
            ->get()
            ->reverse()
            ->map(fn($m) => [
                'role'    => $m->role,
                'content' => $m->content,
            ])
            ->toArray();

        return array_merge($history, $recentMessages);
    }

    // ─── System Prompt ────────────────────────────────────────────────────

    private function buildSystemPrompt(User $mayor, Municipality $municipality, string $ragContext): string
    {
        $voiceProfile    = $this->buildVoiceProfile($municipality);
        $mandateContext  = $this->buildMandateContext($municipality);
        $municipalityData = $this->buildMunicipalityData($municipality);
        $politicalMap    = $this->buildPoliticalMap($municipality);
        $federalAlerts   = $this->buildFederalAlerts($municipality);

        return <<<PROMPT
        Voce e o Assessor Digital do prefeito {$mayor->name}, do municipio de {$municipality->name} ({$municipality->state}).

        ## Sua identidade
        Voce e um assessor politico e de gestao publica de altissimo nivel, com profundo conhecimento em:
        - Marketing politico e comunicacao de mandato
        - Gestao municipal e politicas publicas
        - Captacao de recursos federais
        - Legislacao municipal (LRF, Lei de Licitacoes, FUNDEB, etc.)
        - Benchmarks de municipios de porte similar

        ## Regras ABSOLUTAS de comportamento
        - RESPONDA O QUE FOI PERGUNTADO AGORA. Se o prefeito mudou de assunto, mude junto. Nao fique preso no topico anterior.
        - Se o prefeito perguntar sobre vereador, fale do vereador. Se perguntar sobre programas federais, fale de programas federais.
        - Voce pode mencionar brevemente assuntos anteriores relevantes, mas responda PRIMEIRO o que foi pedido agora.
        - NUNCA use termos tecnicos sem explicar em linguagem simples
        - NUNCA use palavras ou expressoes em ingles
        - SEMPRE responda em portugues do Brasil, linguagem direta e acessivel
        - Seja objetivo — o prefeito tem pouco tempo
        - Quando for fazer analise politica, seja honesto mas construtivo
        - Cite as fontes dos dados quando os usar (SICONFI, IBGE, Portal da Transparencia, etc.)
        - Quando lembrar de algo discutido anteriormente, mencione naturalmente: "Como voce mencionou antes..." ou "Na nossa ultima conversa sobre X..."

        ## Dados do municipio
        {$municipalityData}

        ## Perfil de voz do prefeito
        {$voiceProfile}

        ## Mapa politico
        {$politicalMap}

        ## Contexto do mandato (compromissos ativos)
        {$mandateContext}

        ## Alertas do Radar de Programas Federais
        {$federalAlerts}

        ## Dados e informacoes recuperadas (use esses dados para fundamentar suas respostas):
        {$ragContext}

        ---
        Data de hoje: {$this->today()}
        PROMPT;
    }

    private function buildMunicipalityData(Municipality $municipality): string
    {
        $lines = ["- Municipio: {$municipality->name} — {$municipality->state}"];

        if ($municipality->population)  $lines[] = "- Populacao: " . number_format($municipality->population, 0, ',', '.') . " habitantes";
        if ($municipality->idhm) {
            $nivel = $municipality->idhm >= 0.8 ? 'muito alto' : ($municipality->idhm >= 0.7 ? 'alto' : ($municipality->idhm >= 0.6 ? 'medio' : 'baixo'));
            $lines[] = "- IDHM: {$municipality->idhm} (desenvolvimento {$nivel})";
        }
        if ($municipality->gdp)         $lines[] = "- PIB: R$ " . number_format($municipality->gdp, 2, ',', '.');
        if ($municipality->area_km2)    $lines[] = "- Area: " . number_format($municipality->area_km2, 2, ',', '.') . " km2";
        if ($municipality->region)      $lines[] = "- Regiao: {$municipality->region}";

        if ($settings = $municipality->settings) {
            if (!empty($settings['economia_principal'])) $lines[] = "- Economia: {$settings['economia_principal']}";
            if (!empty($settings['desafios']))            $lines[] = "- Desafios: {$settings['desafios']}";
            if (!empty($settings['potenciais']))          $lines[] = "- Potenciais: {$settings['potenciais']}";
        }

        return implode("\n", $lines);
    }

    private function buildPoliticalMap(Municipality $municipality): string
    {
        $map = $municipality->political_map ?? [];
        if (empty($map)) return 'Mapa politico ainda nao configurado.';

        $lines = [];
        if (!empty($map['allies']))     $lines[] = "- Aliados: {$map['allies']}";
        if (!empty($map['neutral']))    $lines[] = "- Neutros/indecisos: {$map['neutral']}";
        if (!empty($map['opposition'])) $lines[] = "- Oposicao: {$map['opposition']}";
        if (!empty($map['notes']))      $lines[] = "- Observacoes: {$map['notes']}";

        return implode("\n", $lines);
    }

    private function buildVoiceProfile(Municipality $municipality): string
    {
        $profile = $municipality->voice_profile ?? [];
        if (empty($profile)) return 'Perfil de voz ainda nao configurado. Use tom profissional e direto.';

        return implode("\n", [
            "- Tom preferido: " . ($profile['tone'] ?? 'profissional'),
            "- Estilo: "        . ($profile['style'] ?? 'direto'),
            "- Vocabulario: "   . ($profile['vocabulary'] ?? 'acessivel, sem tecnicismos'),
        ]);
    }

    private function buildMandateContext(Municipality $municipality): string
    {
        $commitments = $municipality->governmentCommitments()
            ->whereIn('status', ['em_andamento', 'em_risco', 'prometido'])
            ->orderByRaw("CASE status WHEN 'em_risco' THEN 0 WHEN 'em_andamento' THEN 1 ELSE 2 END")
            ->limit(8)
            ->get(['title', 'status', 'area', 'progress_percent', 'deadline']);

        if ($commitments->isEmpty()) return 'Compromissos de mandato ainda nao configurados.';

        $totalEntregues = $municipality->governmentCommitments()->where('status', 'entregue')->count();
        $totalGeral     = $municipality->governmentCommitments()->count();

        $lines = $commitments->map(fn($c) => sprintf(
            "- %s (%s): %s — %d%% concluido%s",
            $c->title,
            $c->area,
            $c->status,
            $c->progress_percent ?? 0,
            $c->deadline ? " | prazo: {$c->deadline->format('d/m/Y')}" : ''
        ))->implode("\n");

        return "Total: {$totalEntregues}/{$totalGeral} entregues\n" . $lines;
    }

    private function buildFederalAlerts(Municipality $municipality): string
    {
        $alerts = $municipality->federalPrograms()
            ->where('match_score', '>=', 0.75)
            ->whereIn('status', ['open', 'monitoring'])
            ->orderByDesc('match_score')
            ->limit(4)
            ->get(['program_name', 'area', 'match_score', 'max_value']);

        if ($alerts->isEmpty()) return 'Nenhum alerta de programa federal com alta relevancia no momento.';

        return $alerts->map(fn($a) => sprintf(
            "- %s (%s) — relevancia: %.0f%%%s",
            $a->program_name,
            $a->area,
            $a->match_score * 100,
            $a->max_value ? " | ate R$ " . number_format($a->max_value, 0, ',', '.') : ''
        ))->implode("\n");
    }

    private function today(): string
    {
        return now()->locale('pt_BR')->isoFormat('dddd, D [de] MMMM [de] YYYY');
    }
}
