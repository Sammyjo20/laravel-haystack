<?php

use Sammyjo20\LaravelHaystack\Models\Haystack;
use Sammyjo20\LaravelHaystack\Models\HaystackBale;
use Sammyjo20\LaravelHaystack\Tests\Fixtures\Callables\Middleware;
use Sammyjo20\LaravelHaystack\Tests\Fixtures\Jobs\NameJob;

it('works', function () {
    $haystack = Haystack::build()
        ->addBale(new NameJob('Sam'))
        ->addBale(new NameJob('Charlotte'))
        ->addJob(new NameJob('Gareth'))
        ->withMiddleware([
            new Middleware(),
        ])
        ->then(function () {
            ray('Finished!')->green();
        })
        ->catch(function () {
            ray('Failed!')->red();
        })
        ->finally(function () {
            ray('I always happen');
        })
        ->dispatch();

    dd($haystack);
});

test('a haystack can be created with jobs', function () {
    $haystack = Haystack::build()
        ->addJob(new NameJob('Sam'))
        ->addJob(new NameJob('Gareth'))
        ->create();

    expect($haystack)->toBeInstanceOf(Haystack::class);

    $haystackBales = $haystack->bales()->get();

    expect($haystackBales)->toHaveCount(2);
    expect($haystackBales[0])->toBeInstanceOf(HaystackBale::class);
    expect($haystackBales[1])->toBeInstanceOf(HaystackBale::class);

    expect($haystackBales[0]->job)->toEqual(new NameJob('Sam'));
    expect($haystackBales[1]->job)->toEqual(new NameJob('Gareth'));
});

test('a haystack can be created with default delay, queue and connection', function () {

});

test('a haystack can be created with middleware', function () {

});

test('a haystack job can have their own delay, queue and connection', function () {

});

test('a haystack can be created with everything', function () {

});

test('a haystack can be dispatched straight away', function () {

});
