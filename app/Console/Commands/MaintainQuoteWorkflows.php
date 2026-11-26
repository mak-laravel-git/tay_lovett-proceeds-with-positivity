<?php

namespace App\Console\Commands;

use App\Enums\WorkflowState;
use App\Models\AdProviderCall;
use App\Models\QuoteWorkflow;
use App\Models\QuoteWorkflowSetting;
use App\Models\RewardedAdAttempt;
use App\Models\WorkflowEvent;
use App\Services\QuoteWorkflow\WorkflowService;
use Illuminate\Console\Command;

class MaintainQuoteWorkflows extends Command
{
    protected $signature = 'workflow:maintain';

    protected $description = 'Expire abandoned quote runs and delete activity logs older than the retention period';

    public function handle(WorkflowService $workflows): int
    {
        $expired = 0;

        QuoteWorkflow::query()
            ->whereNotNull('active_user_id')
            ->whereIn('state', WorkflowState::beforeGenerationValues())
            ->where('expires_at', '<', now())
            ->chunkById(100, function ($runs) use ($workflows, &$expired): void {
                foreach ($runs as $run) {
                    $expired += $workflows->settle($run)->state === WorkflowState::Expired ? 1 : 0;
                }
            });

        $cutoff = now()->subDays(max(7, QuoteWorkflowSetting::current()->log_retention_days));

        $events = WorkflowEvent::query()->where('created_at', '<', $cutoff)->delete();
        $attempts = RewardedAdAttempt::query()->where('created_at', '<', $cutoff)->delete();
        // The calls themselves stay for the provider statistics; only the stored response copy is cleared.
        $excerpts = AdProviderCall::query()
            ->where('created_at', '<', $cutoff)
            ->whereNotNull('response_excerpt')
            ->update(['response_excerpt' => null]);

        $this->info("Expired {$expired} run(s). Removed {$events} log line(s), {$attempts} ad attempt(s) and {$excerpts} stored response(s).");

        return self::SUCCESS;
    }
}
