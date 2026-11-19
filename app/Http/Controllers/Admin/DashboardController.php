<?php

namespace App\Http\Controllers\Admin;

use App\Enums\QuoteStatus;
use App\Http\Controllers\Controller;
use App\Models\AiGenerationLog;
use App\Models\Quote;
use App\Models\User;
use App\Models\WithdrawalRequest;
use Illuminate\Support\Carbon;
use Inertia\Inertia;
use Inertia\Response;

class DashboardController extends Controller
{
    /**
     * Show the administrator overview with live operational counts.
     */
    public function __invoke(): Response
    {
        $quoteCounts = Quote::query()
            ->selectRaw('status, count(*) as aggregate')
            ->groupBy('status')
            ->pluck('aggregate', 'status');

        $generationTrend = collect(range(6, 0))->map(function (int $daysAgo): array {
            $day = Carbon::today()->subDays($daysAgo);

            return [
                'date' => $day->format('M j'),
                'generations' => AiGenerationLog::query()
                    ->whereDate('created_at', $day)
                    ->count(),
            ];
        })->values()->all();

        $statusChart = collect(QuoteStatus::cases())->map(fn (QuoteStatus $status): array => [
            'status' => $status->value,
            'label' => str_replace('_', ' ', $status->value),
            'count' => (int) ($quoteCounts[$status->value] ?? 0),
        ])->values()->all();

        return Inertia::render('admin/home', [
            'stats' => [
                'users' => User::query()->count(),
                'administrators' => User::query()->where('role', 'administrator')->count(),
                'published_quotes' => (int) ($quoteCounts[QuoteStatus::Published->value] ?? 0),
                'pending_quotes' => (int) ($quoteCounts[QuoteStatus::PendingReview->value] ?? 0),
                'pending_withdrawals' => WithdrawalRequest::query()->where('status', 'pending')->count(),
                'generation_logs' => AiGenerationLog::query()->count(),
            ],
            'generationTrend' => $generationTrend,
            'quoteStatusChart' => $statusChart,
        ]);
    }
}
