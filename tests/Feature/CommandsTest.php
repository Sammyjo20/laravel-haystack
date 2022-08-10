<?php

use Illuminate\Support\Collection;
use Sammyjo20\LaravelHaystack\Models\Haystack;
use Sammyjo20\LaravelHaystack\Tests\Fixtures\Jobs\NameJob;
use function Pest\Laravel\artisan;

it('can delete a haystack by ID', function () {
    $haystack = Haystack::build()
        ->addJob(new NameJob('Sam'))
        ->addJob(new NameJob('Steve'))
        ->create();

    $this->assertDatabaseHas('haystacks', ['id' => $haystack->id]);
    $this->assertDatabaseHas('haystack_bales', ['haystack_id' => $haystack->id]);

    artisan('haystacks:forget', ['id' => $haystack->id]);

    $this->assertDatabaseMissing('haystacks', ['id' => $haystack->id]);
    $this->assertDatabaseMissing('haystack_bales', ['haystack_id' => $haystack->id]);
});

it('can clear all haystacks', function () {
    Collection::times(10, fn () => Haystack::build()
        ->addJob(new NameJob('Sam'))
        ->addJob(new NameJob('Steve'))
        ->create());

    $this->assertDatabaseCount('haystacks', 10);
    $this->assertDatabaseCount('haystack_bales', 20);

    artisan('haystacks:clear');

    $this->assertDatabaseCount('haystacks', 0);
    $this->assertDatabaseCount('haystack_bales', 0);
});
