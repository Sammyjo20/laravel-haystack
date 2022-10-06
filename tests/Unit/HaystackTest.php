<?php

declare(strict_types=1);

use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Sammyjo20\LaravelHaystack\Data\NextJob;
use Sammyjo20\LaravelHaystack\Models\Haystack;
use Sammyjo20\LaravelHaystack\Models\HaystackBale;
use Sammyjo20\LaravelHaystack\Data\HaystackOptions;
use Illuminate\Support\Collection as BaseCollection;
use Laravel\SerializableClosure\SerializableClosure;
use Sammyjo20\LaravelHaystack\Data\CallbackCollection;
use Sammyjo20\LaravelHaystack\Builders\HaystackBuilder;
use Sammyjo20\LaravelHaystack\Middleware\CheckAttempts;
use Sammyjo20\LaravelHaystack\Middleware\CheckFinished;
use Sammyjo20\LaravelHaystack\Data\MiddlewareCollection;
use Sammyjo20\LaravelHaystack\Tests\Fixtures\Jobs\NameJob;
use Sammyjo20\LaravelHaystack\Middleware\IncrementAttempts;
use Sammyjo20\LaravelHaystack\Tests\Fixtures\DataObjects\Repository;
use Sammyjo20\LaravelHaystack\Tests\Fixtures\Callables\InvokableClass;
use Sammyjo20\LaravelHaystack\Tests\Fixtures\Callables\TravelMiddleware;

test('a haystack can have many haystack bales', function () {
    $samJob = new NameJob('Sam');
    $steveJob = new NameJob('Steve');
    $taylorJob = new NameJob('Taylor');

    $haystack = Haystack::factory()
        ->has(HaystackBale::factory()->state(['job' => $samJob]), 'bales')
        ->has(HaystackBale::factory()->state(['job' => $steveJob]), 'bales')
        ->has(HaystackBale::factory()->state(['job' => $taylorJob]), 'bales')
        ->create();

    expect($haystack)->toBeInstanceOf(Haystack::class);
    expect($haystack->bales()->count())->toEqual(3);

    $bales = $haystack->bales()->get();

    // This ensures that the order is correct too.

    expect($bales[0]->job)->toEqual($samJob);
    expect($bales[1]->job)->toEqual($steveJob);
    expect($bales[2]->job)->toEqual($taylorJob);

    // Check that the haystack row relates back to the haystack

    expect($bales[0]->haystack_id)->toEqual($haystack->getKey());
    expect($bales[1]->haystack_id)->toEqual($haystack->getKey());
    expect($bales[2]->haystack_id)->toEqual($haystack->getKey());

    expect($bales[0]->haystack)->toBeInstanceOf(Haystack::class);
    expect($bales[0]->haystack->getKey())->toEqual($haystack->getKey());
});

test('you can store a serialized closure on a haystack using the callbacks class', function () {
    $callbacks = new CallbackCollection;
    $callbacks->addThen(fn () => 'Then');
    $callbacks->addCatch(fn () => 'Catch');
    $callbacks->addFinally(fn () => 'Finally');
    $callbacks->addPaused(fn () => 'Paused');

    $middleware = new MiddlewareCollection;
    $middleware->add(fn () => []);

    $haystack = new Haystack;
    $haystack->callbacks = $callbacks;
    $haystack->middleware = $middleware;
    $haystack->options = new HaystackOptions;
    $haystack->save();

    $haystack->refresh();

    $rawCallbacks = $haystack->getRawOriginal('callbacks');
    $rawMiddleware = $haystack->getRawOriginal('middleware');

    $rawCallbacks = unserialize($rawCallbacks);
    $rawMiddleware = unserialize($rawMiddleware);

    expect($rawCallbacks)->toBeInstanceOf(CallbackCollection::class);

    expect($rawCallbacks->onThen)->toBeArray();
    expect($rawCallbacks->onCatch)->toBeArray();
    expect($rawCallbacks->onFinally)->toBeArray();
    expect($rawCallbacks->onPaused)->toBeArray();
    expect($rawMiddleware->data)->toBeArray();

    expect($rawCallbacks->onThen[0])->toBeInstanceOf(SerializableClosure::class);
    expect($rawCallbacks->onCatch[0])->toBeInstanceOf(SerializableClosure::class);
    expect($rawCallbacks->onFinally[0])->toBeInstanceOf(SerializableClosure::class);
    expect($rawCallbacks->onPaused[0])->toBeInstanceOf(SerializableClosure::class);
    expect($rawMiddleware->data[0])->toBeInstanceOf(SerializableClosure::class);

    expect(call_user_func($rawCallbacks->onThen[0]))->toEqual('Then');
    expect(call_user_func($rawCallbacks->onCatch[0]))->toEqual('Catch');
    expect(call_user_func($rawCallbacks->onFinally[0]))->toEqual('Finally');
    expect(call_user_func($rawCallbacks->onPaused[0]))->toEqual('Paused');
    expect(call_user_func($rawMiddleware->data[0]))->toEqual([]);

    // Check that you can make them nullable too.

    $haystack->callbacks = null;
    $haystack->middleware = null;
    $haystack->save();

    expect($haystack->callbacks)->toBeNull();
    expect($haystack->middleware)->toBeNull();
});

test('you can store an invokable class on a haystack callback', function () {
    $invokableClass = new InvokableClass();

    $callbacks = new CallbackCollection;
    $callbacks->addThen($invokableClass);

    $haystack = new Haystack;
    $haystack->options = new HaystackOptions;
    $haystack->callbacks = $callbacks;
    $haystack->save();

    $haystack->refresh();

    $rawCallbacks = $haystack->getRawOriginal('callbacks');
    $rawCallbacks = unserialize($rawCallbacks);

    expect($rawCallbacks->onThen[0])->toBeInstanceOf(SerializableClosure::class);
    expect(call_user_func($rawCallbacks->onThen[0]))->toEqual('Howdy!');
});

test('you cannot provide a non callable value to a haystack closure', function ($value) {
    $this->expectException(InvalidArgumentException::class);
    $this->expectExceptionMessage('Value provided must be an instance of Sammyjo20\LaravelHaystack\Data\CallbackCollection.');

    $haystack = new Haystack;
    $haystack->callbacks = $value;
    $haystack->save();
})->with([
    fn () => 'Hello',
    fn () => 123,
    fn () => true,
    fn () => (object) [1],
    fn () => [1],
]);

test('you must provide an invokable class if you do not provide a closure', function () {
    $this->expectException(InvalidArgumentException::class);
    $this->expectExceptionMessage('Value provided must be an instance of Sammyjo20\LaravelHaystack\Data\CallbackCollection.');

    function myFunction()
    {
        //
    }

    $haystack = new Haystack;
    $haystack->callbacks = 'myFunction';
    $haystack->save();
});

test('it throws an exception if you provide other values other than middleware collection into middleware', function () {
    $this->expectException(InvalidArgumentException::class);
    $this->expectExceptionMessage('Value provided must be an instance of Sammyjo20\LaravelHaystack\Data\MiddlewareCollection.');

    $haystack = new Haystack;
    $haystack->middleware = 'howdy';
    $haystack->save();
});

test('when a haystack has no jobs left and nextJob is called it is finished and is deleted', function () {
    $haystack = Haystack::factory()->create();

    expect(Haystack::all())->toHaveCount(1);

    $haystack->finish();

    expect(Haystack::all())->toHaveCount(0);
});

test('when a haystack fails it will delete itself and all bales', function () {
    $haystack = Haystack::factory()
        ->has(HaystackBale::factory()->state(['job' => new NameJob('Sam')]), 'bales')
        ->create();

    expect(Haystack::all())->toHaveCount(1);
    expect(HaystackBale::all())->toHaveCount(1);

    $haystack->fail();

    expect(Haystack::all())->toHaveCount(0);
    expect(HaystackBale::all())->toHaveCount(0);
});

test('you can instantiate a haystack builder from the model', function () {
    expect(Haystack::build())->toBeInstanceOf(HaystackBuilder::class);
});

test('started and finished properties are set by default', function () {
    $haystack = new Haystack;

    expect($haystack->started)->toBeFalse();
    expect($haystack->finished)->toBeFalse();
});

test('if you use the dispatchNextJob without starting the job it will start the job first', function () {
    $builder = new HaystackBuilder;

    $haystack = $builder->create();

    expect($haystack->started)->toBeFalse();
    expect($haystack->finished)->toBeFalse();

    $haystack->dispatchNextJob();

    expect($haystack->started)->toBeTrue();
    expect($haystack->finished)->toBeTrue();
});

test('you can get the next bale in the haystack', function () {
    $samJob = new NameJob('Sam');
    $steveJob = new NameJob('Steve');
    $taylorJob = new NameJob('Taylor');

    $haystack = Haystack::factory()
        ->has(HaystackBale::factory()->state(['job' => $samJob]), 'bales')
        ->has(HaystackBale::factory()->state(['job' => $steveJob]), 'bales')
        ->has(HaystackBale::factory()->state(['job' => $taylorJob]), 'bales')
        ->create();

    $bales = $haystack->bales()->get();

    expect($haystack->getNextJobRow())->toEqual($bales[0]);
    expect($haystack->getNextJob())->toBeInstanceOf(NextJob::class);

    $nextJob = $haystack->getNextJob();

    $job = $samJob
        ->setHaystack($haystack)
        ->setHaystackBaleId($bales[0]->getKey())
        ->setHaystackBaleAttempts(0);

    $job->middleware = [new CheckFinished, new CheckAttempts, new IncrementAttempts];

    expect($nextJob->job)->toEqual($job);
    expect($nextJob->haystackRow->getKey())->toEqual($bales[0]->getKey());
});

test('haystacks are deleted by default', function () {
    Haystack::build()
        ->addJob(new NameJob('Sam'))
        ->dispatch();

    expect(Haystack::query()->count())->toEqual(0);
});

test('haystacks can be preserved', function () {
    dontDeleteHaystack();

    Haystack::build()
        ->addJob(new NameJob('Sam'))
        ->dispatch();

    expect(Haystack::query()->count())->toEqual(1);
});

test('a haystack has a started at and finished at date', function () {
    dontDeleteHaystack();

    Carbon::setTestNow('2022-01-01 09:00');

    $startDate = now()->toImmutable();

    $haystack = Haystack::build()
        ->addJob(new NameJob('Sam'))
        ->addMiddleware([
            new TravelMiddleware,
        ])
        ->create();

    $haystack->refresh();

    expect($haystack->started_at)->toBeNull();

    $haystack->start();

    $haystack->refresh();

    $endDate = now();

    expect($haystack->started_at)->toEqual($startDate);
    expect($haystack->finished_at)->toEqual($endDate);
});

test('you can add and get data from the haystack', function () {
    $haystack = Haystack::factory()->create();

    expect($haystack->data()->count())->toEqual(0);

    $haystack = $haystack->setData('name', 'Sam');

    expect($haystack->data()->count())->toEqual(1);

    $name = $haystack->getData('name');

    expect($name)->toEqual('Sam');

    $otherName = $haystack->getData('otherName');

    expect($otherName)->toBeNull();

    $age = $haystack->getData('age', 22);

    expect($age)->toEqual(22);

    $allData = $haystack->allData();

    expect($allData)->toBeInstanceOf(Collection::class);
    expect($allData)->toHaveCount(1);
    expect($allData['name'])->toEqual('Sam');
});

test('setting the data with the same key twice will overwrite it', function () {
    $haystack = Haystack::factory()->create();

    expect($haystack->data()->count())->toEqual(0);

    $haystack = $haystack->setData('name', 'Sam');
    $name = $haystack->getData('name');

    expect($name)->toEqual('Sam');

    $haystack = $haystack->setData('name', 'Leon');
    $name = $haystack->getData('name');

    expect($name)->toEqual('Leon');
});

test('you can add data with a cast', function () {
    $haystack = Haystack::factory()->create();

    expect($haystack->data()->count())->toEqual(0);

    $repository = ['name' => 'Saloon', 'stars' => 700, 'isLaravel' => false];

    $haystack->setData('repository', $repository, 'collection');

    $data = $haystack->getData('repository');

    expect($data)->toBeInstanceOf(BaseCollection::class);
    expect($data['name'])->toEqual('Saloon');
    expect($data['stars'])->toEqual(700);
    expect($data['isLaravel'])->toEqual(false);
});

test('it throws an exception if you try to enter a non string data without adding a cast', function () {
    $haystack = Haystack::factory()->create();

    expect($haystack->data()->count())->toEqual(0);

    $repository = ['name' => 'Saloon', 'stars' => 700, 'isLaravel' => false];

    $haystack->setData('name', 'Sam');

    $haystack->setData('age', 22);

    $this->expectException(InvalidArgumentException::class);
    $this->expectExceptionMessage('You must specify a cast if the value is not a string or integer.');

    $haystack->setData('repository', $repository);
});

test('you can get all the data on a haystack at once', function () {
    $haystack = Haystack::factory()->create();

    $repository = new Repository(name: 'Saloon', stars: 700, isLaravel: false);

    $haystack->setData('name', 'Sam');
    $haystack->setData('data', ['name' => 'Sam', 'work' => 'Plannr Technologies'], 'array');
    $haystack->setData('age', 21, 'integer');
    $haystack->setData('repository', $repository, Repository::class);

    expect($haystack->data()->count())->toEqual(4);

    $all = $haystack->allData();

    expect($all)->toEqual(new Collection([
        'name' => 'Sam',
        'data' => ['name' => 'Sam', 'work' => 'Plannr Technologies'],
        'age' => 21,
        'repository' => $repository,
    ]));
});
