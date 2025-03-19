<?php

namespace App\Services\NYT;

final class BestSellerResponse
{
    public ?string $copyright;

    public string $status = 'OK';

    public int $num_results = 0;

    /**
     * @var array<int, array<string, mixed>>|null
     */
    public ?array $results = null;

    /**
     * @param  array<string, mixed>  $data
     */
    public function __construct(array $data, public bool $cacheBypassed = false)
    {
        foreach ($data as $property => $value) {
            if (property_exists($this, $property)) {
                $this->{$property} = $value;
            }
        }
    }

    /**
     * @param  array<string, mixed>  $args
     */
    public static function make(array $args, bool $cacheBypassed = false): static
    {
        return new self($args, $cacheBypassed);
    }

    /**
     * @return array<string, mixed>
     */
    public function all(): array
    {
        return [
            'data' => $this->results,
            'meta' => $this->meta(),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function meta(): array
    {
        return [
            'status' => $this->status,
            'num_results' => $this->num_results,
            'timestamp' => now()->toDateTimeString(),
            'cache_status' => $this->cacheStatus(),
            'copyright' => $this->copyright,
        ];
    }

    private function cacheStatus(): string
    {
        return $this->cacheBypassed ? 'miss' : 'hit';
    }
}
