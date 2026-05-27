<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ApiSyncLog extends Model
{
    public const RADAR_EXECUTION_SOURCE = 'radar_recursos';
    public const LEGACY_RADAR_EXECUTION_SOURCE = 'portal_transparencia';
    public const RADAR_EXECUTION_DATA_TYPE = 'federal_programs_radar';
    public const RADAR_SOURCE_RUN_DATA_TYPE = 'federal_programs_radar_source';

    protected $table = 'api_sync_logs';

    protected $fillable = [
        'municipality_id',
        'source',
        'data_type',
        'status',
        'records_fetched',
        'records_saved',
        'error_message',
        'error_details',
        'duration_ms',
        'started_at',
        'finished_at',
    ];

    protected function casts(): array
    {
        return [
            'records_fetched' => 'integer',
            'records_saved' => 'integer',
            'error_details' => 'array',
            'duration_ms' => 'integer',
            'started_at' => 'datetime',
            'finished_at' => 'datetime',
        ];
    }

    public function municipality(): BelongsTo
    {
        return $this->belongsTo(Municipality::class);
    }

    public function scopeRadarFederalPrograms($query)
    {
        return $query
            ->where('data_type', self::RADAR_EXECUTION_DATA_TYPE)
            ->whereIn('source', [
                self::RADAR_EXECUTION_SOURCE,
                self::LEGACY_RADAR_EXECUTION_SOURCE,
            ]);
    }

    public function scopeRadarFederalProgramSourceRuns($query)
    {
        return $query->where('data_type', self::RADAR_SOURCE_RUN_DATA_TYPE);
    }
}
