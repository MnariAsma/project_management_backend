<?php

namespace App\Jobs;

use App\Models\RepositorySource;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

class GenerateEmbeddingsJob implements ShouldQueue
{
    use Queueable;

    /**
     * Create a new job instance.
     */
public function __construct(public RepositorySource $source)
    {
        //
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        //
    }
}
