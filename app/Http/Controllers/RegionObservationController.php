<?php

namespace App\Http\Controllers;

use App\Support\PowerGrid\CanadianRegionCatalog;
use App\Support\PowerGrid\EnergyObservationAggregator;
use App\Support\PowerGrid\PowerGridDataBootstrapper;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Symfony\Component\HttpFoundation\Response;

class RegionObservationController extends Controller
{
    public function show(Request $request, string $region, CanadianRegionCatalog $regions, EnergyObservationAggregator $aggregator, PowerGridDataBootstrapper $dataBootstrapper): mixed
    {
        $dataBootstrapper->ensureSeeded();

        $validator = Validator::make($request->query(), [
            'range' => ['sometimes', 'string', Rule::in(['day', 'week', 'month', 'year'])],
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'The selected range is invalid.',
                'errors' => $validator->errors(),
            ], Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        $validated = $validator->validated();
        $metadata = $regions->find($region);

        abort_if($metadata === null, Response::HTTP_NOT_FOUND);

        return response()->json($aggregator->forRegion($metadata, $validated['range'] ?? 'day'));
    }
}
