<?php

declare(strict_types=1);

use Sammyjo20\LaravelHaystack\Models\Haystack;
use Sammyjo20\LaravelHaystack\Tests\Fixtures\Jobs\NameJob;

it('will serialize a job on a haystack bale', function () {
    $job = new NameJob('Sam');

    $haystack = Haystack::factory()->create();

    $haystackBale = $haystack->bales()->make();
    $haystackBale->job = $job;
    $haystackBale->save();

    $haystackBale->refresh();

    $rawJob = $haystackBale->getRawOriginal('job');

    expect(unserialize($rawJob))->toEqual($job);
    expect($haystackBale->job)->toEqual($job);
});

test('a haystack row can store the connection queue and a delay', function () {
    $haystack = Haystack::factory()->create();

    $haystackBale = $haystack->bales()->make();
    $haystackBale->job = new NameJob('Sam');
    $haystackBale->delay = 60;
    $haystackBale->on_queue = 'haystack';
    $haystackBale->on_connection = 'database';
    $haystackBale->save();

    expect($haystackBale->delay)->toEqual(60);
    expect($haystackBale->on_queue)->toEqual('haystack');
    expect($haystackBale->on_connection)->toEqual('database');

    // Test that when retrieving the job, it will have all the options applied

    $job = $haystackBale->configuredJob();

    expect($job->delay)->toEqual(60);
    expect($job->queue)->toEqual('haystack');
    expect($job->connection)->toEqual('database');
});
