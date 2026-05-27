<?php

use App\Models\EnergyObservation;
use App\Models\Region;
use App\Models\SourceVariable;
use App\Support\PowerGrid\PowerGridDataBootstrapper;
use Database\Seeders\PowerGridSeeder;
use Inertia\Testing\AssertableInertia as Assert;

test('home page renders all provinces and territories', function () {
    $this->seed(PowerGridSeeder::class);

    $this->get(route('home'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('welcome')
            ->has('regions', 13)
            ->has('initialRegion.region', fn (Assert $page) => $page
                ->where('code', 'ON')
                ->where('name', 'Ontario')
                ->etc()
            )
            ->has('initialRegion.source_mix')
            ->has('ranges', 4)
            ->has('sources', 2)
        );
});

test('region endpoint validates range and returns normalized data', function () {
    $this->seed(PowerGridSeeder::class);

    $this->getJson(route('regions.show', ['region' => 'AB', 'range' => 'week']))
        ->assertOk()
        ->assertJsonPath('region.code', 'AB')
        ->assertJsonPath('range', 'week')
        ->assertJsonStructure([
            'summary' => ['demand_mw', 'generation_mw', 'clean_share'],
            'source_mix' => [['code', 'label', 'category', 'fuel_type', 'is_clean', 'value', 'unit']],
            'trend' => [['label', 'demand', 'generation']],
        ]);

    $this->getJson(route('regions.show', ['region' => 'AB', 'range' => 'decade']))
        ->assertUnprocessable()
        ->assertJsonPath('errors.range.0', 'The selected range is invalid.');
});

test('missing source data returns unavailable state', function () {
    $this->seed(PowerGridSeeder::class);

    EnergyObservation::query()
        ->whereBelongsTo(Region::query()->where('code', 'ON')->firstOrFail())
        ->delete();

    app()->instance(PowerGridDataBootstrapper::class, new class extends PowerGridDataBootstrapper
    {
        public function ensureSeeded(): void {}
    });

    $this->getJson(route('regions.show', ['region' => 'ON']))
        ->assertOk()
        ->assertJsonPath('status', 'unavailable')
        ->assertJsonPath('summary.demand_mw', null)
        ->assertJsonCount(0, 'source_mix');
});

test('empty deployed data is bootstrapped on first request', function () {
    expect(Region::query()->count())->toBe(0)
        ->and(SourceVariable::query()->count())->toBe(0)
        ->and(EnergyObservation::query()->count())->toBe(0);

    $this->getJson(route('regions.show', ['region' => 'ON']))
        ->assertOk()
        ->assertJsonPath('status', 'current')
        ->assertJsonPath('region.code', 'ON');

    expect(Region::query()->count())->toBe(13)
        ->and(SourceVariable::query()->count())->toBe(17)
        ->and(EnergyObservation::query()->count())->toBe(720);
});

test('stale deployed data is refreshed on visit', function () {
    config(['services.power_grid.refresh_after_minutes' => 60]);

    $this->seed(PowerGridSeeder::class);

    EnergyObservation::query()->get()->each(function (EnergyObservation $observation): void {
        $observation->update([
            'observed_at' => $observation->observed_at->subHours(3),
            'freshness_status' => 'stale',
        ]);
    });

    $this->getJson(route('regions.show', ['region' => 'ON']))
        ->assertOk()
        ->assertJsonPath('status', 'current');

    expect(EnergyObservation::query()->count())->toBe(720)
        ->and(now()->parse(EnergyObservation::query()->max('observed_at'))->greaterThan(now()->subHour()))->toBeTrue();
});

test('power grid seed data is idempotent for deploy commands', function () {
    $this->seed(PowerGridSeeder::class);
    $this->seed(PowerGridSeeder::class);

    expect(Region::query()->count())->toBe(13)
        ->and(EnergyObservation::query()->count())->toBe(720);
});
