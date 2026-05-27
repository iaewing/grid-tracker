<?php

namespace App\Support\PowerGrid;

class CanadianRegionCatalog
{
    /**
     * @return array<int, array{code: string, name: string, display_order: int, tile_row: int, tile_column: int, timezone: string, source_status: string, coverage_notes: string}>
     */
    public function all(): array
    {
        return [
            ['code' => 'YT', 'name' => 'Yukon', 'display_order' => 1, 'tile_row' => 1, 'tile_column' => 1, 'timezone' => 'America/Whitehorse', 'source_status' => 'limited', 'coverage_notes' => 'HFED/CCEI coverage can be intermittent for northern territories.'],
            ['code' => 'NT', 'name' => 'Northwest Territories', 'display_order' => 2, 'tile_row' => 1, 'tile_column' => 2, 'timezone' => 'America/Yellowknife', 'source_status' => 'limited', 'coverage_notes' => 'Territorial systems have smaller, source-limited feeds.'],
            ['code' => 'NU', 'name' => 'Nunavut', 'display_order' => 3, 'tile_row' => 1, 'tile_column' => 3, 'timezone' => 'America/Iqaluit', 'source_status' => 'limited', 'coverage_notes' => 'HFED/CCEI coverage is limited and often reports fewer variables.'],
            ['code' => 'BC', 'name' => 'British Columbia', 'display_order' => 4, 'tile_row' => 2, 'tile_column' => 1, 'timezone' => 'America/Vancouver', 'source_status' => 'available', 'coverage_notes' => 'HFED/CCEI consolidates public utility feed variables where available.'],
            ['code' => 'AB', 'name' => 'Alberta', 'display_order' => 5, 'tile_row' => 2, 'tile_column' => 2, 'timezone' => 'America/Edmonton', 'source_status' => 'available', 'coverage_notes' => 'HFED/CCEI primary; AESO/GridStatus can enrich market-specific details later.'],
            ['code' => 'SK', 'name' => 'Saskatchewan', 'display_order' => 6, 'tile_row' => 2, 'tile_column' => 3, 'timezone' => 'America/Regina', 'source_status' => 'available', 'coverage_notes' => 'HFED/CCEI feed variables vary by publication cadence.'],
            ['code' => 'MB', 'name' => 'Manitoba', 'display_order' => 7, 'tile_row' => 2, 'tile_column' => 4, 'timezone' => 'America/Winnipeg', 'source_status' => 'available', 'coverage_notes' => 'HFED/CCEI primary source metadata retained per observation.'],
            ['code' => 'ON', 'name' => 'Ontario', 'display_order' => 8, 'tile_row' => 2, 'tile_column' => 5, 'timezone' => 'America/Toronto', 'source_status' => 'available', 'coverage_notes' => 'HFED/CCEI primary; IESO/GridStatus can enrich market-specific details later.'],
            ['code' => 'QC', 'name' => 'Quebec', 'display_order' => 9, 'tile_row' => 3, 'tile_column' => 5, 'timezone' => 'America/Toronto', 'source_status' => 'available', 'coverage_notes' => 'HFED/CCEI primary source metadata retained per observation.'],
            ['code' => 'NB', 'name' => 'New Brunswick', 'display_order' => 10, 'tile_row' => 3, 'tile_column' => 6, 'timezone' => 'America/Moncton', 'source_status' => 'available', 'coverage_notes' => 'HFED/CCEI primary source metadata retained per observation.'],
            ['code' => 'PE', 'name' => 'Prince Edward Island', 'display_order' => 11, 'tile_row' => 3, 'tile_column' => 7, 'timezone' => 'America/Halifax', 'source_status' => 'limited', 'coverage_notes' => 'Small system and intertie-heavy data may be sparse or source-limited.'],
            ['code' => 'NS', 'name' => 'Nova Scotia', 'display_order' => 12, 'tile_row' => 4, 'tile_column' => 6, 'timezone' => 'America/Halifax', 'source_status' => 'available', 'coverage_notes' => 'HFED/CCEI primary source metadata retained per observation.'],
            ['code' => 'NL', 'name' => 'Newfoundland and Labrador', 'display_order' => 13, 'tile_row' => 4, 'tile_column' => 7, 'timezone' => 'America/St_Johns', 'source_status' => 'available', 'coverage_notes' => 'HFED/CCEI primary source metadata retained per observation.'],
        ];
    }

    /**
     * @return array{code: string, name: string, display_order: int, tile_row: int, tile_column: int, timezone: string, source_status: string, coverage_notes: string}|null
     */
    public function find(string $code): ?array
    {
        $normalizedCode = mb_strtoupper($code);

        foreach ($this->all() as $region) {
            if ($region['code'] === $normalizedCode) {
                return $region;
            }
        }

        return null;
    }

    /**
     * @return list<string>
     */
    public function codes(): array
    {
        return array_column($this->all(), 'code');
    }
}
