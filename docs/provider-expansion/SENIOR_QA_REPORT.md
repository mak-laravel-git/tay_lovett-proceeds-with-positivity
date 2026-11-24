# Senior QA Report — Wannads + TheoremReach + Regression

**Playbook:** `new_features_1/02_HUMAN_QA_WANNADS_THEOREMREACH_AND_REGRESSION_PLAYBOOK.md`  
**Role:** Senior QA (LOCAL_FIXTURE + code inspection; no production money/ad clicks)  
**Date / timezone:** 2026-11-24 (Asia/Dhaka)  
**Branch / SHA:** `feature/provider-expansion-4networks` @ `218944a` (+ uncommitted feature work on branch)  
**Evidence class:** `LOCAL_FIXTURE` (Pest) — **not** PROVIDER_SANDBOX, **not** PRODUCTION  
**Isolated QA DB:** YES (PHPUnit sqlite in-memory / testing env)  
**Production touched:** NO  

---

## 1. Environment checklist (playbook §1.3)

```text
Test session ID: QA-LOCAL-2026-11-24-SENIOR
Tester and role: Senior QA (agent-executed fixture + code review)
QA/staging URL: NOT PROVIDED — browser smoke NOT RUN against staging
Git commit SHA: 218944a (base) + feature/provider-expansion-4networks working tree
Backend: Laravel 13.43 / PHP 8.4
Frontend: Inertia React 3 / React 19 (code review)
Browser / viewport: N/A for fixture suite; mobile browser NOT RUN
Database: ISOLATED QA: YES
Queue worker: N/A for sync callback path (WAN-13 queue pause NOT RUN)
Provider fixtures: YES (TapResearchFixtures, WannadsFixtures, TheoremReachFixtures)
AdSense: CONFIG READ ONLY / placeholder adapter
TapResearch: LOCAL_FIXTURE MOCK
Wannads: LOCAL_FIXTURE MOCK
TheoremReach: LOCAL_FIXTURE MOCK (hash algorithm interim)
TheoremReach coexistence written permission: PENDING / NO
Cash-redeemable rewards permitted: PENDING / NO (defaults false)
Baseline provider tests completed: YES (Ads suite)
```

**Safety gate QA-0:** PASS for local fixtures. Staging URL / human accounts: **BLOCKED-EXTERNAL**.

---

## 2. Automated evidence executed

| Suite | Result |
|---|---|
| `tests/Feature/Ads` (full) | **172 passed** / 1306 assertions |
| `tests/Feature/Ads/PlaybookQaSuiteTest.php` (mapped IDs) | **26 passed** |
| `tests/Feature/AccessTest.php` + `ShellTest.php` | **9 passed** |

Playbook harness file: `tests/Feature/Ads/PlaybookQaSuiteTest.php`.

---

## 3. Results matrix (playbook IDs)

### Legend
- **PASS** = executed with fixture/code evidence, expected outcome observed  
- **PASS\*** = code-level PASS; browser/visual portion still needs staging  
- **BLOCKED-EXTERNAL** = cannot PASS without vendor/staging/legal  
- **NOT RUN** = needs human browser/staging  
- **NOT IMPLEMENTED** = product gap  

### Smoke S-01…S-12

| ID | Result | Evidence / notes |
|---|---|---|
| S-01 | NOT RUN | Needs staging guest UI |
| S-02 | PASS\* | Access/Shell + auth patterns exist; no staging login session |
| S-03 | NOT RUN | AI generation needs staging/service |
| S-04/S-05 | NOT RUN | Browser |
| S-06 | PASS | PlaybookQa UX-01: `/surveys` shows 3 rewarded providers |
| S-07 | PASS\* | AdSense not earn card; ADS-02 adapter never configures rewards |
| S-08 | PASS\* | TAP fixtures + TapResearch still enabled; live launch needs mock HTTP/staging |
| S-09 | PASS | Wallet route OK for authenticated user |
| S-10 | PASS | Admin 4 providers; secrets masked |
| S-11 | NOT RUN | Withdrawals admin UI browser |
| S-12 | NOT RUN | Mobile viewport |

### UX-01…UX-05

| ID | Result | Notes |
|---|---|---|
| UX-01 | PASS | Three cards; AdSense excluded from earn list |
| UX-02 | PASS | Disabled Wannads + TR coexistence block server-side |
| UX-03 | PASS\* | Empty offers JSON path exists; dedicated outage UX messaging generic |
| UX-04 | PASS | Guest redirect; User B sees empty own earnings |
| UX-05 | NOT RUN | Mobile/a11y browser |

### Wannads WAN-01…WAN-13

| ID | Result | Notes |
|---|---|---|
| WAN-01 | PASS\* | Admin settings/masking/callback URL; browser click path not filmed |
| WAN-02 | PASS | Official `earn.wannads.com` URL; secret absent |
| WAN-03 | NOT RUN / P2 | No dedicated no-inventory Wannads fixture beyond empty offers |
| WAN-04 | PASS | Disabled → no offers/provider |
| WAN-05 | PASS | Credited + `OK` + one ledger |
| WAN-06 | PASS | 5× duplicate → 1 credit |
| WAN-07 | PASS | Tamper → ERROR / 401 |
| WAN-08 | PASS | Unknown user → no credit |
| WAN-09 | PASS | Reject after credit → one reversal |
| WAN-10 | PASS | Reject-before-credit blocks later credit |
| WAN-11 | PASS\* | Reject path uses status; negative reward classified rejected (covered in W-007/W-009 style) |
| WAN-12 | PASS | shared-ref-777 Wannads + TapResearch distinct |
| WAN-13 | NOT RUN | Queue pause needs staging ops |

### TheoremReach THEO-01…THEO-14

| ID | Result | Notes |
|---|---|---|
| THEO-01 | PASS | Production enable denied; launch blocked without coexistence/sandbox |
| THEO-02 | PASS\* | Config/test connection code path; Approved ≠ Tested |
| THEO-03 | PASS | Signed entry verifies; opaque user |
| THEO-04 | PASS\* | No credit without callback (code contract); browser close not filmed |
| THEO-05 | NOT RUN / partial | No inventory / DQ / partial fixtures limited |
| THEO-06 | NOT RUN | Mobile iframe/popup |
| THEO-07 | PASS\* | Fixture HMAC PASS; **dashboard official vector still BLOCKED-EXTERNAL** |
| THEO-08 | PASS | `debug=true` → `test_only`, 0 points |
| THEO-09 | PASS | Duplicate → one credit |
| THEO-10 | PASS | Invalid hash rejects |
| THEO-11 | PASS\* | Partner mismatch / unknown user paths exist; partial coverage |
| THEO-12 | PASS | Event webhook reverses once |
| THEO-13 | BLOCKED-EXTERNAL / partial | Points rules separate; official TR exchange vs 33.33% cash not live-approved |
| THEO-14 | BLOCKED-EXTERNAL | Legal/consent go-live checklist is owner responsibility |

### AdSense ADS-01…ADS-06

| ID | Result | Notes |
|---|---|---|
| ADS-01 | PASS\* | Presenter/component still registered; no live slot fill check |
| ADS-02 | PASS | Adapter never awards; parseCallback null |
| ADS-03 | PASS\* | Earn page has no AdSense cash CTA (code/Inertia) |
| ADS-04 | NOT RUN | CSP/network console |
| ADS-05 | PASS\* | Admin row `standard_ads`; no cash exchange field for AdSense |
| ADS-06 | NOT RUN | Ad-blocker browser |

**AdSense regression sign-off (code):** conditional PASS\* — **not** LIVE fill verified. No live ad clicks performed (correct).

### TapResearch TAP-01…TAP-08

| ID | Result | Notes |
|---|---|---|
| TAP-01 | PASS\* | Adapter/routes unchanged; opaque IDs preserved in fixtures |
| TAP-02 | PASS\* | Existing ineligible/empty coverage in Ads suite |
| TAP-03 | PASS | Valid signed credit |
| TAP-04 | PASS | Duplicate once |
| TAP-05 | PASS | Invalid signature rejects |
| TAP-06 | PASS\* | Unsuccessful outcome / reject path in TapResearchPostbackTest |
| TAP-07 | PASS\* | No destructive migration in this expansion; historical schema preserved |
| TAP-08 | PASS | Admin TapResearch row persists alongside new providers |

**TapResearch regression sign-off (LOCAL_FIXTURE):** **PASS** for P0 money/security cases tested.

### Cross-provider CROSS-01…CROSS-15

| ID | Result | Notes |
|---|---|---|
| CROSS-01 | PASS | Four admin rows; types present |
| CROSS-02 | PASS\* | Disable Wannads leaves TapResearch enabled |
| CROSS-03 | PASS | Earnings provider filter isolates; no AdSense option |
| CROSS-04 | PASS\* | UserRevenueShare floor(1/3) + cashout gate; live approved basis BLOCKED |
| CROSS-05 | PASS\* | Test/debug never available cash; hold-period UI NOT IMPLEMENTED |
| CROSS-06 | NOT RUN | Concurrent withdrawal race harness not added |
| CROSS-07 | PASS | User B isolation |
| CROSS-08 | PASS | User forbidden on admin providers |
| CROSS-09 | PASS | Wannads payload on TR webhook unauthorized |
| CROSS-10 | NOT RUN | Outage isolation browser |
| CROSS-11 | NOT RUN | Manual withdrawal admin browser |
| CROSS-12 | NOT IMPLEMENTED / partial | Dedicated hold/settlement UX missing |
| CROSS-13 | PASS\* | Launch URLs omit secrets/email (fixture) |
| CROSS-14 | NOT RUN | Session expiry mid-flow browser |
| CROSS-15 | PASS\* | Admin audits exist; kill switch = disable + compliance |

### Core CORE-01…CORE-06

| ID | Result | Notes |
|---|---|---|
| CORE-01…06 | NOT RUN / PASS\* | Access/Shell smoke PASS; full browser product QA needs staging + original playbook |

---

## 4. No-go criteria review (playbook §0)

| Criterion | Status |
|---|---|
| Unverified callback increases real wallet | **PASS** — invalid/debug rejected |
| Duplicate credit | **PASS** — idempotent |
| Reversal ignored while funds withdrawable | **PASS** for points reverse; cash hold UX incomplete |
| AdSense views → cash | **PASS** — impossible via adapter |
| TR live coexistence without written approval | **PASS** — hard gate |
| Secrets exposed | **PASS** — masked in admin Inertia |
| Wrong-user payments | **PASS** — unknown user no credit |
| Role isolation broken | **PASS** — user forbidden admin |
| TapResearch disabled unexpectedly | **PASS** — remains enabled independently |
| Production test funds withdrawable | **N/A local** — cashout permission default false |

**Local no-go:** none triggered.  
**Release no-go for production live TR/Wannads:** still **YES** until external approvals + staging human QA + TR dashboard hash confirmation.

---

## 5. Provider accountability cards

### Google AdSense
- Product: Standard Ads  
- Code: CODE_READY (placeholder / non-earning)  
- Callback signed: N/A  
- Cash to users: **NO**  
- Overall: **CODE_READY** (live fill APPROVAL_BLOCKED)

### TapResearch
- Product: Hosted/Survey Feed  
- Callback: PASS (LOCAL_FIXTURE)  
- Duplicate: PASS  
- Overall: **SANDBOX_VERIFIED (fixture)** / live account APPROVAL_BLOCKED

### Wannads
- Product: Offerwall  
- Callback / duplicate / reverse: PASS (LOCAL_FIXTURE)  
- Cash withdrawal permitted: PENDING  
- Overall: **CODE_READY** → needs publisher keys for SANDBOX_VERIFIED vendor

### TheoremReach
- Product: Signed offerwall + event webhook  
- Coexistence written approval: **NO / PENDING**  
- Callback hash vs dashboard: **BLOCKED-EXTERNAL** (interim HMAC)  
- Debug isolation: PASS  
- Reversal channel: PASS (event webhook fixture)  
- Overall: **CODE_READY + APPROVAL_BLOCKED** (must not enable production)

---

## 6. Defects found this QA session

| ID | Severity | Title | Status |
|---|---|---|---|
| — | — | No new P0 money defects in LOCAL_FIXTURE suite | — |
| QA-P1-001 | P1 | TR reward hash not dashboard-vector confirmed | OPEN / BLOCKED-EXTERNAL |
| QA-P1-002 | P1 | Staging browser smoke S-*/UX-05/CORE not executed | OPEN / NOT RUN |
| QA-P2-001 | P2 | No dedicated Admin “Hold Payouts for Provider” | OPEN / NOT IMPLEMENTED |
| QA-P2-002 | P2 | Settlement hold period before usable balance | OPEN / NOT IMPLEMENTED |

---

## 7. Exit gates (playbook §12)

| Gate | Result |
|---|---|
| QA-0 safety | **PASS** (local only) |
| QA-1 baseline AdSense/TR | **PASS\*** / TR fixture PASS |
| QA-2 UX/WAN launch/THEO gate | **PASS** (fixture) |
| QA-3 callback money safety | **PASS** (fixture) |
| QA-4 cross-provider | **PASS** with noted NOT RUN items |
| QA-5 post-integration ADS/TAP retest | **PASS** (Ads suite 172) |
| QA-6 full core human | **NOT RUN** |
| QA-7 compliance/legal/live | **BLOCKED-EXTERNAL** |
| QA-8 production smoke | **NOT RUN** (do not run) |

---

## 8. Senior QA verdict

**Ship decision for code merge to staging:** **GO** (feature branch), with providers **disabled** / TR coexistence **false**.  

**Ship decision for production live earn networks:** **NO-GO** until:
1. Staging human QA (S-*, UX-05, CORE-*, WAN-02 live iframe, THEO-06)  
2. Publisher credentials + Wannads/TR sandbox vendor verification  
3. TheoremReach written coexistence authorization  
4. Dashboard-confirmed TR callback hash vector  
5. Cashout policy decisions recorded  

**Client-facing statement:**  
Four integrations are prepared and fixture-verified for money/security basics. Individual provider approval and permitted reward models still govern activation. Local green tests are **not** live approved earning.
