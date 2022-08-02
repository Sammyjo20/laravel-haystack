<?php

use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Sammyjo20\LaravelHaystack\Data\NextJob;
use Sammyjo20\LaravelHaystack\Models\Haystack;
use Sammyjo20\LaravelHaystack\Models\HaystackBale;
use Illuminate\Support\Collection as BaseCollection;
use Laravel\SerializableClosure\SerializableClosure;
use Sammyjo20\LaravelHaystack\Builders\HaystackBuilder;
use Sammyjo20\LaravelHaystack\Tests\Fixtures\Jobs\NameJob;
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

test('you can store a serialized closure on a haystack', function () {
    $thenClosure = fn () => 'Then';
    $catchClosure = fn () => 'Catch';
    $finallyClosure = fn () => 'Finally';
    $middlewareClosure = fn () => [];

    $haystack = new Haystack;
    $haystack->on_then = $thenClosure;
    $haystack->on_catch = $catchClosure;
    $haystack->on_finally = $finallyClosure;
    $haystack->middleware = $middlewareClosure;
    $haystack->save();

    $haystack->refresh();

    $rawThen = $haystack->getRawOriginal('on_then');
    $rawCatch = $haystack->getRawOriginal('on_catch');
    $rawFinally = $haystack->getRawOriginal('on_finally');
    $rawMiddleware = $haystack->getRawOriginal('middleware');

    expect(unserialize($rawThen))->toBeInstanceOf(SerializableClosure::class);
    expect(unserialize($rawCatch))->toBeInstanceOf(SerializableClosure::class);
    expect(unserialize($rawFinally))->toBeInstanceOf(SerializableClosure::class);
    expect(unserialize($rawMiddleware))->toBeInstanceOf(SerializableClosure::class);

    expect($haystack->on_then)->toBeInstanceOf(Closure::class);
    expect($haystack->on_catch)->toBeInstanceOf(Closure::class);
    expect($haystack->on_finally)->toBeInstanceOf(Closure::class);
    expect($haystack->middleware)->toBeInstanceOf(Closure::class);

    expect(call_user_func($haystack->on_then))->toEqual('Then');
    expect(call_user_func($haystack->on_catch))->toEqual('Catch');
    expect(call_user_func($haystack->on_finally))->toEqual('Finally');
    expect(call_user_func($haystack->middleware))->toEqual([]);

    // Check that you can make them nullable too.

    $haystack->on_then = null;
    $haystack->on_catch = null;
    $haystack->on_finally = null;
    $haystack->middleware = null;
    $haystack->save();

    expect($haystack->on_then)->toBeNull();
    expect($haystack->on_catch)->toBeNull();
    expect($haystack->on_finally)->toBeNull();
    expect($haystack->middleware)->toBeNull();
});

test('you can store an invokable class on a haystack', function () {
    $invokableClass = new InvokableClass();

    $haystack = new Haystack;
    $haystack->on_then = $invokableClass;
    $haystack->save();

    $haystack->refresh();

    $rawThen = $haystack->getRawOriginal('on_then');

    expect(unserialize($rawThen))->toBeInstanceOf(SerializableClosure::class);
    expect($haystack->on_then)->toBeInstanceOf(Closure::class);
    expect(call_user_func($haystack->on_then))->toEqual('Howdy!');
});

test('you cannot provide a non callable value to a haystack closure', function ($value) {
    $this->expectException(InvalidArgumentException::class);
    $this->expectExceptionMessage('Value provided must be a closure or an invokable class.');

    $haystack = new Haystack;
    $haystack->on_then = $value;
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
    $this->expectExceptionMessage('Callable value provided must be an invokable class.');

    function myFunction()
    {
        //
    }

    $haystack = new Haystack;
    $haystack->on_then = 'myFunction';
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

    expect($nextJob->job)->toEqual($samJob->setHaystack($haystack)->setHaystackBaleId($bales[0]->getKey()));
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
        ->withMiddleware([
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
