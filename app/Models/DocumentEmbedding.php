<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Armazena chunks de documentos com embeddings vetoriais (pgvector).
 * Usado como base do sistema RAG por município.
 */
class DocumentEmbedding extends Model
{
    use HasFactory;

    protected $fillable = [
        'municipality_id',
        'document_id',       // FK para MunicipalityDocument (nullable para knowledge base geral)
        'layer',             // public_data | knowledge_base | client_data
        'category',          // fiscal | education | health | political | communication | federal_programs
        'source',            // nome da fonte (SICONFI, IBGE, programa de governo, etc.)
        'chunk_index',       // índice do chunk dentro do documento
        'content',           // texto do chunk
        'embedding',         // vetor (pgvector — vector(1536))
        'metadata',          // JSON — ano, período, tags adicionais
        'token_count',
        'expires_at',        // para dados que ficam obsoletos
    ];

    protected function casts(): array
    {
        return [
            'chunk_index' => 'integer',
            'token_count' => 'integer',
            'metadata'    => 'array',
            'expires_at'  => 'datetime',
        ];
    }

    public function municipality(): BelongsTo
    {
        return $this->belongsTo(Municipality::class);
    }

    public function document(): BelongsTo
    {
        return $this->belongsTo(MunicipalityDocument::class, 'document_id');
    }
}
