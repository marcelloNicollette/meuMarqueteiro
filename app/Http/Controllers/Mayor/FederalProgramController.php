<?php

namespace App\Http\Controllers\Mayor;

use App\Http\Controllers\Controller;
use App\Models\Conversation;
use App\Models\FederalProgramAlert;
use App\Models\User;
use App\Services\AI\AssistantService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;

class FederalProgramController extends Controller
{
    public function __construct(private AssistantService $assistant) {}

    public function index()
    {
        $user = Auth::user();
        if (!$user instanceof User) abort(401);
        $municipality = $user->municipality;
        $programs     = $municipality->federalPrograms()
            ->orderByDesc('match_score')
            ->orderBy('deadline', 'DESC')
            ->get();

        $total = $programs->count();

        return view('mayor.federal-programs.index', compact('municipality', 'programs', 'total'));
    }

    public function askAssistant(Request $request, FederalProgramAlert $program)
    {
        $user = Auth::user();
        if (!$user instanceof User) abort(401);
        if ($program->municipality_id !== $user->municipality_id) abort(403);

        $conversation = $user->conversations()->create([
            'municipality_id' => $user->municipality_id,
            'title'           => 'Programa Federal: ' . Str::limit($program->program_name, 60),
            'is_active'       => true,
            'last_message_at' => now(),
        ]);

        $lines = [];
        $lines[] = "Quero transformar este programa federal em um plano de ação executável para o município.";
        $lines[] = "";
        $lines[] = "DADOS DO PROGRAMA:";
        $lines[] = "- Nome: {$program->program_name}";
        if ($program->program_code) $lines[] = "- Código: {$program->program_code}";
        if ($program->ministry) $lines[] = "- Ministério: {$program->ministry}";
        if ($program->area) $lines[] = "- Área: {$program->area}";
        if ($program->funding_type) $lines[] = "- Tipo de recurso: {$program->funding_type}";
        if ($program->max_value) $lines[] = "- Valor máximo: R$ " . number_format((float) $program->max_value, 2, ',', '.');
        if ($program->deadline) $lines[] = "- Prazo: " . $program->deadline->format('d/m/Y');
        if ($program->source_url) $lines[] = "- Link: {$program->source_url}";
        if ($program->description) {
            $lines[] = "";
            $lines[] = "DESCRIÇÃO (resumo):";
            $lines[] = Str::limit(trim($program->description), 900);
        }
        $lines[] = "";
        $lines[] = "ENTREGA OBRIGATÓRIA:";
        $lines[] = "1) Resumo executivo (em 5 linhas) do porquê vale a pena (ou não).";
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
}
