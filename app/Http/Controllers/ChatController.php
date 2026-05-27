<?php

namespace App\Http\Controllers;

use App\Models\Conversation;
use App\Models\Message;
use App\Models\MessageShare;
use App\Models\User;
use App\Enums\UserRole;
use App\Services\AI\ChatProactiveAlertService;
use App\Services\AI\ConversationExportSuggestionService;
use App\Services\AI\AssistantService;
use App\Services\AI\ChatAudioService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;
use Illuminate\View\View;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class ChatController extends Controller
{
    public function __construct(
        private AssistantService $assistant,
        private ConversationExportSuggestionService $exports,
        private ChatProactiveAlertService $proactiveAlerts,
        private ChatAudioService $chatAudio,
    ) {}

    /**
     * Tela principal do chat.
     */
    public function index(): View
    {
        $user = Auth::user();
        $conversations = $this->loadConversationsForChat($user);

        return $this->renderChatView($user, $conversations->first(), $conversations);
    }

    /**
     * Exibir uma conversa específica.
     */
    public function show(Conversation $conversation): View
    {
        if ($conversation->user_id !== Auth::id()) {
            abort(403);
        }

        $user = Auth::user();
        $conversations = $this->loadConversationsForChat($user);

        return $this->renderChatView($user, $conversation, $conversations);
    }

    /**
     * Criar nova conversa.
     */
    public function create(): JsonResponse
    {
        $user = Auth::user();
        $conversation = $user->conversations()->create([
            'municipality_id' => $user->municipality_id,
            'origin_module'   => 'chat',
            'title'           => 'Nova conversa',
            'auto_tags'       => [],
            'is_active'       => true,
            'last_message_at' => now(),
        ]);

        return response()->json([
            ...$this->serializeConversation($conversation),
        ]);
    }

    /**
     * Enviar mensagem e receber resposta do assistente (AJAX).
     */
    public function sendMessage(Request $request, Conversation $conversation): JsonResponse
    {
        // Verificar que a conversa pertence ao usuário logado
        if ($conversation->user_id !== Auth::id()) {
            abort(403);
        }

        $request->validate([
            'message' => ['required', 'string', 'max:4000'],
            'input_type' => ['nullable', 'in:text,voice'],
            'voice_transcript' => ['nullable', 'string', 'max:4000'],
        ]);

        try {
            $inputType = $request->string('input_type')->toString() ?: 'text';
            $voiceTranscript = $request->filled('voice_transcript')
                ? $request->string('voice_transcript')->toString()
                : null;

            $assistantMessage = $this->assistant->chat(
                userMessage: $request->input('message'),
                mayor: Auth::user(),
                conversation: $conversation,
                inputType: $inputType,
                voiceTranscript: $inputType === 'voice' ? $voiceTranscript : null,
            );

            $freshConversation = $conversation->fresh();
            $exportSuggestion = $this->exports->suggest($freshConversation, $assistantMessage);

            return response()->json([
                'success'    => true,
                'message_id' => $assistantMessage->id,
                'content'    => $assistantMessage->content,
                'sources'    => $assistantMessage->rag_sources,
                'memory'     => $assistantMessage->metadata['memory'] ?? null,
                'export_suggestion' => $exportSuggestion,
                'conversation' => $this->serializeConversation($freshConversation),
            ]);
        } catch (\Throwable $e) {
            report($e);

            return response()->json([
                'success' => false,
                'error'   => 'Não foi possível processar sua mensagem. Tente novamente.',
            ], 500);
        }
    }

    public function updateAudioPreferences(Request $request): JsonResponse
    {
        $data = $request->validate([
            'input_enabled' => ['nullable', 'boolean'],
            'output_enabled' => ['nullable', 'boolean'],
            'speech_rate' => ['nullable', 'numeric', 'min:0.7', 'max:1.4'],
        ]);

        $user = Auth::user();
        $preferences = is_array($user->preferences) ? $user->preferences : [];
        $audioPreferences = is_array($preferences['chat_audio'] ?? null) ? $preferences['chat_audio'] : [];

        if (array_key_exists('input_enabled', $data)) {
            $audioPreferences['input_enabled'] = (bool) $data['input_enabled'];
        }

        if (array_key_exists('output_enabled', $data)) {
            $audioPreferences['output_enabled'] = (bool) $data['output_enabled'];
        }

        if (array_key_exists('speech_rate', $data)) {
            $audioPreferences['speech_rate'] = round((float) $data['speech_rate'], 2);
        }

        $preferences['chat_audio'] = $audioPreferences;

        $user->update([
            'preferences' => $preferences,
        ]);

        return response()->json([
            'success' => true,
            'preferences' => $audioPreferences,
        ]);
    }

    public function updateConversationTags(Request $request, Conversation $conversation): JsonResponse
    {
        if ($conversation->user_id !== Auth::id()) {
            abort(403);
        }

        $data = $request->validate([
            'tags' => ['nullable', 'array', 'max:8'],
            'tags.*' => ['nullable', 'string', 'max:30'],
        ]);

        $normalizedTags = collect($data['tags'] ?? [])
            ->map(fn(string $tag) => $this->normalizeTag($tag))
            ->filter()
            ->unique()
            ->values()
            ->take(8)
            ->all();

        $context = is_array($conversation->context) ? $conversation->context : [];

        if ($normalizedTags === []) {
            unset($context['manual_tags']);
        } else {
            $context['manual_tags'] = $normalizedTags;
        }

        $conversation->update([
            'context' => $context,
        ]);

        $freshConversation = $conversation->fresh();

        return response()->json([
            'success' => true,
            'conversation' => $this->serializeConversation($freshConversation),
        ]);
    }

    public function transcribeAudio(Request $request): JsonResponse
    {
        $request->validate([
            'audio' => ['required', 'file', 'max:12288', 'mimetypes:audio/webm,audio/mp4,audio/mpeg,audio/mpga,audio/mp3,audio/wav,audio/x-wav,audio/ogg,video/webm'],
        ]);

        try {
            $transcript = $this->chatAudio->transcribe($request->file('audio'));

            return response()->json([
                'success' => true,
                'transcript' => $transcript['text'],
                'provider' => $transcript['provider'],
                'model' => $transcript['model'],
            ]);
        } catch (\Throwable $e) {
            report($e);

            return response()->json([
                'success' => false,
                'error' => 'Nao foi possivel transcrever o audio agora.',
            ], 422);
        }
    }

    public function messageAudio(Request $request, Message $message): Response
    {
        $conversation = $message->conversation;

        if (!$conversation || $conversation->user_id !== Auth::id() || $message->role !== 'assistant') {
            abort(403);
        }

        $data = $request->validate([
            'speed' => ['nullable', 'numeric', 'min:0.7', 'max:1.4'],
        ]);

        try {
            $audio = $this->chatAudio->synthesize(
                text: (string) $message->content,
                speed: (float) ($data['speed'] ?? 1.0),
            );

            return response($audio['content'], 200, [
                'Content-Type' => $audio['content_type'],
                'Cache-Control' => 'no-store, no-cache, must-revalidate',
                'X-Chat-Audio-Provider' => $audio['provider'],
                'X-Chat-Audio-Model' => $audio['model'],
                'X-Chat-Audio-Voice' => $audio['voice'],
                'X-Chat-Audio-Cached' => !empty($audio['cached']) ? '1' : '0',
            ]);
        } catch (\Throwable $e) {
            report($e);

            return response('Nao foi possivel gerar o audio agora.', 422, [
                'Content-Type' => 'text/plain; charset=UTF-8',
            ]);
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

        $message = \App\Models\Message::query()
            ->whereKey($messageId)
            ->whereHas('conversation', fn($query) => $query->where('user_id', Auth::id()))
            ->first();

        if (!$message) {
            return response()->json(['error' => 'Mensagem não  encontrada.'], 404);
        }

        $message->update([
            'feedback'      => $request->feedback,
            'feedback_note' => $request->note,
        ]);

        return response()->json(['success' => true]);
    }

    public function exportMessage(Request $request, Message $message): JsonResponse
    {
        $request->validate([
            'type' => ['required', 'string', 'max:50'],
        ]);

        $conversation = $message->conversation;

        if (!$conversation || $conversation->user_id !== Auth::id() || $message->role !== 'assistant') {
            abort(403);
        }

        $type = $request->string('type')->toString();

        if (!$this->exports->isExportableType($type)) {
            return response()->json(['success' => false, 'error' => 'Tipo de exportacao invalido.'], 422);
        }

        $content = $this->exports->export(Auth::user(), $conversation, $message, $type);

        return response()->json([
            'success' => true,
            'content_id' => $content->id,
            'title' => $content->title,
            'redirect_url' => $this->exports->formatSavedContent($content)['redirect_url'],
        ]);
    }

    public function destroy(Conversation $conversation): JsonResponse
    {
        if ($conversation->user_id !== Auth::id()) {
            abort(403);
        }
        $conversation->delete();
        return response()->json(['success' => true]);
    }

    public function shareMessage(Request $request, Message $message): JsonResponse
    {
        $request->validate([
            'recipient_user_id' => ['required', 'integer', 'exists:users,id'],
            'excerpt' => ['required', 'string', 'max:2000'],
            'note' => ['nullable', 'string', 'max:500'],
        ]);

        $conversation = $message->conversation;
        $owner = Auth::user();

        if (!$conversation || $conversation->user_id !== $owner->id) {
            abort(403);
        }

        $recipient = $this->eligibleShareRecipients($owner)
            ->firstWhere('id', (int) $request->integer('recipient_user_id'));

        if (!$recipient) {
            return response()->json([
                'success' => false,
                'error' => 'Destinatario invalido para compartilhamento.',
            ], 422);
        }

        $excerpt = trim($request->string('excerpt')->toString());

        if (!$this->excerptBelongsToMessage($excerpt, (string) $message->content)) {
            return response()->json([
                'success' => false,
                'error' => 'O trecho informado precisa existir dentro da mensagem original.',
            ], 422);
        }

        $contextExcerpt = $conversation->messages()
            ->where('id', '<=', $message->id)
            ->orderByDesc('id')
            ->limit(2)
            ->get()
            ->reverse()
            ->map(fn(Message $item) => ($item->role === 'user' ? 'Prefeito' : 'Meu Marqueteiro') . ': ' . Str::limit(trim($item->content), 240))
            ->implode("\n");

        $share = MessageShare::create([
            'municipality_id' => $conversation->municipality_id,
            'conversation_id' => $conversation->id,
            'message_id' => $message->id,
            'owner_user_id' => $owner->id,
            'recipient_user_id' => $recipient->id,
            'share_token' => (string) Str::uuid(),
            'excerpt' => $excerpt,
            'context_excerpt' => $contextExcerpt,
            'message_role' => $message->role,
            'note' => $request->input('note'),
        ]);

        return response()->json([
            'success' => true,
            'share_id' => $share->id,
            'recipient_name' => $recipient->name,
            'share_url' => route('chat.shared.show', $share->share_token),
            'share' => $this->serializeShare($share->loadMissing(['recipient', 'revokedBy']), $owner),
            'message_share_state' => $this->buildShareStateForMessage($message->fresh(['shares.recipient', 'shares.revokedBy']), $owner),
        ]);
    }

    public function revokeShare(MessageShare $share): JsonResponse
    {
        $owner = Auth::user();

        if ($share->owner_user_id !== $owner->id) {
            abort(403);
        }

        if (!$share->revoked_at) {
            $share->update([
                'revoked_at' => now(),
                'revoked_by_user_id' => $owner->id,
            ]);
        }

        $message = $share->message()->with(['shares.recipient', 'shares.revokedBy'])->firstOrFail();

        return response()->json([
            'success' => true,
            'message_id' => $message->id,
            'share_id' => $share->id,
            'message_share_state' => $this->buildShareStateForMessage($message, $owner),
        ]);
    }

    public function showShared(string $shareToken): View
    {
        $share = MessageShare::query()
            ->where('share_token', $shareToken)
            ->with(['owner', 'recipient', 'conversation', 'revokedBy'])
            ->firstOrFail();

        $user = Auth::user();
        $isAllowed = $share->owner_user_id === $user->id
            || $share->recipient_user_id === $user->id
            || $user->isAdmin();

        if (!$isAllowed) {
            abort(403);
        }

        if ($share->revoked_at && $share->owner_user_id !== $user->id) {
            abort(410);
        }

        if ($share->recipient_user_id === $user->id && !$share->viewed_at) {
            $share->update(['viewed_at' => now()]);
        }

        return view('mayor.chat.shared', compact('share'));
    }

    private function buildExportSuggestions(Conversation $conversation, Collection $messages): array
    {
        return $messages
            ->filter(fn(Message $message) => $message->role === 'assistant')
            ->mapWithKeys(function (Message $message) use ($conversation) {
                $suggestion = $this->exports->suggest($conversation, $message);

                return $suggestion ? [$message->id => $suggestion] : [];
            })
            ->all();
    }

    private function renderChatView(User $user, ?Conversation $activeConversation, ?Collection $conversations = null): View
    {
        $conversations ??= $this->loadConversationsForChat($user);

        $messages = $activeConversation
            ? $activeConversation->messages()->with(['shares.recipient', 'shares.revokedBy'])->orderBy('created_at')->get()
            : collect();

        $exportSuggestions = $activeConversation
            ? $this->buildExportSuggestions($activeConversation, $messages)
            : [];
        $chatAlerts = $this->proactiveAlerts->buildFor($user, $user->municipality, $activeConversation);
        $shareRecipients = $this->eligibleShareRecipients($user);
        $shareStates = $this->buildShareStates($messages, $user);
        $globalShareFeed = $this->buildGlobalShareFeed($user);
        $audioServerCapabilities = $this->chatAudio->capabilities();
        $activeConversationTags = $activeConversation ? $this->resolveConversationTags($activeConversation) : [];
        $activeConversationHasManualTags = $activeConversation ? $this->hasManualTags($activeConversation) : false;

        return view('mayor.chat.index', compact(
            'conversations',
            'activeConversation',
            'messages',
            'exportSuggestions',
            'chatAlerts',
            'shareRecipients',
            'shareStates',
            'globalShareFeed',
            'audioServerCapabilities',
            'activeConversationTags',
            'activeConversationHasManualTags'
        ));
    }

    private function loadConversationsForChat(User $user): Collection
    {
        $conversations = $user->conversations()
            ->withCount('messages')
            ->latest('last_message_at')
            ->limit(20)
            ->get();

        $activeShareCounts = MessageShare::query()
            ->where('owner_user_id', $user->id)
            ->whereNull('revoked_at')
            ->selectRaw('conversation_id, COUNT(*) as active_shares_count')
            ->groupBy('conversation_id')
            ->pluck('active_shares_count', 'conversation_id');

        return $conversations->map(function (Conversation $conversation) use ($activeShareCounts) {
            $conversation->setAttribute('active_shares_count', (int) ($activeShareCounts[$conversation->id] ?? 0));
            $conversation->setAttribute('display_tags', $this->resolveConversationTags($conversation));
            $conversation->setAttribute('has_manual_tags', $this->hasManualTags($conversation));

            return $conversation;
        });
    }

    private function serializeConversation(Conversation $conversation): array
    {
        return [
            'id' => $conversation->id,
            'title' => $conversation->title,
            'tags' => $this->resolveConversationTags($conversation),
            'origin_module' => $conversation->origin_module,
            'intent' => $conversation->context['intent'] ?? null,
            'active_shares_count' => (int) ($conversation->getAttribute('active_shares_count') ?? 0),
            'last_message_at_iso' => optional($conversation->last_message_at)->toIso8601String(),
            'has_manual_tags' => $this->hasManualTags($conversation),
        ];
    }

    private function resolveConversationTags(Conversation $conversation): array
    {
        $manualTags = $conversation->context['manual_tags'] ?? null;

        if (is_array($manualTags) && $manualTags !== []) {
            return array_values(array_filter($manualTags, fn($tag) => is_string($tag) && trim($tag) !== ''));
        }

        return is_array($conversation->auto_tags) ? $conversation->auto_tags : [];
    }

    private function hasManualTags(Conversation $conversation): bool
    {
        $manualTags = $conversation->context['manual_tags'] ?? null;

        return is_array($manualTags) && $manualTags !== [];
    }

    private function normalizeTag(?string $tag): ?string
    {
        $value = trim(Str::lower(Str::ascii((string) $tag)));
        $value = preg_replace('/[^a-z0-9\s_-]/', ' ', $value) ?? $value;
        $value = preg_replace('/[\s-]+/', '_', $value) ?? $value;
        $value = trim($value, '_');

        return $value !== '' ? Str::limit($value, 30, '') : null;
    }

    private function eligibleShareRecipients(User $owner): Collection
    {
        return User::query()
            ->where('is_active', true)
            ->where('id', '!=', $owner->id)
            ->where(function ($query) use ($owner) {
                $query->where('municipality_id', $owner->municipality_id)
                    ->orWhere('role', UserRole::Admin);
            })
            ->orderBy('name')
            ->get(['id', 'name', 'email', 'role', 'municipality_id']);
    }

    private function buildShareStates(Collection $messages, User $viewer): array
    {
        return $messages
            ->filter(fn(Message $message) => $message->shares->isNotEmpty())
            ->mapWithKeys(fn(Message $message) => [
                $message->id => $this->buildShareStateForMessage($message, $viewer),
            ])
            ->all();
    }

    private function buildShareStateForMessage(Message $message, User $viewer): array
    {
        $shares = $message->relationLoaded('shares')
            ? $message->shares
            : $message->shares()->with(['recipient', 'revokedBy'])->latest()->get();

        $serializedShares = $shares
            ->map(fn(MessageShare $share) => $this->serializeShare($share->loadMissing(['recipient', 'revokedBy', 'conversation', 'message']), $viewer))
            ->values();

        $activeShares = $serializedShares
            ->filter(fn(array $share) => !$share['is_revoked'])
            ->values();

        return [
            'message_id' => $message->id,
            'active_count' => $activeShares->count(),
            'active_recipients' => $activeShares->pluck('recipient_name')->unique()->values()->all(),
            'history_count' => $serializedShares->count(),
            'shares' => $serializedShares->all(),
        ];
    }

    private function buildGlobalShareFeed(User $viewer): array
    {
        return MessageShare::query()
            ->where('owner_user_id', $viewer->id)
            ->with(['recipient', 'revokedBy', 'conversation', 'message'])
            ->latest()
            ->limit(200)
            ->get()
            ->map(fn(MessageShare $share) => $this->serializeShare($share, $viewer))
            ->values()
            ->all();
    }

    private function serializeShare(MessageShare $share, User $viewer): array
    {
        $recipient = $share->recipient;
        $revokedBy = $share->revokedBy;
        $conversation = $share->conversation;
        $message = $share->message;

        return [
            'id' => $share->id,
            'conversation_id' => $share->conversation_id,
            'conversation_title' => $conversation?->title ?: 'Nova conversa',
            'conversation_origin' => $conversation?->origin_module,
            'message_id' => $share->message_id,
            'message_preview' => $message ? Str::limit(trim((string) $message->content), 240) : null,
            'message_created_at' => optional($message?->created_at)?->format('d/m/Y H:i'),
            'excerpt' => $share->excerpt,
            'context_excerpt' => $share->context_excerpt,
            'note' => $share->note,
            'message_role' => $share->message_role,
            'recipient_name' => $recipient?->name ?? 'Destinatario removido',
            'recipient_role' => $this->formatRecipientRole($recipient),
            'share_url' => route('chat.shared.show', $share->share_token),
            'created_at_iso' => optional($share->created_at)?->toIso8601String(),
            'created_at' => optional($share->created_at)?->format('d/m/Y H:i'),
            'viewed_at_iso' => optional($share->viewed_at)?->toIso8601String(),
            'viewed_at' => optional($share->viewed_at)?->format('d/m/Y H:i'),
            'is_revoked' => !is_null($share->revoked_at),
            'revoked_at_iso' => optional($share->revoked_at)?->toIso8601String(),
            'revoked_at' => optional($share->revoked_at)?->format('d/m/Y H:i'),
            'revoked_by_name' => $revokedBy?->name,
            'can_revoke' => $share->owner_user_id === $viewer->id && is_null($share->revoked_at),
        ];
    }

    private function formatRecipientRole(?User $recipient): string
    {
        if (!$recipient) {
            return 'Usuario';
        }

        return $recipient->role === UserRole::Admin
            ? 'Administrador'
            : 'Usuario do município';
    }

    private function excerptBelongsToMessage(string $excerpt, string $messageContent): bool
    {
        if ($excerpt === '' || $messageContent === '') {
            return false;
        }

        $normalizedExcerpt = $this->normalizeShareText($excerpt);
        $normalizedMessage = $this->normalizeShareText($messageContent);

        return $normalizedExcerpt !== '' && str_contains($normalizedMessage, $normalizedExcerpt);
    }

    private function normalizeShareText(string $text): string
    {
        $normalized = preg_replace('/\s+/u', ' ', trim($text));

        return mb_strtolower($normalized ?? '');
    }
}
