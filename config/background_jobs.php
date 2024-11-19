<?php

return [
    'retries' => 3,
    'retry_delay' => 5, // seconds
    'approved_classes' => [
        \App\Jobs\TestJob::class,

    ],
];
