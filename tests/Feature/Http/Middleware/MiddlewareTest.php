<?php

namespace Mpyw\LaravelCachedDatabaseStickiness\Tests\Feature\Http\Middleware;

use Illuminate\Auth\Middleware\Authenticate;
use Illuminate\Auth\Middleware\AuthenticateWithBasicAuth;
use Illuminate\Contracts\Foundation\Application;
use Illuminate\Contracts\Http\Kernel as KernelContract;
use Illuminate\Routing\Router;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Route;
use Mpyw\LaravelCachedDatabaseStickiness\ConnectionServiceProvider;
use Mpyw\LaravelCachedDatabaseStickiness\Http\Middleware\ResolveStickinessOnResolvedConnections;
use Mpyw\LaravelCachedDatabaseStickiness\StickinessResolvers\AuthBasedResolver;
use Mpyw\LaravelCachedDatabaseStickiness\StickinessResolvers\StickinessResolverInterface;
use Mpyw\LaravelCachedDatabaseStickiness\StickinessServiceProvider;
use Mpyw\LaravelCachedDatabaseStickiness\Tests\Stubs\Models\User;
use Orchestra\Testbench\Http\Kernel;
use Orchestra\Testbench\TestCase;
use PDO;
use ReflectionProperty;

abstract class MiddlewareTest extends TestCase
{
    /**
     * @var null|bool
     */
    protected $withMiddleware;

    /**
     * @var string
     */
    protected $database;

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
            'database' => $this->database = tempnam(storage_path(''), 'sqlite_'),
            'sticky' => true,
        ]);
        $app['config']->set('auth.providers.users.model', User::class);
        $app->bind(StickinessResolverInterface::class, AuthBasedResolver::class);
    }

    protected function setUp(): void
    {
        parent::setUp();

        $pdo = new PDO("sqlite:$this->database", null, null, [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        ]);
        $pdo->exec('drop table if exists users');
        $pdo->exec('create table users(
            id integer primary key autoincrement,
            email text not null,
            password text not null,
            created_at datetime,
            updated_at datetime
        )');
        $stmt = $pdo->prepare('insert into users(email, password) values (?, ?)');
        $stmt->execute(['example@example.com', Hash::make('password')]);

        Route::middleware('auth.basic')->get('/', function () {
            return ['message' => 'ok'];
        });
    }

    protected function tearDown(): void
    {
        parent::tearDown();

        @unlink($this->database);
    }

    protected function resolveApplicationHttpKernel($app)
    {
        $app->singleton(KernelContract::class, function ($app) {
            return new class($app, $app['router'], $this->withMiddleware) extends Kernel {
                public function __construct(Application $app, Router $router, bool $withMiddleware)
                {
                    $this->middlewareGroups['auth'] = array_filter([
                        Authenticate::class,
                        $withMiddleware ? ResolveStickinessOnResolvedConnections::class : null,
                    ]);
                    $this->middlewareGroups['auth.basic'] = array_filter([
                        AuthenticateWithBasicAuth::class,
                        $withMiddleware ? ResolveStickinessOnResolvedConnections::class : null,
                    ]);

                    unset($this->routeMiddleware['auth'], $this->routeMiddleware['auth.basic']);

                    parent::__construct($app, $router);
                }
            };
        });
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
}
