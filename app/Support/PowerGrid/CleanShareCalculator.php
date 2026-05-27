<?php

namespace App\Support\PowerGrid;

class CleanShareCalculator
{
    /**
     * @param  iterable<array{value: float|int|string, category?: string|null, fuel_type?: string|null, is_clean?: bool|null}>  $sourceMix
     * @return array{clean_mw: float, known_generation_mw: float, unknown_mw: float, percentage: float|null}
     */
    public function calculate(iterable $sourceMix): array
    {
        $cleanMw = 0.0;
        $knownGenerationMw = 0.0;
        $unknownMw = 0.0;

        foreach ($sourceMix as $source) {
            $value = max(0.0, (float) $source['value']);
            $fuelType = $source['fuel_type'] ?? null;

            if ($fuelType === null || $fuelType === 'unknown') {
                $unknownMw += $value;

                continue;
            }

            $knownGenerationMw += $value;

            if (($source['is_clean'] ?? false) === true) {
                $cleanMw += $value;
            }
        }

        return [
            'clean_mw' => round($cleanMw, 3),
            'known_generation_mw' => round($knownGenerationMw, 3),
            'unknown_mw' => round($unknownMw, 3),
            'percentage' => $knownGenerationMw > 0 ? round(($cleanMw / $knownGenerationMw) * 100, 1) : null,
        ];
    }
}
