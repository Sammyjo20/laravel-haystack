<?php

use Sammyjo20\ChunkableJobs\Chunk;
use Sammyjo20\LaravelHaystack\Models\Haystack;
use Sammyjo20\LaravelHaystack\Tests\Fixtures\Jobs\ChunkableJob;

test('a chunkable haystack job can dispatch multiple times', function () {
    withAutomaticProcessing();

    Haystack::build()
        ->addJob(new ChunkableJob)
        ->dispatch();

    $jobOne = cache()->get('1');
    $jobTwo = cache()->get('2');
    $jobThree = cache()->get('3');

    expect($jobOne)->toBeInstanceOf(Chunk::class);
    expect($jobTwo)->toBeInstanceOf(Chunk::class);
    expect($jobThree)->toBeInstanceOf(Chunk::class);

    expect($jobOne->position)->toEqual(1);
    expect($jobTwo->position)->toEqual(2);
    expect($jobThree->position)->toEqual(3);
});

test('a chunkable haystack job can be dispatched on its own', function () {
    ChunkableJob::dispatch();

    $jobOne = cache()->get('1');
    $jobTwo = cache()->get('2');
    $jobThree = cache()->get('3');

    expect($jobOne)->toBeInstanceOf(Chunk::class);
    expect($jobTwo)->toBeInstanceOf(Chunk::class);
    expect($jobThree)->toBeInstanceOf(Chunk::class);

    expect($jobOne->position)->toEqual(1);
    expect($jobTwo->position)->toEqual(2);
    expect($jobThree->position)->toEqual(3);
});
