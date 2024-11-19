<?php

if (!function_exists('runBackgroundJob')) {
    function runBackgroundJob($className, $methodName, $parameters = [])
    {
        $command = 'php artisan custom:run-job ' . escapeshellarg($className) . ' ' . escapeshellarg($methodName) . ' ' . escapeshellarg(json_encode($parameters));

        if (stripos(PHP_OS, 'WIN') === 0) {
            // For Windows
            pclose(popen("start /B " . $command, "r"));
        } else {
            // For Unix-based systems
            exec($command . " > /dev/null &");
        }
    }
}
