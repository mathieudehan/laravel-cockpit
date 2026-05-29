<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Cockpit Enabled
    |--------------------------------------------------------------------------
    | Set to false to completely disable the Cockpit interface and routes.
    */
    'enabled' => env('COCKPIT_ENABLED', true),

    /*
    |--------------------------------------------------------------------------
    | Cockpit Path
    |--------------------------------------------------------------------------
    | The URI prefix where Cockpit will be accessible.
    */
    'path' => env('COCKPIT_PATH', 'cockpit'),

    /*
    |--------------------------------------------------------------------------
    | Cockpit Middleware
    |--------------------------------------------------------------------------
    | Middleware applied to every Cockpit route. CockpitAuthorize handles
    | local-vs-production access logic; keep it last in the chain.
    */
    'middleware' => [
        'web',
        \Mathieu\Cockpit\Http\Middleware\CockpitAuthorize::class,
    ],

    /*
    |--------------------------------------------------------------------------
    | Allowed Emails
    |--------------------------------------------------------------------------
    | Emails allowed to access Cockpit in non-local environments.
    | Can be a comma-separated string from env or a PHP array.
    |
    | Example in .env: COCKPIT_ALLOWED_EMAILS="admin@example.com,dev@example.com"
    */
    'allowed_emails' => array_filter(
        explode(',', env('COCKPIT_ALLOWED_EMAILS', ''))
    ),

    /*
    |--------------------------------------------------------------------------
    | Queue Tables
    |--------------------------------------------------------------------------
    | Adjust these if you renamed the default Laravel queue tables.
    */
    'queue_table'       => env('COCKPIT_QUEUE_TABLE', 'jobs'),
    'failed_jobs_table' => env('COCKPIT_FAILED_JOBS_TABLE', 'failed_jobs'),

    /*
    |--------------------------------------------------------------------------
    | Logs
    |--------------------------------------------------------------------------
    | log_file            — absolute path to the log file to read/parse.
    | log_lines_per_page  — entries per page in the log viewer.
    | log_max_read_bytes  — maximum bytes read from the tail of the log file
    |                       to prevent out-of-memory errors on large files.
    |                       Default: 20 MB.
    */
    'log_file'           => env('COCKPIT_LOG_FILE', storage_path('logs/laravel.log')),
    'log_lines_per_page' => 100,
    'log_max_read_bytes' => env('COCKPIT_LOG_MAX_BYTES', 20 * 1024 * 1024),

    /*
    |--------------------------------------------------------------------------
    | Command Blocklist
    |--------------------------------------------------------------------------
    | Commands whose names start with any of these prefixes cannot be executed
    | via Cockpit, regardless of environment. Extend this list to match your
    | security policy.
    */
    'command_blocklist' => [
        'db:wipe',
        'migrate:fresh',
        'migrate:reset',
        'down',
    ],

];
