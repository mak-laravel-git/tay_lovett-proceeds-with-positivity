<?php

namespace App\Services\QuoteWorkflow;

use App\Enums\AdAttemptStatus;
use App\Enums\AdPlacementKind;
use App\Enums\AdStep;
use App\Models\AdPlacement;
use App\Models\GoogleAdSetting;
use App\Models\QuoteWorkflow;
use App\Models\RewardedAdAttempt;
use Carbon\CarbonInterface;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * Rewarded ad attempts. A web rewarded ad is reported by the visitor's browser and cannot be proven on the
 * server, so this limits what a forged report can do: each attempt is issued here, belongs to one user and one
 * step, accepts events only in order, expires, and cannot be granted faster than the ad could have played.
 */
class RewardedAdService
{
    public const EVENTS = ['ready', 'shown', 'granted', 'closed', 'declined', 'unavailable', 'error'];

    private const LIFETIME_MINUTES = 30;

    private const DEFAULT_LOAD_TIMEOUT = 10;

    private const DEFAULT_MIN_WATCH = 5;

    /**
     * Whether this ad step runs at all. Switched off globally or for the step means it is skipped.
     *
     * @param  array<string, mixed>  $rules
     */
    public function stepActive(array $rules, AdStep $step): bool
    {
        return (bool) ($rules[$step->prefix().'_enabled'] ?? false) && GoogleAdSetting::current()->rewarded_enabled;
    }

    /**
     * The ad unit to request for a step, or null when none is configured.
     *
     * @return array{placement_id: int|null, ad_unit_path: string, load_timeout_seconds: int, min_watch_seconds: int, test_mode: bool}|null
     */
    public function unitFor(QuoteWorkflow $workflow, AdStep $step): ?array
    {
        $testMode = GoogleAdSetting::current()->test_mode;
        $chosen = $workflow->rule($step->prefix().'_placement_id');

        $placement = $chosen === null
            ? null
            : AdPlacement::query()->active(AdPlacementKind::Rewarded)->whereKey($chosen)->first();
        $placement ??= AdPlacement::query()->active(AdPlacementKind::Rewarded)->first();

        $path = $testMode ? GoogleAdSetting::TEST_REWARDED_UNIT : $placement?->ad_unit_path;

        if ($path === null || $path === '') {
            return null;
        }

        return [
            'placement_id' => $placement?->id,
            'ad_unit_path' => $path,
            'load_timeout_seconds' => $placement->load_timeout_seconds ?? self::DEFAULT_LOAD_TIMEOUT,
            'min_watch_seconds' => $placement->min_watch_seconds ?? self::DEFAULT_MIN_WATCH,
            'test_mode' => $testMode,
        ];
    }

    /**
     * @param  array{placement_id: int|null, ad_unit_path: string, load_timeout_seconds: int, min_watch_seconds: int, test_mode: bool}  $unit
     */
    public function issue(QuoteWorkflow $workflow, AdStep $step, array $unit, ?string $ip, ?string $userAgent): RewardedAdAttempt
    {
        // Only the newest attempt of a run counts.
        RewardedAdAttempt::query()
            ->where('quote_workflow_id', $workflow->id)
            ->whereIn('status', AdAttemptStatus::openValues())
            ->update(['status' => AdAttemptStatus::Superseded->value]);

        return RewardedAdAttempt::query()->create([
            'quote_workflow_id' => $workflow->id,
            'user_id' => $workflow->user_id,
            'step' => $step,
            'ad_placement_id' => $unit['placement_id'],
            'ad_unit_path' => $unit['ad_unit_path'],
            'status' => AdAttemptStatus::Issued,
            'min_watch_seconds' => $unit['min_watch_seconds'],
            'ip' => $ip,
            'user_agent' => $userAgent === null ? null : mb_substr($userAgent, 0, 255),
        ]);
    }

    /**
     * Record one browser event and return the attempt as it now stands.
     *
     * @throws ValidationException when the event is out of order, too early or too late
     */
    public function record(RewardedAdAttempt $attempt, string $event, ?string $detail = null): RewardedAdAttempt
    {
        return DB::transaction(function () use ($attempt, $event, $detail): RewardedAdAttempt {
            $row = RewardedAdAttempt::query()->whereKey($attempt->id)->lockForUpdate()->firstOrFail();
            $status = $row->status;

            // Closing after the reward is the normal end of an ad; it only records the time.
            if ($status === AdAttemptStatus::Granted && $event === 'closed') {
                $row->forceFill(['closed_at' => $row->closed_at ?? now()])->save();

                return $row;
            }

            if ($row->created_at !== null && $row->created_at->lt(now()->subMinutes(self::LIFETIME_MINUTES))) {
                $this->refuse('This ad session has expired. Please try again.');
            }

            $changes = match (true) {
                $event === 'ready' && $status === AdAttemptStatus::Issued => ['status' => AdAttemptStatus::Ready, 'ready_at' => now()],
                $event === 'shown' && $status === AdAttemptStatus::Ready => ['status' => AdAttemptStatus::Shown, 'shown_at' => now()],
                $event === 'granted' && $status === AdAttemptStatus::Shown => $this->grant($row),
                $event === 'closed' && $status === AdAttemptStatus::Shown => ['status' => AdAttemptStatus::Dismissed, 'closed_at' => now()],
                $event === 'declined' && $status === AdAttemptStatus::Ready => ['status' => AdAttemptStatus::Dismissed, 'closed_at' => now()],
                $event === 'unavailable' && $status === AdAttemptStatus::Issued => ['status' => AdAttemptStatus::Unavailable],
                $event === 'error' && $status->isOpen() => ['status' => AdAttemptStatus::Error],
                default => $this->refuse('That ad event was not expected.'),
            };

            if ($detail !== null && $detail !== '') {
                $changes['error_detail'] = mb_substr($detail, 0, 191);
            }

            $row->forceFill($changes)->save();

            return $row;
        });
    }

    /**
     * @return array{status: AdAttemptStatus, granted_at: CarbonInterface}
     */
    private function grant(RewardedAdAttempt $attempt): array
    {
        if ($attempt->shown_at === null || $attempt->shown_at->copy()->addSeconds($attempt->min_watch_seconds)->isFuture()) {
            $this->refuse('The ad has not played long enough yet.');
        }

        return ['status' => AdAttemptStatus::Granted, 'granted_at' => now()];
    }

    private function refuse(string $message): never
    {
        throw ValidationException::withMessages(['ad' => $message]);
    }
}
