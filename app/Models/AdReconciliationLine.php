<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['ad_reconciliation_id', 'external_user_id', 'provider_revenue_micros', 'local_revenue_micros', 'difference_micros', 'flag'])]
class AdReconciliationLine extends Model
{
    public const FLAG_MATCHED = 'matched';

    public const FLAG_MISSING_LOCAL = 'missing_local';

    public const FLAG_MISSING_PROVIDER = 'missing_provider';

    public const FLAG_AMOUNT_MISMATCH = 'amount_mismatch';

    /**
     * @return BelongsTo<AdReconciliation, $this>
     */
    public function reconciliation(): BelongsTo
    {
        return $this->belongsTo(AdReconciliation::class, 'ad_reconciliation_id');
    }
}
