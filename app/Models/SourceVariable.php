<?php

namespace App\Models;

use Database\Factories\SourceVariableFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class SourceVariable extends Model
{
    /** @use HasFactory<SourceVariableFactory> */
    use HasFactory;

    protected $fillable = [
        'source',
        'source_code',
        'label',
        'category',
        'fuel_type',
        'is_clean',
        'unit',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'is_clean' => 'boolean',
        ];
    }

    public function observations(): HasMany
    {
        return $this->hasMany(EnergyObservation::class);
    }
}
