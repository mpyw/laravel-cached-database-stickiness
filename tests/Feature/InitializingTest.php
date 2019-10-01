<?php

namespace Mpyw\LaravelCachedDatabaseStickiness\Tests\Feature;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Queue;
use Mpyw\LaravelCachedDatabaseStickiness\ConnectionServiceProvider;
use Mpyw\LaravelCachedDatabaseStickiness\StickinessServiceProvider;
use Orchestra\Testbench\TestCase;
use ReflectionProperty;

class InitializingTest extends TestCase
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
        ]);
        $app['request'] = Request::create('/', 'GET', [], [], [], ['REMOTE_ADDR' => '192.168.0.1']);
    }

    protected function getRecordsModifiedViaReflection()
    {
        /* @var \Illuminate\Database\Connection $connection */
        $connection = DB::connection();

        /* @noinspection PhpUnhandledExceptionInspection */
        $property = new ReflectionProperty($connection, 'recordsModified');
        $property->setAccessible(true);
        return $property->getValue($connection);
    }

    public function testInitializationForJobs(): void
    {
        DB::connection();

        $this->assertFalse($this->getRecordsModifiedViaReflection());

        Queue::push(new GeneralJob());

        $this->assertTrue($this->getRecordsModifiedViaReflection());

        Queue::push(new FreshJob());

        $this->assertFalse($this->getRecordsModifiedViaReflection());

        Queue::push(new ModifiedJob());

        $this->assertTrue($this->getRecordsModifiedViaReflection());
    }
}
