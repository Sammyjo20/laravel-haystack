<div align="center">
    
<img src="https://user-images.githubusercontent.com/29132017/181642184-e95e6214-2ff0-4a32-985e-938432b7b3f5.jpeg" width="250">

# Laravel Haystack
Beautifully simple database-driven job chains.

![Build Status](https://github.com/sammyjo20/saloon/actions/workflows/tests.yml/badge.svg)

</div>

## Introduction

Laravel Haystack is a beautifully simple package that allows you to have a job chain powered by the database. Since all of the jobs in the chain are stored in the database, memory usage is low and you can delay jobs or have long running jobs without risking using all your memory. Laravel Haystack supports every queue connection/worker out of the box. (Database, Redis/Horizon, SQS)

This package is great if you are chaining thousands of jobs or if you are building an API integration which requires job throttling with middleware.

## Installation

You can install the package via composer:

```bash
composer require sammyjo20/laravel-haystack
```
> Requires Laravel 8+ and PHP 8.1

Then publish and run the migrations with:

```bash
php artisan vendor:publish --tag="laravel-haystack-migrations"
php artisan migrate
```

You can publish the config file with:

```bash
php artisan vendor:publish --tag="laravel-haystack-config"
```

Coming soon... 🪐 
