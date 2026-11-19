<?php

namespace App\Jobs;

use App\Models\QuoteCategory;
use App\Services\QuoteGenerator;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

class GenerateQuotePoolJob implements ShouldQueue
{
    use Queueable;

    /**
     * Create a job that carries a category id and no provider instance.
     */
    public function __construct(public int $categoryId) {}

    /**
     * Execute the job.
     */
    public function handle(QuoteGenerator $generator): void
    {
        $category = QuoteCategory::query()->findOrFail($this->categoryId);

        $generator->generate($category);
    }
}
