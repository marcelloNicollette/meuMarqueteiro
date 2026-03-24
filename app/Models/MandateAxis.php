<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class MandateAxis extends Model
{
    protected $fillable = [
        'municipality_id',
        'name',
        'icon',
        'color',
        'description',
        'order',
        'is_active',
    ];

    protected function casts(): array
    {
        return ['is_active' => 'boolean'];
    }

    public function municipality(): BelongsTo
    {
        return $this->belongsTo(Municipality::class);
    }

    public function promises(): HasMany
    {
        return $this->hasMany(MandatePromise::class)->orderBy('order');
    }

    public function actions(): HasMany
    {
        return $this->hasMany(MandateAction::class);
    }

    /**
     * Score do eixo = média dos scores de todas as promessas ativas.
     */
    public function getScoreAttribute(): int
    {
        $promises = $this->promises()->where('is_active', true)->get();
        if ($promises->isEmpty()) return 0;
        return (int) round($promises->avg('score'));
    }

    /**
     * Cor da barra de progresso baseada no score.
     */
    public function getScoreColorAttribute(): string
    {
        $score = $this->score;
        if ($score >= 75) return '#1e7e48'; // verde
        if ($score >= 50) return '#b8902a'; // âmbar
        if ($score >= 25) return '#b8902a'; // âmbar
        return '#b52b2b';                   // vermelho
    }

    public function getPromiseCountsAttribute(): array
    {
        $promises = $this->promises()->where('is_active', true)->get();
        return [
            'plenas'    => $promises->where('score', 100)->count(),
            'parciais'  => $promises->whereBetween('score', [1, 99])->count(),
            'pendentes' => $promises->where('score', 0)->count(),
            'total'     => $promises->count(),
        ];
    }
}
