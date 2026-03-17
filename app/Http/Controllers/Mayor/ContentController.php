<?php

namespace App\Http\Controllers\Mayor;

use App\Http\Controllers\Controller;
use App\Models\GeneratedContent;
use App\Services\Communication\ContentGenerationService;
use Illuminate\Http\Request;

class ContentController extends Controller
{
    public function __construct(private ContentGenerationService $service) {}

    public function index()
    {
        $contents = auth()->user()->municipality
            ->generatedContents()
            ->orderByDesc('created_at')
            ->get();

        $posts       = $contents->filter(fn($c) => str_starts_with($c->type, 'post'));
        $entrevistas = $contents->where('type', 'entrevista');
        $crises      = $contents->where('type', 'crise');

        return view('mayor.content.index', compact('contents', 'posts', 'entrevistas', 'crises'));
    }

    public function generatePost(Request $request)
    {
        $request->validate([
            'theme'   => 'required|string|max:1000',
            'channel' => 'required|string',
            'tones'   => 'nullable|array',
        ]);

        try {
            $content = $this->service->generateSocialPost(
                theme: $request->theme,
                channel: $request->channel,
                municipality: auth()->user()->municipality,
                mayor: auth()->user(),
                tones: $request->tones ?? ['celebratorio', 'tecnico', 'empatico'],
            );

            return response()->json([
                'success'    => true,
                'content_id' => $content->id,
                'title'      => $content->title,
                'variations' => $content->variations,
            ]);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'error' => $e->getMessage()], 500);
        }
    }

    public function interviewPrep(Request $request)
    {
        $request->validate([
            'context'          => 'required|string',
            'sensitive_topics' => 'nullable|string',
        ]);

        try {
            $context = $request->context;
            if ($request->sensitive_topics) {
                $context .= "\n\nTemas sensíveis a evitar ou tratar com cuidado: " . $request->sensitive_topics;
            }

            $result = $this->service->prepareInterview(
                context: $context,
                municipality: auth()->user()->municipality,
                mayor: auth()->user(),
            );

            GeneratedContent::create([
                'municipality_id' => auth()->user()->municipality_id,
                'user_id'         => auth()->id(),
                'type'            => 'entrevista',
                'channel'         => 'interno',
                'title'           => 'Prep. Entrevista — ' . now()->format('d/m/Y H:i'),
                'content'         => $result,
                'variations'      => [],
                'tone'            => 'tecnico',
                'status'          => 'draft',
                'tags'            => ['entrevista', 'gerado_ia'],
                'metadata'        => ['provider' => 'anthropic'],
            ]);

            return response()->json(['success' => true, 'content' => $result]);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'error' => $e->getMessage()], 500);
        }
    }

    public function crisisResponse(Request $request)
    {
        $request->validate(['crisis_description' => 'required|string']);

        try {
            $result = $this->service->crisisResponse(
                crisisDescription: $request->crisis_description,
                municipality: auth()->user()->municipality,
                mayor: auth()->user(),
            );

            GeneratedContent::create([
                'municipality_id' => auth()->user()->municipality_id,
                'user_id'         => auth()->id(),
                'type'            => 'crise',
                'channel'         => 'interno',
                'title'           => 'Gestão de Crise — ' . now()->format('d/m/Y H:i'),
                'content'         => $result,
                'variations'      => [],
                'tone'            => 'tecnico',
                'status'          => 'draft',
                'tags'            => ['crise', 'gerado_ia'],
                'metadata'        => ['provider' => 'anthropic'],
            ]);

            return response()->json(['success' => true, 'content' => $result]);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'error' => $e->getMessage()], 500);
        }
    }

    public function show(GeneratedContent $content)
    {
        return response()->json($content);
    }

    public function update(Request $request, GeneratedContent $content)
    {
        $content->update($request->only(['title', 'content', 'variations']));
        return response()->json(['success' => true]);
    }

    public function publish(Request $request, GeneratedContent $content)
    {
        $content->update(['status' => 'published', 'published_at' => now()]);
        return response()->json(['success' => true]);
    }

    public function generateImage(Request $request)
    {
        $request->validate([
            'theme'       => 'required|string|max:1000',
            'image_style' => 'required|string',
            'format'      => 'required|string',
            'color_tone'  => 'nullable|string',
        ]);

        try {
            $municipality = auth()->user()->municipality;

            $styleMap = [
                'moderno'      => 'clean modern government design, professional photography style, bright and optimistic',
                'tradicional'  => 'traditional Brazilian municipal government style, formal, trustworthy',
                'vibrante'     => 'vibrant colorful illustration style, energetic, community-focused',
                'minimalista'  => 'minimalist flat design, simple shapes, clean typography space',
                'fotografico'  => 'realistic photographic style, candid government action shot',
                'aquarela'     => 'watercolor illustration style, warm and approachable, Brazilian cultural elements',
            ];

            $formatMap = [
                'feed'      => 'square 1:1 format, Instagram feed post, with space for text overlay at bottom',
                'stories'   => 'vertical 9:16 format, Instagram Stories, full-bleed background, central composition',
                'carrossel' => 'square 1:1 format, first slide of carousel, clear visual hierarchy',
            ];

            $colorMap = [
                'governo'   => 'Brazilian government colors: green and yellow and blue',
                'neutro'    => 'neutral palette: white, light gray, navy blue, professional tones',
                'terra'     => 'warm earth tones: terracotta, ochre, warm beige, Brazilian landscape',
                'vibrante'  => 'vibrant saturated colors: coral, teal, golden yellow, energetic palette',
            ];

            $styleDesc  = $styleMap[$request->image_style] ?? $styleMap['moderno'];
            $formatDesc = $formatMap[$request->format] ?? $formatMap['feed'];
            $colorDesc  = $colorMap[$request->color_tone ?? 'governo'] ?? $colorMap['neutro'];

            $anthropicKey = env('ANTHROPIC_API_KEY');

            $systemPrompt = "Voce e um especialista em design grafico para comunicacao politica municipal brasileira e expert em criar prompts para geradores de imagem com IA (DALL-E 3, Midjourney). Crie prompts de imagem detalhados em INGLES, apropriados para publicacoes oficiais de prefeituras brasileiras. NUNCA inclua texto, palavras ou letras dentro das imagens. Retorne APENAS JSON valido, sem markdown.";

            $pop = number_format($municipality->population ?? 0, 0, ',', '.');
            $jsonExample = '{"prompts":[{"label":"Opcao 1 - nome criativo","prompt":"prompt detalhado em ingles","negative_prompt":"text, words, letters, numbers, watermark, blurry, low quality","description":"descricao curta em portugues","caption_suggestion":"legenda com emojis para Instagram","hashtags":"hashtags relevantes"}],"design_tips":["dica 1","dica 2","dica 3"]}';

            $userPrompt = "Crie prompts de imagem para Instagram de uma prefeitura brasileira.\n\n"
                . "MUNICIPIO: {$municipality->name} / {$municipality->state}\n"
                . "POPULACAO: {$pop} habitantes\n"
                . "REGIAO: {$municipality->region}\n\n"
                . "TEMA: {$request->theme}\n"
                . "ESTILO: {$styleDesc}\n"
                . "FORMATO: {$formatDesc}\n"
                . "CORES: {$colorDesc}\n\n"
                . "Gere 3 opcoes de prompts diferentes para o mesmo tema.\n\n"
                . "Retorne SOMENTE este JSON:\n"
                . $jsonExample;

            $response = \Illuminate\Support\Facades\Http::withHeaders([
                'x-api-key'         => $anthropicKey,
                'anthropic-version' => '2023-06-01',
                'content-type'      => 'application/json',
            ])->timeout(60)->post('https://api.anthropic.com/v1/messages', [
                'model'      => 'claude-sonnet-4-6',
                'max_tokens' => 2000,
                'messages'   => [['role' => 'user', 'content' => $userPrompt]],
                'system'     => $systemPrompt,
            ]);

            if (!$response->successful()) {
                throw new \Exception('Erro na API: ' . $response->status());
            }

            $raw    = $response->json()['content'][0]['text'] ?? '';
            $clean  = trim(preg_replace(['/^```json\s*/m', '/```\s*$/m'], '', $raw));
            $parsed = json_decode($clean, true);

            if (!$parsed || !isset($parsed['prompts'])) {
                throw new \Exception('Resposta invalida da IA.');
            }

            \App\Models\GeneratedContent::create([
                'municipality_id' => auth()->user()->municipality_id,
                'user_id'         => auth()->id(),
                'type'            => 'imagem_instagram',
                'channel'         => 'instagram',
                'title'           => 'Imagem Instagram - ' . \Illuminate\Support\Str::limit($request->theme, 50),
                'content'         => json_encode($parsed),
                'variations'      => [],
                'tone'            => $request->image_style,
                'status'          => 'draft',
                'tags'            => ['imagem', 'instagram', 'gerado_ia'],
                'metadata'        => [
                    'theme'       => $request->theme,
                    'image_style' => $request->image_style,
                    'format'      => $request->format,
                    'color_tone'  => $request->color_tone,
                ],
            ]);

            return response()->json([
                'success'     => true,
                'prompts'     => $parsed['prompts'],
                'design_tips' => $parsed['design_tips'] ?? [],
            ]);
        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::error('generateImage erro: ' . $e->getMessage());
            return response()->json(['success' => false, 'error' => $e->getMessage()], 500);
        }
    }
}
