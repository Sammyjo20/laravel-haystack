<?php

use Laravel\SerializableClosure\SerializableClosure;
use Sammyjo20\LaravelHaystack\Builders\HaystackBuilder;
use Sammyjo20\LaravelHaystack\Models\Haystack;
use Sammyjo20\LaravelHaystack\Models\HaystackBale;
use Sammyjo20\LaravelHaystack\Tests\Fixtures\Callables\InvokableClass;
use Sammyjo20\LaravelHaystack\Tests\Fixtures\Jobs\NameJob;

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
    fn () => (object)[1],
    fn () => [1],
]);

test('you must provide an invokable class if you do not provide a closure', function () {
    $this->expectException(InvalidArgumentException::class);
    $this->expectExceptionMessage('Callable value provided must be an invokable class.');

    function myFunction() {
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
