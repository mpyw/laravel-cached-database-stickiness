<?php

namespace Mpyw\LaravelCachedDatabaseStickiness\Tests\Feature;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Mpyw\LaravelCachedDatabaseStickiness\ConnectionServiceProvider;
use Mpyw\LaravelCachedDatabaseStickiness\StickinessServiceProvider;
use Orchestra\Testbench\TestCase;
use PDO;
use ReflectionProperty;

class ResolvingTest extends TestCase
{
    /**
     * @param  \Illuminate\Foundation\Application $app
     * @return array
     */
    protected function getPackageProviders($app): array
    {
        return [
            StickinessServiceProvider::class,
            ConnectionServiceProvider::class,
        ];
    }

    /**
     * @param \Illuminate\Foundation\Application $app
     */
    protected function getEnvironmentSetUp($app): void
    {
        $app['config']->set('database.default', 'test');
        $app['config']->set('database.connections.test', [
            'driver' => 'sqlite',
            'database' => ':memory:',
            'read' => [
                'database' => ':memory:',
            ],
            'write' => [],
            'sticky' => true,
            'stickiness_ttl' => 3,
        ]);
        $app['request'] = Request::create('/', 'GET', [], [], [], ['REMOTE_ADDR' => '192.168.0.1']);
    }

    protected function getReadPdoViaReflection()
    {
        /* @var \Illuminate\Database\Connection $connection */
        $connection = DB::connection();

        /* @noinspection PhpUnhandledExceptionInspection */
        $property = new ReflectionProperty($connection, 'readPdo');
        $property->setAccessible(true);
        return $property->getValue($connection);
    }

    protected function getWritePdoViaReflection()
    {
        /* @var \Illuminate\Database\Connection $connection */
        $connection = DB::connection();

        /* @noinspection PhpUnhandledExceptionInspection */
        $property = new ReflectionProperty($connection, 'pdo');
        $property->setAccessible(true);
        return $property->getValue($connection);
    }

    public function testNonAffectingStatementWhenCacheDoesNotExist(): void
    {
        /* @var \Illuminate\Database\Connection $connection */
        $connection = DB::connection();
        $connection->select('select 1');

        $this->assertInstanceOf(PDO::class, $this->getReadPdoViaReflection());
        $this->assertInstanceOf(Closure::class, $this->getWritePdoViaReflection());
    }

    public function testNonAffectingStatementWhenCacheExists(): void
    {
        Cache::put('database-stickiness:connection=test,resolver=ip,ip=192.168.0.1', true, 5);

        /* @var \Illuminate\Database\Connection $connection */
        $connection = DB::connection();
        $connection->select('select 1');

        $this->assertInstanceOf(Closure::class, $this->getReadPdoViaReflection());
        $this->assertInstanceOf(PDO::class, $this->getWritePdoViaReflection());
    }

    public function testAffectingStatementWhenCacheDoesNotExist(): void
    {
        /* @var \Illuminate\Database\Connection $connection */
        $connection = DB::connection();
        $connection->statement('select 1'); // This is a fake of insert/update/delete

        $this->assertInstanceOf(Closure::class, $this->getReadPdoViaReflection());
        $this->assertInstanceOf(PDO::class, $this->getWritePdoViaReflection());
    }

    public function testAffectingStatementWhenCacheExists(): void
    {
        Cache::put('database-stickiness:connection=test,resolver=ip,ip=192.168.0.1', true, 5);

        /* @var \Illuminate\Database\Connection $connection */
        $connection = DB::connection();
        $connection->statement('select 1'); // This is a fake of insert/update/delete

        $this->assertInstanceOf(Closure::class, $this->getReadPdoViaReflection());
        $this->assertInstanceOf(PDO::class, $this->getWritePdoViaReflection());
    }
}
