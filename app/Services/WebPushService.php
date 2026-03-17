<?php

namespace App\Services;

use App\Models\PushSubscription;
use App\Models\User;
use Illuminate\Support\Facades\Log;
use Minishlink\WebPush\WebPush;
use Minishlink\WebPush\Subscription;

/**
 * Serviço de Web Push Notifications usando minishlink/web-push.
 */
class WebPushService
{
    private WebPush $webPush;

    public function __construct()
    {
        $auth = [
            'VAPID' => [
                'subject'    => config('webpush.vapid_subject', 'mailto:admin@meumarqueteiro.com.br'),
                'publicKey'  => config('webpush.vapid_public_key'),
                'privateKey' => config('webpush.vapid_private_key'),
            ],
        ];

        $this->webPush = new WebPush($auth);
        $this->webPush->setReuseVAPIDHeaders(true);
    }

    /**
     * Enviar push para todos os dispositivos ativos de um usuário.
     */
    public function sendToUser(User $user, array $payload): void
    {
        $subscriptions = PushSubscription::where('user_id', $user->id)
            ->where('is_active', true)
            ->get();

        if ($subscriptions->isEmpty()) {
            Log::info("Push: nenhum dispositivo ativo para user {$user->id}");
            return;
        }

        $payloadJson = json_encode($payload, JSON_UNESCAPED_UNICODE);

        foreach ($subscriptions as $sub) {
            try {
                $subscription = Subscription::create([
                    'endpoint'        => $sub->endpoint,
                    'publicKey'       => $sub->public_key,
                    'authToken'       => $sub->auth_token,
                    'contentEncoding' => 'aesgcm',
                ]);

                $this->webPush->queueNotification($subscription, $payloadJson);
            } catch (\Exception $e) {
                Log::warning("Push: erro ao enfileirar para subscription {$sub->id}: " . $e->getMessage());
            }
        }

        // Enviar todas as notificações enfileiradas
        foreach ($this->webPush->flush() as $report) {
            $endpoint = $report->getRequest()->getUri()->__toString();

            if ($report->isSuccess()) {
                // Atualizar last_used_at
                PushSubscription::where('endpoint', $endpoint)
                    ->update(['last_used_at' => now()]);

                Log::info("Push enviado com sucesso para: " . substr($endpoint, 0, 60));
            } else {
                $reason = $report->getReason();
                Log::warning("Push falhou ({$reason}) para: " . substr($endpoint, 0, 60));

                // Endpoint inválido ou revogado — desativar
                if ($report->isSubscriptionExpired()) {
                    PushSubscription::where('endpoint', $endpoint)
                        ->update(['is_active' => false]);

                    Log::info("Push: subscription desativada (expirada)");
                }
            }
        }
    }

    /**
     * Enviar push para uma subscription específica (para testes).
     */
    public function sendToSubscription(PushSubscription $sub, array $payload): bool
    {
        try {
            $subscription = Subscription::create([
                'endpoint'        => $sub->endpoint,
                'publicKey'       => $sub->public_key,
                'authToken'       => $sub->auth_token,
                'contentEncoding' => 'aesgcm',
            ]);

            $this->webPush->queueNotification($subscription, json_encode($payload, JSON_UNESCAPED_UNICODE));

            foreach ($this->webPush->flush() as $report) {
                if ($report->isSuccess()) {
                    $sub->update(['last_used_at' => now()]);
                    return true;
                } else {
                    if ($report->isSubscriptionExpired()) {
                        $sub->update(['is_active' => false]);
                    }
                    throw new \Exception($report->getReason());
                }
            }
        } catch (\Exception $e) {
            throw $e;
        }

        return false;
    }
}
