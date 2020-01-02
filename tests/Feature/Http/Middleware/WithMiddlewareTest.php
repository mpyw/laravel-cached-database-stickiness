<?php

namespace Mpyw\LaravelCachedDatabaseStickiness\Tests\Feature\Http\Middleware;

use Illuminate\Contracts\Cache\Repository as CacheRepository;

class WithMiddlewareTest extends MiddlewareTest
{
    protected $withMiddleware = true;

    public function testWithMiddlewareWhenCacheExists(): void
    {
        $this->mock(CacheRepository::class)
            ->shouldReceive('has')
            ->with('database-stickiness:connection=test,resolver=auth,id=1')
            ->once()
            ->andReturnTrue();

        $this->get('/', [
            'Authorization' => 'Basic ' . base64_encode('example@example.com:password'),
        ])->assertSuccessful();

        $this->assertTrue($this->getRecordsModifiedViaReflection());
    }

    public function testWithMiddlewareWhenCacheDoesNotExist(): void
    {
        $this->mock(CacheRepository::class)
            ->shouldReceive('has')
            ->with('database-stickiness:connection=test,resolver=auth,id=1')
            ->once()
            ->andReturnFalse();

        $this->get('/', [
            'Authorization' => 'Basic ' . base64_encode('example@example.com:password'),
        ])->assertSuccessful();

        $this->assertFalse($this->getRecordsModifiedViaReflection());
    }
}
