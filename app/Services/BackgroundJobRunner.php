<?php
namespace App\Services;

use Illuminate\Support\Facades\Log;

class BackgroundJobRunner
{
    public static function run($className, $methodName, $parameters = [])
    {
        // Retry Configuration
        $retries = config('background_jobs.retries', 3);
        $retryDelay = config('background_jobs.retry_delay', 5);

        // Sanitize and validate inputs
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
                // Attempt to execute the job
                $attempts++;

                $instance = new $className;

                if (!method_exists($instance, $methodName)) {
                    throw new \Exception("Method $methodName does not exist in class $className.");
                }

                $result = call_user_func_array([$instance, $methodName], $parameters);

                // Log success and exit loop
                Log::info("Job executed successfully", [
                    'class' => $className,
                    'method' => $methodName,
                    'parameters' => $parameters,
                    'attempt' => $attempts,
                ]);

                return $result;
            } catch (\Exception $e) {
                // Log the error
                Log::warning("Job execution failed on attempt $attempts", [
                    'class' => $className,
                    'method' => $methodName,
                    'parameters' => $parameters,
                    'error' => $e->getMessage(),
                ]);

                // Retry logic
                if ($attempts > $retries) {
                    Log::error("Job failed after $retries retries", [
                        'class' => $className,
                        'method' => $methodName,
                        'parameters' => $parameters,
                    ]);
                    throw $e; // Throw the exception after exhausting retries
                }

                // Wait before retrying
                sleep($retryDelay);
            }
        }
    }
}
