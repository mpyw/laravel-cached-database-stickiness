<?php

namespace Mpyw\LaravelCachedDatabaseStickiness\Tests\Stubs\Concerns;

use Illuminate\Support\Facades\DB;
use PHPUnit\Framework\Assert as PHPUnit;
use ReflectionProperty;

trait LogsConnectionState
{
    protected static $enableStateLogging = false;
    protected static $loggedState;

    public static function enableStateLogging(bool $bool = true): void
    {
        static::$enableStateLogging = $bool;
        static::$loggedState = null;
    }

    public function logState(): void
    {
        if (!static::$enableStateLogging) {
            return;
        }

        /* @noinspection PhpUnhandledExceptionInspection */
        $property = new ReflectionProperty(DB::connection(), 'recordsModified');
        $property->setAccessible(true);

        static::$loggedState = $property->getValue(DB::connection());
    }

    public static function assertLoggedState(?bool $bool): void
    {
        PHPUnit::assertSame($bool, static::$loggedState, sprintf(
            '%s::$loggedState expected to be %s, but was %s actually.',
            static::class,
            var_export($bool, true),
            var_export(static::$loggedState, true)
        ));
    }
}
