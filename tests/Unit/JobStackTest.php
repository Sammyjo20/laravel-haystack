<?php

use Laravel\SerializableClosure\SerializableClosure;
use Sammyjo20\LaravelJobStack\Builders\JobStackBuilder;
use Sammyjo20\LaravelJobStack\Models\JobStack;
use Sammyjo20\LaravelJobStack\Models\JobStackRow;
use Sammyjo20\LaravelJobStack\Tests\Fixtures\Callables\InvokableClass;
use Sammyjo20\LaravelJobStack\Tests\Fixtures\Jobs\NameJob;

test('a job stack can have many job stack rows', function () {
    $samJob = new NameJob('Sam');
    $steveJob = new NameJob('Steve');
    $taylorJob = new NameJob('Taylor');

    $jobStack = JobStack::factory()
        ->has(JobStackRow::factory()->state(['job' => $samJob]), 'rows')
        ->has(JobStackRow::factory()->state(['job' => $steveJob]), 'rows')
        ->has(JobStackRow::factory()->state(['job' => $taylorJob]), 'rows')
        ->create();

    expect($jobStack)->toBeInstanceOf(JobStack::class);
    expect($jobStack->rows()->count())->toEqual(3);

    $rows = $jobStack->rows()->get();

    // This ensures that the order is correct too.

    expect($rows[0]->job)->toEqual($samJob);
    expect($rows[1]->job)->toEqual($steveJob);
    expect($rows[2]->job)->toEqual($taylorJob);

    // Check that the job stack row relates back to the job stack

    expect($rows[0]->job_stack_id)->toEqual($jobStack->getKey());
    expect($rows[1]->job_stack_id)->toEqual($jobStack->getKey());
    expect($rows[2]->job_stack_id)->toEqual($jobStack->getKey());

    expect($rows[0]->jobStack)->toBeInstanceOf(JobStack::class);
    expect($rows[0]->jobStack->getKey())->toEqual($jobStack->getKey());
});

test('you can store a serialized closure on a job stack', function () {
    $thenClosure = fn () => 'Then';
    $catchClosure = fn () => 'Catch';
    $finallyClosure = fn () => 'Finally';
    $middlewareClosure = fn () => [];

    $jobStack = new JobStack;
    $jobStack->on_then = $thenClosure;
    $jobStack->on_catch = $catchClosure;
    $jobStack->on_finally = $finallyClosure;
    $jobStack->middleware = $middlewareClosure;
    $jobStack->save();

    $jobStack->refresh();

    $rawThen = $jobStack->getRawOriginal('on_then');
    $rawCatch = $jobStack->getRawOriginal('on_catch');
    $rawFinally = $jobStack->getRawOriginal('on_finally');
    $rawMiddleware = $jobStack->getRawOriginal('middleware');

    expect(unserialize($rawThen))->toBeInstanceOf(SerializableClosure::class);
    expect(unserialize($rawCatch))->toBeInstanceOf(SerializableClosure::class);
    expect(unserialize($rawFinally))->toBeInstanceOf(SerializableClosure::class);
    expect(unserialize($rawMiddleware))->toBeInstanceOf(SerializableClosure::class);

    expect($jobStack->on_then)->toBeInstanceOf(Closure::class);
    expect($jobStack->on_catch)->toBeInstanceOf(Closure::class);
    expect($jobStack->on_finally)->toBeInstanceOf(Closure::class);
    expect($jobStack->middleware)->toBeInstanceOf(Closure::class);

    expect(call_user_func($jobStack->on_then))->toEqual('Then');
    expect(call_user_func($jobStack->on_catch))->toEqual('Catch');
    expect(call_user_func($jobStack->on_finally))->toEqual('Finally');
    expect(call_user_func($jobStack->middleware))->toEqual([]);
});

test('you can store an invokable class on a job stack', function () {
    $invokableClass = new InvokableClass();

    $jobStack = new JobStack;
    $jobStack->on_then = $invokableClass;
    $jobStack->save();

    $jobStack->refresh();

    $rawThen = $jobStack->getRawOriginal('on_then');

    expect(unserialize($rawThen))->toBeInstanceOf(SerializableClosure::class);
    expect($jobStack->on_then)->toBeInstanceOf(Closure::class);
    expect(call_user_func($jobStack->on_then))->toEqual('Howdy!');
});

test('you cannot provide a non callable value to a job stack closure', function ($value) {
    $this->expectException(InvalidArgumentException::class);
    $this->expectExceptionMessage('Value provided must be a closure or an invokable class.');

    $jobStack = new JobStack;
    $jobStack->on_then = $value;
    $jobStack->save();
})->with([
    fn () => 'Hello',
    fn () => 123,
    fn () => true,
    fn () => (object)[1],
    fn () => [1],
]);

test('you must provide an invokable class if you do not provide a closure', function () {
    $this->expectException(InvalidArgumentException::class);
    $this->expectExceptionMessage('Callable value provided must be an invokable class.');

    function myFunction() {
        //
    }

    $jobStack = new JobStack;
    $jobStack->on_then = 'myFunction';
    $jobStack->save();
});

test('when a job stack has no jobs left and nextJob is called it is finished and is deleted', function () {
    $jobStack = JobStack::factory()->create();

    expect(JobStack::all())->toHaveCount(1);

    $jobStack->finish();

    expect(JobStack::all())->toHaveCount(0);
});

test('when a job stack fails it will delete itself and all other rows', function () {
    $jobStack = JobStack::factory()
        ->has(JobStackRow::factory()->state(['job' => new NameJob('Sam')]), 'rows')
        ->create();

    expect(JobStack::all())->toHaveCount(1);
    expect(JobStackRow::all())->toHaveCount(1);

    $jobStack->fail();

    expect(JobStack::all())->toHaveCount(0);
    expect(JobStackRow::all())->toHaveCount(0);
});

test('you can instantiate a job stack builder from the model', function () {
    expect(JobStack::build())->toBeInstanceOf(JobStackBuilder::class);
});

test('started and finished properties are set by default', function () {
    $jobStack = new JobStack;

    expect($jobStack->started)->toBeFalse();
    expect($jobStack->finished)->toBeFalse();
});
