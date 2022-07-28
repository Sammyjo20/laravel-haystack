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

];
