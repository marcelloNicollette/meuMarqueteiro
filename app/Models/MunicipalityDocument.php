<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MunicipalityDocument extends Model
{
    protected $fillable = [
        'municipality_id', 'name', 'type',
        'disk', 'path', 'mime_type', 'size_bytes', 'original_filename',
        'indexing_status', 'indexed_at', 'chunks_count',
        'indexing_error', 'uploaded_by',
    ];

    protected function casts(): array
    {
        return [
            'indexed_at' => 'datetime',
            'size_bytes' => 'integer',
        ];
    }

    public function municipality(): BelongsTo
    {
        return $this->belongsTo(Municipality::class);
    }

    public function uploader(): BelongsTo
    {
        return $this->belongsTo(User::class, 'uploaded_by');
    }
}
