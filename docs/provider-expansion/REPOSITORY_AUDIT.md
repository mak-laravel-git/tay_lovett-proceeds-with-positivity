# Repository audit — provider expansion

**Date:** 2026-11-24  
**Branch:** `feature/provider-expansion-4networks`  
**Baseline HEAD before feature work:** `218944a` (working tree had uncommitted `.env.example` + `new_features_1/`)

## Stack (verified)

| Component | Version |
|---|---|
| PHP | 8.4.27 |
| Laravel | v13.43.0 (`composer.lock`) |
| Inertia React | `@inertiajs/react` ^3.0.0 |
| React | ^19.2.0 |
| Test runner | Pest (existing suite) |

## Existing monetization architecture (CODE-VERIFIED)

| Path | Role |
|---|---|
| `app/Services/Ads/Contracts/AdProviderAdapter.php` | Provider contract |
| `app/Services/Ads/AdProviderRegistry.php` | Adapter registry + DB row ensure |
| `app/Providers/AdsServiceProvider.php` | Registers TapResearch + Google AdSense |
| `app/Services/Ads/Providers/TapResearch/*` | Survey Feed API + signed postback |
| `app/Services/Ads/Providers/GoogleAdSense/GoogleAdSenseAdapter.php` | Placeholder; `isConfigured()` always false; **not** a cash-earning path |
| `app/Services/Ads/CallbackProcessor.php` | Shared verified-callback → activity/reward pipeline |
| `app/Http/Controllers/Api/ProviderCallbackController.php` | `GET /api/webhooks/{provider}/reward` |
| `app/Http/Controllers/SurveyController.php` | User survey list/start/status/return |
| `app/Http/Controllers/Admin/Ads/ProviderController.php` | Admin credentials (write-only secrets) |
| `resources/js/pages/surveys/index.tsx` | User “Surveys” earn surface |
| `resources/js/pages/admin/ads/providers.tsx` | Admin provider management |

## Financial model (CODE-VERIFIED)

- **Points:** `PointLedger` / `PointBalance` — credited only from verified callbacks via `RewardService`.
- **Cash wallet:** `WalletLedger` — separate; sandbox 1/3 revenue share via `UserRevenueShare` (pending only).
- **Uniqueness:** `ad_activities` unique on `(provider_key, provider_transaction_id)`.
- **Opaque IDs:** `ProviderIdentities` → `provider_identities` table.

## Routes (CODE-VERIFIED)

- User earn: `GET /surveys`, `POST /surveys/offers`, `POST /surveys/start`
- Callback: `GET /api/webhooks/{provider}/reward`
- Admin: `GET/PUT /admin/ads/providers*`
- Wallet/earnings: `GET /wallet`, `GET /earnings`

## Gaps before this expansion

| Item | Status |
|---|---|
| Wannads adapter | NOT STARTED → implementing |
| TheoremReach adapter | NOT STARTED → implementing |
| Reversal handling on shared processor | Missing (needed for Wannads reject + TR event webhook) |
| Compliance gates (coexistence / cashout) | Missing |
| Multi-provider user cards | Surveys page is single fallback-chain UI |

## Non-negotiables preserved

- Do not credit from browser/iframe events.
- Do not make AdSense a user cash-earning provider.
- Do not rewrite TapResearch signature or historical ledger rows.
- New providers default **disabled**.
