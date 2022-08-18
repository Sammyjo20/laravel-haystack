<?php

declare(strict_types=1);

namespace Sammyjo20\LaravelHaystack\Tests\Fixtures\Jobs;

use Carbon\CarbonInterface;
use Illuminate\Bus\Queueable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Sammyjo20\LaravelHaystack\Concerns\Stackable;
use Sammyjo20\LaravelHaystack\Contracts\StackableJob;

class AutoPauseNextJob implements ShouldQueue, StackableJob
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels, Stackable;

    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct(public string $key, public string $value, public int|CarbonInterface $pause)
    {
        //
    }

    /**
     * Execute the job.
     *
     * @return void
     *
     * @throws \Sammyjo20\LaravelHaystack\Tests\Exceptions\StackableException
     */
    public function handle()
    {
        cache()->put($this->key, $this->value);

        $this->pauseHaystack(300);
    }
}
