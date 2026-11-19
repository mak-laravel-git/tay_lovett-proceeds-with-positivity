# ARCHITECTURE MAP

**Baseline HEAD:** `29e31de7dd4fc0dc91eaff63fdf878e99064530d`  
**Updated:** 2026-11-19

## Bootstrapping

- Laravel 13 app with Fortify session auth, Inertia React pages, Wayfinder-generated TS route helpers.
- Providers of note: `AppServiceProvider`, `FortifyServiceProvider`, `AppSettingsServiceProvider`, `AdsServiceProvider`.
- Frontend: React 19 + Tailwind 4 + motion; layouts `user-layout.tsx`, `admin-layout.tsx`, auth split layout.
- Brand tokens: dark navy / light blue / red direction in CSS (inspect `resources/css` / theme classes `bg-ink`, `brand`, `sky-light`).

## Auth & roles

| Capability | Existing files | Endpoint | UI | Auth | Test | Gap |
|---|---|---|---|---|---|---|
| Login-first root | `routes/web.php` | `GET /` → login or role home | Auth pages | Guest | `AuthenticationTest`, `AccessTest`, `ShellTest` | None material |
| Register | Fortify + custom rules | `POST /register` | `auth/register.tsx` | Guest | `RegistrationTest`, `RegistrationRulesTest` | — |
| Email OTP verify | `Auth\EmailOtpController` | `/email/verify` | `auth/verify-otp.tsx` | Auth | `EmailVerificationTest` | SMTP external |
| Password reset OTP | `Auth\PasswordResetOtpController` | `/forgot-password*` | auth reset pages | Guest | `PasswordResetTest` (**1 failing case**) | Fix test/assertion |
| Roles | `UserRole` enum on `users.role` | middleware `administrator`, `account.active`, `verified` | Role redirect | Server | `AccessTest`, `AdminDashboardTest` | No production role switcher found |

## User navigation

Desktop sidebar + mobile bottom bar in `resources/js/layouts/user-layout.tsx`:

`Inspiration Feed` | `Quote Studio` | `Surveys` | `My Wallet` | `My Earnings` | `Saved Quotes` | `Withdrawals`

Mobile primary: Feed / Create / Surveys / Saved; **More** → Wallet, Earnings, Withdrawals.

## Quote domain

| Capability | Files | Endpoint | UI | Data | Gap |
|---|---|---|---|---|---|
| Categories | `QuoteCategory`, migrations | feed filters | Feed / Studio | DB | Align UI tabs with seeded categories |
| Generate AI quote | `StudioController`, `QuoteGenerator`, `QuoteRouter` | `POST /studio` (throttle 10/min) | `studio/index.tsx` | `quotes` + AI logs | May require survey **AdGate** unlock |
| Feed | `FeedController` | `GET /feed` | `feed/index.tsx` | published quotes | — |
| Save toggle | `FeedController@save` | `POST /quotes/{quote}/save` | quote card | `saved_quotes` unique | — |
| Saved list | `FeedController@saved` | `GET /saved` | `saved/index.tsx` | own saves | — |
| Public quote + OG | `PublicQuoteController` | `GET /q/{quote}`, `og.png` | `quotes/public.tsx` | published only | Conflicts with “website-only share” clarification |
| Moderation | `QuoteModerationController` | `/admin/quotes*` | `admin/quotes.tsx` | status machine | Creator-pay publishing still optional |

## Demo ads vs provider ads

| Path | Behavior | Credits money? |
|---|---|---|
| `AdController` demo (`POST /quotes/{quote}/ad`, `GET /ads/{ad}`) | Labeled demonstration session (`provider=demo`) | **No** |
| Surveys / AdGate (`SurveyController`, `AdGateController`) | Provider offers → activity → **verified callback** → points | Points only today |
| Local simulate | `POST /dev/ads/activities/{activity}/simulate` (`local` only) | Through real callback pipeline; no live revenue |

## Monetization adapters

Registry: `AdsServiceProvider` → `TapResearchAdapter`, `GoogleAdSenseAdapter`.

| Provider | Mode | Callback | Status |
|---|---|---|---|
| TapResearch | Survey feed API v4 | `GET /api/webhooks/{provider}/reward` + signature parse | Code + Pest fixtures; live account **BLOCKED-EXTERNAL** |
| Google AdSense | Placeholder / standard ads adapter | Must not cash-reward views | Registered; not a user cash path |
| Wannads / TheoremReach | — | — | **NOT STARTED** |

Core services: `CallbackProcessor`, `RewardService`, `RewardRuleResolver`, `PointLedger`, `ReconciliationService`, `AdProviderManager`, `Micros` money helper.

## Financial domain

| Layer | Models / services | Notes |
|---|---|---|
| Points | `PointBalance`, `PointLedgerEntry`, `PointLedger` | Credited from verified ad activities via fixed rule points |
| Cash wallet | `Wallet` (`pending/available/reserved_cents`), `LedgerEntry`, `WalletLedger` | Integer cents, idempotent keys, lockForUpdate |
| Withdrawals | `WithdrawalRequest` + admin approve/reject/paid | Manual; reserve on request |
| 1/3 USD allocation | — | **Missing** as automated rule (PROPOSED in context) |

**Critical architecture note:** points earnings and cash wallet are parallel systems. There is no verified automatic conversion from TapResearch payout micros → user wallet cents at floor(1/3).

## Admin shell

Groups in `admin-sidebar.tsx`: Overview; People (Administrators, Users); Content (Moderation); Advertising (ads overview, activity, providers, rewards, requests, callbacks, reconciliation); Operations (Revenue, Withdrawals); AI (providers, models, routing, usage, settings); System (Application settings).

## Queues / mail / cache

- Default example: database queue + database cache + log mailer.
- Redis available as optional configuration; prior progress notes Redis not reachable locally.
- OTP / transactional mail depends on configured mailer (local `log` OK for demo).

## Test inventory (feature areas covered)

Auth, access, shell, studio quotes, quote agent, AI management, ads admin pages, TapResearch postback, survey flow, reconciliation, provider fallback, AdGate, wallet-adjacent ops, people accounts, legal docs, maintenance, public OG, moderation, settings.

See `FEATURE_MATRIX.md` for requirement-level status.
