<?php

namespace Sammyjo20\LaravelHaystack\Tests\Fixtures\Jobs;

use Exception;
use Illuminate\Bus\Queueable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Sammyjo20\LaravelHaystack\Concerns\Stackable;
use Sammyjo20\LaravelHaystack\Contracts\StackableJob;

class ExceptionJob implements ShouldQueue, StackableJob
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels, Stackable;

    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct(public int $tries = 1)
    {
        //
    }

    /**
     * Execute the job.
     *
     * @return void
     *
     * @throws Exception
     */
    public function handle()
    {
        throw new Exception('Oh yee-naw! Something bad happened.');
    }
}
