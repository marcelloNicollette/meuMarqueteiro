<?php

namespace App\Http\Controllers;

use App\Models\Conversation;
use App\Services\AI\AssistantService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ChatController extends Controller
{
    public function __construct(private AssistantService $assistant) {}

    /**
     * Tela principal do chat.
     */
    public function index(): View
    {
        $user          = auth()->user();
        $conversations = $user->conversations()
            ->latest('last_message_at')
            ->limit(20)
            ->get();

        $activeConversation = $conversations->first();

        // Carregar mensagens da conversa ativa para renderizar no blade
        $messages = $activeConversation
            ? $activeConversation->messages()->orderBy('created_at')->get()
            : collect();

        return view('mayor.chat.index', compact('conversations', 'activeConversation', 'messages'));
    }

    /**
     * Exibir uma conversa específica.
     */
    public function show(Conversation $conversation): View
    {
        if ($conversation->user_id !== auth()->id()) {
            abort(403);
        }

        $messages = $conversation->messages()->get();

        return view('mayor.chat.show', compact('conversation', 'messages'));
    }

    /**
     * Criar nova conversa.
     */
    public function create(): JsonResponse
    {
        $conversation = auth()->user()->conversations()->create([
            'municipality_id' => auth()->user()->municipality_id,
            'title'           => 'Nova conversa',
            'is_active'       => true,
            'last_message_at' => now(),
        ]);

        return response()->json(['id' => $conversation->id]);
    }

    /**
     * Enviar mensagem e receber resposta do assistente (AJAX).
     */
    public function sendMessage(Request $request, Conversation $conversation): JsonResponse
    {
        // Verificar que a conversa pertence ao usuário logado
        if ($conversation->user_id !== auth()->id()) {
            abort(403);
        }

        $request->validate([
            'message' => ['required', 'string', 'max:4000'],
        ]);

        try {
            $assistantMessage = $this->assistant->chat(
                userMessage: $request->input('message'),
                mayor: auth()->user(),
                conversation: $conversation,
            );

            return response()->json([
                'success'    => true,
                'message_id' => $assistantMessage->id,
                'content'    => $assistantMessage->content,
                'sources'    => $assistantMessage->rag_sources,
            ]);
        } catch (\Throwable $e) {
            report($e);

            return response()->json([
                'success' => false,
                'error'   => 'Não foi possível processar sua mensagem. Tente novamente.',
            ], 500);
        }
    }

    /**
     * Registrar feedback do usuário sobre uma mensagem.
     */
    public function feedback(Request $request, $messageId): JsonResponse
    {
        $request->validate([
            'feedback' => ['required', 'in:thumbs_up,thumbs_down'],
            'note'     => ['nullable', 'string', 'max:500'],
        ]);

        $message = auth()->user()
            ->conversations()
            ->with('messages')
            ->get()
            ->flatMap->messages
            ->firstWhere('id', $messageId);

        if (!$message) {
            return response()->json(['error' => 'Mensagem não encontrada.'], 404);
        }

        $message->update([
            'feedback'      => $request->feedback,
            'feedback_note' => $request->note,
        ]);

        return response()->json(['success' => true]);
    }
}
