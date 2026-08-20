<?php

return [

    /*
    |--------------------------------------------------------------------------
    | View Storage Paths
    |--------------------------------------------------------------------------
    */

    'paths' => [
        resource_path('views'),
    ],

    /*
    |--------------------------------------------------------------------------
    | Compiled View Path
    |--------------------------------------------------------------------------
    |
    | Serverless runtimes expose the application bundle as read-only. Allow the
    | compiled Blade cache to be redirected to /tmp for Vercel and similar hosts.
    |
    */

    'compiled' => env(
        'VIEW_COMPILED_PATH',
        env('VERCEL') ? '/tmp/frotacerta/views' : realpath(storage_path('framework/views'))
    ),

];
