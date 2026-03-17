<?php

namespace App\Http\Controllers\Mayor;

use App\Http\Controllers\Controller;
use App\Models\PushSubscription;
use App\Services\WebPushService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class PushController extends Controller
{
    public function __construct(private WebPushService $pushService) {}

    /**
     * Salvar ou atualizar subscription do browser.
     */
    public function subscribe(Request $request): JsonResponse
    {
        $request->validate([
            'endpoint'   => ['required', 'url'],
            'public_key' => ['nullable', 'string'],
            'auth_token' => ['nullable', 'string'],
        ]);

        $user = auth()->user();

        PushSubscription::updateOrCreate(
            ['endpoint' => $request->endpoint],
            [
                'user_id'     => $user->id,
                'public_key'  => $request->public_key,
                'auth_token'  => $request->auth_token,
                'device_info' => substr($request->header('User-Agent', ''), 0, 255),
                'is_active'   => true,
                'last_used_at' => now(),
            ]
        );

        return response()->json(['ok' => true]);
    }

    /**
     * Remover subscription (usuário revogou permissão).
     */
    public function unsubscribe(Request $request): JsonResponse
    {
        $request->validate(['endpoint' => ['required', 'string']]);

        PushSubscription::where('endpoint', $request->endpoint)
            ->where('user_id', auth()->id())
            ->update(['is_active' => false]);

        return response()->json(['ok' => true]);
    }

    /**
     * Enviar notificação de teste para o usuário logado.
     */
    public function test(): JsonResponse
    {
        $user = auth()->user();

        $count = PushSubscription::where('user_id', $user->id)
            ->where('is_active', true)
            ->count();

        if ($count === 0) {
            return response()->json([
                'ok'  => false,
                'msg' => 'Nenhum dispositivo inscrito.',
            ], 400);
        }

        try {
            $this->pushService->sendToUser($user, [
                'title' => '✅ Meu Marqueteiro',
                'body'  => 'Notificações ativadas com sucesso! Você receberá alertas importantes aqui.',
                'icon'  => '/images/mascote-robo.jpg',
                'url'   => '/mayor/chat',
                'tag'   => 'test-' . time(),
            ]);

            return response()->json(['ok' => true, 'devices' => $count]);
        } catch (\Exception $e) {
            return response()->json(['ok' => false, 'msg' => $e->getMessage()], 500);
        }
    }

    /**
     * Retornar a VAPID public key para o frontend registrar o SW.
     */
    public function vapidPublicKey(): JsonResponse
    {
        return response()->json([
            'key' => config('webpush.vapid_public_key'),
        ]);
    }
}
