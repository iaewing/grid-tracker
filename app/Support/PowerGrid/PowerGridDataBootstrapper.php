<?php

namespace App\Support\PowerGrid;

use App\Models\EnergyObservation;
use App\Models\Region;
use App\Models\SourceVariable;
use Carbon\CarbonInterface;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

class PowerGridDataBootstrapper
{
    public function ensureSeeded(): void
    {
        $latestObservedAt = $this->latestObservedAt();

        if (! $this->shouldRefresh($latestObservedAt)) {
            return;
        }

        Log::warning('power_grid.observations_refresh_needed', [
            ...$this->counts(),
            'latest_observed_at' => $latestObservedAt?->toIso8601String(),
            'refresh_after_minutes' => $this->refreshAfterMinutes(),
        ]);

        $refreshed = Cache::lock('power-grid-data-refresh', 30)->get(function (): bool {
            $latestObservedAt = $this->latestObservedAt();

            if (! $this->shouldRefresh($latestObservedAt)) {
                Log::info('power_grid.observations_refresh_skipped_after_lock', [
                    ...$this->counts(),
                    'latest_observed_at' => $latestObservedAt?->toIso8601String(),
                ]);

                return true;
            }

            $this->seed();

            return true;
        });

        if ($refreshed !== true) {
            Log::info('power_grid.observations_refresh_skipped_lock_busy', [
                ...$this->counts(),
                'latest_observed_at' => $latestObservedAt?->toIso8601String(),
            ]);
        }
    }

    public function seed(): void
    {
        Log::info('power_grid.seed_starting', $this->counts());

        $regions = collect(app(CanadianRegionCatalog::class)->all());
        $variables = collect(app(SourceVariableCatalog::class)->all());

        $regions->each(fn (array $region): Region => Region::query()->updateOrCreate(['code' => $region['code']], $region));
        $variables->each(fn (array $variable): SourceVariable => SourceVariable::query()->updateOrCreate(['source' => $variable['source'], 'source_code' => $variable['source_code']], $variable));

        $sourceVariables = SourceVariable::query()->get()->keyBy('source_code');
        $regionModels = Region::query()->get()->keyBy('code');
        $baseObservedAt = now()->startOfHour();

        EnergyObservation::query()
            ->where('metadata->seeded', true)
            ->delete();

        foreach ($this->profiles() as $regionCode => $profile) {
            $region = $regionModels->get($regionCode);

            if (! $region instanceof Region) {
                Log::warning('power_grid.seed_region_missing', ['region_code' => $regionCode]);

                continue;
            }

            for ($step = 5; $step >= 0; $step--) {
                $observedAt = $baseObservedAt->copy()->subHours($step * 4);
                $factor = 1 + (($step - 2) * 0.018);
                $mixTotal = $profile['hydro'] + $profile['wind'] + $profile['solar'] + $profile['nuclear'] + $profile['biomass'] + $profile['gas'] + $profile['coal'] + $profile['oil'];

                $this->storeObservation($region, $sourceVariables->get('DEMAND'), $observedAt, $profile['demand'] * $factor);
                $this->storeObservation($region, $sourceVariables->get('GEN_TOTAL'), $observedAt, $mixTotal * $factor);
                $this->storeObservation($region, $sourceVariables->get('GEN_HYDRO'), $observedAt, $profile['hydro'] * $factor);
                $this->storeObservation($region, $sourceVariables->get('GEN_WIND'), $observedAt, $profile['wind'] * $factor);
                $this->storeObservation($region, $sourceVariables->get('GEN_SOLAR'), $observedAt, $profile['solar'] * $factor);
                $this->storeObservation($region, $sourceVariables->get('GEN_NUCLEAR'), $observedAt, $profile['nuclear'] * $factor);
                $this->storeObservation($region, $sourceVariables->get('GEN_BIOMASS'), $observedAt, $profile['biomass'] * $factor);
                $this->storeObservation($region, $sourceVariables->get('GEN_GAS'), $observedAt, $profile['gas'] * $factor);
                $this->storeObservation($region, $sourceVariables->get('GEN_COAL'), $observedAt, $profile['coal'] * $factor);
                $this->storeObservation($region, $sourceVariables->get('GEN_OIL_DIESEL'), $observedAt, $profile['oil'] * $factor);
            }
        }

        Log::info('power_grid.seed_completed', $this->counts());
    }

    private function latestObservedAt(): ?CarbonInterface
    {
        $latestObservedAt = EnergyObservation::query()->max('observed_at');

        return $latestObservedAt === null ? null : now()->parse($latestObservedAt);
    }

    private function shouldRefresh(?CarbonInterface $latestObservedAt): bool
    {
        if ($latestObservedAt === null) {
            return true;
        }

        return $latestObservedAt->lessThan(now()->subMinutes($this->refreshAfterMinutes()));
    }

    private function refreshAfterMinutes(): int
    {
        return max(1, (int) config('services.power_grid.refresh_after_minutes', 60));
    }

    /**
     * @return array<string, int>
     */
    private function counts(): array
    {
        return [
            'regions_count' => Region::query()->count(),
            'source_variables_count' => SourceVariable::query()->count(),
            'energy_observations_count' => EnergyObservation::query()->count(),
        ];
    }

    /**
     * @return array<string, array{demand: int, hydro: int, wind: int, solar: int, nuclear: int, biomass: int, gas: int, coal: int, oil: int}>
     */
    private function profiles(): array
    {
        return [
            'BC' => ['demand' => 7200, 'hydro' => 5900, 'wind' => 320, 'solar' => 60, 'nuclear' => 0, 'biomass' => 220, 'gas' => 650, 'coal' => 0, 'oil' => 20],
            'AB' => ['demand' => 11400, 'hydro' => 420, 'wind' => 2100, 'solar' => 640, 'nuclear' => 0, 'biomass' => 120, 'gas' => 7200, 'coal' => 280, 'oil' => 40],
            'SK' => ['demand' => 3600, 'hydro' => 520, 'wind' => 620, 'solar' => 130, 'nuclear' => 0, 'biomass' => 30, 'gas' => 1250, 'coal' => 980, 'oil' => 45],
            'MB' => ['demand' => 4700, 'hydro' => 5200, 'wind' => 410, 'solar' => 15, 'nuclear' => 0, 'biomass' => 20, 'gas' => 65, 'coal' => 0, 'oil' => 10],
            'ON' => ['demand' => 17300, 'hydro' => 5100, 'wind' => 1650, 'solar' => 520, 'nuclear' => 9400, 'biomass' => 110, 'gas' => 2100, 'coal' => 0, 'oil' => 50],
            'QC' => ['demand' => 22900, 'hydro' => 24600, 'wind' => 2300, 'solar' => 120, 'nuclear' => 0, 'biomass' => 350, 'gas' => 190, 'coal' => 0, 'oil' => 30],
            'NB' => ['demand' => 1600, 'hydro' => 310, 'wind' => 180, 'solar' => 25, 'nuclear' => 680, 'biomass' => 65, 'gas' => 120, 'coal' => 160, 'oil' => 45],
            'PE' => ['demand' => 320, 'hydro' => 0, 'wind' => 220, 'solar' => 20, 'nuclear' => 0, 'biomass' => 0, 'gas' => 25, 'coal' => 0, 'oil' => 30],
            'NS' => ['demand' => 1900, 'hydro' => 110, 'wind' => 420, 'solar' => 55, 'nuclear' => 0, 'biomass' => 80, 'gas' => 620, 'coal' => 420, 'oil' => 110],
            'NL' => ['demand' => 1550, 'hydro' => 1850, 'wind' => 90, 'solar' => 5, 'nuclear' => 0, 'biomass' => 0, 'gas' => 45, 'coal' => 0, 'oil' => 60],
            'YT' => ['demand' => 82, 'hydro' => 52, 'wind' => 2, 'solar' => 1, 'nuclear' => 0, 'biomass' => 0, 'gas' => 0, 'coal' => 0, 'oil' => 25],
            'NT' => ['demand' => 118, 'hydro' => 34, 'wind' => 1, 'solar' => 4, 'nuclear' => 0, 'biomass' => 0, 'gas' => 8, 'coal' => 0, 'oil' => 72],
        ];
    }

    private function storeObservation(Region $region, ?SourceVariable $sourceVariable, mixed $observedAt, float $value): void
    {
        if (! $sourceVariable instanceof SourceVariable) {
            Log::warning('power_grid.seed_source_variable_missing', [
                'region_code' => $region->code,
                'observed_at' => $observedAt->toIso8601String(),
            ]);

            return;
        }

        EnergyObservation::query()->updateOrCreate(
            [
                'region_id' => $region->id,
                'source_variable_id' => $sourceVariable->id,
                'observed_at' => $observedAt,
                'source' => $sourceVariable->source,
            ],
            [
                'value' => round(max(0, $value), 3),
                'unit' => $sourceVariable->unit,
                'source_code' => $sourceVariable->source_code,
                'freshness_status' => $observedAt->lessThan(now()->subHours(6)) ? 'stale' : 'current',
                'received_at' => now(),
                'metadata' => ['seeded' => true, 'source_name' => 'HFED/CCEI normalized demo feed'],
            ],
        );
    }
}
