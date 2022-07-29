<div align="center">
    
<img src="https://user-images.githubusercontent.com/29132017/181642184-e95e6214-2ff0-4a32-985e-938432b7b3f5.jpeg" width="250">

# Laravel Haystack
Beautifully simple but powerful database-driven job chains.

![Build Status](https://github.com/sammyjo20/saloon/actions/workflows/tests.yml/badge.svg)

</div>

## Introduction
Laravel Haystack is a package that allows you to have a job chain powered by the database. Since all of the jobs in the chain are stored in the database, memory usage is low and you can delay jobs for a long time or have long running jobs without risking using all your memory. Laravel Haystack supports every queue connection/worker out of the box. (Database, Redis/Horizon, SQS).

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

Laravel Haystack aims to solve this by storing the job chain in the database and queuing one job at a time. When the job is completed, Laravel Haystack listens out for the "job completed" event and queues the next job in the chain from the database.

### Laravel Haystack Features
- Low memory consumption as one job is processed at a time and the chain is stored in the database
- You can have unlimited delay times since it will use the scheduler to restart a chain. Even if your queue driver is SQS.
- It provides callback methods like `then`, `catch` and `finally`.
- Global middleware that can be applied to every single job in the chain
- Delay that can be added to every job in the chain
- You can store the model for later processing.

### Use Cases
- Great if you need to make hundreds or thousands of API calls in a row, can be combined with Spatie's Job Rate Limiter to keep track of delays and pause jobs when a rate limit is hit.
- Great if you need to queue thousands of jobs in a chain at a time.
- If you need to batch import rows of data - each row can be a haystack job (bale) and processed one at a time. 

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

## Basic Usage

### Building Haystacks

### Dispatching Haystacks

### Creating Haystacks For Later

Use the create method to create the model and store it somewhere for later processing.

## Callback Events

### Then

### Catch

### Finally

### Invokable classes

## Configuring

### Delay

### Connection

### Queue

## Appending Jobs

## Global Middleware

## Delaying Entire Stack

## Manual Processing

## Configuration Options

- Automatic Processing
- Preserve Finished Haystacks
