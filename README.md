<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Custom Background Job Runner for Laravel</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            line-height: 1.6;
            background-color: #f9f9f9;
            color: #333;
            padding: 20px;
        }
        h1, h2, h3 {
            color: #0056b3;
        }
        pre {
            background-color: #f4f4f4;
            padding: 10px;
            border-left: 5px solid #0056b3;
            overflow-x: auto;
        }
        code {
            background-color: #eee;
            padding: 2px 4px;
            border-radius: 4px;
        }
        .container {
            max-width: 800px;
            margin: 0 auto;
            background: #fff;
            padding: 20px;
            border-radius: 8px;
            box-shadow: 0 2px 5px rgba(0, 0, 0, 0.1);
        }
        ul {
            margin-left: 20px;
        }
        a {
            color: #0056b3;
            text-decoration: none;
        }
        a:hover {
            text-decoration: underline;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>Custom Background Job Runner for Laravel</h1>

        <h2>Overview</h2>
        <p>The Custom Background Job Runner is a lightweight, customizable solution for executing PHP classes and methods as background jobs, independent of Laravel's built-in queue system. It supports retries, error handling, logging, and security validations to ensure a robust background job execution framework.</p>

        <h2>Features</h2>
        <ul>
            <li>Execute PHP classes/methods as background jobs.</li>
            <li>Retry mechanism with configurable retry attempts and delay.</li>
            <li>Error handling with detailed logs for failures and success.</li>
            <li>Security validations to restrict execution to approved classes and methods.</li>
            <li>Platform-independent (supports Windows and Unix-based systems).</li>
            <li>Optional job prioritization and delayed execution (advanced features).</li>
        </ul>

        <h2>Requirements</h2>
        <ul>
            <li>Laravel 9 or later.</li>
            <li>PHP 8.1 or later.</li>
        </ul>

        <h2>Installation</h2>
        <h3>1. Create Configuration File</h3>
        <p>Add a new configuration file for background job settings:</p>
        <pre>php artisan make:config background_jobs</pre>
        <p>Update the file <code>config/background_jobs.php</code>:</p>
        <pre>
return [
    'approved_classes' => [
        \App\Jobs\TestJob::class, // Add your job classes here
    ],
    'retries' => 3, // Number of retry attempts
    'retry_delay' => 5, // Delay between retries in seconds
];
        </pre>

        <h3>2. Add the Background Job Runner Service</h3>
        <p>Create the <code>BackgroundJobRunner</code> service in <code>app/Services/BackgroundJobRunner.php</code>:</p>
        <pre>
namespace App\Services;

use Illuminate\Support\Facades\Log;

class BackgroundJobRunner
{
    public static function run($className, $methodName, $parameters = [])
    {
        $retries = config('background_jobs.retries', 3);
        $retryDelay = config('background_jobs.retry_delay', 5);

        $className = filter_var($className, FILTER_SANITIZE_STRING);
        $methodName = filter_var($methodName, FILTER_SANITIZE_STRING);

        $approvedClasses = config('background_jobs.approved_classes');
        $forbiddenMethods = ['__construct', '__destruct', '__call', '__invoke'];

        if (!in_array($className, $approvedClasses)) {
            throw new \Exception("Unauthorized class: $className");
        }

        if (!class_exists($className)) {
            throw new \Exception("Class $className does not exist.");
        }

        if (in_array($methodName, $forbiddenMethods)) {
            throw new \Exception("Unauthorized or forbidden method: $methodName.");
        }

        $attempts = 0;

        while ($attempts <= $retries) {
            try {
                $attempts++;

                $instance = new $className;

                if (!method_exists($instance, $methodName)) {
                    throw new \Exception("Method $methodName does not exist in class $className.");
                }

                $result = call_user_func_array([$instance, $methodName], $parameters);

                Log::info("Job executed successfully", [
                    'class' => $className,
                    'method' => $methodName,
                    'parameters' => $parameters,
                    'attempt' => $attempts,
                ]);

                return $result;
            } catch (\Exception $e) {
                Log::warning("Job execution failed on attempt $attempts", [
                    'class' => $className,
                    'method' => $methodName,
                    'parameters' => $parameters,
                    'error' => $e->getMessage(),
                ]);

                if ($attempts > $retries) {
                    Log::error("Job failed after $retries retries", [
                        'class' => $className,
                        'method' => $methodName,
                        'parameters' => $parameters,
                    ]);
                    throw $e;
                }

                sleep($retryDelay);
            }
        }
    }
}
        </pre>

        <h3>3. Create a Test Job</h3>
        <p>Create a test job to validate the runner:</p>
        <pre>php artisan make:job TestJob</pre>
        <p>Edit the job class <code>app/Jobs/TestJob.php</code>:</p>
        <pre>
namespace App\Jobs;

use Illuminate\Support\Facades\Log;

class TestJob
{
    public function execute($message)
    {
        Log::info("TestJob executed with message: $message");
        return "Job completed with message: $message";
    }
}
        </pre>

        <h2>Usage</h2>
        <h3>Run a Job</h3>
        <p>Execute a background job by calling:</p>
        <pre>
use App\Services\BackgroundJobRunner;

BackgroundJobRunner::run(\App\Jobs\TestJob::class, 'execute', ['Hello, background job!']);
        </pre>

        <h3>Check Logs</h3>
        <p>Logs are stored in <code>storage/logs/laravel.log</code>.</p>

        <h2>Testing</h2>
        <h3>1. Using Tinker</h3>
        <pre>
php artisan tinker
\App\Services\BackgroundJobRunner::run(\App\Jobs\TestJob::class, 'execute', ['Testing job']);
        </pre>

        <h3>2. With a Web Route</h3>
        <p>Add a test route:</p>
        <pre>
Route::get('/test-job', function () {
    \App\Services\BackgroundJobRunner::run(\App\Jobs\TestJob::class, 'execute', ['Hello from route!']);
    return 'Job executed! Check logs for details.';
});
        </pre>

        <p>Visit <code>http://your-project.test/test-job</code> to test.</p>

        <h2>Conclusion</h2>
        <p>The Custom Background Job Runner provides a flexible and secure way to manage background tasks in Laravel. It ensures robust error handling, logging, and retry mechanisms, making it a reliable choice for running independent jobs outside Laravel's built-in queue system.</p>
    </div>
</body>
</html>
