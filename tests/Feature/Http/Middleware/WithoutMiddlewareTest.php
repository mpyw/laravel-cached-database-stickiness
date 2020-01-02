<?php

namespace Mpyw\LaravelCachedDatabaseStickiness\Tests\Feature\Http\Middleware;

use Illuminate\Contracts\Cache\Repository as CacheRepository;

class WithoutMiddlewareTest extends MiddlewareTest
{
    protected $withMiddleware = false;

    public function testWithoutMiddleware(): void
    {
        $this->mock(CacheRepository::class)
            ->shouldNotReceive('has');

        $this->get('/', [
            'Authorization' => 'Basic ' . base64_encode('example@example.com:password'),
        ])->assertSuccessful();

        $this->assertFalse($this->getRecordsModifiedViaReflection());
    }
}
