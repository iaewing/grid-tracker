<?php

use App\Support\PowerGrid\HfedClient;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;

test('hfed client fetches rows with faked http and prevents stray requests', function () {
    Cache::flush();
    Http::preventStrayRequests();
    Http::fake([
        'energy-information.canada.ca/*' => Http::response([
            'data' => [
                ['region' => 'ON', 'variable' => 'DEMAND', 'value' => 12000],
                'malformed',
                ['region' => 'ON', 'variable' => 'GEN_HYDRO', 'value' => 5000],
            ],
        ]),
    ]);

    $rows = app(HfedClient::class)->observations('ON', '2026-05-26', '2026-05-27');

    expect($rows)->toHaveCount(2)
        ->and($rows[0]['variable'])->toBe('DEMAND');

    Http::assertSentCount(1);
});

test('hfed client caches duplicate requests', function () {
    Cache::flush();
    Http::preventStrayRequests();
    Http::fake([
        'energy-information.canada.ca/*' => Http::response([['region' => 'AB', 'variable' => 'DEMAND', 'value' => 100]]),
    ]);

    $client = app(HfedClient::class);

    expect($client->observations('AB', '2026-05-26', '2026-05-27'))->toHaveCount(1);
    expect($client->observations('AB', '2026-05-26', '2026-05-27'))->toHaveCount(1);

    Http::assertSentCount(1);
});
