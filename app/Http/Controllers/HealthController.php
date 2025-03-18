<?php

namespace App\Http\Controllers;

use App\Enums\HealthStatus;
use App\Services\NYTService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class HealthController extends Controller
{
    /**
     * Check the health of the application
     */
    public function __invoke(Request $request, NYTService $nytService): JsonResponse
    {
        try {
            // set on a date that is not in the database
            // so we have a faster response to check api-server health
            $nytService->fetchOverviews(['published_date' => '2222-12-31']);
        } catch (\Exception $e) {
            return response()->json([
                'status' => HealthStatus::ERROR,
                'timestamp' => now()->toDateTimeString(),
                'message' => $e->getMessage(),
            ], 500);
        }

        return response()->json([
            'status' => HealthStatus::SUCCESS,
            'timestamp' => now()->toDateTimeString(),
            'message' => __('All systems operational'),
        ]);
    }
}
