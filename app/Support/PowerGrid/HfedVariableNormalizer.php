<?php

namespace App\Support\PowerGrid;

class HfedVariableNormalizer
{
    public function __construct(private readonly SourceVariableCatalog $catalog = new SourceVariableCatalog) {}

    /**
     * @return array{source: string, source_code: string, label: string, category: string, fuel_type: ?string, is_clean: bool, unit: string, notes: string}
     */
    public function normalize(string $sourceCode, ?string $label = null): array
    {
        $normalizedCode = mb_strtoupper(trim($sourceCode));
        $direct = $this->catalog->find($normalizedCode);

        if ($direct !== null) {
            return $direct;
        }

        $combined = mb_strtolower($normalizedCode.' '.$label);

        return match (true) {
            str_contains($combined, 'demand') => $this->withCode('DEMAND', $normalizedCode, $label),
            str_contains($combined, 'load') => $this->withCode('LOAD', $normalizedCode, $label),
            str_contains($combined, 'total') && str_contains($combined, 'gen') => $this->withCode('GEN_TOTAL', $normalizedCode, $label),
            str_contains($combined, 'hydro') => $this->withCode('GEN_HYDRO', $normalizedCode, $label),
            str_contains($combined, 'wind') => $this->withCode('GEN_WIND', $normalizedCode, $label),
            str_contains($combined, 'solar') => $this->withCode('GEN_SOLAR', $normalizedCode, $label),
            str_contains($combined, 'nuclear') => $this->withCode('GEN_NUCLEAR', $normalizedCode, $label),
            str_contains($combined, 'biomass') => $this->withCode('GEN_BIOMASS', $normalizedCode, $label),
            str_contains($combined, 'gas') => $this->withCode('GEN_GAS', $normalizedCode, $label),
            str_contains($combined, 'coal') => $this->withCode('GEN_COAL', $normalizedCode, $label),
            str_contains($combined, 'diesel') || str_contains($combined, 'oil') => $this->withCode('GEN_OIL_DIESEL', $normalizedCode, $label),
            str_contains($combined, 'import') => $this->withCode('IMPORTS', $normalizedCode, $label),
            str_contains($combined, 'export') => $this->withCode('EXPORTS', $normalizedCode, $label),
            str_contains($combined, 'price') => $this->withCode('PRICE', $normalizedCode, $label),
            str_contains($combined, 'reserve') => $this->withCode('RESERVES', $normalizedCode, $label),
            str_contains($combined, 'capability') || str_contains($combined, 'capacity') => $this->withCode('CAPABILITY', $normalizedCode, $label),
            default => ['source' => 'hfed', 'source_code' => $normalizedCode, 'label' => $label ?: $normalizedCode, 'category' => 'unknown', 'fuel_type' => 'unknown', 'is_clean' => false, 'unit' => 'MW', 'notes' => 'Unmapped HFED/CCEI variable retained for inspection.'],
        };
    }

    /**
     * @return array{source: string, source_code: string, label: string, category: string, fuel_type: ?string, is_clean: bool, unit: string, notes: string}
     */
    private function withCode(string $catalogCode, string $sourceCode, ?string $label): array
    {
        $variable = $this->catalog->find($catalogCode);
        $variable['source_code'] = $sourceCode;

        if ($label !== null && $label !== '') {
            $variable['label'] = $label;
        }

        return $variable;
    }
}
