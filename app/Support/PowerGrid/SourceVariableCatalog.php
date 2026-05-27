<?php

namespace App\Support\PowerGrid;

class SourceVariableCatalog
{
    /**
     * @return array<int, array{source: string, source_code: string, label: string, category: string, fuel_type: ?string, is_clean: bool, unit: string, notes: string}>
     */
    public function all(): array
    {
        return [
            ['source' => 'hfed', 'source_code' => 'DEMAND', 'label' => 'System demand', 'category' => 'demand', 'fuel_type' => null, 'is_clean' => false, 'unit' => 'MW', 'notes' => 'Normalized demand or load feed.'],
            ['source' => 'hfed', 'source_code' => 'LOAD', 'label' => 'System load', 'category' => 'load', 'fuel_type' => null, 'is_clean' => false, 'unit' => 'MW', 'notes' => 'Normalized load feed when published separately from demand.'],
            ['source' => 'hfed', 'source_code' => 'GEN_TOTAL', 'label' => 'Total generation', 'category' => 'total_generation', 'fuel_type' => null, 'is_clean' => false, 'unit' => 'MW', 'notes' => 'Known total generation where published.'],
            ['source' => 'hfed', 'source_code' => 'GEN_HYDRO', 'label' => 'Hydro generation', 'category' => 'generation', 'fuel_type' => 'hydro', 'is_clean' => true, 'unit' => 'MW', 'notes' => 'Non-emitting hydro source mix.'],
            ['source' => 'hfed', 'source_code' => 'GEN_WIND', 'label' => 'Wind generation', 'category' => 'generation', 'fuel_type' => 'wind', 'is_clean' => true, 'unit' => 'MW', 'notes' => 'Non-emitting wind source mix.'],
            ['source' => 'hfed', 'source_code' => 'GEN_SOLAR', 'label' => 'Solar generation', 'category' => 'generation', 'fuel_type' => 'solar', 'is_clean' => true, 'unit' => 'MW', 'notes' => 'Non-emitting solar source mix.'],
            ['source' => 'hfed', 'source_code' => 'GEN_NUCLEAR', 'label' => 'Nuclear generation', 'category' => 'generation', 'fuel_type' => 'nuclear', 'is_clean' => true, 'unit' => 'MW', 'notes' => 'Non-emitting nuclear source mix.'],
            ['source' => 'hfed', 'source_code' => 'GEN_BIOMASS', 'label' => 'Biomass generation', 'category' => 'generation', 'fuel_type' => 'biomass', 'is_clean' => true, 'unit' => 'MW', 'notes' => 'Renewable category where explicitly published.'],
            ['source' => 'hfed', 'source_code' => 'GEN_GAS', 'label' => 'Gas generation', 'category' => 'generation', 'fuel_type' => 'gas', 'is_clean' => false, 'unit' => 'MW', 'notes' => 'Fossil thermal source mix.'],
            ['source' => 'hfed', 'source_code' => 'GEN_COAL', 'label' => 'Coal generation', 'category' => 'generation', 'fuel_type' => 'coal', 'is_clean' => false, 'unit' => 'MW', 'notes' => 'Fossil thermal source mix.'],
            ['source' => 'hfed', 'source_code' => 'GEN_OIL_DIESEL', 'label' => 'Oil and diesel generation', 'category' => 'generation', 'fuel_type' => 'oil_diesel', 'is_clean' => false, 'unit' => 'MW', 'notes' => 'Fossil thermal source mix.'],
            ['source' => 'hfed', 'source_code' => 'GEN_OTHER', 'label' => 'Other generation', 'category' => 'generation', 'fuel_type' => 'other', 'is_clean' => false, 'unit' => 'MW', 'notes' => 'Generation not clean-classified in v1.'],
            ['source' => 'hfed', 'source_code' => 'IMPORTS', 'label' => 'Imports', 'category' => 'imports', 'fuel_type' => null, 'is_clean' => false, 'unit' => 'MW', 'notes' => 'Interchange value; excluded from clean share.'],
            ['source' => 'hfed', 'source_code' => 'EXPORTS', 'label' => 'Exports', 'category' => 'exports', 'fuel_type' => null, 'is_clean' => false, 'unit' => 'MW', 'notes' => 'Interchange value; excluded from clean share.'],
            ['source' => 'hfed', 'source_code' => 'PRICE', 'label' => 'Market price', 'category' => 'price', 'fuel_type' => null, 'is_clean' => false, 'unit' => '$/MWh', 'notes' => 'Market price where a public feed provides one.'],
            ['source' => 'hfed', 'source_code' => 'RESERVES', 'label' => 'Operating reserves', 'category' => 'reserves', 'fuel_type' => null, 'is_clean' => false, 'unit' => 'MW', 'notes' => 'Reserve or ancillary capacity where available.'],
            ['source' => 'hfed', 'source_code' => 'CAPABILITY', 'label' => 'Capability', 'category' => 'capability', 'fuel_type' => null, 'is_clean' => false, 'unit' => 'MW', 'notes' => 'Available capability where published.'],
        ];
    }

    /**
     * @return array{source: string, source_code: string, label: string, category: string, fuel_type: ?string, is_clean: bool, unit: string, notes: string}|null
     */
    public function find(string $sourceCode): ?array
    {
        $normalizedSourceCode = mb_strtoupper($sourceCode);

        foreach ($this->all() as $variable) {
            if ($variable['source_code'] === $normalizedSourceCode) {
                return $variable;
            }
        }

        return null;
    }
}
