<?php

namespace Mpyw\LaravelCachedDatabaseStickiness;

use Closure;
use Illuminate\Database\Connection;
use Illuminate\Support\ServiceProvider;
use Mpyw\LaravelCachedDatabaseStickiness\Connections\MySqlConnection;
use Mpyw\LaravelCachedDatabaseStickiness\Connections\PostgresConnection;
use Mpyw\LaravelCachedDatabaseStickiness\Connections\SQLiteConnection;
use Mpyw\LaravelCachedDatabaseStickiness\Connections\SqlServerConnection;

class ConnectionServiceProvider extends ServiceProvider
{
    /**
     * {@inheritdoc}
     */
    public function register(): void
    {
        Connection::resolverFor('mysql', $this->resolverFor(MySqlConnection::class));
        Connection::resolverFor('pgsql', $this->resolverFor(PostgresConnection::class));
        Connection::resolverFor('sqlite', $this->resolverFor(SQLiteConnection::class));
        Connection::resolverFor('sqlsrv', $this->resolverFor(SqlServerConnection::class));
    }

    /**
     * Create resolver for the connection.
     *
     * @param  string   $class
     * @return \Closure
     */
    protected function resolverFor(string $class): Closure
    {
        return static function (...$args) use ($class) {
            return new $class(...$args);
        };
    }
}
