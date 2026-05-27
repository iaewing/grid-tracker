<?php

namespace App\Http\Controllers;

use App\Support\PowerGrid\CanadianRegionCatalog;
use App\Support\PowerGrid\EnergyObservationAggregator;
use App\Support\PowerGrid\PowerGridDataBootstrapper;
use Inertia\Inertia;
use Inertia\Response;

class PowerGridController extends Controller
{
    public function __invoke(CanadianRegionCatalog $regions, EnergyObservationAggregator $aggregator, PowerGridDataBootstrapper $dataBootstrapper): Response
    {
        $dataBootstrapper->ensureSeeded();

        $regionPayloads = collect($regions->all())
            ->map(fn (array $region): array => [
                ...$region,
                'summary' => $aggregator->forRegion($region)['summary'],
                'status' => $aggregator->forRegion($region)['status'],
            ])
            ->values()
            ->all();

        return Inertia::render('welcome', [
            'regions' => $regionPayloads,
            'initialRegion' => $aggregator->forRegion($regions->find('ON')),
            'ranges' => [
                ['value' => 'day', 'label' => 'Day'],
                ['value' => 'week', 'label' => 'Week'],
                ['value' => 'month', 'label' => 'Month'],
                ['value' => 'year', 'label' => 'Year'],
            ],
            'sources' => [
                ['label' => 'HFED/CCEI', 'url' => 'https://energy-information.canada.ca/en/resources/high-frequency-electricity-data', 'role' => 'Primary normalized Canadian electricity feed'],
                ['label' => 'GridStatus', 'url' => 'https://github.com/gridstatus/gridstatus', 'role' => 'Optional future enrichment for AESO/IESO coverage'],
            ],
        ]);
    }
}
