<?php

namespace App\Support\PowerGrid;

use Illuminate\Http\Client\RequestException;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;

class HfedClient
{
    public function __construct(
        private readonly string $baseUrl = 'https://energy-information.canada.ca/api/high-frequency-electricity-data',
    ) {}

    /**
     * @return array<int, array<string, mixed>>
     *
     * @throws RequestException
     */
    public function observations(string $regionCode, string $start, string $end): array
    {
        $cacheKey = sprintf('hfed:%s:%s:%s', mb_strtoupper($regionCode), $start, $end);

        return Cache::remember($cacheKey, now()->addMinutes(10), function () use ($regionCode, $start, $end): array {
            $response = Http::acceptJson()
                ->timeout(10)
                ->connectTimeout(5)
                ->retry([250, 750, 1500])
                ->get($this->baseUrl, [
                    'region' => mb_strtoupper($regionCode),
                    'start' => $start,
                    'end' => $end,
                ])
                ->throw();

            $payload = $response->json();

            if (! is_array($payload)) {
                return [];
            }

            $rows = $payload['data'] ?? $payload['rows'] ?? $payload;

            return is_array($rows) ? array_values(array_filter($rows, 'is_array')) : [];
        });
    }
}
