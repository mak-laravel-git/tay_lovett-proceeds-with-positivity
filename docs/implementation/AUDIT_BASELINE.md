# AUDIT BASELINE — Proceeds With Positivity

**Audit date:** 2026-11-19  
**Auditor:** AI coding agent (PHASE 0)  
**Baseline HEAD (local `main`):** `29e31de7dd4fc0dc91eaff63fdf878e99064530d`  
**Branch:** `main`  
**Companion specs:** `context/01_AI_AGENT_IMPLEMENTATION_MASTER_CONTEXT.md`, `context/02_HUMAN_MANUAL_QA_TEST_PLAYBOOK.md`

## Environment snapshot (no secrets)

| Item | Finding |
|---|---|
| Working tree | Local checkout of `mak-laravel-git/tay_lovett-proceeds-with-positivity` |
| Git CLI | Not on PATH in this shell; HEAD read from `.git/refs/heads/main` |
| PHP | 8.4.27 |
| Laravel | 13.43.0 (`php artisan --version`) |
| Node | v24.22.1 |
| Frontend toolchain | Vite Plus (`vp`), pnpm lockfile present (`pnpm-lock.yaml`); also `package-lock.json` |
| Composer lock | Present |
| DB driver (`.env.example`) | sqlite default |
| Session / queue / cache (example) | database drivers |
| Mail (example) | `log` mailer |
| Redis | Configured in example; not required for local default |
| Migrations | All 21 migrations Ran (including `2026_11_19_000001_add_gate_columns_to_ad_activities_table`) |
| Routes | 160 routes registered |
| Automated tests | `php artisan test` → **384 passed**, **1 error**, **1 skipped** (386 tests, 2287 assertions) |

**Secrets policy:** `.env` was not dumped. Variable *presence* is assumed from `.env.example` keys only. Do not commit secrets.

## What “two commits” means here

The remote history is short because the project was moved. The working tree already contains substantial product code: quotes, AI admin, ads/survey adapters, wallet/withdrawals, admin people/ops, legal docs, and a large Pest suite. Treat HEAD as accumulated work, not a starter scaffold.

## Stack — SOURCE-CONFIRMED vs prior reports

| Claim | Status |
|---|---|
| Laravel 13 + Inertia React | **VERIFIED-IMPLEMENTED** (`composer.json`, `package.json`) |
| React 19 + TypeScript + Tailwind 4 | **VERIFIED-IMPLEMENTED** |
| shadcn/ui (Radix) | **VERIFIED-IMPLEMENTED** (component tree under `resources/js/components/ui`) |
| Laravel AI SDK (`laravel/ai`) | **VERIFIED-IMPLEMENTED** |
| Fortify auth + OTP flows | **VERIFIED-IMPLEMENTED** |
| Pest test runner | **VERIFIED-IMPLEMENTED** |
| Redis queues as active default | **EXISTS UNVERIFIED / optional** — example uses database queue/cache |
| Multiple AI providers in admin | **EXISTS UNVERIFIED → code present** — admin AI pages + credentials models |

## High-level product readiness

The app is **far past empty scaffold**. Core login-first UX, user/admin shells, quote studio/feed/saved, demo ads, TapResearch adapter + signed callbacks, point rewards, wallet cents ledger, and manual withdrawals exist in code with tests.

Largest product gaps vs the 2026-11-19 master context:

1. **Revenue model mismatch:** client Option B expects ~1/3 of *eligible provider USD* into a cash wallet. Current rewards credit **points** via `AdRewardRule` / `PointLedger`. Cash `Wallet` / `WalletLedger` is separate and not auto-fed by verified provider payouts.
2. **Website sharing vs quote sharing:** UI shares **public quote URLs** (`/q/{slug}`). Client latest clarification emphasized **website** sharing (social/email/SMS). Public quote pages already exist — document as OWNER-DESIGN conflict pending product confirmation.
3. **Quote Studio survey gate:** generation can require a provider-confirmed survey unlock (`AdGate`). This changes the original “unlimited generate then Generate Ad” UX; kill switch exists in admin rewards settings.
4. **Provider scope:** TapResearch + Google AdSense adapters registered. Wannads / TheoremReach **not** in registry.
5. **External blockers:** live publisher accounts, SMTP, domain/VPS, payout rails, legal copy approval.
6. **Test flake/error:** `PasswordResetTest` “wrong code…” currently errors (`Call to a member function first() on array`).

## Seed / local accounts (from code comments & seeders)

`Database\Seeders\UserSeeder` creates:

- `admin@dev.com` (Administrator)
- `user@dev.com` and `user1@dev.com`…`user6@dev.com` (User)

Passwords are `Hash::make($email)` (password equals the email), despite a comment that says `"password"`. Older progress notes mention `user@example.com` / `admin@example.com` — treat those as historical unless re-seeded.

## PHASE 0 acceptance

- [x] Inspected working tree, routes, migrations, models, services, pages, tests  
- [x] Recorded baseline HEAD and stack versions without exposing secrets  
- [x] Wrote living docs under `docs/implementation/`  
- [x] Ran automated tests and recorded result  
- [ ] Git feature branch / tag (Git CLI unavailable in agent shell — human or PATH fix needed before branching)  
- [ ] Manual browser screenshots at requested viewports (hand off to human QA)

## Next agent action after PHASE 0

Prioritize **confirmed independent** gaps in this order:

1. Fix failing `PasswordResetTest` (cheap reliability).  
2. Document/decide points↔USD bridge and 1/3 sandbox allocation (do not invent live cash without rule confirmation).  
3. Add explicit **Share Website** affordance (keep quote share if intentional).  
4. Harden withdrawal minimum / double-submit / sandbox labeling.  
5. Continue TapResearch sandbox vertical-slice verification against official docs + fixtures.
