<?php

namespace App\Models;

use App\Enums\ReconciliationStatus;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;

/**
 * @property ReconciliationStatus $status
 * @property string $report_date Y-m-d, a calendar day in the settlement timezone
 * @property Carbon|null $fetched_at
 * @property array<string, int>|null $country_breakdown
 * @property array<string, int>|null $flag_counts
 */
#[Fillable([
    'provider_key', 'report_date', 'timezone', 'provider_revenue_micros', 'local_revenue_micros', 'difference_micros',
    'local_transaction_count', 'status', 'country_breakdown', 'flag_counts', 'last_error', 'fetched_at', 'rechecked_count',
])]
class AdReconciliation extends Model
{
    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'status' => ReconciliationStatus::class,
            // `report_date` is deliberately NOT cast: a date cast writes "Y-m-d 00:00:00", which breaks the exact
            // (provider, date) lookup that keeps re-runs from creating duplicates. It stays a plain "Y-m-d" string.
            'provider_revenue_micros' => 'integer',
            'local_revenue_micros' => 'integer',
            'difference_micros' => 'integer',
            'country_breakdown' => 'array',
            'flag_counts' => 'array',
            'fetched_at' => 'datetime',
        ];
    }

    /**
     * @return HasMany<AdReconciliationLine, $this>
     */
    public function lines(): HasMany
    {
        return $this->hasMany(AdReconciliationLine::class);
    }
}
