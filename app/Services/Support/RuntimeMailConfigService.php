<?php

namespace App\Services\Support;

use App\Models\SystemSetting;
use Illuminate\Mail\Mailable;
use Illuminate\Support\Facades\Mail;

class RuntimeMailConfigService
{
    private const RUNTIME_MAILER = 'system_runtime_smtp';

    public function settings(): array
    {
        return [
            'enabled' => (bool) SystemSetting::get('mail_runtime_enabled', false),
            'host' => (string) SystemSetting::get('mail_runtime_host', env('MAIL_HOST', '')),
            'port' => (int) SystemSetting::get('mail_runtime_port', (int) env('MAIL_PORT', 2525)),
            'username' => (string) SystemSetting::get('mail_runtime_username', env('MAIL_USERNAME', '')),
            'password' => (string) SystemSetting::get('mail_runtime_password', env('MAIL_PASSWORD', '')),
            'encryption' => (string) SystemSetting::get('mail_runtime_encryption', env('MAIL_SCHEME', 'tls')),
            'from_address' => (string) SystemSetting::get('mail_runtime_from_address', env('MAIL_FROM_ADDRESS', 'hello@example.com')),
            'from_name' => (string) SystemSetting::get('mail_runtime_from_name', env('MAIL_FROM_NAME', 'Meu Assistente')),
            'ehlo_domain' => (string) SystemSetting::get(
                'mail_runtime_ehlo_domain',
                env('MAIL_EHLO_DOMAIN', parse_url((string) env('APP_URL', 'http://localhost'), PHP_URL_HOST) ?: 'localhost')
            ),
            'timeout' => (int) SystemSetting::get('mail_runtime_timeout', 15),
            'test_recipient' => (string) SystemSetting::get('mail_runtime_test_recipient', ''),
        ];
    }

    public function activeMailerName(): string
    {
        return $this->shouldUseRuntimeSmtp()
            ? self::RUNTIME_MAILER
            : (string) config('mail.default', 'log');
    }

    public function shouldUseRuntimeSmtp(): bool
    {
        $settings = $this->settings();

        return $settings['enabled']
            && $settings['host'] !== ''
            && $settings['port'] > 0
            && $settings['from_address'] !== '';
    }

    public function send(array $recipients, Mailable $mailable): void
    {
        Mail::mailer($this->bootRuntimeMailer())
            ->to($recipients)
            ->send($mailable);
    }

    public function sendRaw(array $recipients, string $subject, string $message): void
    {
        Mail::mailer($this->bootRuntimeMailer())
            ->raw($message, function ($mail) use ($recipients, $subject) {
                $mail->to($recipients)->subject($subject);
            });
    }

    private function bootRuntimeMailer(): string
    {
        if (!$this->shouldUseRuntimeSmtp()) {
            return (string) config('mail.default', 'log');
        }

        $settings = $this->settings();

        config([
            'mail.mailers.' . self::RUNTIME_MAILER => [
                'transport' => 'smtp',
                'host' => $settings['host'],
                'port' => $settings['port'],
                'username' => $settings['username'] !== '' ? $settings['username'] : null,
                'password' => $settings['password'] !== '' ? $settings['password'] : null,
                'scheme' => $settings['encryption'] !== '' && $settings['encryption'] !== 'none' ? $settings['encryption'] : null,
                'timeout' => $settings['timeout'] > 0 ? $settings['timeout'] : null,
                'local_domain' => $settings['ehlo_domain'] !== '' ? $settings['ehlo_domain'] : null,
            ],
            'mail.from.address' => $settings['from_address'],
            'mail.from.name' => $settings['from_name'],
        ]);

        app('mail.manager')->forgetMailers();

        return self::RUNTIME_MAILER;
    }
}
