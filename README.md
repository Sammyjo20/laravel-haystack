<div align="center">
    
<img src="https://user-images.githubusercontent.com/29132017/181642184-e95e6214-2ff0-4a32-985e-938432b7b3f5.jpeg" width="250">

# Laravel Haystack
Beautifully simple but powerful database-driven job chains.

![Build Status](https://github.com/sammyjo20/saloon/actions/workflows/tests.yml/badge.svg)

</div>

## Introduction
Laravel Haystack is a package that allows you to have a job chain powered by the database. Since all of the jobs in the chain are stored in the database, memory usage is low and you can delay jobs for a long time or have long running jobs without risking using all your memory. Laravel Haystack supports every queue connection/worker out of the box. (Database, Redis/Horizon, SQS).

### But doesn't Laravel already have job chains?
That's right! Let's just be clear that we're not talking about **Batched Jobs**. Laravel does have job chains but they have some considerations.

- They consume quite a lot of memory/data since the chain is stored inside the job. This is especially true if you are storing thousands of jobs.
- They are volatile, meaning if you lose one job in the chain - you lose the whole chain.
- They do not provide the `then`, `catch`, `finally` helper methods that batched jobs do.
- Long delays with memory based or SQS queue is not possible as you could lose the jobs due to expiry or if the server shuts down.


This package is great if you are chaining thousands of jobs or if you are building an API integration which requires job throttling with middleware.

(Code Summary Here)

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

Coming soon... 🪐 
