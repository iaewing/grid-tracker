<?php

namespace App\Support\PowerGrid;

use App\Models\EnergyObservation;
use App\Models\Region;
use Carbon\CarbonInterface;
use Illuminate\Support\Collection;

class EnergyObservationAggregator
{
    public function __construct(private readonly CleanShareCalculator $cleanShareCalculator = new CleanShareCalculator) {}

    /**
     * @param  array{code: string, name: string, display_order: int, tile_row: int, tile_column: int, timezone: string, source_status: string, coverage_notes: string}  $regionMetadata
     * @return array<string, mixed>
     */
    public function forRegion(array $regionMetadata, string $range = 'day'): array
    {
        $region = Region::query()->where('code', $regionMetadata['code'])->first();

        if (! $region instanceof Region) {
            return $this->unavailablePayload($regionMetadata, $range);
        }

        $observations = $this->observations($region, $range);

        if ($observations->isEmpty()) {
            return $this->unavailablePayload($regionMetadata, $range);
        }

        $latestObservedAt = $observations->max('observed_at');
        $latest = $observations
            ->where('observed_at', $latestObservedAt)
            ->sortBy('sourceVariable.display_order');

        $sourceMix = $latest
            ->filter(fn (EnergyObservation $observation): bool => $observation->sourceVariable?->category === 'generation')
            ->map(fn (EnergyObservation $observation): array => $this->variablePayload($observation))
            ->values()
            ->all();

        $trend = $observations
            ->filter(fn (EnergyObservation $observation): bool => in_array($observation->sourceVariable?->category, ['demand', 'load', 'total_generation'], true))
            ->groupBy(fn (EnergyObservation $observation): string => $this->bucket($observation->observed_at, $range))
            ->map(fn (Collection $bucket, string $label): array => [
                'label' => $label,
                'demand' => $this->sumCategory($bucket, ['demand', 'load']),
                'generation' => $this->sumCategory($bucket, ['total_generation']),
            ])
            ->values()
            ->all();

        return [
            'region' => $regionMetadata,
            'range' => $range,
            'status' => $this->freshnessStatus($latestObservedAt),
            'latest_observed_at' => $latestObservedAt?->toIso8601String(),
            'summary' => [
                'demand_mw' => $this->sumCategory($latest, ['demand', 'load']),
                'generation_mw' => $this->sumCategory($latest, ['total_generation']),
                'clean_share' => $this->cleanShareCalculator->calculate($sourceMix),
            ],
            'source_mix' => $sourceMix,
            'trend' => $trend,
            'variables' => $latest->map(fn (EnergyObservation $observation): array => $this->variablePayload($observation))->values()->all(),
            'source_note' => $regionMetadata['coverage_notes'],
        ];
    }

    /**
     * @return Collection<int, EnergyObservation>
     */
    private function observations(Region $region, string $range): Collection
    {
        $start = match ($range) {
            'week' => now()->subWeek(),
            'month' => now()->subMonth(),
            'year' => now()->subYear(),
            default => now()->subDay(),
        };

        return EnergyObservation::query()
            ->with('sourceVariable')
            ->whereBelongsTo($region)
            ->where('observed_at', '>=', $start)
            ->orderBy('observed_at')
            ->get();
    }

    private function bucket(CarbonInterface $observedAt, string $range): string
    {
        return match ($range) {
            'month', 'year' => $observedAt->format('M j'),
            default => $observedAt->format('H:00'),
        };
    }

    /**
     * @param  Collection<int, EnergyObservation>  $observations
     * @param  list<string>  $categories
     */
    private function sumCategory(Collection $observations, array $categories): float
    {
        return round((float) $observations
            ->filter(fn (EnergyObservation $observation): bool => in_array($observation->sourceVariable?->category, $categories, true))
            ->sum(fn (EnergyObservation $observation): float => (float) $observation->value), 1);
    }

    /**
     * @return array<string, mixed>
     */
    private function variablePayload(EnergyObservation $observation): array
    {
        return [
            'code' => $observation->source_code,
            'label' => $observation->sourceVariable?->label ?? $observation->source_code,
            'category' => $observation->sourceVariable?->category ?? 'unknown',
            'fuel_type' => $observation->sourceVariable?->fuel_type,
            'is_clean' => (bool) $observation->sourceVariable?->is_clean,
            'value' => round((float) $observation->value, 1),
            'unit' => $observation->unit,
            'freshness_status' => $observation->freshness_status,
            'source' => $observation->source,
        ];
    }

    /**
     * @param  array{code: string, name: string, display_order: int, tile_row: int, tile_column: int, timezone: string, source_status: string, coverage_notes: string}  $regionMetadata
     * @return array<string, mixed>
     */
    private function unavailablePayload(array $regionMetadata, string $range): array
    {
        return [
            'region' => $regionMetadata,
            'range' => $range,
            'status' => 'unavailable',
            'latest_observed_at' => null,
            'summary' => [
                'demand_mw' => null,
                'generation_mw' => null,
                'clean_share' => ['clean_mw' => 0.0, 'known_generation_mw' => 0.0, 'unknown_mw' => 0.0, 'percentage' => null],
            ],
            'source_mix' => [],
            'trend' => [],
            'variables' => [],
            'source_note' => $regionMetadata['coverage_notes'],
        ];
    }

    private function freshnessStatus(?CarbonInterface $observedAt): string
    {
        if ($observedAt === null) {
            return 'unavailable';
        }

        return $observedAt->lessThan(now()->subHours(6)) ? 'stale' : 'current';
    }
}
