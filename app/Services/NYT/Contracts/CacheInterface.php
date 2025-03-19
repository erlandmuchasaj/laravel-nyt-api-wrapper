<?php

namespace App\Services\NYT\Contracts;

use App\Services\NYT\BestSellerResponse;

interface CacheInterface
{
    public function get(string $key): BestSellerResponse;

    /**
     * @param  array<string, mixed>  $value
     */
    public function put(string $key, array $value, int $ttl): void;

    public function has(string $key): bool;

    /**
     * @param  array<string, mixed>  $params
     */
    public function generateKey(array $params): string;
}
