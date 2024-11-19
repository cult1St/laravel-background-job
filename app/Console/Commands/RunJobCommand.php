<?php
namespace App\Console\Commands;

use App\Services\BackgroundJobRunner;
use Illuminate\Console\Command;

class RunJobCommand extends Command
{
    protected $signature = 'custom:run-job {class} {method} {parameters}';
    protected $description = 'Run a background job.';

    public function handle()
    {
        $class = $this->argument('class');
        $method = $this->argument('method');
        $parameters = json_decode($this->argument('parameters'), true);

        try {
            BackgroundJobRunner::run($class, $method, $parameters);
            $this->info("Job executed successfully!");
        } catch (\Exception $e) {
            $this->error("Job execution failed: " . $e->getMessage());
        }
    }
}
