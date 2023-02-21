<?php

declare(strict_types=1);

use Illuminate\Support\Carbon;
use function Pest\Laravel\travel;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Queue;
use function Pest\Laravel\assertModelMissing;
use Sammyjo20\LaravelHaystack\Models\Haystack;
use Sammyjo20\LaravelHaystack\Tests\Fixtures\Jobs\FailJob;
use Sammyjo20\LaravelHaystack\Tests\Fixtures\Jobs\NameJob;
use Sammyjo20\LaravelHaystack\Tests\Fixtures\Jobs\CacheJob;
use Sammyjo20\LaravelHaystack\Tests\Fixtures\Jobs\SetDataJob;
use Sammyjo20\LaravelHaystack\Tests\Fixtures\Jobs\AutoCacheJob;
use Sammyjo20\LaravelHaystack\Tests\Fixtures\Jobs\ExceptionJob;
use Sammyjo20\LaravelHaystack\Tests\Fixtures\Jobs\PauseNextJob;
use Sammyjo20\LaravelHaystack\Tests\Fixtures\Jobs\NativeFailJob;
use Sammyjo20\LaravelHaystack\Tests\Fixtures\Jobs\LongReleaseJob;
use Sammyjo20\LaravelHaystack\Tests\Fixtures\Callables\Middleware;
use Sammyjo20\LaravelHaystack\Tests\Fixtures\Jobs\AppendMultipleJob;
use Sammyjo20\LaravelHaystack\Tests\Fixtures\Jobs\ManuallyFailedJob;
use Sammyjo20\LaravelHaystack\Tests\Fixtures\Jobs\OrderCheckCacheJob;
use Sammyjo20\LaravelHaystack\Tests\Fixtures\Jobs\PrependMultipleJob;
use Sammyjo20\LaravelHaystack\Tests\Fixtures\Callables\CounterMiddleware;
use Sammyjo20\LaravelHaystack\Tests\Fixtures\Jobs\AddNextOrderCheckCacheJob;
use Sammyjo20\LaravelHaystack\Tests\Fixtures\Callables\InvokableCounterMiddleware;
use Sammyjo20\LaravelHaystack\Tests\Fixtures\Jobs\AppendingNextOrderCheckCacheJob;

test('you can start a haystack', function () {
    Queue::fake();

    $haystack = Haystack::build()
        ->addJob(new NameJob('Sam'))
        ->addJob(new NameJob('Steve'))
        ->create();

    Queue::assertNothingPushed();

    $haystack->start();

    expect($haystack->started)->toBeTrue();

    Queue::assertPushed(NameJob::class, function ($job) {
        return $job->name === 'Sam';
    });
});

test('you can finish a haystack early', function () {
    Queue::fake();

    $variable = 0;

    $haystack = Haystack::build()
        ->addJob(new NameJob('Sam'))
        ->then(function () use (&$variable) {
            $variable++;
        })
        ->create();

    expect($variable)->toEqual(0);

    $haystack->finish();

    expect($variable)->toEqual(1);

    Queue::assertNothingPushed();
});

test('you can fail a haystack', function () {
    Queue::fake();

    $variable = 0;

    $haystack = Haystack::build()
        ->addJob(new NameJob('Sam'))
        ->catch(function () use (&$variable) {
            $variable++;
        })
        ->create();

    expect($variable)->toEqual(0);

    $haystack->fail();

    expect($variable)->toEqual(1);

    Queue::assertNothingPushed();
});

test('jobs are processed in the right order', function () {
    Haystack::build()
        ->addJob(new OrderCheckCacheJob('Sam'))
        ->addJob(new OrderCheckCacheJob('Steve'))
        ->addJob(new OrderCheckCacheJob('Taylor'))
        ->dispatch();

    expect(cache()->get('order'))->toEqual(['Sam', 'Steve', 'Taylor']);
});

test('you can append a job onto the haystack and the job will be executed at the end', function () {
    Haystack::build()
        ->addJob(new AppendingNextOrderCheckCacheJob('Sam'))
        ->addJob(new OrderCheckCacheJob('Taylor'))
        ->dispatch();

    expect(cache()->get('order'))->toEqual(['Sam', 'Taylor', 'Sam']);
});

test('you can conditionally append a job onto the haystack', function () {
    Haystack::build()
        ->addJob(new OrderCheckCacheJob('Sam'))
        ->addJobWhen(true, new OrderCheckCacheJob('Neil'))
        ->addJobWhen(false, new OrderCheckCacheJob('Carlo'))
        ->addJobUnless(true, new OrderCheckCacheJob('Alex'))
        ->addJobUnless(false, new OrderCheckCacheJob('Marie'))
        ->dispatch();

    expect(cache()->get('order'))->toEqual(['Sam', 'Neil', 'Marie']);
});

test('you can conditionally append jobs onto the haystack', function () {
    $jobsA = [
        new OrderCheckCacheJob('Gareth'),
        new OrderCheckCacheJob('Nick'),
    ];

    $jobsB = [
        new OrderCheckCacheJob('Michael'),
        new OrderCheckCacheJob('Sam'),
    ];

    $jobsC = [
        new OrderCheckCacheJob('Mantas'),
        new OrderCheckCacheJob('Teo'),
    ];

    $jobsD = [
        new OrderCheckCacheJob('Carla'),
        new OrderCheckCacheJob('Olive'),
    ];

    Haystack::build()
        ->addJob(new OrderCheckCacheJob('Sam'))
        ->addJobsWhen(true, $jobsA)
        ->addJobsWhen(false, $jobsB)
        ->addJobsUnless(false, $jobsC)
        ->addJobsUnless(true, $jobsD)
        ->dispatch();

    expect(cache()->get('order'))->toEqual([
        'Sam', 'Gareth', 'Nick', 'Mantas', 'Teo',
    ]);
});

test('you can set the next job to process on the haystack', function () {
    Haystack::build()
        ->addJob(new AddNextOrderCheckCacheJob('Sam'))
        ->addJob(new OrderCheckCacheJob('Taylor'))
        ->dispatch();

    expect(cache()->get('order'))->toEqual(['Sam', 'Sam', 'Taylor']);
});

test('you can append multiple jobs to the haystack', function () {
    Haystack::build()
        ->addJob(new OrderCheckCacheJob('Steve'))
        ->addJob(new AppendMultipleJob('Sam', 'Gareth', 'Mantas'))
        ->addJob(new OrderCheckCacheJob('Taylor'))
        ->dispatch();

    expect(cache()->get('order'))->toEqual(['Steve', 'Taylor', 'Sam', 'Gareth', 'Mantas']);
});

test('you can append multiple jobs as a collection to the haystack', function () {
    Haystack::build()
        ->addJob(new OrderCheckCacheJob('Steve'))
        ->addJob(new AppendMultipleJob('Sam', 'Gareth', 'Mantas', true))
        ->addJob(new OrderCheckCacheJob('Taylor'))
        ->dispatch();

    expect(cache()->get('order'))->toEqual(['Steve', 'Taylor', 'Sam', 'Gareth', 'Mantas']);
});

test('you can prepend multiple jobs to the haystack', function () {
    Haystack::build()
        ->addJob(new OrderCheckCacheJob('Steve'))
        ->addJob(new PrependMultipleJob('Sam', 'Gareth', 'Mantas'))
        ->addJob(new OrderCheckCacheJob('Taylor'))
        ->dispatch();

    expect(cache()->get('order'))->toEqual(['Steve', 'Sam', 'Gareth', 'Mantas', 'Taylor']);
});

test('you can prepend multiple jobs as a collection to the haystack', function () {
    Haystack::build()
        ->addJob(new OrderCheckCacheJob('Steve'))
        ->addJob(new PrependMultipleJob('Sam', 'Gareth', 'Mantas', true))
        ->addJob(new OrderCheckCacheJob('Taylor'))
        ->dispatch();

    expect(cache()->get('order'))->toEqual(['Steve', 'Sam', 'Gareth', 'Mantas', 'Taylor']);
});

test('when a haystack is finished the then and finally methods are executed', function () {
    Haystack::build()
        ->addJob(new NameJob('Sam'))
        ->then(function () {
            cache()->put('then', true);
        })
        ->catch(function () {
            cache()->put('catch', true);
        })
        ->finally(function () {
            cache()->put('finally', true);
        })
        ->dispatch();

    expect(cache()->get('then'))->toBeTrue();
    expect(cache()->get('catch'))->toBeNull();
    expect(cache()->get('finally'))->toBeTrue();
});

test('when a haystack is failed the then and finally methods are executed', function () {
    Haystack::build()
        ->addJob(new FailJob())
        ->then(function () {
            cache()->put('then', true);
        })
        ->catch(function () {
            cache()->put('catch', true);
        })
        ->finally(function () {
            cache()->put('finally', true);
        })
        ->dispatch();

    expect(cache()->get('then'))->toBeNull();
    expect(cache()->get('catch'))->toBeTrue();
    expect(cache()->get('finally'))->toBeTrue();
});

test('when a haystack is paused the paused method is executed', function () {
    Haystack::build()
        ->addJob(new PauseNextJob('name', 'Sam', 300))
        ->paused(function () {
            cache()->put('paused', true);
        })
        ->dispatch();

    expect(cache()->get('paused'))->toBeTrue();
});

test('when a haystack job is long released the paused method is executed', function () {
    cache()->set('release', true);

    Haystack::build()
        ->addJob(new LongReleaseJob(300))
        ->paused(function () {
            cache()->put('paused', true);
        })
        ->dispatch();

    expect(cache()->get('paused'))->toBeTrue();
});

test('the closures can receive the data if the option is enabled', function () {
    Haystack::build()
        ->addJob(new SetDataJob('name', 'Sam'))
        ->addJob(new SetDataJob('friend', 'Steve'))
        ->addJob(new PauseNextJob('pause', true, 300))
        ->then(function ($data) {
            cache()->set('thenOne', $data);
        })
        ->then(function ($data) {
            cache()->set('thenTwo', $data);
        })
        ->finally(function ($data) {
            cache()->set('finally', $data);
        })
        ->paused(function ($data) {
            cache()->set('paused', $data);
        })
        ->dispatch();

    travel(6)->minutes();

    $this->artisan('haystacks:resume');

    $data = new Collection([
        'name' => 'Sam',
        'friend' => 'Steve',
    ]);

    expect(cache()->get('thenOne'))->toEqual($data);
    expect(cache()->get('thenTwo'))->toEqual($data);
    expect(cache()->get('finally'))->toEqual($data);
    expect(cache()->get('paused'))->toEqual($data);
});

test('the catch callback will recieve the data when a haystack fails', function () {
    Haystack::build()
        ->addJob(new SetDataJob('name', 'Sam'))
        ->addJob(new SetDataJob('friend', 'Steve'))
        ->addJob(new FailJob())
        ->catch(function ($data) {
            cache()->set('catchOne', $data);
        })
        ->catch(function ($data) {
            cache()->set('catchTwo', $data);
        })
        ->dispatch();

    $data = new Collection([
        'name' => 'Sam',
        'friend' => 'Steve',
    ]);

    expect(cache()->get('catchOne'))->toEqual($data);
    expect(cache()->get('catchTwo'))->toEqual($data);
});

test('multiple closures can be executed for successful and paused haystacks', function () {
    Haystack::build()
        ->addJob(new CacheJob('name', 'Sam'))
        ->addJob(new PauseNextJob('pause', true, 300))
        ->then(fn () => cache()->increment('then'))
        ->then(fn () => cache()->increment('then'))
        ->then(fn () => cache()->increment('then'))
        ->finally(fn () => cache()->increment('finally'))
        ->finally(fn () => cache()->increment('finally'))
        ->finally(fn () => cache()->increment('finally'))
        ->paused(fn () => cache()->increment('paused'))
        ->paused(fn () => cache()->increment('paused'))
        ->paused(fn () => cache()->increment('paused'))
        ->dispatch();

    travel(6)->minutes();

    $this->artisan('haystacks:resume');

    expect(cache()->get('then'))->toEqual(3);
    expect(cache()->get('finally'))->toEqual(3);
    expect(cache()->get('paused'))->toEqual(3);
});

test('multiple closures can be executed for unsuccessful haystacks', function () {
    Haystack::build()
        ->addJob(new FailJob)
        ->catch(fn () => cache()->increment('catch'))
        ->catch(fn () => cache()->increment('catch'))
        ->catch(fn () => cache()->increment('catch'))
        ->dispatch();

    expect(cache()->get('catch'))->toEqual(3);
});

test('the closures cannot receive the data if the option is disabled on a per builder instance', function () {
    Haystack::build()
        ->addJob(new SetDataJob('name', 'Sam'))
        ->addJob(new SetDataJob('friend', 'Steve'))
        ->addJob(new PauseNextJob('pause', true, 300))
        ->then(function ($data) {
            cache()->set('then', $data ?? 'empty');
        })
        ->finally(function ($data) {
            cache()->set('finally', $data ?? 'empty');
        })
        ->paused(function ($data) {
            cache()->set('paused', $data ?? 'empty');
        })
        ->dontReturnData()
        ->dispatch();

    travel(6)->minutes();

    $this->artisan('haystacks:resume');

    expect(cache()->get('then'))->toEqual('empty');
    expect(cache()->get('finally'))->toEqual('empty');
    expect(cache()->get('paused'))->toEqual('empty');
});

test('the closures will not receive the data if the option is enabled', function () {
    config()->set('haystack.return_all_haystack_data_when_finished', false);

    Haystack::build()
        ->addJob(new SetDataJob('name', 'Sam'))
        ->addJob(new SetDataJob('friend', 'Steve'))
        ->addJob(new PauseNextJob('pause', true, 300))
        ->then(function ($data) {
            cache()->set('then', $data ?? 'empty');
        })
        ->finally(function ($data) {
            cache()->set('finally', $data ?? 'empty');
        })
        ->paused(function ($data) {
            cache()->set('paused', $data ?? 'empty');
        })
        ->dispatch();

    travel(6)->minutes();

    $this->artisan('haystacks:resume');

    expect(cache()->get('then'))->toEqual('empty');
    expect(cache()->get('finally'))->toEqual('empty');
    expect(cache()->get('paused'))->toEqual('empty');
});

test('the haystack will fail if the job fails from an exception if automatic processing is turned on', function () {
    withAutomaticProcessing();
    withJobsTable();

    config()->set('queue.default', 'database');

    expect(cache()->has('failed'))->toBeFalse();

    $haystack = Haystack::build()
        ->addJob(new ExceptionJob)
        ->catch(function () {
            cache()->set('failed', true);
        })
        ->onConnection('database')
        ->create();

    $haystack->start();

    expect(DB::table('jobs')->count())->toEqual(1);

    $this->artisan('queue:work', ['--once' => true]);

    expect(DB::table('jobs')->count())->toEqual(0);

    expect(cache()->get('failed'))->toBeTrue();

    assertModelMissing($haystack);
});

test('the haystack will fail if the job is manually failed', function () {
    withAutomaticProcessing();

    expect(cache()->has('failed'))->toBeFalse();

    $haystack = Haystack::build()
        ->addJob(new NativeFailJob)
        ->catch(function () {
            cache()->set('failed', true);
        })
        ->create();

    $haystack->start();

    expect(cache()->get('failed'))->toBeTrue();

    assertModelMissing($haystack);
});

test('a haystack can be cancelled early and future jobs wont be processed', function () {
    withJobsTable();
    dontDeleteHaystack();

    config()->set('queue.default', 'database');

    $haystack = Haystack::build()
        ->addJob(new CacheJob('name', 'Sam'))
        ->finally(function () {
            cache()->set('finished', true);
        })
        ->onConnection('database')
        ->create();

    $haystack->start();

    expect(DB::table('jobs')->count())->toEqual(1);

    $haystack->cancel();

    expect(cache()->get('finished'))->toBeTrue();

    $this->artisan('queue:work', ['--once' => true]);

    expect(cache()->get('name'))->toBeNull();
});

test('haystacks have timestamps configured when you create the jobs', function () {
    Carbon::setTestNow('2022-09-29 20:37');

    $haystack = Haystack::build()
        ->addJob(new NameJob('Sam'))
        ->addJob(new NameJob('Gareth'))
        ->create();

    $bales = $haystack->bales()->get();

    expect($bales)->toHaveCount(2);

    expect($bales[0]->created_at)->toEqual(now());
    expect($bales[0]->updated_at)->toEqual(now());

    expect($bales[1]->created_at)->toEqual(now());
    expect($bales[1]->updated_at)->toEqual(now());
});

test('jobs that are added to the haystack after it has been created will have timestamps', function () {
    Carbon::setTestNow('2022-09-29 20:37');

    $haystack = Haystack::build()
        ->addJob(new NameJob('Sam'))
        ->create();

    $haystack->addJobs([
        new NameJob('Gareth'),
    ]);

    $bales = $haystack->bales()->get();

    expect($bales)->toHaveCount(2);

    expect($bales[0]->created_at)->toEqual(now());
    expect($bales[0]->updated_at)->toEqual(now());

    expect($bales[1]->created_at)->toEqual(now());
    expect($bales[1]->updated_at)->toEqual(now());
});

test('allow failures will not stop the job from processing if a job fails', function () {
    withJobsTable();
    dontDeleteHaystack();
    withAutomaticProcessing();

    config()->set('queue.default', 'database');

    $haystack = Haystack::build()
        ->addJob(new AutoCacheJob('name', 'Sam'))
        ->addJob(new ExceptionJob)
        ->addJob(new AutoCacheJob('friend', 'Steve'))
        ->allowFailures()
        ->dispatch();

    expect(DB::table('jobs')->count())->toEqual(1);

    $this->artisan('queue:work', ['--once' => true]);

    expect(DB::table('jobs')->count())->toEqual(1);

    expect(cache()->get('name'))->toEqual('Sam');

    $failedJobs = DB::table('failed_jobs')->get();

    expect($failedJobs)->toHaveCount(0);

    $this->artisan('queue:work', ['--once' => true]);

    expect(DB::table('jobs')->count())->toEqual(1);

    $failedJobs = DB::table('failed_jobs')->get();

    expect($failedJobs)->toHaveCount(1);

    expect($failedJobs[0]->exception)->toStartWith('Exception: Oh yee-naw! Something bad happened.');

    $haystack->refresh();

    expect($haystack->finished)->toBeFalse();

    $this->artisan('queue:work', ['--once' => true]);

    expect(DB::table('jobs')->count())->toEqual(0);

    expect(cache()->get('friend'))->toEqual('Steve');

    $bales = $haystack->bales()->get();

    expect($bales)->toHaveCount(0);
});

test('allow failures will not stop the job from processing if a job manually fails', function () {
    withJobsTable();
    dontDeleteHaystack();
    withAutomaticProcessing();

    config()->set('queue.default', 'database');
    config()->set('queue.failed.database', 'testing');

    $haystack = Haystack::build()
        ->addJob(new AutoCacheJob('name', 'Sam'))
        ->addJob(new ManuallyFailedJob)
        ->addJob(new AutoCacheJob('friend', 'Steve'))
        ->allowFailures()
        ->dispatch();

    expect(DB::table('jobs')->count())->toEqual(1);

    $this->artisan('queue:work', ['--once' => true]);

    expect(DB::table('jobs')->count())->toEqual(1);

    expect(cache()->get('name'))->toEqual('Sam');

    $this->artisan('queue:work', ['--once' => true]);

    expect(DB::table('jobs')->count())->toEqual(1);

    $haystack->refresh();

    expect($haystack->finished)->toBeFalse();

    $this->artisan('queue:work', ['--once' => true]);

    expect(DB::table('jobs')->count())->toEqual(0);

    expect(cache()->get('friend'))->toEqual('Steve');

    $bales = $haystack->bales()->get();

    expect($bales)->toHaveCount(0);
});

test('multiple middleware can be added in various ways and on every job', function () {
    Haystack::build()
        ->addJob(new NameJob('Sam'))
        ->addJob(new NameJob('Gareth'))
        ->addMiddleware([
            new CounterMiddleware,
            new CounterMiddleware,
        ])
        ->addMiddleware(new InvokableCounterMiddleware)
        ->addMiddleware(fn () => [new CounterMiddleware, new CounterMiddleware])
        ->addMiddleware(fn () => new CounterMiddleware)
        ->dispatch();

    // 6 middleware * 2 jobs = 12

    expect(cache()->get('count'))->toEqual(12);
});

test('invalid jobs are ignored as middleware', function () {
    Haystack::build()
        ->addJob(new NameJob('Sam'))
        ->addJob(new NameJob('Gareth'))
        ->addMiddleware([new CounterMiddleware])
        ->addMiddleware(['a', 'b', 'c'])
        ->addMiddleware(fn () => 'yo')
        ->addMiddleware(fn () => ['a', 'b'])
        ->dispatch();

    expect(cache()->get('count'))->toEqual(2);
});
