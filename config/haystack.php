<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Queue Haystack Jobs Automatically
    |--------------------------------------------------------------------------
    |
    | This value if set to true, will instruct Laravel Haystack to listen
    | out for "Stackable" jobs and automatically queue them after each
    | job is processed. If this value is set to false, you will need
    | to call "$this->nextJob" inside your jobs manually.
    |
    */

    'process_automatically' => true,

    /*
    |--------------------------------------------------------------------------
    | Stale Haystacks
    |--------------------------------------------------------------------------
    |
    | This value determines how long "stale" haystacks are kept for. These are
    | haystacks where the job that controlled them has failed without sending
    | the failure signal to laravel-haystack. This shouldn't happen if auto
    | processing has been turned on.
    |
    */

    'keep_stale_haystacks_for_days' => 3,

];
