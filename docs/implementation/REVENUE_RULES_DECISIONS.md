# REVENUE RULES — DECISIONS LOG

**Updated:** 2026-11-19  
**Purpose:** Prevent inventing financial policy. Only confirmed or explicitly PROPOSED sandbox rules live here.

## Confirmed client direction

| Item | State | Notes |
|---|---|---|
| Monetization model | SOURCE-CONFIRMED Option **B** — User Revenue Sharing (2026-11-15) | Viewer reward + wallet remains baseline |
| Approximate share | SOURCE-CONFIRMED “one-third” / “33.33%” language | Numerically distinct for some cent amounts — **not finalized which** |
| Eligible base | UNRESOLVED | Gross vs net after fees unknown |
| Quote views alone | NOT revenue | Do not credit cash for views |
| Google AdSense ordinary impressions | NOT user cash | Owner revenue only |
| Creator pay-per-view | UNCONFIRMED question | Out of baseline scope |
| Minimum withdrawal | UNRESOLVED | QA may use sandbox `$10.00` fixture only |
| Payout rails | Preference Cash App / PayPal | Manual milestone first |

## Current code behavior (VERIFIED by audit)

1. Verified provider callbacks credit **points** via `AdRewardRule` → `PointLedger` (`RewardService`).  
2. Cash `Wallet` balances are integer **cents** via `WalletLedger`, used for withdrawals.  
3. Demo ads and browser events do **not** credit either ledger.  
4. Provider payout amounts can be parsed as **micros** (`App\Support\Micros`) for reconciliation — separate from user share formula.  
5. **No** automated `floor(eligible_cents / 3)` user allocation was found in application services.

## PROPOSED sandbox rule (implemented behind admin toggle)

Until client confirms live formula:

- Eligible base = verified `publisher_revenue_micros` on the activity (not AdSense impressions, not quote views).  
- User allocation = `floor(eligible_cents / 3)` via `Micros::oneThirdUserShareCents()`.  
- Remainder stays platform.  
- Example: $30.00 → $10.00 user pending; $1.00 → $0.33 user pending.  
- Credits **pending_cents only** with ledger type `sandbox_revenue_share` and a `SANDBOX` description.  
- Gate: `ad_settings.user_revenue_share_enabled` (default **false**). Admin → Ads → Rewards.  
- Idempotent key: `revenue-share:{provider}:{transaction_id}`.  
- Points rewards remain separate (`PointLedger`).

**Status:** PROPOSED sandbox code exists; toggle default off. Do not treat as signed live contractual rate.
