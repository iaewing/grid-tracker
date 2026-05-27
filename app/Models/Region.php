<?php

namespace App\Models;

use Database\Factories\RegionFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Region extends Model
{
    /** @use HasFactory<RegionFactory> */
    use HasFactory;

    protected $fillable = [
        'code',
        'name',
        'display_order',
        'tile_row',
        'tile_column',
        'timezone',
        'source_status',
        'coverage_notes',
    ];

    protected function casts(): array
    {
        return [
            'display_order' => 'integer',
            'tile_row' => 'integer',
            'tile_column' => 'integer',
        ];
    }

    public function observations(): HasMany
    {
        return $this->hasMany(EnergyObservation::class);
    }
}
