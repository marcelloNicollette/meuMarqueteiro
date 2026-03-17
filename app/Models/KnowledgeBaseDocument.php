<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class KnowledgeBaseDocument extends Model
{
    protected $fillable = [
        'title',
        'description',
        'category',
        'tags',
        'reference_year',
        'valid_from',
        'valid_until',
        'disk',
        'path',
        'mime_type',
        'size_bytes',
        'original_filename',
        'content_raw',
        'indexing_status',
        'indexed_at',
        'chunks_count',
        'indexing_error',
        'published_by',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'tags'        => 'array',
            'valid_from'  => 'date',
            'valid_until' => 'date',
            'indexed_at'  => 'datetime',
            'is_active'   => 'boolean',
        ];
    }

    public function publisher(): BelongsTo
    {
        return $this->belongsTo(User::class, 'published_by');
    }

    public function getCategoryLabelAttribute(): string
    {
        return match ($this->category) {
            'legislation'       => 'Legislação',
            'federal_programs'  => 'Programas Federais',
            'benchmark'         => 'Benchmarks',
            'best_practice'     => 'Boas Práticas',
            'communication'     => 'Comunicação Política',
            'policy'            => 'Políticas Setoriais',
            default             => 'Outros',
        };
    }

    public function getStatusLabelAttribute(): string
    {
        return match ($this->indexing_status) {
            'done'       => 'Indexado',
            'processing' => 'Processando',
            'failed'     => 'Erro',
            default      => 'Pendente',
        };
    }

    public function getSizeFormattedAttribute(): string
    {
        if (!$this->size_bytes) return '—';
        $kb = $this->size_bytes / 1024;
        if ($kb < 1024) return round($kb, 1) . ' KB';
        return round($kb / 1024, 1) . ' MB';
    }
}
