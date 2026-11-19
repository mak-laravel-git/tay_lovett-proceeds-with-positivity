# Implementation progress

Updated: 2026-11-02

## Decision log

- Same-driver failover proof passed on `laravel/ai` v1.3.0. Two `Ai::build()` OpenAI providers with unique names stayed distinct, and a rate limit on the first credential let the second succeed. Classified routing still calls the SDK once per credential so a restriction can stop the chain before the next credential is included.
- Quote jobs carry a category id only. Provider instances are not serialized.
- Generated quotes are stored as `pending_review`. A denylist match cannot publish.
- Demo advertisements do not create ledger rows or wallet credits.
- User share, remainder cents, gross versus net, payout minimums, and identity checks are not invented. The wallet shows ledger balances only.
- Live Playwire, PayPal, and Cash App adapters are not bound. `AdNetwork` and `PayoutProvider` are contracts. `DemoAdProvider` is the only ad implementation.
- Redis: the PHP redis extension is present. A local probe of `127.0.0.1:6379` did not connect. Cache and queue drivers were not switched, and Redis was not flushed.
- Client context and the V14 prototype were excluded from the frontend formatter.

## Phase status

| Phase                          | Status              | Notes                                                                                                                                                                |
| ------------------------------ | ------------------- | -------------------------------------------------------------------------------------------------------------------------------------------------------------------- |
| 1 Authentication and roles     | Completed           | Guests at `/` reach login. Roles redirect to `/feed` or `/admin`. Registration cannot set the role.                                                                  |
| 2 Design system and shells     | Completed           | Brand tokens, split login, user shell, admin shell, draft Privacy and Terms.                                                                                         |
| 3 Quotes and moderation        | Completed           | Publication states, denylist, saves, and admin review.                                                                                                               |
| 4 Laravel AI SDK               | Completed           | `QuoteAgent`, fakes, and the same-driver proof.                                                                                                                      |
| 5 Redis cutover                | Blocked             | No reachable local Redis. Application cache keys are ready for the configured cache store.                                                                           |
| 6 AI credentials               | Partially completed | Write-only secrets, two credentials, simulation without HTTP, rate-limited connection test.                                                                          |
| 7 Generation, feed, budget     | Partially completed | Studio stores review quotes. Daily ceiling is reserved and spent. Public URLs require `published`.                                                                   |
| 8 Classified failover          | Partially completed | Classifier and one-call-per-credential loop. Unknown failures stop the chain.                                                                                        |
| 9 Usage and settings           | Partially completed | Admin usage list and settings form.                                                                                                                                  |
| 10 Advertisements              | Partially completed | Demo full-screen ad. Exit does not post revenue.                                                                                                                     |
| 11 Ledger and wallet           | Partially completed | Integer cents, idempotent posts, no automatic ad or AI credits.                                                                                                      |
| 12 Withdrawals                 | Partially completed | Manual request, reserve, admin approve, mark paid with a reference. No external transfer.                                                                            |
| 13 Live ad and payout adapters | Blocked             | No written provider approval.                                                                                                                                        |
| 14 Hardening                   | Partially completed | Pint, PHPStan, 64 Pest tests, TypeScript, frontend check, and production build passed. Staging Redis, counsel-approved legal copy, and live credentials remain open. |

## Verification

- `vendor/bin/pint --dirty --format agent`
- `vendor/bin/phpstan analyse --memory-limit=1G --no-progress` — passed, 0 errors
- `php artisan test --compact` — 64 passed, 252 assertions
- `pnpm run types:check` — passed
- `pnpm run check` — passed
- `pnpm run build` — passed
- Browser at `http://127.0.0.1:8000`: guest `/` opened login; login reached the feed; a normal user received 403 on `/admin`; Generate Ad opened a labeled demo advertisement. A 390px emulation kept the ad inside a narrow column. Not every requested viewport was captured.

## Resume

1. Point `CACHE_STORE` and `QUEUE_CONNECTION` at the existing private Redis only after host, auth, TLS, and a free logical database are confirmed. Do not flush it.
2. Add a real AI credential only in an authorized environment. Keep generation disabled until the daily budget is set.
3. Do not enable a live ad or payout adapter without written approval.
4. Replace the Privacy Policy and Terms placeholder when counsel provides copy.

## Local browser accounts

Created only in the local database for interface checks: `user@example.com` and `admin@example.com`, password `password`. These are not production accounts.
