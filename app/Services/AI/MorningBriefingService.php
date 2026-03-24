<?php

namespace App\Services\AI;

use App\Models\MorningBriefing;
use App\Models\Municipality;
use App\Services\RAG\RAGService;
use App\Services\WebPushService;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * Gera o Briefing Matinal automático para cada prefeito.
 * Executado via comando agendado todo dia às 6h30 (BRT).
 */
class MorningBriefingService
{

    public function __construct(
        private RAGService $rag,
        private AIProviderService $ai,
    ) {}

    public function generate(Municipality $municipality): MorningBriefing
    {
        $mayor = $municipality->mayor;
        $today = Carbon::today()->locale('pt_BR')->isoFormat('dddd, D [de] MMMM [de] YYYY');
        $diaSemana = Carbon::today()->locale('pt_BR')->isoFormat('dddd');

        // ── Dados do mandato ──────────────────────────────────────────
        $commitments   = $municipality->governmentCommitments()->get();
        $totalGeral    = $commitments->count();
        $entregues     = $commitments->where('status', 'entregue')->count();
        $emRisco       = $commitments->where('status', 'em_risco');
        $emAndamento   = $commitments->where('status', 'em_andamento');

        $pctConcluido  = $totalGeral > 0 ? round(($entregues / $totalGeral) * 100) : 0;

        // Compromissos com prazo próximo (próximos 30 dias)
        $prazosProximos = $commitments
            ->filter(
                fn($c) => $c->deadline && $c->deadline->diffInDays(now(), false) <= 0
                    && $c->deadline->diffInDays(now(), false) >= -30
                    && !in_array($c->status, ['entregue', 'cancelado'])
            )
            ->sortBy('deadline')
            ->take(3);

        // ── Radar de programas federais ───────────────────────────────
        $programasAbertos = $municipality->federalPrograms()
            ->where('match_score', '>=', 0.80)
            ->whereIn('status', ['open'])
            ->orderByDesc('match_score')
            ->limit(3)
            ->get();

        // ── Demandas recentes não resolvidas ──────────────────────────
        $demandasPendentes = 0;
        try {
            $demandasPendentes = DB::table('demands')
                ->where('municipality_id', $municipality->id)
                ->whereNotIn('status', ['resolvida', 'arquivada'])
                ->count();
        } catch (\Exception $e) {
            // tabela pode nao existir ainda
        }

        // ── Contexto RAG ──────────────────────────────────────────────
        $ragContext = '';
        try {
            $ragChunks  = $this->rag->retrieve('situacao fiscal orcamentaria indicadores municipio hoje', $municipality, 10);
            $ragContext  = $this->rag->buildContext($ragChunks);
        } catch (\Exception $e) {
            Log::warning("RAG falhou no briefing de {$municipality->name}: " . $e->getMessage());
        }

        // ── Montar contexto estruturado ───────────────────────────────
        $compromisoRiscoTxt = $emRisco->isNotEmpty()
            ? $emRisco->map(fn($c) => "- {$c->title} ({$c->area})")->implode("\n")
            : 'Nenhum compromisso em risco no momento.';

        $compromisoAndamentoTxt = $emAndamento->take(4)->map(
            fn($c) =>
            "- {$c->title} ({$c->area}): {$c->progress_percent}% concluido"
        )->implode("\n");

        $prazosProximosTxt = $prazosProximos->isNotEmpty()
            ? $prazosProximos->map(
                fn($c) =>
                "- {$c->title}: prazo em " . $c->deadline->format('d/m/Y') . " (" . abs($c->deadline->diffInDays(now())) . " dias)"
            )->implode("\n")
            : 'Nenhum prazo critico nos proximos 30 dias.';

        $programasTxt = $programasAbertos->isNotEmpty()
            ? $programasAbertos->map(
                fn($p) =>
                "- {$p->program_name} ({$p->area}) — relevancia: " . round($p->match_score * 100) . "%"
            )->implode("\n")
            : 'Sem novos alertas de alta relevancia esta semana.';

        // ── Prompt rico ───────────────────────────────────────────────
        $mayorName = $mayor?->name ?: 'Prefeito(a)';

        $prompt = "Voce e o assessor digital pessoal do prefeito {$mayorName}, de {$municipality->name} ({$municipality->state}).

Hoje e {$today}.

Gere o briefing matinal do dia — um resumo executivo personalizado para o prefeito comecar bem o {$diaSemana}.

DADOS DO MANDATO:
- Total de compromissos: {$totalGeral} | Entregues: {$entregues} ({$pctConcluido}% do mandato cumprido)
- Demandas de cidadaos pendentes: {$demandasPendentes}

COMPROMISSOS EM RISCO (precisa de atencao):
{$compromisoRiscoTxt}

COMPROMISSOS EM ANDAMENTO:
{$compromisoAndamentoTxt}

PRAZOS PROXIMOS (30 dias):
{$prazosProximosTxt}

RADAR DE PROGRAMAS FEDERAIS (oportunidades abertas):
{$programasTxt}

DADOS DO MUNICIPIO (RAG):
{$ragContext}

---
ESTRUTURA OBRIGATORIA DO BRIEFING:

## Bom dia, [nome do prefeito]!
[Saudacao personalizada e motivacional, 2 linhas, fazendo referencia ao dia da semana e ao momento do mandato]

## O que voce precisa saber hoje
[3-4 pontos objetivos sobre o que acontece hoje que merece atencao — use marcadores. Se for segunda-feira, mencione a semana que comeca. Se for sexta, o balanco da semana.]

## Pauta de comunicacao sugerida
[1-2 sugestoes concretas do que comunicar hoje nas redes sociais ou internamente, com base nos dados do mandato]

## Alertas ⚠️
[Apenas se houver: compromissos em risco, prazos criticos ou demandas pendentes em volume alto. Se nao houver alertas reais, OMITIR esta secao.]

## Oportunidade da semana
[Se houver programas federais abertos com alta relevancia, destaque o principal com 2-3 linhas explicando por que e uma boa oportunidade para o municipio. Caso contrario, omitir.]

## Pergunta estrategica do dia
[Uma reflexao provocativa e relevante para o prefeito pensar durante o dia, relacionada ao momento do mandato]

REGRAS ABSOLUTAS:
- NUNCA use ingles
- Linguagem direta, como um assessor de confianca falaria pessoalmente
- Maximo 500 palavras no total
- Dados precisos (use os numeros fornecidos)
- Nao invente informacoes que nao foram fornecidas
- Use emojis com moderacao apenas onde agregar valor visual (⚠️ ✅ 🎯)";

        // ── Chamar API ────────────────────────────────────────────────
        $response = $this->ai->chat(
            [['role' => 'user', 'content' => $prompt]],
            ['max_tokens' => 1500]
        );

        $content = $response->content ?? '';

        if (empty(trim($content))) {
            throw new \RuntimeException("Conteudo vazio gerado para {$municipality->name}");
        }

        // ── Salvar no banco ───────────────────────────────────────────
        $briefing = MorningBriefing::create([
            'municipality_id'  => $municipality->id,
            'date'             => today(),
            'content'          => $content,
            'sections'         => [
                'generated_at'       => now()->toISOString(),
                'compromissos_total' => $totalGeral,
                'compromissos_ok'    => $entregues,
                'pct_concluido'      => $pctConcluido,
                'em_risco'           => $emRisco->count(),
                'demandas_pendentes' => $demandasPendentes,
                'programas_abertos'  => $programasAbertos->count(),
            ],
            'delivery_channel' => 'app',
            'delivered_at'     => now(),
            'ai_provider'      => $response->provider,
            'tokens_used'      => $response->tokensUsed ?? 0,
        ]);

        // ── Disparar notificação push ─────────────────────────────────
        try {
            $mayor = $municipality->mayor;
            if ($mayor) {
                $alertas = $emRisco->count() > 0
                    ? " | {$emRisco->count()} alerta(s) no mandato"
                    : '';

                app(WebPushService::class)->sendToUser($mayor, [
                    'title'             => '☀️ Seu briefing do dia está pronto',
                    'body'              => "Bom dia, {$mayor->name}! {$pctConcluido}% do mandato cumprido{$alertas}.",
                    'icon'              => '/images/mascote-robo.jpg',
                    'url'               => '/mayor/mandato/briefings/' . $briefing->id,
                    'tag'               => 'briefing-' . today()->format('Y-m-d'),
                    'requireInteraction' => false,
                ]);
            }
        } catch (\Exception $e) {
            // Push falhou — não impede o fluxo principal
            Log::warning("Push do briefing falhou para {$municipality->name}: " . $e->getMessage());
        }

        return $briefing;
    }
}
