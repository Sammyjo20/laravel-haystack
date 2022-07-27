<?php

use Sammyjo20\LaravelHaystack\Models\Haystack;
use Sammyjo20\LaravelHaystack\Tests\Fixtures\Jobs\NameJob;

it('will serialize a job on a haystack row', function () {
    $job = new NameJob('Sam');

    $haystack = Haystack::factory()->create();

    $haystackRow = $haystack->rows()->make();
    $haystackRow->job = $job;
    $haystackRow->save();

    $haystackRow->refresh();

    $rawJob = $haystackRow->getRawOriginal('job');

    expect(unserialize($rawJob))->toEqual($job);
    expect($haystackRow->job)->toEqual($job);
});

test('a haystack row can store the connection queue and a delay', function () {
    $haystack = Haystack::factory()->create();

    $haystackRow = $haystack->rows()->make();
    $haystackRow->job = new NameJob('Sam');
    $haystackRow->delay = 60;
    $haystackRow->on_queue = 'jobStacks';
    $haystackRow->on_connection = 'database';
    $haystackRow->save();

    expect($haystackRow->delay)->toEqual(60);
    expect($haystackRow->on_queue)->toEqual('jobStacks');
    expect($haystackRow->on_connection)->toEqual('database');

    // Test that when retrieving the job, it will have all the options applied

    $job = $haystackRow->configuredJob();

    expect($job->delay)->toEqual(60);
    expect($job->queue)->toEqual('jobStacks');
    expect($job->connection)->toEqual('database');
});
