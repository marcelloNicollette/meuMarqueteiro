<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MonitoredUrl extends Model
{
    protected $fillable = [
        'municipality_id', 'created_by', 'url', 'title', 'page_title',
        'category', 'description', 'fetch_status', 'last_fetched_at',
        'last_indexed_at', 'chunks_count', 'fetch_error',
        'refresh_frequency', 'is_active', 'index_subpages',
    ];

    protected function casts(): array
    {
        return [
            'last_fetched_at'  => 'datetime',
            'last_indexed_at'  => 'datetime',
            'is_active'        => 'boolean',
            'index_subpages'   => 'boolean',
        ];
    }

    public function municipality(): BelongsTo
    {
        return $this->belongsTo(Municipality::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function getDisplayTitleAttribute(): string
    {
        return $this->title ?: $this->page_title ?: parse_url($this->url, PHP_URL_HOST) ?: $this->url;
    }

    public function getStatusLabelAttribute(): string
    {
        return match($this->fetch_status) {
            'indexed'  => 'Indexado',
            'fetching' => 'Buscando...',
            'failed'   => 'Erro',
            default    => 'Pendente',
        };
    }

    public function getStatusColorAttribute(): array
    {
        return match($this->fetch_status) {
            'indexed'  => ['bg' => '#dcfce7', 'text' => '#1e7e48'],
            'fetching' => ['bg' => '#dbeafe', 'text' => '#1e3a5f'],
            'failed'   => ['bg' => '#fee2e2', 'text' => '#b52b2b'],
            default    => ['bg' => '#f3f4f6', 'text' => '#666'],
        };
    }

    public function needsRefresh(): bool
    {
        if (!$this->last_indexed_at) return true;

        return match($this->refresh_frequency) {
            'daily'   => $this->last_indexed_at->lt(now()->subDay()),
            'weekly'  => $this->last_indexed_at->lt(now()->subWeek()),
            'monthly' => $this->last_indexed_at->lt(now()->subMonth()),
            default   => false, // manual — só re-indexa quando solicitado
        };
    }

    public function getCategoryLabelAttribute(): string
    {
        return match($this->category) {
            'noticias'      => 'Notícias',
            'transparencia' => 'Transparência',
            'legislacao'    => 'Legislação',
            'governo'       => 'Governo',
            default         => 'Geral',
        };
    }
}
