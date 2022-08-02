<?php

namespace Sammyjo20\LaravelHaystack\Tests\Fixtures\Jobs;

use Dflydev\DotAccessData\Data;
use Illuminate\Bus\Queueable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Sammyjo20\LaravelHaystack\Concerns\Stackable;
use Sammyjo20\LaravelHaystack\Contracts\StackableJob;

class GetAllAndCacheDataJob implements ShouldQueue, StackableJob
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels, Stackable;

    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct()
    {
        //
    }

    /**
     * Execute the job.
     *
     * @return void
     * @throws \Sammyjo20\LaravelHaystack\Tests\Exceptions\StackableException
     */
    public function handle()
    {
        $data = $this->allHaystackData();

        cache()->set('all', $data);

        $this->nextBale(); // Alias of next job
    }
}
