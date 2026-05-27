<?php

namespace App\Http\Controllers\Mayor;

use App\Http\Controllers\Controller;
use App\Models\Conversation;
use App\Models\MorningBriefing;
use App\Models\User;
use App\Services\AI\AssistantService;
use App\Services\AI\MorningBriefingService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class BriefingController extends Controller
{
    public function __construct(
        private MorningBriefingService $service,
        private AssistantService $assistant,
    ) {}

    public function index()
    {
        $user = Auth::user();
        if (!$user) abort(401);
        $briefings = MorningBriefing::query()
            ->where('user_id', $user->id)
            ->whereDate('date', today())
            ->orderByDesc('date')
            ->paginate(15);

        $todayBriefing = MorningBriefing::query()
            ->where('user_id', $user->id)
            ->whereDate('date', today())
            ->latest('id')
            ->first();

        $praHojePreferences = [
            'enabled' => (bool) data_get($user->preferences, 'pra_hoje.enabled', true),
            'delivery_time' => (string) data_get($user->preferences, 'pra_hoje.delivery_time', '07:30'),
            'email_enabled' => (bool) data_get($user->preferences, 'pra_hoje.email_enabled', false),
        ];

        return view('mayor.briefings.index', compact('briefings', 'todayBriefing', 'praHojePreferences'));
    }

    public function show(MorningBriefing $briefing)
    {
        $this->authorizeAccess($briefing);

        if (!$briefing->read_at) {
            $briefing->update(['read_at' => now()]);
        }

        return view('mayor.briefings.show', compact('briefing'));
    }

    public function markRead(MorningBriefing $briefing): JsonResponse
    {
        $this->authorizeAccess($briefing);
        $briefing->update(['read_at' => now()]);
        return response()->json(['ok' => true]);
    }

    /**
     * Gerar briefing sob demanda (quando o automático ainda não  rodou hoje).
     */
    public function generate(): JsonResponse
    {
        $user = Auth::user();
        if (!$user) abort(401);
        $refresh = request()->boolean('refresh');

        $existing = MorningBriefing::query()
            ->where('user_id', $user->id)
            ->whereDate('date', today())
            ->latest('id')
            ->first();

        if ($existing && !$refresh) {
            return response()->json([
                'ok'          => true,
                'already_had' => true,
                'briefing_id' => $existing->id,
                'redirect'    => route('pra-hoje.show', $existing),
            ]);
        }

        try {
            $briefing = $this->service->generateForUser($user, force: $refresh);

            return response()->json([
                'ok'          => true,
                'already_had' => $existing !== null,
                'briefing_id' => $briefing->id,
                'redirect'    => route('pra-hoje.show', $briefing),
            ]);
        } catch (\Throwable $e) {
            $ref = (string) Str::uuid();
            Log::error("Falha ao gerar briefing sob demanda ({$ref}) para {$user->name}", ['exception' => $e]);
            return response()->json([
                'ok' => false,
                'error' => "Não foi possível gerar o briefing agora (ref: {$ref}). Tente novamente em instantes.",
            ], 500);
        }
    }

    public function openCardConversation(MorningBriefing $briefing, int $cardIndex): RedirectResponse
    {
        $this->authorizeAccess($briefing);

        $cards = collect($briefing->cards ?? []);
        $card = $cards->get($cardIndex);

        if (!is_array($card) || blank($card['conversation_prompt'] ?? null)) {
            abort(404);
        }

        $user = Auth::user();
        if (!$user instanceof User) {
            abort(401);
        }

        $conversation = Conversation::query()->create([
            'municipality_id' => $user->municipality_id,
            'user_id' => $user->id,
            'origin_module' => 'pra_hoje',
            'title' => 'Pra Hoje: ' . Str::limit((string) ($card['title'] ?? 'Prioridade do dia'), 60),
            'auto_tags' => ['pra_hoje', (string) ($card['module_key'] ?? 'prioridade_dia')],
            'is_active' => true,
            'last_message_at' => now(),
        ]);

        $this->assistant->chat(
            userMessage: (string) $card['conversation_prompt'],
            mayor: $user,
            conversation: $conversation,
        );

        return redirect()->route('chat.show', $conversation);
    }

    public function updatePreferences(Request $request): RedirectResponse
    {
        $user = Auth::user();
        if (!$user instanceof User) {
            abort(401);
        }

        $data = $request->validate([
            'enabled' => ['nullable', 'boolean'],
            'delivery_time' => ['required', 'date_format:H:i'],
            'email_enabled' => ['nullable', 'boolean'],
        ]);

        $preferences = $user->preferences ?? [];
        data_set($preferences, 'pra_hoje.enabled', (bool) ($data['enabled'] ?? false));
        data_set($preferences, 'pra_hoje.delivery_time', (string) $data['delivery_time']);
        data_set($preferences, 'pra_hoje.email_enabled', (bool) ($data['email_enabled'] ?? false));

        $user->update([
            'preferences' => $preferences,
        ]);

        return back()->with('success', 'Preferencias do Pra hoje atualizadas.');
    }

    private function authorizeAccess(MorningBriefing $briefing): void
    {
        $user = Auth::user();
        if (!$user) abort(401);

        if ($briefing->user_id !== null) {
            if ((int) $briefing->user_id !== (int) $user->id) {
                abort(403);
            }

            return;
        }

        if ($briefing->municipality_id !== $user->municipality_id) {
            abort(403);
        }
    }
}
