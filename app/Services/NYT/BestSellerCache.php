<?php

namespace App\Services\NYT;

use App\Services\NYT\Contracts\CacheInterface;
use Illuminate\Support\Facades\Cache;

class BestSellerCache implements CacheInterface
{
    public function get(string $key): BestSellerResponse
    {
        /**
         * @var array<string, mixed> $data
         */
        $data = Cache::get($key);

        return BestSellerResponse::make($data);
    }

    /**
     * @param  array<string, mixed>  $value
     */
    public function put(string $key, array $value, int $ttl): void
    {
        Cache::put($key, $value, $ttl);
    }

    public function has(string $key): bool
    {
        return Cache::has($key);
    }

    /**
     * @param  array<string, mixed>  $params
     */
    public function generateKey(array $params): string
    {
        ksort($params);

        return 'nyt:bs:'.sha1(serialize($params));
    }
}
