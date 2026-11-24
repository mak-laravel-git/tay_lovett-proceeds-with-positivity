# Automated test results

**Date:** 2026-11-24  
**Command:** `php artisan test tests/Feature/Ads --compact`

| Suite | Result |
|---|---|
| Full `tests/Feature/Ads` | **143 passed**, 1146 assertions |
| TapResearch (regression) | Included PASS |
| WannadsPostbackTest | 8 passed |
| TheoremReachPostbackTest | 7 passed |

Baseline TapResearch-only filter before changes: 29 passed. Post-change TapResearch cases still pass inside the Ads suite.
