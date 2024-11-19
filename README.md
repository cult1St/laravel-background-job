# Custom Background Job Runner for Laravel

## Overview
The Custom Background Job Runner is a lightweight solution for executing PHP classes and methods as background jobs. It operates independently of Laravel's built-in queue system, offering robust error handling, logging, and retry mechanisms.

---

## Features
- Execute PHP classes and methods in the background.
- Configurable retry mechanism and delay.
- Secure execution by validating and restricting to approved classes.
- Detailed logging of job execution status.
- Compatible with both Unix and Windows systems.

---

## Usage

### Configuration
Ensure the `config/background_jobs.php` file contains your job settings:

```php
return [
    'approved_classes' => [
        \App\Jobs\TestJob::class, // Add your approved job classes
    ],
    'retries' => 3, // Number of retry attempts
    'retry_delay' => 5, // Delay between retries (in seconds)
];
###Then run the bacjgroundjob using laravel tinker
php artisan tinker
\App\Services\BackgroundJobRunner::run(\App\Jobs\TestJob::class, 'execute', ['Testing job']);

###Or via a route
Route::get('/test-job', function () {
    \App\Services\BackgroundJobRunner::run(\App\Jobs\TestJob::class, 'execute', ['Hello from route!']);
    return 'Job executed! Check logs for details.';
});

