<?php

namespace App\Services\QuoteWorkflow;

use App\Models\QuoteWorkflow;
use App\Models\WorkflowEvent;
use App\Support\Redactor;

/**
 * The only writer of the activity log shown to administrators.
 */
class WorkflowLogger
{
    public const WORKFLOW = 'workflow';

    public const AD = 'ad';

    public const SURVEY = 'survey';

    public const GENERATION = 'generation';

    public const PUBLISH = 'publish';

    public const ADMIN = 'admin';

    public const CATEGORIES = [self::WORKFLOW, self::AD, self::SURVEY, self::GENERATION, self::PUBLISH, self::ADMIN];

    public const LEVELS = ['info', 'warning', 'error'];

    /** Counted against the daily fallback limit, see StepPolicy. */
    public const FALLBACK_EVENT = 'fallback_skip';

    /**
     * @param  array<string, mixed>  $context
     */
    public function log(
        string $category,
        string $event,
        ?QuoteWorkflow $workflow = null,
        array $context = [],
        string $level = 'info',
        ?int $userId = null,
    ): void {
        WorkflowEvent::query()->create([
            'quote_workflow_id' => $workflow?->id,
            'user_id' => $userId ?? $workflow?->user_id,
            'category' => $category,
            'event' => $event,
            'level' => $level,
            'context' => $context === [] ? null : Redactor::context($context),
            'ip' => app()->runningInConsole() ? null : request()->ip(),
        ]);
    }
}
