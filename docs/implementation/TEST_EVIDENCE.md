# TEST EVIDENCE LOG

**Updated:** 2026-11-19

## Automated

| Date (tz) | Environment | Build SHA | Command | Result | Notes |
|---|---|---|---|---|---|
| 2026-11-19 (local) | LOCAL | `29e31de7…64530d` | `php artisan test` | 384 passed / 1 error / 1 skipped | Error in `PasswordResetTest` wrong-code case |
| 2026-11-19 (local) | LOCAL | working tree (+ test fix) | `--filter="wrong code gives\|repeated wrong codes"` | 2 passed | Session errors asserted via `assertSessionHasErrors` |
| 2026-11-19 (local) | LOCAL | working tree (+ share/revenue) | MicrosTest + UserRevenueShareTest + TapResearchPostbackTest | 62 passed | Sandbox 1/3 pending; default toggle off |

## Manual (human)

Use template from `context/02_HUMAN_MANUAL_QA_TEST_PLAYBOOK.md` §1.

```text
Date/time (timezone):
Tester:
Environment: LOCAL / STAGING / QA SANDBOX
Build/commit SHA: 29e31de7dd4fc0dc91eaff63fdf878e99064530d
Browser and device:
Test ID:
Expected:
Actual:
Status: PASS / FAIL / BLOCKED / NOT IMPLEMENTED / NOT APPLICABLE
Evidence:
Severity:
Notes:
```

### Suggested first pass after PHASE 0

AUTH-01, AUTH-05, AUTH-07, AUTH-10, NAV-01, NAV-02, NAV-03, NAV-04, QUOTE-01, QUOTE-02, QUOTE-06, QUOTE-08, AD-02, AD-03, FIN-01, FIN-02, PROV-01, ADMIN-01.
