<?php

declare(strict_types=1);

return [

    /*
   |--------------------------------------------------------------------------
   | Return All Haystack Data When Finished
   |--------------------------------------------------------------------------
   |
   | This value if set to true, will instruct Haystack to query all the
   | haystack data rows out of the database and return them to the
   | then/finally/catch blocks as a collection.
   |
   */

    'return_all_haystack_data_when_finished' => true,

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

    /*
    |--------------------------------------------------------------------------
    | Delete Finished Haystacks
    |--------------------------------------------------------------------------
    |
    | This value determines if laravel-haystack should automatically delete
    | haystacks when they have finished processing. If this value is set
    | to false, make sure to use the scheduled command to clean up
    | old finished haystacks.
    |
    */

    'delete_finished_haystacks' => true,

    /*
    |--------------------------------------------------------------------------
    | Keep Finished Haystacks For Days
    |--------------------------------------------------------------------------
    |
    | This value determines how long finished haystacks will be retained for
    | this is only applicable if "deleted_finished_haystacks" has been disabled.
    |
    */

    'keep_finished_haystacks_for_days' => 1,

];
