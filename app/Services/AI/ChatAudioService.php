<?php

namespace App\Services\AI;

use App\Models\SystemSetting;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Http;
use Throwable;
use Illuminate\Support\Str;

class ChatAudioService
{
    public function capabilities(): array
    {
        $enabled = !empty($this->getOpenAiApiKey());

        return [
            'input_enabled' => $enabled,
            'output_enabled' => $enabled,
            'provider' => $enabled ? 'openai' : null,
            'transcription_model' => $enabled ? $this->transcriptionModel() : null,
            'speech_model' => $enabled ? $this->speechModel() : null,
            'voice' => $enabled ? $this->voice() : null,
        ];
    }

    public function healthSnapshot(): array
    {
        $disk = $this->audioDisk();
        $basePath = trim($this->temporaryAudioPrefix(), '/');
        $speechPath = $basePath . '/speech';
        $inputPath = $basePath . '/input';
        $speechFiles = $disk->exists($speechPath) ? $disk->files($speechPath) : [];
        $inputFiles = $disk->exists($inputPath) ? $disk->files($inputPath) : [];
        $cacheBytes = collect([...$speechFiles, ...$inputFiles])
            ->sum(fn(string $path) => $disk->size($path));

        return [
            'provider' => !empty($this->getOpenAiApiKey()) ? 'openai' : null,
            'api_key_configured' => !empty($this->getOpenAiApiKey()),
            'transcription_model' => $this->transcriptionModel(),
            'speech_model' => $this->speechModel(),
            'voice' => $this->voice(),
            'cache_disk' => (string) config('ai.audio.cache.disk', 'local'),
            'cache_prefix' => $basePath,
            'cache_ttl_minutes' => $this->temporaryAudioTtlMinutes(),
            'speech_files' => count($speechFiles),
            'input_files' => count($inputFiles),
            'cache_size_bytes' => $cacheBytes,
        ];
    }

    public function pruneTemporaryAudio(): array
    {
        $disk = $this->audioDisk();
        $basePath = trim($this->temporaryAudioPrefix(), '/');
        $deleted = 0;

        foreach ($disk->allFiles($basePath) as $path) {
            if ($this->isExpired($path)) {
                $disk->delete($path);
                $deleted++;
            }
        }

        Log::info('chat_audio.prune.completed', [
            'deleted_files' => $deleted,
            'disk' => (string) config('ai.audio.cache.disk', 'local'),
            'prefix' => $basePath,
            'ttl_minutes' => $this->temporaryAudioTtlMinutes(),
        ]);

        return [
            'deleted_files' => $deleted,
            'disk' => (string) config('ai.audio.cache.disk', 'local'),
            'prefix' => $basePath,
            'ttl_minutes' => $this->temporaryAudioTtlMinutes(),
        ];
    }

    public function transcribe(UploadedFile $audioFile): array
    {
        $apiKey = $this->requireOpenAiApiKey();
        $this->pruneTemporaryAudio();
        $storedInput = $this->storeTemporaryInputAudio($audioFile);

        try {
            $response = Http::withToken($apiKey)
                ->acceptJson()
                ->timeout(120)
                ->attach(
                    'file',
                    fopen($this->audioDisk()->path($storedInput['path']), 'r'),
                    $storedInput['name'],
                    ['Content-Type' => $storedInput['mime_type']]
                )
                ->post('https://api.openai.com/v1/audio/transcriptions', [
                    'model' => $this->transcriptionModel(),
                    'language' => 'pt',
                    'response_format' => 'json',
                ]);

            if (!$response->successful()) {
                throw new \RuntimeException($this->resolveAudioError(
                    $response->json('error.message'),
                    'Nao foi possivel transcrever o audio agora.'
                ));
            }

            $text = trim((string) $response->json('text', ''));

            if ($text === '') {
                throw new \RuntimeException('O servidor não conseguiu identificar fala no audio enviado.');
            }
        } catch (Throwable $e) {
            Log::warning('chat_audio.transcribe.failed', [
                'provider' => 'openai',
                'model' => $this->transcriptionModel(),
                'mime_type' => $storedInput['mime_type'],
                'error' => $e->getMessage(),
            ]);

            throw $e;
        } finally {
            $this->audioDisk()->delete($storedInput['path']);
        }

        Log::info('chat_audio.transcribe.completed', [
            'provider' => 'openai',
            'model' => $this->transcriptionModel(),
            'mime_type' => $storedInput['mime_type'],
            'chars' => mb_strlen($text),
        ]);

        return [
            'text' => $text,
            'provider' => 'openai',
            'model' => $this->transcriptionModel(),
        ];
    }

    public function synthesize(string $text, float $speed = 1.0): array
    {
        $apiKey = $this->requireOpenAiApiKey();
        $normalizedText = mb_substr(trim($text), 0, 4000);
        $normalizedSpeed = round($this->normalizeSpeed($speed), 2);
        $this->pruneTemporaryAudio();

        $cachedAudio = $this->findCachedSpeechAudio($normalizedText, $normalizedSpeed);
        if ($cachedAudio) {
            Log::info('chat_audio.synthesize.cache_hit', [
                'provider' => 'openai',
                'model' => $this->speechModel(),
                'voice' => $this->voice(),
                'speed' => $normalizedSpeed,
                'cache_path' => $cachedAudio['cache_path'],
            ]);

            return $cachedAudio + [
                'provider' => 'openai',
                'model' => $this->speechModel(),
                'voice' => $this->voice(),
                'speed' => $normalizedSpeed,
                'cached' => true,
            ];
        }

        $response = Http::withToken($apiKey)
            ->accept('audio/mpeg')
            ->timeout(120)
            ->post('https://api.openai.com/v1/audio/speech', [
                'model' => $this->speechModel(),
                'voice' => $this->voice(),
                'input' => $normalizedText,
                'format' => 'mp3',
                'speed' => $normalizedSpeed,
            ]);

        if (!$response->successful()) {
            $message = $this->resolveAudioError(
                $response->json('error.message'),
                'Nao foi possivel gerar o audio da resposta agora.'
            );

            Log::warning('chat_audio.synthesize.failed', [
                'provider' => 'openai',
                'model' => $this->speechModel(),
                'voice' => $this->voice(),
                'speed' => $normalizedSpeed,
                'error' => $message,
            ]);

            throw new \RuntimeException($message);
        }

        $cachedPath = $this->storeCachedSpeechAudio($normalizedText, $normalizedSpeed, $response->body());

        Log::info('chat_audio.synthesize.completed', [
            'provider' => 'openai',
            'model' => $this->speechModel(),
            'voice' => $this->voice(),
            'speed' => $normalizedSpeed,
            'cache_path' => $cachedPath,
            'chars' => mb_strlen($normalizedText),
        ]);

        return [
            'content' => $response->body(),
            'content_type' => $response->header('Content-Type', 'audio/mpeg'),
            'cache_path' => $cachedPath,
            'provider' => 'openai',
            'model' => $this->speechModel(),
            'voice' => $this->voice(),
            'speed' => $normalizedSpeed,
            'cached' => false,
        ];
    }

    private function transcriptionModel(): string
    {
        return (string) SystemSetting::get(
            'openai_audio_transcription_model',
            config('ai.audio.openai.transcription_model', 'gpt-4o-mini-transcribe')
        );
    }

    private function speechModel(): string
    {
        return (string) SystemSetting::get(
            'openai_audio_speech_model',
            config('ai.audio.openai.speech_model', 'gpt-4o-mini-tts')
        );
    }

    private function voice(): string
    {
        return (string) SystemSetting::get(
            'openai_audio_voice',
            config('ai.audio.openai.voice', 'alloy')
        );
    }

    private function normalizeSpeed(float $speed): float
    {
        return min(1.4, max(0.7, $speed));
    }

    private function findCachedSpeechAudio(string $text, float $speed): ?array
    {
        $path = $this->speechCachePath($text, $speed);
        $disk = $this->audioDisk();

        if (!$disk->exists($path)) {
            return null;
        }

        if ($this->isExpired($path)) {
            $disk->delete($path);
            return null;
        }

        return [
            'content' => $disk->get($path),
            'content_type' => 'audio/mpeg',
            'cache_path' => $path,
        ];
    }

    private function storeCachedSpeechAudio(string $text, float $speed, string $content): string
    {
        $path = $this->speechCachePath($text, $speed);
        $this->audioDisk()->put($path, $content);

        return $path;
    }

    private function speechCachePath(string $text, float $speed): string
    {
        $fingerprint = sha1(implode('|', [
            'speech',
            $this->speechModel(),
            $this->voice(),
            number_format($speed, 2, '.', ''),
            $text,
        ]));

        return trim($this->temporaryAudioPrefix(), '/') . '/speech/' . $fingerprint . '.mp3';
    }

    private function storeTemporaryInputAudio(UploadedFile $audioFile): array
    {
        $extension = $audioFile->guessExtension() ?: $audioFile->extension() ?: 'bin';
        $name = 'input-' . Str::uuid() . '.' . $extension;
        $path = trim($this->temporaryAudioPrefix(), '/') . '/input/' . $name;

        $this->audioDisk()->putFileAs(
            trim($this->temporaryAudioPrefix(), '/') . '/input',
            $audioFile,
            $name
        );

        return [
            'path' => $path,
            'name' => $audioFile->getClientOriginalName() ?: $name,
            'mime_type' => $audioFile->getMimeType() ?: 'application/octet-stream',
        ];
    }

    private function isExpired(string $path): bool
    {
        $ttlSeconds = $this->temporaryAudioTtlMinutes() * 60;
        $lastModified = $this->audioDisk()->lastModified($path);

        return $lastModified < now()->subSeconds($ttlSeconds)->getTimestamp();
    }

    private function audioDisk()
    {
        return Storage::disk(config('ai.audio.cache.disk', 'local'));
    }

    private function temporaryAudioPrefix(): string
    {
        return (string) config('ai.audio.cache.prefix', 'chat-audio-temp');
    }

    private function temporaryAudioTtlMinutes(): int
    {
        return max(5, (int) SystemSetting::get(
            'openai_audio_cache_ttl_minutes',
            config('ai.audio.cache.ttl_minutes', 60)
        ));
    }

    private function requireOpenAiApiKey(): string
    {
        $apiKey = $this->getOpenAiApiKey();

        if (empty($apiKey)) {
            throw new \RuntimeException('A chave OpenAI não esta configurada para o fallback de audio do chat.');
        }

        return $apiKey;
    }

    private function getOpenAiApiKey(): string
    {
        return (string) SystemSetting::get(
            'openai_api_key',
            config('ai.providers.openai.api_key', '')
        );
    }

    private function resolveAudioError(?string $providerMessage, string $fallback): string
    {
        $message = trim((string) $providerMessage);

        return $message !== '' ? $message : $fallback;
    }
}
