<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class FederalProgramAlert extends Model
{
    protected $table = 'federal_program_alerts';

    protected $fillable = [
        'municipality_id',
        'program_name',
        'ministry',
        'program_code',
        'description',
        'area',
        'max_value',
        'min_value',
        'funding_type',
        'eligibility_criteria',
        'open_date',
        'deadline',
        'status',
        'applied_at',
        'ai_matched',
        'match_score',
        'match_reason',
        'source_url',
        'source_platform',
    ];

    protected function casts(): array
    {
        return [
            'eligibility_criteria' => 'array',
            'open_date'            => 'date',
            'deadline'             => 'date',
            'applied_at'           => 'datetime',
            'max_value'            => 'decimal:2',
            'min_value'            => 'decimal:2',
            'match_score'          => 'decimal:2',
            'ai_matched'           => 'boolean',
        ];
    }

    public function municipality(): BelongsTo
    {
        return $this->belongsTo(Municipality::class);
    }
}
