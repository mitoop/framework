<?php

namespace Illuminate\Tests\Console\Scheduling;

use Mockery as m;
use Illuminate\Support\Carbon;
use PHPUnit\Framework\TestCase;
use Illuminate\Console\Scheduling\Event;
use Illuminate\Console\Scheduling\CacheEventMutex;
use Illuminate\Console\Scheduling\CacheSchedulingMutex;

class CacheSchedulingMutexTest extends TestCase
{
    /**
     * @var CacheSchedulingMutex
     */
    protected $cacheMutex;

    /**
     * @var Event
     */
    protected $event;

    /**
     * @var Carbon
     */
    protected $time;

    /**
     * @var \Illuminate\Contracts\Cache\Repository
     */
    protected $cacheRepository;

    public function setUp()
    {
        parent::setUp();

        $this->cacheRepository = m::mock('Illuminate\Contracts\Cache\Repository');
        $this->cacheMutex = new CacheSchedulingMutex($this->cacheRepository);
        $this->event = new Event(new CacheEventMutex($this->cacheRepository), 'command');
        $this->time = Carbon::now();
    }

    public function testMutexReceviesCorrectCreate()
    {
        $this->cacheRepository->shouldReceive('add')->once()->with($this->event->mutexName().$this->time->format('Hi'), true, 60)->andReturn(true);

        $this->assertTrue($this->cacheMutex->create($this->event, $this->time));
    }

    public function testPreventsMultipleRuns()
    {
        $this->cacheRepository->shouldReceive('add')->once()->with($this->event->mutexName().$this->time->format('Hi'), true, 60)->andReturn(false);

        $this->assertFalse($this->cacheMutex->create($this->event, $this->time));
    }

    public function testChecksForNonRunSchedule()
    {
        $this->cacheRepository->shouldReceive('has')->once()->with($this->event->mutexName().$this->time->format('Hi'))->andReturn(false);

        $this->assertFalse($this->cacheMutex->exists($this->event, $this->time));
    }

    public function testChecksForAlreadyRunSchedule()
    {
        $this->cacheRepository->shouldReceive('has')->with($this->event->mutexName().$this->time->format('Hi'))->andReturn(true);

        $this->assertTrue($this->cacheMutex->exists($this->event, $this->time));
    }
}
