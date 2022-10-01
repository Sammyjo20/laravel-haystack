<?php

declare(strict_types=1);

namespace Sammyjo20\LaravelHaystack\Tests\Fixtures\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Sammyjo20\LaravelHaystack\Concerns\Stackable;
use Sammyjo20\LaravelHaystack\Contracts\StackableJob;
use Sammyjo20\LaravelHaystack\Tests\Exceptions\StackableException;
use Sammyjo20\LaravelHaystack\Tests\Fixtures\Models\CountrySinger;

class CountrySingerJob implements ShouldQueue, StackableJob
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels, Stackable;

    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct(public string $key)
    {
        //
    }

    /**
     * Execute the job.
     *
     * @return void
     *
     * @throws StackableException
     */
    public function handle()
    {
        $model = $this->getHaystackModel($this->key);

        cache()->put('singer', $model->name);

        $this->nextJob();
    }
}
