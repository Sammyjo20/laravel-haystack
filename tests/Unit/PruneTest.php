<?php

declare(strict_types=1);

use Illuminate\Support\Carbon;

use function Pest\Laravel\assertModelExists;
use function Pest\Laravel\assertModelMissing;

use Sammyjo20\LaravelHaystack\Models\Haystack;

test('can correctly prune stale haystacks', function () {
    Carbon::setTestNow('2022-01-01 09:00');

    config()->set('haystack.keep_stale_haystacks_for_days', 5);
    config()->set('haystack.keep_finished_haystacks_for_days', 15);

    $now = now()->toImmutable();

    $a = Haystack::factory()->create(['started_at' => $now->subDays(1)]);
    $b = Haystack::factory()->create(['started_at' => $now->subDays(3)]);
    $c = Haystack::factory()->create(['started_at' => $now->subDays(5)]);
    $d = Haystack::factory()->create(['started_at' => $now->subDays(10)]);
    $e = Haystack::factory()->create(['started_at' => $now->subDays(10), 'finished_at' => $now->subDays(10)]); // This should now be deleted

    expect(Haystack::count())->toEqual(5);

    $this->artisan('model:prune', [
        '--model' => Haystack::class,
    ]);

    assertModelExists($a);
    assertModelExists($b);
    assertModelExists($e);

    assertModelMissing($c);
    assertModelMissing($d);
});

test('can correctly prune old finished haystacks', function () {
    Carbon::setTestNow('2022-01-01 09:00');

    config()->set('haystack.keep_stale_haystacks_for_days', 15);
    config()->set('haystack.keep_finished_haystacks_for_days', 5);

    $now = now()->toImmutable();

    $a = Haystack::factory()->create(['started_at' => $now->subDays(1), 'finished_at' => $now->subDays(1)]);
    $b = Haystack::factory()->create(['started_at' => $now->subDays(3), 'finished_at' => $now->subDays(3)]);
    $c = Haystack::factory()->create(['started_at' => $now->subDays(5), 'finished_at' => $now->subDays(5)]);
    $d = Haystack::factory()->create(['started_at' => $now->subDays(10), 'finished_at' => $now->subDays(10)]);
    $e = Haystack::factory()->create(['started_at' => $now->subDays(10)]);

    expect(Haystack::count())->toEqual(5);

    $this->artisan('model:prune', [
        '--model' => Haystack::class,
    ]);

    assertModelExists($a);
    assertModelExists($b);
    assertModelExists($e);

    assertModelMissing($c);
    assertModelMissing($d);
});
