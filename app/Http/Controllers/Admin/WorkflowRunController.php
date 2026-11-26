<?php

namespace App\Http\Controllers\Admin;

use App\Enums\WorkflowState;
use App\Http\Controllers\Controller;
use App\Models\AdActivity;
use App\Models\QuoteWorkflow;
use App\Models\RewardedAdAttempt;
use App\Models\WorkflowEvent;
use App\Services\QuoteWorkflow\WorkflowLogger;
use App\Services\QuoteWorkflow\WorkflowService;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Every quote workflow run, with its full timeline, and the two things an administrator may do to a stuck one.
 */
class WorkflowRunController extends Controller
{
    public function index(Request $request): Response
    {
        $filters = $request->validate([
            'state' => ['nullable', Rule::enum(WorkflowState::class)],
            'user' => ['nullable', 'string', 'max:100'],
        ]);

        $runs = QuoteWorkflow::query()
            ->with(['user:id,name,email', 'category:id,name'])
            ->when($filters['state'] ?? null, fn (Builder $query, string $state) => $query->where('state', $state))
            ->when($filters['user'] ?? null, fn (Builder $query, string $term) => $query->whereHas('user', fn (Builder $user) => $user
                ->where('name', 'like', "%{$term}%")
                ->orWhere('email', 'like', "%{$term}%")))
            ->orderByDesc('id')
            ->paginate(25)
            ->withQueryString()
            ->through(fn (QuoteWorkflow $run): array => $this->row($run));

        return Inertia::render('admin/workflow/runs', [
            'runs' => $runs,
            'filters' => array_filter($filters, fn ($value) => $value !== null && $value !== ''),
            'states' => WorkflowState::options(),
        ]);
    }

    public function show(QuoteWorkflow $workflow): Response
    {
        $workflow->load(['user:id,name,email', 'category:id,name', 'quote']);

        return Inertia::render('admin/workflow/run-show', [
            'run' => [
                ...$this->row($workflow),
                'quote' => $workflow->quote === null ? null : [
                    'text' => $workflow->quote->text,
                    'status' => $workflow->quote->status->value,
                ],
                'can_unlock' => $workflow->state === WorkflowState::AdBeforePublish,
                'can_cancel' => $workflow->state->isBeforeGeneration(),
                'rules' => $workflow->settings_snapshot,
            ],
            'events' => $workflow->events()->orderBy('id')->limit(500)->get()->map(fn (WorkflowEvent $event): array => [
                'id' => $event->id,
                'at' => $event->created_at?->toIso8601String(),
                'category' => $event->category,
                'event' => $event->event,
                'level' => $event->level,
                'context' => $event->context,
            ])->all(),
            'attempts' => $workflow->adAttempts()->orderBy('id')->get()->map(fn (RewardedAdAttempt $attempt): array => [
                'id' => $attempt->id,
                'step' => $attempt->step->label(),
                'status' => $attempt->status->value,
                'ad_unit_path' => $attempt->ad_unit_path,
                'error_detail' => $attempt->error_detail,
                'at' => $attempt->created_at?->toIso8601String(),
            ])->all(),
            'surveys' => $workflow->surveys()->orderBy('id')->get()->map(fn (AdActivity $activity): array => [
                'id' => $activity->id,
                'provider' => $activity->provider_key,
                'status' => $activity->status->value,
                'reward_status' => $activity->reward_status->value,
                'at' => $activity->survey_started_at?->toIso8601String(),
            ])->all(),
        ]);
    }

    public function unlock(Request $request, QuoteWorkflow $workflow, WorkflowService $workflows): RedirectResponse
    {
        $workflows->unlockByAdministrator($workflow, $request->user());

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Quote released.']);

        return back();
    }

    public function cancel(Request $request, QuoteWorkflow $workflow, WorkflowService $workflows, WorkflowLogger $logger): RedirectResponse
    {
        $workflows->cancel($workflow);
        $logger->log(WorkflowLogger::ADMIN, 'run_cancelled', $workflow, ['administrator_id' => $request->user()->id]);

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Run cancelled.']);

        return back();
    }

    /**
     * @return array<string, mixed>
     */
    private function row(QuoteWorkflow $run): array
    {
        return [
            'id' => $run->id,
            'user' => $run->user === null ? null : ['id' => $run->user->id, 'name' => $run->user->name, 'email' => $run->user->email],
            'category' => $run->category?->name,
            'state' => $run->state->value,
            'ad1_result' => $run->ad1_result,
            'survey_result' => $run->survey_result,
            'ad2_result' => $run->ad2_result,
            'created_at' => $run->created_at?->toIso8601String(),
            'completed_at' => $run->completed_at?->toIso8601String(),
        ];
    }
}
