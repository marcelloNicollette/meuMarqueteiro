<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ProjectThesisTemplate extends Model
{
    use HasFactory;

    protected $fillable = [
        'slug',
        'title',
        'category',
        'estimated_size',
        'default_urgency',
        'execution_complexity',
        'base_justification_template',
        'base_impact_template',
        'base_funding_template',
        'reference_municipalities_template',
        'government_alignment_template',
        'keywords',
        'profile_rules',
        'sort_order',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'keywords' => 'array',
            'profile_rules' => 'array',
            'sort_order' => 'integer',
            'is_active' => 'boolean',
        ];
    }

    public function theses(): HasMany
    {
        return $this->hasMany(ProjectThesis::class);
    }
}
