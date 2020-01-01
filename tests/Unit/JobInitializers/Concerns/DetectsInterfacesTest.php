<?php

namespace Mpyw\LaravelCachedDatabaseStickiness\Tests\Unit\JobInitializers\Concerns;

use Illuminate\Contracts\Queue\Job;
use Illuminate\Events\CallQueuedListener;
use Illuminate\Mail\SendQueuedMailable;
use Illuminate\Notifications\AnonymousNotifiable;
use Illuminate\Notifications\SendQueuedNotifications;
use Mockery;
use Mpyw\LaravelCachedDatabaseStickiness\JobInitializers\Concerns\DetectsInterfaces;
use Mpyw\LaravelCachedDatabaseStickiness\Tests\Stubs\Jobs\FreshJob;
use Mpyw\LaravelCachedDatabaseStickiness\Tests\Stubs\Jobs\GeneralJob;
use Mpyw\LaravelCachedDatabaseStickiness\Tests\Stubs\Mailables\FreshMailable;
use Mpyw\LaravelCachedDatabaseStickiness\Tests\Stubs\Mailables\GeneralMailable;
use Mpyw\LaravelCachedDatabaseStickiness\Tests\Stubs\Mailables\ModifiedMailable;
use Mpyw\LaravelCachedDatabaseStickiness\Tests\Stubs\Notifications\FreshNotification;
use Mpyw\LaravelCachedDatabaseStickiness\Tests\Stubs\Notifications\GeneralNotification;
use Mpyw\LaravelCachedDatabaseStickiness\Tests\Stubs\Notifications\ModifiedNotification;
use Orchestra\Testbench\TestCase;

class DetectsInterfacesTest extends TestCase
{
    /**
     * @var \Illuminate\Contracts\Queue\Job|\Mockery\LegacyMockInterface|\Mockery\MockInterface
     */
    protected $job;

    /**
     * @var \Mpyw\LaravelCachedDatabaseStickiness\JobInitializers\Concerns\DetectsInterfaces
     */
    protected $trait;

    protected function setUp(): void
    {
        parent::setUp();

        $this->job = Mockery::mock(Job::class);
        $this->trait = new class() {
            use DetectsInterfaces;
        };
    }

    public function testJobCallersFreshCaller(): void
    {
        $this->job->shouldReceive('payload')->once()->andReturn([
            'job' => 'Mpyw\LaravelCachedDatabaseStickiness\Tests\Stubs\JobCallers\FreshCaller@call',
        ]);

        $this->assertTrue($this->trait->shouldAssumeFresh($this->job));
    }

    public function testJobCallersModifiedCaller(): void
    {
        $this->job->shouldReceive('payload')->once()->andReturn([
            'job' => 'Mpyw\LaravelCachedDatabaseStickiness\Tests\Stubs\JobCallers\ModifiedCaller@call',
        ]);

        $this->assertTrue($this->trait->shouldAssumeModified($this->job));
    }

    public function testJobCallersGeneralCaller(): void
    {
        $this->job->shouldReceive('payload')->once()->andReturn([
            'job' => 'Mpyw\LaravelCachedDatabaseStickiness\Tests\Stubs\JobCallers\GeneralCaller@call',
        ]);

        $this->assertNull($this->trait->shouldAssumeModified($this->job));
    }

    public function testJobCallerBrokenCaller(): void
    {
        $this->job->shouldReceive('payload')->once()->andReturn([
            'job' => [],
        ]);

        $this->assertNull($this->trait->shouldAssumeModified($this->job));
    }

    public function testJobCallerMissingCaller(): void
    {
        $this->job->shouldReceive('payload')->once()->andReturn([
        ]);

        $this->assertNull($this->trait->shouldAssumeModified($this->job));
    }

    public function testCallQueuedHandlerFreshCommand(): void
    {
        $this->job->shouldReceive('payload')->once()->andReturn([
            'job' => 'Illuminate\Queue\CallQueuedHandler@call',
            'data' => [
                'commandName' => 'Mpyw\LaravelCachedDatabaseStickiness\Tests\Stubs\Jobs\FreshJob',
                'command' => serialize(new FreshJob()),
            ],
        ]);

        $this->assertTrue($this->trait->shouldAssumeFresh($this->job));
    }

    public function testCallQueuedHandlerModifiedCommand(): void
    {
        $this->job->shouldReceive('payload')->once()->andReturn([
            'job' => 'Illuminate\Queue\CallQueuedHandler@call',
            'data' => [
                'commandName' => 'Mpyw\LaravelCachedDatabaseStickiness\Tests\Stubs\Jobs\ModifiedJob',
                'command' => serialize(new FreshJob()),
            ],
        ]);

        $this->assertTrue($this->trait->shouldAssumeModified($this->job));
    }

    public function testCallQueuedHandlerGeneralCommand(): void
    {
        $this->job->shouldReceive('payload')->once()->andReturn([
            'job' => 'Illuminate\Queue\CallQueuedHandler@call',
            'data' => [
                'commandName' => 'Mpyw\LaravelCachedDatabaseStickiness\Tests\Stubs\Jobs\GeneralJob',
                'command' => serialize(new GeneralJob()),
            ],
        ]);

        $this->assertNull($this->trait->shouldAssumeModified($this->job));
    }

    public function testCallQueuedHandlerBrokenCommand(): void
    {
        $this->job->shouldReceive('payload')->once()->andReturn([
            'job' => 'Illuminate\Queue\CallQueuedHandler@call',
            'data' => [
                'commandName' => [],
                'command' => [],
            ],
        ]);

        $this->assertNull($this->trait->shouldAssumeModified($this->job));
    }

    public function testCallQueuedHandlerMissingCommand(): void
    {
        $this->job->shouldReceive('payload')->once()->andReturn([
            'job' => 'Illuminate\Queue\CallQueuedHandler@call',
        ]);

        $this->assertNull($this->trait->shouldAssumeModified($this->job));
    }

    public function testCallQueuedListenerFreshListener(): void
    {
        $this->job->shouldReceive('payload')->once()->andReturn([
            'job' => 'Illuminate\Queue\CallQueuedHandler@call',
            'data' => [
                'commandName' => 'Illuminate\Events\CallQueuedListener',
                'command' => serialize(new CallQueuedListener(
                    'Mpyw\LaravelCachedDatabaseStickiness\Tests\Stubs\Listeners\FreshListener',
                    'handle',
                    []
                )),
            ],
        ]);

        $this->assertTrue($this->trait->shouldAssumeFresh($this->job));
    }

    public function testCallQueuedListenerModifiedListener(): void
    {
        $this->job->shouldReceive('payload')->once()->andReturn([
            'job' => 'Illuminate\Queue\CallQueuedHandler@call',
            'data' => [
                'commandName' => 'Illuminate\Events\CallQueuedListener',
                'command' => serialize(new CallQueuedListener(
                    'Mpyw\LaravelCachedDatabaseStickiness\Tests\Stubs\Listeners\FreshListener',
                    'handle',
                    []
                )),
            ],
        ]);

        $this->assertTrue($this->trait->shouldAssumeFresh($this->job));
    }

    public function testCallQueuedListenerGeneralListener(): void
    {
        $this->job->shouldReceive('payload')->once()->andReturn([
            'job' => 'Illuminate\Queue\CallQueuedHandler@call',
            'data' => [
                'commandName' => 'Illuminate\Events\CallQueuedListener',
                'command' => serialize(new CallQueuedListener(
                    'Mpyw\LaravelCachedDatabaseStickiness\Tests\Stubs\Listeners\GeneralListener',
                    'handle',
                    []
                )),
            ],
        ]);

        $this->assertNull($this->trait->shouldAssumeModified($this->job));
    }

    public function testCallQueuedListenerBrokenTypeListener(): void
    {
        $this->job->shouldReceive('payload')->once()->andReturn([
            'job' => 'Illuminate\Queue\CallQueuedHandler@call',
            'data' => [
                'commandName' => 'Illuminate\Events\CallQueuedListener',
                'command' => [],
            ],
        ]);

        $this->assertNull($this->trait->shouldAssumeModified($this->job));
    }

    public function testCallQueuedListenerBrokenSerialListener(): void
    {
        $this->job->shouldReceive('payload')->once()->andReturn([
            'job' => 'Illuminate\Queue\CallQueuedHandler@call',
            'data' => [
                'commandName' => 'Illuminate\Events\CallQueuedListener',
                'command' => 'foo-bar-baz',
            ],
        ]);

        $this->assertNull($this->trait->shouldAssumeModified($this->job));
    }

    public function testCallQueuedListenerMissingListener(): void
    {
        $this->job->shouldReceive('payload')->once()->andReturn([
            'job' => 'Illuminate\Queue\CallQueuedHandler@call',
            'data' => [
                'commandName' => 'Illuminate\Events\CallQueuedListener',
            ],
        ]);

        $this->assertNull($this->trait->shouldAssumeModified($this->job));
    }

    public function testSendPushNotificationFreshNotification(): void
    {
        $this->job->shouldReceive('payload')->once()->andReturn([
            'job' => 'Illuminate\Queue\CallQueuedHandler@call',
            'data' => [
                'commandName' => 'Illuminate\Notifications\SendQueuedNotifications',
                'command' => serialize(new SendQueuedNotifications(
                    collect([new AnonymousNotifiable()]),
                    new FreshNotification())
                ),
            ],
        ]);

        $this->assertTrue($this->trait->shouldAssumeFresh($this->job));
    }

    public function testSendPushNotificationModifiedNotification(): void
    {
        $this->job->shouldReceive('payload')->once()->andReturn([
            'job' => 'Illuminate\Queue\CallQueuedHandler@call',
            'data' => [
                'commandName' => 'Illuminate\Notifications\SendQueuedNotifications',
                'command' => serialize(new SendQueuedNotifications(
                    collect([new AnonymousNotifiable()]),
                    new ModifiedNotification())
                ),
            ],
        ]);

        $this->assertTrue($this->trait->shouldAssumeModified($this->job));
    }

    public function testSendPushNotificationGeneralNotification(): void
    {
        $this->job->shouldReceive('payload')->once()->andReturn([
            'job' => 'Illuminate\Queue\CallQueuedHandler@call',
            'data' => [
                'commandName' => 'Illuminate\Notifications\SendQueuedNotifications',
                'command' => serialize(new SendQueuedNotifications(
                    collect([new AnonymousNotifiable()]),
                    new GeneralNotification())
                ),
            ],
        ]);

        $this->assertNull($this->trait->shouldAssumeModified($this->job));
    }

    public function testSendPushNotificationBrokenTypeNotification(): void
    {
        $this->job->shouldReceive('payload')->once()->andReturn([
            'job' => 'Illuminate\Queue\CallQueuedHandler@call',
            'data' => [
                'commandName' => 'Illuminate\Notifications\SendQueuedNotifications',
                'command' => [],
            ],
        ]);

        $this->assertNull($this->trait->shouldAssumeModified($this->job));
    }

    public function testSendPushNotificationBrokenSerialNotification(): void
    {
        $this->job->shouldReceive('payload')->once()->andReturn([
            'job' => 'Illuminate\Queue\CallQueuedHandler@call',
            'data' => [
                'commandName' => 'Illuminate\Notifications\SendQueuedNotifications',
                'command' => 'foo-bar-baz',
            ],
        ]);

        $this->assertNull($this->trait->shouldAssumeModified($this->job));
    }

    public function testSendPushNotificationMissingNotification(): void
    {
        $this->job->shouldReceive('payload')->once()->andReturn([
            'job' => 'Illuminate\Queue\CallQueuedHandler@call',
            'data' => [
                'commandName' => 'Illuminate\Notifications\SendQueuedNotifications',
            ],
        ]);

        $this->assertNull($this->trait->shouldAssumeModified($this->job));
    }

    public function testSendQueuedMailableFreshMailable(): void
    {
        $this->job->shouldReceive('payload')->once()->andReturn([
            'job' => 'Illuminate\Queue\CallQueuedHandler@call',
            'data' => [
                'commandName' => 'Illuminate\Mail\SendQueuedMailable',
                'command' => serialize(new SendQueuedMailable(new FreshMailable())),
            ],
        ]);

        $this->assertTrue($this->trait->shouldAssumeFresh($this->job));
    }

    public function testSendQueuedMailableModifiedMailable(): void
    {
        $this->job->shouldReceive('payload')->once()->andReturn([
            'job' => 'Illuminate\Queue\CallQueuedHandler@call',
            'data' => [
                'commandName' => 'Illuminate\Mail\SendQueuedMailable',
                'command' => serialize(new SendQueuedMailable(new ModifiedMailable())),
            ],
        ]);

        $this->assertTrue($this->trait->shouldAssumeModified($this->job));
    }

    public function testSendQueuedMailableGeneralMailable(): void
    {
        $this->job->shouldReceive('payload')->once()->andReturn([
            'job' => 'Illuminate\Queue\CallQueuedHandler@call',
            'data' => [
                'commandName' => 'Illuminate\Mail\SendQueuedMailable',
                'command' => serialize(new SendQueuedMailable(new GeneralMailable())),
            ],
        ]);

        $this->assertNull($this->trait->shouldAssumeModified($this->job));
    }

    public function testSendQueuedMailableBrokenTypeMailable(): void
    {
        $this->job->shouldReceive('payload')->once()->andReturn([
            'job' => 'Illuminate\Queue\CallQueuedHandler@call',
            'data' => [
                'commandName' => 'Illuminate\Mail\SendQueuedMailable',
                'command' => [],
            ],
        ]);

        $this->assertNull($this->trait->shouldAssumeModified($this->job));
    }

    public function testSendQueuedMailableBrokenSerialMailable(): void
    {
        $this->job->shouldReceive('payload')->once()->andReturn([
            'job' => 'Illuminate\Queue\CallQueuedHandler@call',
            'data' => [
                'commandName' => 'Illuminate\Mail\SendQueuedMailable',
                'command' => 'foo-bar-baz',
            ],
        ]);

        $this->assertNull($this->trait->shouldAssumeModified($this->job));
    }

    public function testSendQueuedMailableMissingMailable(): void
    {
        $this->job->shouldReceive('payload')->once()->andReturn([
            'job' => 'Illuminate\Queue\CallQueuedHandler@call',
            'data' => [
                'commandName' => 'Illuminate\Mail\SendQueuedMailable',
            ],
        ]);

        $this->assertNull($this->trait->shouldAssumeModified($this->job));
    }
}
