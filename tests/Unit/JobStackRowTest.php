<?php

use Sammyjo20\LaravelJobStack\Models\JobStack;
use Sammyjo20\LaravelJobStack\Tests\Fixtures\Jobs\NameJob;

it('will serialize a job on a job stack row', function () {
    $job = new NameJob('Sam');

    $jobStack = JobStack::factory()->create();

    $jobStackRow = $jobStack->rows()->make();
    $jobStackRow->job = $job;
    $jobStackRow->save();

    $jobStackRow->refresh();

    $rawJob = $jobStackRow->getRawOriginal('job');

    expect(unserialize($rawJob))->toEqual($job);
    expect($jobStackRow->job)->toEqual($job);
});

test('a job stack row can store the connection queue and a delay', function () {
    $jobStack = JobStack::factory()->create();

    $jobStackRow = $jobStack->rows()->make();
    $jobStackRow->job = new NameJob('Sam');
    $jobStackRow->delay = 60;
    $jobStackRow->on_queue = 'jobStacks';
    $jobStackRow->on_connection = 'database';
    $jobStackRow->save();

    expect($jobStackRow->delay)->toEqual(60);
    expect($jobStackRow->on_queue)->toEqual('jobStacks');
    expect($jobStackRow->on_connection)->toEqual('database');

    // Test that when retrieving the job, it will have all the options applied

    $job = $jobStackRow->configuredJob();

    expect($job->delay)->toEqual(60);
    expect($job->queue)->toEqual('jobStacks');
    expect($job->connection)->toEqual('database');
});
