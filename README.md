> ### Notice 14/05/2024
> 
> I am no longer going to be accepting new features for Laravel Haystack. I intend to still ensure security fixes 
> are made, but I feel that the project is now complete. Additionally, I feel that Laravel's job batches and
> chains in Laravel 10+ are a lot more powerful and you may not need Laravel Haystack in 2024.

<div align="center">
    
<img src="https://user-images.githubusercontent.com/29132017/181642184-e95e6214-2ff0-4a32-985e-938432b7b3f5.jpeg" width="250">

# Laravel Haystack
⚡️ Supercharged job chains for Laravel

![Build Status](https://github.com/sammyjo20/laravel-haystack/actions/workflows/tests.yml/badge.svg)

[Click here to read the documentation](https://docs.laravel-haystack.dev)

</div>

Laravel Haystack provides supercharged job chains for Laravel. It comes with powerful features like delaying jobs for as long as you like, applying middleware to every job, sharing data and models between jobs and even chunking jobs. Laravel Haystack supports every queue connection/worker out of the box. (Database, Redis/Horizon, SQS). It's great if you need to queue thousands of jobs in a chain or if you are looking for features that the original Bus chain doesn't provide.

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
   ->withMiddleware([
      // Middleware for every job
   ])
   ->withDelay(60)
   ->withModel($user)
   ->dispatch();
```

#### But doesn't Laravel already have job chains?

That's right! Laravel does have job chains but they have some disadvantages that you might want to think about.

* They consume quite a lot of memory/data since the chain is stored inside the job. This is especially true if you are storing thousands of jobs.
* They are volatile, meaning if you lose one job in the chain - you lose the whole chain.
* They do not provide the `then`, `catch`, `finally` callable methods that batched jobs do.
* Long delays with memory-based or SQS queue is not possible as you could lose the jobs due to expiry or if the server shuts down.
* You can't share data between jobs as there is no "state" across the chain

Laravel Haystack aims to solve this by storing the job chain in the database and queuing one job at a time. When the job is completed, Laravel Haystack listens out for the "job completed" event and queues the next job in the chain from the database.

#### Laravel Haystack Features

* Low memory consumption as one job is processed at a time and the chain is stored in the database
* You can delay/release jobs for as long as you want since it will use the scheduler to restart a chain. Even if your queue driver is SQS!
* It provides callback methods like `then`, `catch` and `finally`.
* Global middleware that can be applied to every single job in the chain
* You can store models and data that are shared with every job in the chain.
* You can prepare a Haystack and dispatch it at a later time

#### Use Cases

* If you need to make hundreds or thousands of API calls in a row, can be combined with Spatie's Job Rate Limiter to keep track of delays and pause jobs when a rate limit is hit.
* If you need to queue thousands of jobs in a chain at a time.
* If you need to batch import rows of data - each row can be a haystack job (bale) and processed one at a time. While keeping important job information stored in the database.
* If you need "release" times longer than 15 minutes if you are using Amazon SQS

## Installation

You can install the package with Composer. **Laravel Haystack Requires Laravel 8+ and PHP 8.1**

```bash
composer require sammyjo20/laravel-haystack
```

Next, just run the installation command!

```bash
php artisan haystack:install
```

## Documentation

[Click here to read the documentation](https://docs.laravel-haystack.dev)

## Support Haystack's Development
While I never expect anything, if you would like to support my work, you can donate to my Ko-Fi page by simply buying me a coffee or two!

<a href='https://ko-fi.com/sammyjo20' target='_blank'><img height='35' style='border:0px;height:46px;' src='https://az743702.vo.msecnd.net/cdn/kofi3.png?v=0' border='0' alt='Buy Me a Coffee at ko-fi.com' />

Thank you for using Laravel Haystack ❤️
