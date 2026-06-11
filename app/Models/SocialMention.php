<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SocialMention extends Model
{
    protected $fillable = [
        'municipality_id',
        'source',
        'platform',
        'keyword',
        'title',
        'content',
        'url',
        'author',
        'published_at',
        'sentiment',
        'sentiment_score',
        'sentiment_reason',
        'is_read',
        'alert_sent',
        'external_id',
    ];

    protected function casts(): array
    {
        return [
            'published_at'    => 'datetime',
            'is_read'         => 'boolean',
            'alert_sent'      => 'boolean',
            'sentiment_score' => 'integer',
        ];
    }

    public function municipality(): BelongsTo
    {
        return $this->belongsTo(Municipality::class);
    }

    public function getSentimentLabelAttribute(): string
    {
        return match ($this->sentiment) {
            'positive'  => 'Positivo',
            'negative'  => 'Negativo',
            'neutral'   => 'Neutro',
            'urgent'    => 'Urgente',
            'analyzing' => 'Analisando...',
            default     => 'Pendente',
        };
    }

    public function getSentimentColorAttribute(): array
    {
        return match ($this->sentiment) {
            'positive' => ['bg' => '#dcfce7', 'text' => '#1e7e48', 'dot' => '#1e7e48'],
            'negative' => ['bg' => '#fee2e2', 'text' => '#b52b2b', 'dot' => '#b52b2b'],
            'neutral'  => ['bg' => '#f3f4f6', 'text' => '#666',    'dot' => '#aaa'],
            'urgent'   => ['bg' => '#ffedd5', 'text' => '#c2410c', 'dot' => '#ea580c'],
            default    => ['bg' => '#fef3c7', 'text' => '#b8902a', 'dot' => '#b8902a'],
        };
    }

    public function getPlatformIconAttribute(): string
    {
        return match ($this->platform) {
            'twitter'  => '𝕏',
            'news'     => '📰',
            'blog'     => '✍️',
            'youtube'  => '▶️',
            'whatsapp' => '💬',
            'social'   => '📣',
            'manual'   => '📝',
            'official' => '📜',
            default    => '🌐',
        };
    }

    public function getSourceLabelAttribute(): string
    {
        return match ($this->source) {
            'google_news'    => 'Google News',
            'nitter'         => 'Twitter/X',
            'youtube'        => 'YouTube',
            'diario_oficial' => 'Diário Oficial da União',
            'portal_rss'     => 'Portal local',
            'rss'            => 'RSS',
            'manual_whatsapp' => 'WhatsApp manual',
            'manual_news'    => 'Portal manual',
            'manual_social'  => 'Rede social manual',
            'manual_manual'  => 'Manual',
            default          => $this->source,
        };
    }

    public function getTimeAgoAttribute(): string
    {
        if (!$this->published_at) return '';
        return $this->published_at->diffForHumans();
    }
}