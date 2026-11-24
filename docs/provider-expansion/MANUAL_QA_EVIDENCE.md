# Manual QA evidence tracker

**Primary report:** `SENIOR_QA_REPORT.md` (2026-11-24)  
**Playbook:** `new_features_1/02_HUMAN_QA_WANNADS_THEOREMREACH_AND_REGRESSION_PLAYBOOK.md`  
**Fixture harness:** `tests/Feature/Ads/PlaybookQaSuiteTest.php` (26 mapped cases PASS)

| Layer | Status |
|---|---|
| LOCAL_FIXTURE money/security (WAN-05..10, THEO-07..12, TAP-03..05, CROSS-*) | PASS |
| Admin/UX server surfaces (S-06, S-10, UX-01/02/04, THEO-01) | PASS |
| Full Ads regression suite | 172 PASS |
| Staging browser / mobile / screenshots | NOT RUN — needs staging URL |
| Vendor sandbox / production | BLOCKED-EXTERNAL |

Do not mark production live earn as PASS from this file alone.
