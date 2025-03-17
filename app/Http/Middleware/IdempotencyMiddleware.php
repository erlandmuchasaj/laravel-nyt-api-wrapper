<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Contracts\Cache\LockTimeoutException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Symfony\Component\HttpFoundation\Response;

/**
 * In the context of REST APIs,
 * when making multiple identical requests has the same effect as making a single request
 * then that REST API is called idempotent.
 *
 * @todo: EM - We have to make our APIs fault-tolerant in such a way that the duplicate requests do not leave the system unstable.
 */
class IdempotencyMiddleware
{
    /**
     * The HTTP methods that are idempotent.
     *
     * @var array<int, string>
     */
    protected array $allowedMethods = ['POST', 'PUT', 'PATCH', 'DELETE'];

    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        if (config()->boolean('services.idempotency.enabled', false)) {
            return $next($request);
        }

        if (! $this->isMethodAllowed($request)) {
            return $next($request);
        }

        // Check for the `Idempotency-Key` header.
        $idempotencyKey = (string) $request->header(config()->string('services.idempotency.key', 'Idempotency-Key'));
        if (! $idempotencyKey) {
            return $next($request);
        }

        $cacheKey = $this->getCacheKey($request, $idempotencyKey);

        if (Cache::has($cacheKey)) {
            return $this->getCachedResponse($cacheKey);
        }

        $lock = Cache::lock($this->getLockKey($request), 10);

        try {
            $lock->block(5); // Wait up to 5 seconds for the lock

            $response = $next($request);

            if ($response->isSuccessful()) {
                $this->cacheResponse($cacheKey, $response);
            }

            return $response;
        } catch (LockTimeoutException $e) {
            report($e);
        } finally {
            $lock->release();
        }

        return $next($request);
    }

    protected function isMethodAllowed(Request $request): bool
    {
        return in_array(
            $request->method(),
            config()->array('services.idempotency.allowed_methods', $this->allowedMethods)
        );
    }

    protected function getLockKey(Request $request): string
    {
        return 'idempotency_lock:'.sha1($request->url().'|'.$request->header(config()->string('services.idempotency.key', 'Idempotency-Key')));
    }

    /**
     * Get the cache key for the request.
     */
    protected function getCacheKey(Request $request, string $idempotencyKey): string
    {
        $userId = auth()->id() ?? 'guest';
        $prefix = config()->string('services.idempotency.cache_prefix', 'idempotency');
        $token = sha1($idempotencyKey);

        return "$prefix:{$userId}:{$token}";
    }

    protected function cacheResponse(string $cacheKey, Response $response): void
    {
        $data = [
            'content' => $response->getContent(),
            'status' => $response->getStatusCode(),
            'headers' => $response->headers->all(),
        ];

        Cache::put(
            $cacheKey,
            $data,
            config()->integer('services.idempotency.ttl', 1440) // Default 24 hours
        );
    }

    protected function getCachedResponse(string $cacheKey): Response
    {
        $data = Cache::get($cacheKey);

        // Ensure $data is an array with the expected keys
        if (! is_array($data) || ! isset($data['content'], $data['status'], $data['headers'])) {
            return new Response('Cached data is invalid.', 500);
        }

        /** @var string $content */
        $content = $data['content'];

        /** @var int $status */
        $status = $data['status'];

        /** @var array<string, array<int, string>> $headers */
        $headers = $data['headers'];

        return new Response($content, $status, $headers);
    }
}
