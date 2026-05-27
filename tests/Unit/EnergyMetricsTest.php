<?php

use App\Support\PowerGrid\CleanShareCalculator;
use App\Support\PowerGrid\HfedVariableNormalizer;

test('clean share handles unknown values and excludes imports', function () {
    $result = app(CleanShareCalculator::class)->calculate([
        ['value' => 60, 'fuel_type' => 'hydro', 'is_clean' => true],
        ['value' => 20, 'fuel_type' => 'wind', 'is_clean' => true],
        ['value' => 20, 'fuel_type' => 'gas', 'is_clean' => false],
        ['value' => 50, 'fuel_type' => 'unknown', 'is_clean' => false],
        ['value' => -10, 'fuel_type' => 'coal', 'is_clean' => false],
    ]);

    expect($result)
        ->clean_mw->toBe(80.0)
        ->known_generation_mw->toBe(100.0)
        ->unknown_mw->toBe(50.0)
        ->percentage->toBe(80.0);
});

test('clean share returns null percentage for zero known generation', function () {
    expect(app(CleanShareCalculator::class)->calculate([]))
        ->percentage->toBeNull()
        ->known_generation_mw->toBe(0.0);
});

test('hfed variable normalization maps known and inferred codes', function (string $code, ?string $label, string $category, ?string $fuelType, bool $isClean) {
    $normalized = app(HfedVariableNormalizer::class)->normalize($code, $label);

    expect($normalized)
        ->category->toBe($category)
        ->fuel_type->toBe($fuelType)
        ->is_clean->toBe($isClean);
})->with([
    'known hydro' => ['GEN_HYDRO', null, 'generation', 'hydro', true],
    'inferred gas' => ['foo_17', 'Gas generation output', 'generation', 'gas', false],
    'demand' => ['demand_mw', null, 'demand', null, false],
    'unknown' => ['mystery', 'Unclassified value', 'unknown', 'unknown', false],
]);
