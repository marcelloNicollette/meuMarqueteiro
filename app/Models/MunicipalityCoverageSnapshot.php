<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class MunicipalityCoverageSnapshot extends Model
{
    protected $fillable = [
        'period',
        'captured_at',
        'summary',
        'comparison',
        'ranking',
    ];

    protected function casts(): array
    {
        return [
            'captured_at' => 'datetime',
            'summary' => 'array',
            'comparison' => 'array',
            'ranking' => 'array',
        ];
    }
}
