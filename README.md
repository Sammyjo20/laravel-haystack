<div align="center">
    
<img src="https://user-images.githubusercontent.com/29132017/181642184-e95e6214-2ff0-4a32-985e-938432b7b3f5.jpeg" width="250">

# Laravel Haystack
Beautifully simple but powerful database-driven job chains.

![Build Status](https://github.com/sammyjo20/laravel-haystack/actions/workflows/tests.yml/badge.svg)

</div>

## Introduction
Laravel Haystack is a package that allows you to have a job chain stored in the database. Since all of the jobs in the chain are in the database, memory usage is low and you can delay jobs for a long time or have long running jobs without risking using all your memory. Laravel Haystack supports every queue connection/worker out of the box. (Database, Redis/Horizon, SQS).

```php
$haystack = Haystack::build()
   ->addJob(new RecordPodcast)
   ->addJob(new ProcessPodcast)
   ->addJob(new PublishPodcast)
   ->then(function () {
      // Haystack completed
   })
   ->catch(function () {
      // Haystack failed
   })
   ->finally(function () {
      // Always run either on success or fail.
   })
   ->paused(function () {
      // Run if the haystack is paused
   })
   ->withMiddleware([
      // Middleware to apply on every job
   ])
   ->withDelay(60) // Add a delay to every job
   ->dispatch();
```

### But doesn't Laravel already have job chains?
That's right! Let's just be clear that we're not talking about **Batched Jobs**. Laravel does have job chains but they have some considerations.

- They consume quite a lot of memory/data since the chain is stored inside the job. This is especially true if you are storing thousands of jobs.
- They are volatile, meaning if you lose one job in the chain - you lose the whole chain.
- They do not provide the `then`, `catch`, `finally` callable methods that batched jobs do.
- Long delays with memory based or SQS queue is not possible as you could lose the jobs due to expiry or if the server shuts down.
- You can't share data between jobs as there is no "state" across the chain

Laravel Haystack aims to solve this by storing the job chain in the database and queuing one job at a time. When the job is completed, Laravel Haystack listens out for the "job completed" event and queues the next job in the chain from the database.

### Laravel Haystack Features
- Low memory consumption as one job is processed at a time and the chain is stored in the database
- You can delay/release jobs for as long as you want since it will use the scheduler to restart a chain. Even if your queue driver is SQS!
- It provides callback methods like `then`, `catch` and `finally`.
- Global middleware that can be applied to every single job in the chain
- Delay that can be added to every job in the chain
- You can store and retrieve data/state that is accessible to every job in the chain.
- You can store the model for later processing.

### Use Cases
- If you need to make hundreds or thousands of API calls in a row, can be combined with Spatie's Job Rate Limiter to keep track of delays and pause jobs when a rate limit is hit.
- If you need to queue thousands of jobs in a chain at a time.
- If you need to batch import rows of data - each row can be a haystack job (bale) and processed one at a time. While keeping important job information stored in the database. 
- If you need "release" times longer than 15 minutes if you are using Amazon SQS

## Installation

You can install the package via composer:

```bash
composer require sammyjo20/laravel-haystack
```
> Requires Laravel 8+ and PHP 8.1

Then publish and run the migrations with:

```bash
php artisan vendor:publish --tag="haystack-migrations"
php artisan migrate
```

You can publish the config file with:

```bash
php artisan vendor:publish --tag="haystack-config"
```

## Getting Started

### Configuring Jobs

To prepare your jobs for Laravel Haystack, you must add the **StackableJob** interface and **Stackable** trait to your jobs. This provides the properties and methods Haystack requires.

```php
<?php
 
namespace App\Jobs;
 
use Sammyjo20\LaravelHaystack\Contracts\StackableJob;
use Sammyjo20\LaravelHaystack\Concerns\Stackable;
 
class ProcessPodcast implements ShouldQueue, StackableJob
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels, Stackable
```

### Building Haystacks

Let’s create our first Haystack. To get started, import the Haystack model and then call the “build” static function on it. This will provide you with the **HaystackBuilder** class which can be used to define your haystack. You can then use the “addJob” method to add jobs to the Haystack. Make sure all the jobs have the **StackableJob** interface and **Stackable** trait.

```php
<?php

use Sammyjo20\LaravelHaystack\Models\Haystack;

Haystack::build()
   ->addJob(new RecordPodcast)
   ->addJob(new PublishPodcast)
   ->addJob(new TweetAboutPodcast);
```

### Dispatching Haystacks

Once you have created your Haystack, you can dispatch it using the `dispatch` method.

```php
<?php

use Sammyjo20\LaravelHaystack\Models\Haystack;

Haystack::build()
   ->addJob(new RecordPodcast)
   ->addJob(new PublishPodcast)
   ->addJob(new TweetAboutPodcast)
   ->dispatch();
```

### Creating Haystacks For Later

Use the create method to create the model and store it somewhere for later processing. You can start the Haystack at any time.

```php
<?php

use Sammyjo20\LaravelHaystack\Models\Haystack;

$haystack = Haystack::build()
   ->addJob(new RecordPodcast)
   ->addJob(new PublishPodcast)
   ->addJob(new TweetAboutPodcast)
   ->create();

// Do other things...

$haystack->start(); // Initiate haystack
```

## Callback Events

Laravel Haystack provides you with three really simple callback events that will be serialized and used at certain points in time.

### Then

The “then” event is triggered when the haystack has completed successfully.

```php
$haystack = Haystack::build()
   ->addJob(new RecordPodcast)
   ->then(function () {
       // Do something... 
   })
   ->dispatch();
```

### Catch

The “catch” event is triggered when the haystack has failed. You will still have the failed job to see what the error was, but this is useful if you need to perform any cleanup.

```php
$haystack = Haystack::build()
   ->addJob(new RecordPodcast)
   ->catch(function () {
       // Do something... 
   })
   ->dispatch();
```

### Finally

The “finally “event is always triggered at the end of a haystack, if it was successful or if it failed. This is very useful if you want to have loading states in your application.

```php
$haystack = Haystack::build()
   ->addJob(new RecordPodcast)
   ->finally(function () {
       // Do something... 
   })
   ->dispatch();
```

### Paused
The "paused" event is triggered if the Haystack has been paused using the `pauseHaystack` method or a job has been released using the `longRelease` method. This is useful if you need to update the database
to mark an import as paused, especially if the pause is for a long time.

```php
$haystack = Haystack::build()
   ->addJob(new RecordPodcast)
   ->paused(function () {
       // Do something... 
   })
   ->dispatch();
```

### Invokable classes

Each of these methods support invokable classes.

```php
$haystack = Haystack::build()
   ->addJob(new RecordPodcast)
   ->then(new Then)
   ->catch(new Catch)
   ->finally(new Finally)
   ->paused(new Paused)
   ->dispatch();

// Example Invokable class

class Then {
   public function __invoke()
   {
       // Do something...
   }
}
```

## Configuring

You can configure a global delay, connection and queue that will apply to all jobs in the haystack. You can also provide a per-job configuration if you choose to too.

### Delay

You can use the `withDelay` method to apply a global delay to every job.

```php
$haystack = Haystack::build()
   ->withDelay(60)
   ->addJob(new RecordPodcast) 
   ->addJob(new ProcessPodcast)
   ->dispatch();
```

### Connection

You can use the `onConnection` method to use a given connection for every job.

```php
$haystack = Haystack::build()
   ->onConnection('redis')
   ->addJob(new RecordPodcast) 
   ->addJob(new ProcessPodcast)
   ->dispatch();
```

### Queue

You can use the `onQueue` method to use a given queue for every job.

```php
$haystack = Haystack::build()
   ->onQueue('podcasts')
   ->addJob(new RecordPodcast) 
   ->addJob(new ProcessPodcast)
   ->dispatch();
```

### Custom Delay, Connection, Queue Per Job

You can also choose to use a different delay, connection or queue for every job! 

```php
$haystack = Haystack::build()
   ->onQueue('podcasts')
   ->addJob(new RecordPodcast, delay: 60, queue: 'low', connection: 'redis') 
   ->addJob(new ProcessPodcast, delay: 120, queue: 'high', connection: 'sqs')
   ->dispatch();
```

> If you have already configured the job with delay, connection or queue, it will use that configuration.

## Global Middleware

You can also provide middleware that will be applied to every job in the haystack. This is perfect if you don’t want to manually add the middleware to every job, or if the middleware can’t belong to the job on its own. This is extremely useful if you want to apply an API rate limiter to your requests. Just use the `withMiddleware` method. It will accept either an array, a closure that returns an array or an invokable class that returns an array.

### Array

```php
$haystack = Haystack::build()
   ->onQueue('podcasts')
   ->addJob(new RecordPodcast) 
   ->addJob(new ProcessPodcast)
   ->withMiddleware([
       (new RateLimited)->allows(30)->everyMinute(),
       new OtherMiddleware,
   ])
   ->dispatch();
```

### Closure

You must return an array in the closure for it to work.

```php
$haystack = Haystack::build()
   ->onQueue('podcasts')
   ->addJob(new RecordPodcast) 
   ->addJob(new ProcessPodcast)
   ->withMiddleware(function () {
        return [
           (new RateLimited)->allows(30)->everyMinute(),
           new OtherMiddleware,
        ];
   })
   ->dispatch();
```

### Invokable class

You must return an array inside your invokable class for it to work.

```php
$haystack = Haystack::build()
   ->onQueue('podcasts')
   ->addJob(new RecordPodcast) 
   ->addJob(new ProcessPodcast)
   ->withMiddleware(new PodcastMiddleware)
   ->dispatch();

// Invokable class...

class PodcastMiddleware {
    public function __invoke()
    {
		return [
	        (new RateLimited)->allows(30)->everyMinute(),
		    new OtherMiddleware,
		];
    }
}
```

## Appending Jobs

You can append to the haystack inside of your job. If you choose to do this, the appended job will go at the end of the chain. Just use the `appendToHaystack` method.

```php
<?php
 
namespace App\Jobs;
 
use Sammyjo20\LaravelHaystack\Contracts\StackableJob;
use Sammyjo20\LaravelHaystack\Concerns\Stackable;
 
class ProcessPodcast implements ShouldQueue, StackableJob
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels, Stackable

    public function handle()
    {
        // Your application code...

        $this->appendToHaystack(new DifferentJob);
    }
```

## Long Delays & Pausing Haystack

Haystack fully supports the existing `release` and `delay` methods in Laravel, however occasionally you may want to pause the haystack for an extended period of time, or release a job until the next day when an API rate limit is lifted. This can also be used as a longer delay if you are using Amazon SQS which only has a delay of 15 minutes.

Laravel Haystack can provide this by storing the resume date in the database and using the Scheduler to dispatch the haystack when it is ready. When Laravel Haystack is paused, no job is left in your queue.

**Warning: Since Laravel Haystack stores a copy of the job in the database before it is dispatched, it cannot keep track of job attempts or back-off. This means that a job can be released using the "longRelease" as many times as you like. [This is something that is being looked into.](https://github.com/Sammyjo20/laravel-haystack/issues/9)**

### Setting Up

Before you configure long releases or pauses, you must make sure your scheduler is running and the following line is added to your Console/Kernel.php file. If you can, provide the `onOneServer` method as well which will prevent any accidental duplicate-resumes.

```php
<?php

// Add to Console/Kernel.php

$schedule->command('haystacks:resume')->everyMinute();

// One server if you are running the scheduler on multiple servers

$schedule->command('haystacks:resume')->everyMinute()->onOneServer();
```

### Long Releasing

If you would like to release the current job back onto the queue, just use the `longRelease` method inside your job’s handle method. You can provide an integer for seconds or a Carbon datetime instance.

```php
<?php
 
namespace App\Jobs;
 
use Sammyjo20\LaravelHaystack\Contracts\StackableJob;
use Sammyjo20\LaravelHaystack\Concerns\Stackable;
 
class ProcessPodcast implements ShouldQueue, StackableJob
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels, Stackable

    public function handle()
    {
        // Your application code...

        $this->longRelease(300); // Release for 5 minutes

        // Or use Carbon

        $this->longRelease(now()->addDays(2)); // Release for 2 days.
    }
```

### Pausing the next job

If you want to process the current job but pause the Haystack for the next job, use the `pauseHaystack` method. If you have disabled automatic processing, you can provide a delay to the `nextJob` method.

```php
<?php
 
namespace App\Jobs;
 
use Sammyjo20\LaravelHaystack\Contracts\StackableJob;
use Sammyjo20\LaravelHaystack\Concerns\Stackable;
 
class ProcessPodcast implements ShouldQueue, StackableJo
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels, Stackable

    public function handle()
    {
        // Your application code...

        $this->pauseHaystack(now()->addHours(4)); // Pause the haystack for 4 hours.
    }
```

## Shared Job Data

Laravel Haystack has the ability for your jobs to store and retrieve state/data between jobs. This is really useful if you need to store data in the first job and then in the second job, process the data and in the final job, email the processed data. You are able to create a process pipeline since each job is processed in sequential order. This is really exciting because with traditional chained jobs, you cannot share data between jobs.

```php
<?php

$haystack = Haystack::build()
   ->addJob(new RetrieveDataFromApi)
   ->addJob(new ProcessDataFromApi)
   ->addJob(new StoreDataFromApi)
   ->dispatch();
```

### Storing data inside of jobs

Inside your job, you can use the `setHaystackData()` method to store some data. This method accepts a key, value and optional Eloquent cast.

```php
<?php
 
namespace App\Jobs;
 
use Sammyjo20\LaravelHaystack\Contracts\StackableJob;
use Sammyjo20\LaravelHaystack\Concerns\Stackable;
 
class RetrieveDataFromApi implements ShouldQueue, StackableJob
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels, Stackable

    public function handle()
    {
        // Your application code...
        
        $this->setHaystackData('username', 'Sammyjo20');
    }
```

### Casting data 

The `setHaystackData` method supports any data type. It supports fully casting your data into any of Eloquent's existing casts, or even your custom casts. Just provide a third argument to specify the cast.

```php
<?php
 
namespace App\Jobs;
 
use Sammyjo20\LaravelHaystack\Contracts\StackableJob;
use Sammyjo20\LaravelHaystack\Concerns\Stackable;
 
class RetrieveDataFromApi implements ShouldQueue, StackableJob
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels, Stackable

    public function handle()
    {
        // Your application code...
        
        // Array Data, provide third argument to specify cast.
        
        $this->setHaystackData('data', ['username' => 'Sammyjo20'], 'array');
        
        // Carbon dates...
        
        $this->setHaystackData('currentDate', now(), 'immutable_datetime');
        
        // Supports custom casts
        
        $this->setHaystackData('customData', $object, CustomCast::class);
    }
```

### Retrieving data inside of jobs

From one job you can set the data, but that data will be available to every job in the haystack there after. Just use the `getHaystackData` method to get data by key or use the `allHaystackData` to get a collection containing the haystack data.

```php
<?php
 
namespace App\Jobs;
 
use Sammyjo20\LaravelHaystack\Contracts\StackableJob;
use Sammyjo20\LaravelHaystack\Concerns\Stackable;
 
class RetrieveDataFromApi implements ShouldQueue, StackableJob
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels, Stackable

    public function handle()
    {
        // Get data by key
        
        $username = $this->getHaystackData('username'); // Sammyjo20
        
        // Get all data
        
        $allData = $this->allHaystackData(); // Collection: ['username' => 'Sammyjo20']
    }
```

### Getting The Data After Processing

Laravel Haystack will conveniently pass a collection into your then/catch/finally closures with all the of the data that you stored inside the Haystack. You can then use this data however you wish.

```php
<?php

$haystack = Haystack::build()
   ->addJob(new RetrieveDataFromApi)
   ->addJob(new ProcessDataFromApi)
   ->addJob(new StoreDataFromApi)
   ->then(function ($data) {
        // $data: ['username' => 'Sammyjo20', 'other-key' => 'value', 'collection' => new Collection],
   })
   ->catch(function ($data) {
        // $data: ['username' => 'Sammyjo20', 'other-key' => 'value', 'collection' => new Collection],
   })
   ->finally(function ($data) {
        // $data: ['username' => 'Sammyjo20', 'other-key' => 'value', 'collection' => new Collection],
   })
   ->dispatch();
```

If you would like to disable this functionality, you can provide the `dontReturnData` method to the Haystack builder. If this method is provided, Haystack won't run the query that retrieves all the data at the end of a Haystack.

```php
<?php

$haystack = Haystack::build()
   ->addJob(new RetrieveDataFromApi)
   ->addJob(new ProcessDataFromApi)
   ->addJob(new StoreDataFromApi)
   ->dontReturnData()
   ->then(function ($data) {
        // $data: null
   })
   ->dispatch();
```

If you would like to disable this feature entirely, you can set the `return_all_haystack_data_when_finished` config variable to false.

## Manual Processing

By default, Laravel Haystack will use events to automatically process the next job in the haystack. If you would like to disable this functionality, you will need to make sure to disable the `automatic_processing` in your config/haystack.php file. After that, you will need to make sure your jobs tell Haystack when to process the next job.

### Using nextJob()

Since Haystack is no longer listening to your jobs, you will need to tell it when to dispatch the next job. You can do this by using the `nextJob` method. Laravel Haystack will automatically stop when it gets to the end of the haystack.

```php
<?php
 
namespace App\Jobs;
 
use Sammyjo20\LaravelHaystack\Contracts\StackableJob;
use Sammyjo20\LaravelHaystack\Concerns\Stackable;
 
class ProcessPodcast implements ShouldQueue, StackableJob
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels, Stackable

    public function handle()
    {
        // Your application code...

        $this->nextJob();
    }
```

### Manually Failing Haystacks

Since Haystack is no longer listening out for failed jobs, you will need to call the `failHaystack` method in your job, preferably in the `failed` method. If you do not provide this, Haystack won’t call your “catch” and “finally” methods.

```php
<?php
 
namespace App\Jobs;
 
use Sammyjo20\LaravelHaystack\Contracts\StackableJob;
use Sammyjo20\LaravelHaystack\Concerns\Stackable;
 
class ProcessPodcast implements ShouldQueue, StackableJob
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels, Stackable

    public function failed($exception)
    {
        $this->failHaystack();
    }
```

## Cleaning Up Stale And Finished Haystacks

Laravel Haystack will attempt to clean up every job on successful/failed haystacks, however there may be a situation, especially if you have not enabled the automatic processing. If you have disabled the option to automatically delete haystacks when they are finished they may build up quickly. 

You can prevent this by running the following prune command every day in your scheduler:

```php
<?php

// Add to Console/Kernel.php

use Sammyjo20\LaravelHaystack\Models\Haystack;

$schedule->command('model:prune', [
    '--model' => [Haystack::class],
])->daily();
```

## Support Haystack's Development
While I never expect anything, if you would like to support my work, you can donate to my Ko-Fi page by simply buying me a coffee or two! https://ko-fi.com/sammyjo20

Thank you for using Laravel Haystack ❤️
