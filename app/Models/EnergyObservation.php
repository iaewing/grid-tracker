<?php

namespace App\Models;

use Database\Factories\EnergyObservationFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class EnergyObservation extends Model
{
    /** @use HasFactory<EnergyObservationFactory> */
    use HasFactory;

    protected $fillable = [
        'region_id',
        'source_variable_id',
        'observed_at',
        'value',
        'unit',
        'source',
        'source_code',
        'freshness_status',
        'received_at',
        'metadata',
    ];

    protected function casts(): array
    {
        return [
            'observed_at' => 'immutable_datetime',
            'received_at' => 'immutable_datetime',
            'value' => 'decimal:3',
            'metadata' => 'array',
        ];
    }

    public function region(): BelongsTo
    {
        return $this->belongsTo(Region::class);
    }

    public function sourceVariable(): BelongsTo
    {
        return $this->belongsTo(SourceVariable::class);
    }
}
