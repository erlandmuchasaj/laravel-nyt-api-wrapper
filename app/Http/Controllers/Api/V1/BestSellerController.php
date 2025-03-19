<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\BestSellersRequest;
use App\Services\NYT\NYTService;
use Illuminate\Http\JsonResponse;

class BestSellerController extends Controller
{
    protected NYTService $nytService;

    public function __construct(NYTService $nytService)
    {
        $this->nytService = $nytService;
    }

    /**
     * Store the newly created resource in storage.
     */
    public function __invoke(BestSellersRequest $request): JsonResponse
    {
        $params = $request->validated();

        /**
         * If this is set and is false, we need to bypass cache
         * false - bypass cache
         * true - use cache
         */
        $cacheBypassed = $request->has('cache') &&
                         $request->boolean('cache') === false;

        unset($params['cache']);

        try {
            $response = $this->nytService->fetchBestSellers($params, $cacheBypassed);
        } catch (\Exception $e) {
            return response()->json([
                'error' => __('Failed to retrieve bestsellers data'),
                'message' => $e->getMessage(),
            ], $e->getCode() ?: 500);
        }

        return response()->json($response->all());
    }
}
