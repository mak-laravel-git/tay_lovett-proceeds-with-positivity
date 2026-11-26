<?php

namespace App\Http\Controllers\Admin;

use App\Enums\AdAttemptStatus;
use App\Enums\AdStep;
use App\Http\Controllers\Controller;
use App\Models\RewardedAdAttempt;
use App\Models\User;
use App\Models\WorkflowEvent;
use App\Services\QuoteWorkflow\WorkflowLogger;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Read-only logs: rewarded ad activity, and the activity of users and administrators. Survey transactions,
 * provider requests and callbacks have their own pages under Advertising.
 */
class ActivityLogController extends Controller
{
    public function ads(Request $request): Response
    {
        $filters = $request->validate([
            'status' => ['nullable', Rule::enum(AdAttemptStatus::class)],
            'step' => ['nullable', Rule::enum(AdStep::class)],
            ...$this->sharedRules(),
        ]);

        $attempts = $this->filtered(RewardedAdAttempt::query()->with('user:id,name,email'), $filters)
            ->when($filters['status'] ?? null, fn (Builder $query, string $status) => $query->where('status', $status))
            ->when($filters['step'] ?? null, fn (Builder $query, string $step) => $query->where('step', $step))
            ->orderByDesc('id')
            ->paginate(30)
            ->withQueryString()
            ->through(fn (RewardedAdAttempt $attempt): array => [
                'id' => $attempt->id,
                'run_id' => $attempt->quote_workflow_id,
                'user' => $this->person($attempt->user),
                'step' => $attempt->step->label(),
                'status' => $attempt->status->value,
                'ad_unit_path' => $attempt->ad_unit_path,
                'error_detail' => $attempt->error_detail,
                'created_at' => $attempt->created_at?->toIso8601String(),
                'shown_at' => $attempt->shown_at?->toIso8601String(),
                'granted_at' => $attempt->granted_at?->toIso8601String(),
                'closed_at' => $attempt->closed_at?->toIso8601String(),
            ]);

        // How the last seven days of attempts ended.
        $summary = RewardedAdAttempt::query()
            ->where('created_at', '>=', now()->subDays(7))
            ->select('status', DB::raw('count(*) as total'))
            ->groupBy('status')
            ->toBase()
            ->get()
            ->map(fn (object $row): array => ['status' => (string) $row->status, 'total' => (int) $row->total])
            ->all();

        return Inertia::render('admin/logs/ads', [
            'attempts' => $attempts,
            'summary' => $summary,
            'filters' => $this->active($filters),
            'options' => [
                'statuses' => AdAttemptStatus::options(),
                'steps' => array_map(fn (AdStep $step): array => ['value' => $step->value, 'label' => $step->label()], AdStep::cases()),
            ],
        ]);
    }

    public function activity(Request $request): Response
    {
        $filters = $request->validate([
            'category' => ['nullable', Rule::in(WorkflowLogger::CATEGORIES)],
            'level' => ['nullable', Rule::in(WorkflowLogger::LEVELS)],
            ...$this->sharedRules(),
        ]);

        $events = $this->filtered(WorkflowEvent::query()->with('user:id,name,email'), $filters)
            ->when($filters['category'] ?? null, fn (Builder $query, string $category) => $query->where('category', $category))
            ->when($filters['level'] ?? null, fn (Builder $query, string $level) => $query->where('level', $level))
            ->orderByDesc('id')
            ->paginate(40)
            ->withQueryString()
            ->through(fn (WorkflowEvent $event): array => [
                'id' => $event->id,
                'at' => $event->created_at?->toIso8601String(),
                'user' => $this->person($event->user),
                'run_id' => $event->quote_workflow_id,
                'category' => $event->category,
                'event' => $event->event,
                'level' => $event->level,
                'context' => $event->context,
                'ip' => $event->ip,
            ]);

        $option = fn (string $value): array => ['value' => $value, 'label' => ucfirst($value)];

        return Inertia::render('admin/logs/activity', [
            'events' => $events,
            'filters' => $this->active($filters),
            'options' => [
                'categories' => array_map($option, WorkflowLogger::CATEGORIES),
                'levels' => array_map($option, WorkflowLogger::LEVELS),
            ],
        ]);
    }

    /**
     * @return array<string, list<string>>
     */
    private function sharedRules(): array
    {
        return [
            'user' => ['nullable', 'string', 'max:100'],
            'from' => ['nullable', 'date_format:Y-m-d'],
            'to' => ['nullable', 'date_format:Y-m-d'],
        ];
    }

    /**
     * @template TModel of \Illuminate\Database\Eloquent\Model
     *
     * @param  Builder<TModel>  $query
     * @param  array<string, mixed>  $filters
     * @return Builder<TModel>
     */
    private function filtered(Builder $query, array $filters): Builder
    {
        return $query
            ->when($filters['user'] ?? null, fn (Builder $builder, string $term) => $builder->whereHas('user', fn (Builder $user) => $user
                ->where('name', 'like', "%{$term}%")
                ->orWhere('email', 'like', "%{$term}%")))
            ->when($filters['from'] ?? null, fn (Builder $builder, string $from) => $builder->whereDate('created_at', '>=', $from))
            ->when($filters['to'] ?? null, fn (Builder $builder, string $to) => $builder->whereDate('created_at', '<=', $to));
    }

    /**
     * @param  array<string, mixed>  $filters
     * @return array<string, mixed>
     */
    private function active(array $filters): array
    {
        return array_filter($filters, fn ($value) => $value !== null && $value !== '');
    }

    /**
     * @return array{id: int, name: string, email: string}|null
     */
    private function person(?User $user): ?array
    {
        return $user === null ? null : ['id' => $user->id, 'name' => $user->name, 'email' => $user->email];
    }
}
