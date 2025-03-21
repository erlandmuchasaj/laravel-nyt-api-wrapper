<?php

namespace App\Services\NYT;

use App\Services\NYT\Contracts\CacheInterface;
use Exception;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Support\Facades\Http;
use Symfony\Component\HttpFoundation\Response;

class NYTService
{
    protected PendingRequest $client;

    protected string $baseUrl;

    protected string $apiKey;

    protected int $timeOut = 5;

    protected int $retryAttempts = 3;

    protected int $retryDelay = 100;

    private CacheInterface $cache;

    /**
     * @var array<string, string> The available endpoints.
     */
    protected array $urls = [
        'best-seller' => '/lists/best-sellers/history.json',
        'overview' => '/lists/overview.json',
    ];

    public function __construct(CacheInterface $cache)
    {
        $this->baseUrl = config()->string('services.nyt.baseUrl');
        $this->apiKey = config()->string('services.nyt.key');
        $this->cache = $cache;
    }

    /**
     * Fetches bestsellers data from NYT using the provided parameters.
     *
     * @param  array<string, mixed>  $params  The validated query parameters.
     * @param  bool  $cacheBypassed  When true - use cache, false - bypasses cache.
     * @return \App\Services\NYT\BestSellerResponse The JSON-decoded response.
     *
     * @throws \Exception When the API call fails and no cache is available.
     */
    public function fetchBestSellers(array $params, bool $cacheBypassed = false): BestSellerResponse
    {
        $params['api-key'] = $this->apiKey;

        if (isset($params['offset']) && is_numeric($params['offset'])) {
            $params['offset'] = $this->normalizeOffset((int) $params['offset']);
        }

        if (isset($params['isbn']) && is_array($params['isbn'])) {
            /**
             * @var array<int, string> $isbns
             */
            $isbns = $params['isbn'];
            $params['isbn'] = $this->formatIsbns($isbns);
        }

        $cacheKey = $this->cache->generateKey($params);

        if (! $cacheBypassed && $this->cache->has($cacheKey)) {
            return $this->cache->get($cacheKey);
        }

        $response = $this
            ->client()
            ->get($this->urls['best-seller'], $params);

        if (! $response->successful()) {
            // Fallback to cached data if available.
            if ($this->cache->has($cacheKey)) {
                return $this->cache->get($cacheKey);
            }
            throw new \Exception('NYT API request failed with status '.$response->status());
        }

        /**
         * @var array<string, mixed> $data
         */
        $data = $response->json();

        // Set TTL: if offset is provided (indicating pagination), use a shorter TTL.
        $ttl = isset($params['offset']) ? 300 : 3600;

        $this->cache->put($cacheKey, $data, $ttl);

        return BestSellerResponse::make($data, $cacheBypassed);
    }

    /**
     * Fetches bestsellers data from NYT using the provided parameters.
     *
     * @param  array<string, mixed>  $params  The validated query parameters.
     * @return array<string, mixed> The JSON-decoded response.
     *
     * @throws \Exception When the API call fails.
     */
    public function fetchOverviews(array $params): array
    {
        $params['api-key'] = $this->apiKey;

        $response = $this->client()->get($this->urls['overview'], $params);

        if (! $response->successful()) {
            throw new \Exception('NYT API request failed with status '.$response->status());
        }

        /**
         * @var array<string, mixed> $data
         */
        $data = $response->json();

        return $data;
    }

    private function client(): PendingRequest
    {
        if (empty($this->client)) {
            $this->client = Http::retry($this->retryAttempts, $this->retryDelay, function (Exception $exception, PendingRequest $request) {
                return $exception->getCode() === Response::HTTP_TOO_MANY_REQUESTS;
            })->withHeaders([
                'X-Application' => 'NYT Laravel API',
            ])
                ->timeout($this->timeOut)
                ->baseUrl($this->baseUrl);
        }

        return $this->client;
    }

    private function normalizeOffset(int $offset): int
    {
        return max(0, $offset - ($offset % 20));
    }

    /**
     * Formats the ISBNs into a string.
     *
     * @param  array<int, string>  $isbns  The ISBNs to format.
     * @return string The formatted ISBNs.
     */
    private function formatIsbns(array $isbns): string
    {
        return implode(';', $isbns);
    }
}
