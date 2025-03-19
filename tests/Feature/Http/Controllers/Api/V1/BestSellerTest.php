<?php

namespace Tests\Feature\Http\Controllers\Api\V1;

use App\Services\NYT\BestSellerResponse;
use App\Services\NYT\Contracts\CacheInterface;
use App\Services\NYT\NYTService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Mockery;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class BestSellerTest extends TestCase
{
    use RefreshDatabase;

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    #[Test]
    public function it_returns_bestsellers_data_successfully(): void
    {
        $mockResponse = new BestSellerResponse($this->getSuccessData(), true);

        $cache = mock(CacheInterface::class);
        $cache->shouldReceive('has')->andReturn(true);
        $cache->shouldReceive('get')->andReturn(new BestSellerResponse([]));

        $service = new NYTService($cache);

        $mockedService = Mockery::mock($service);
        $mockedService->shouldReceive('fetchBestSellers')->andReturn($mockResponse);
        $this->app->instance(NYTService::class, $mockedService);

        $response = $this->getJson('/api/v1/bestsellers?cache=0');

        $response->assertOk()
            ->assertJsonStructure([
                'data',
                'meta' => ['status', 'num_results', 'timestamp', 'cache_status', 'copyright'],
            ])
            ->assertJson([
                'data' => [['author' => 'Sophia Amoruso']],
                'meta' => [
                    'status' => 'OK',
                    'num_results' => 1,
                    'cache_status' => 'miss',
                ],
            ]);
    }

    #[Test]
    public function it_returns_error_on_invalid_parameters(): void
    {
        $response = $this->getJson('/api/v1/bestsellers?offset=2');

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['offset']);
    }

    #[Test]
    public function it_handles_api_errors_gracefully(): void
    {
        $cache = mock(CacheInterface::class);
        $cache->shouldReceive('has')->andReturn(true);
        $cache->shouldReceive('get')->andReturn(new BestSellerResponse([]));

        $service = new NYTService($cache);

        $mockedService = Mockery::mock($service);
        $mockedService->shouldReceive('fetchBestSellers')
            ->andThrow(new \Exception('API Error', 500));
        $this->app->instance(NYTService::class, $mockedService);

        $response = $this->getJson('/api/v1/bestsellers');

        $response->assertStatus(500)
            ->assertJson([
                'error' => 'Failed to retrieve bestsellers data',
                'message' => 'API Error',
            ]);
    }

    #[Test]
    public function meta_includes_cache_status(): void
    {
        $mockResponse = new BestSellerResponse([
            'results' => [],
            'num_results' => 0,
            'copyright' => '',
        ], false);

        $cache = mock(CacheInterface::class);
        $cache->shouldReceive('has')->andReturn(true);
        $cache->shouldReceive('get')->andReturn(new BestSellerResponse([]));

        $service = new NYTService($cache);

        $mockedService = Mockery::mock($service);
        $mockedService->shouldReceive('fetchBestSellers')->andReturn($mockResponse);
        $this->app->instance(NYTService::class, $mockedService);

        $response = $this->getJson('/api/v1/bestsellers');

        $response->assertJson([
            'meta' => [
                'cache_status' => 'hit',
            ],
        ]);
    }

    #[Test]
    public function test_valid_request_returns_data(): void
    {
        $fakeResponse = $this->getSuccessData();

        // Fake a successful NYT API response.
        Http::fake(['*' => Http::response($fakeResponse, 200)]);

        $params = [
            'author' => 'Sophia Amoruso',
            'title' => 'Test Book',
            'isbn' => ['9783161484100'], // Single ISBN can be provided as a string
            'offset' => 0, // Valid because 0 is a multiple of 20
        ];

        $response = $this->getJson('/api/v1/bestsellers?'.http_build_query($params));

        $response->assertStatus(200)
            ->assertJsonStructure([
                'data',
                'meta' => ['status', 'num_results', 'timestamp', 'cache_status', 'copyright'],
            ]);

        // Test that caching is working by faking a failure on subsequent HTTP calls.
        Http::fake(['*' => Http::response([], 500)]);

        $cachedResponse = $this->getJson('/api/v1/bestsellers?'.http_build_query($params));
        $cachedResponse->assertStatus(200)
            ->assertJsonFragment(['author' => 'Sophia Amoruso']);
    }

    public function test_multiple_isbn_search()
    {
        $fakeResponse = $this->getSuccessData();

        Http::fake(['*' => Http::response($fakeResponse, 200)]);

        $params = [
            'isbn' => ['9780446579933', '0061374229'],
            'offset' => 0, // 0 is valid as a multiple of 20
        ];

        $response = $this->getJson('/api/v1/bestsellers?'.http_build_query($params));
        $response->assertStatus(200)
            ->assertJsonFragment(['title' => '#GIRLBOSS']);
    }

    #[Test]
    public function test_invalid_isbn_type_returns_validation_error(): void
    {
        $params = [
            'isbn' => 'invalid_isbn',
        ];

        $response = $this->getJson('/api/v1/bestsellers?'.http_build_query($params));

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['isbn']);
    }

    #[Test]
    public function test_invalid_isbn_returns_validation_error(): void
    {
        $params = [
            'isbn' => ['invalid_isbn'],
        ];

        $response = $this->getJson('/api/v1/bestsellers?'.http_build_query($params));

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['isbn.0']);
    }

    #[Test]
    public function test_invalid_offset_returns_validation_error()
    {
        $params = [
            'offset' => 25, // 25 is not a multiple of 20
        ];

        $response = $this->getJson('/api/v1/bestsellers?'.http_build_query($params));

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['offset']);
    }

    private function getSuccessData(): array
    {
        return [
            'status' => 'OK',
            'copyright' => 'Copyright (c) 2019 The New York Times Company.  All Rights Reserved.',
            'num_results' => 1,
            'results' => [
                [
                    'title' => '#GIRLBOSS',
                    'description' => 'An online fashion retailer traces her path to success.',
                    'contributor' => 'by Sophia Amoruso',
                    'author' => 'Sophia Amoruso',
                    'contributor_note' => '',
                    'price' => 0,
                    'age_group' => '',
                    'publisher' => 'Portfolio/Penguin/Putnam',
                    'isbns' => [
                        [
                            'isbn10' => '039916927X',
                            'isbn13' => '9780399169274',
                        ],
                    ],
                    'ranks_history' => [
                        [
                            'primary_isbn10' => '1591847931',
                            'primary_isbn13' => '9781591847939',
                            'rank' => 8,
                            'list_name' => 'Business Books',
                            'display_name' => 'Business',
                            'published_date' => '2016-03-13',
                            'bestsellers_date' => '2016-02-27',
                            'weeks_on_list' => 0,
                            'ranks_last_week' => null,
                            'asterisk' => 0,
                            'dagger' => 0,
                        ],
                    ],
                    'reviews' => [
                        [
                            'book_review_link' => '',
                            'first_chapter_link' => '',
                            'sunday_review_link' => '',
                            'article_chapter_link' => '',
                        ],
                    ],
                ],
            ],
        ];
    }

    private function getErrorData(): array
    {
        return [
            'status' => 'ERROR',
            'copyright' => 'Copyright (c) 2025 The New York Times Company.  All Rights Reserved.',
            'errors' => [
                'No list found for list name and/or date provided.',
                'Not Found',
            ],
            'results' => [
            ],
        ];
    }
}
