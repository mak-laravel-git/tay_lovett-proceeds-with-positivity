# Google AdSense Placement — Test Plan

**Status:** PLAN-ONLY (run when Phase 1+ implementation lands)  
**Companion:** [PLACEMENT_PLAN.md](./PLACEMENT_PLAN.md)  
**Style:** Aligned with `docs/provider-expansion/` checklist IDs (PASS / FAIL / BLOCKED / N/A)

---

## 1. Scope

### In scope

- Display AdSense slot presence, density flags, and SPA safety
- Regressions: AdSense never becomes a rewarded earn provider
- UX: Feed quotes, Earn Rewards, AdGate, SurveyPresenter offer rows (Phase 2)
- No points/wallet credit from AdSense views

### Out of scope

- Live AdSense revenue accuracy vs Google Dashboard (external)
- Full GDPR consent legal review (note BLOCKED if product requires consent before script)
- Third-party survey provider postback correctness (covered by existing ads suite)

---

## 2. Prerequisites

| Item | Notes |
|---|---|
| Feature flag | `display_enabled` (or equivalent) togglable |
| Density | `phase1` and `phase2` configs available |
| Test client / slots | AdSense test units or documented placeholder IDs |
| Accounts | Normal user for Feed / Earn / Studio gate |
| Browsers | Chromium + one mobile viewport (or device) |
| Evidence | Screenshots per GAP id; console log for duplicate pushes |

Record environment:

| Field | Value |
|---|---|
| Build / SHA | |
| Date | |
| Tester | |
| Density under test | phase1 / phase2 |
| `display_enabled` | on / off |

---

## 3. Automated tests (Pest / existing suite)

Run from repo root (adjust if CI uses a wrapper):

```bash
php artisan test --filter=PlaybookQaSuite
php artisan test --filter=AdsAdminPages
php artisan test --filter=AdGate
```

### Regression checklist

| ID | Check | Expected | Result |
|---|---|---|---|
| AUTO-01 | ADS-02: `GoogleAdSenseAdapter` never configures as reward provider | `isConfigured` false / no reward offers | |
| AUTO-02 | UX-01 / S-06: Earn page shows three rewarded providers; not AdSense cash card | No `google_adsense` in earn `providers` | |
| AUTO-03 | CROSS-03: Earnings provider filter excludes AdSense | No `google_adsense` option | |
| AUTO-04 | Admin lists AdSense as `standard_ads` placeholder / display, not launchable reward | Row present; not rewarded CTA | |
| AUTO-05 | AdGate / fallback: AdSense skipped in offer chain (`not_reward_provider`) | Surveys still load from TapResearch (or next configured) | |
| AUTO-06 | **New (when implemented):** `display_enabled=false` → Feed/Surveys HTML has no AdSense slot markers / `data-placement` | Assert absence in feature or browser test | |
| AUTO-07 | **New (when implemented):** Phase 1 density does not require Phase 2 option-row slots | Config-driven; tests for density helper if added | |

Mark AUTO-06 / AUTO-07 **N/A** until code exists; do not fail the suite on missing tests before implementation.

### Suggested new Pest cases (write during implementation)

1. Shared Inertia props omit slot markup path when flag off (controller/feature test).
2. Manager still records skip for `google_adsense` when fetching offers.
3. Enabling display config does **not** add AdSense to `SurveyController::index` providers array.

---

## 4. Manual QA matrix

Legend: **PASS** / **FAIL** / **BLOCKED** / **N/A** (phase not shipped).

### Core placement (GAP-01..10)

| ID | Phase | Check | Steps | Expected | Result | Evidence |
|---|---|---|---|---|---|---|
| GAP-01 | 1+ | Initial ad renders once per page load | Open `/feed` and `/surveys` with display on; count `initial` slots | Exactly one `initial` per page (not duplicated on soft nav if designed that way) | | |
| GAP-02 | 1 / 2 | Quote slots on feed | Open `/feed` with quotes; inspect each card | Phase 1: `quote_after` only. Phase 2: `quote_before` + `quote_after` | | |
| GAP-03 | 1 / 2 | Survey / offerwall before & after | Open Earn; open a survey provider and an offerwall provider; open AdGate from Studio | Phase 1: before only. Phase 2: before + after. Correct placement ID for survey vs offerwall | | |
| GAP-04 | 2 | Offer option slots | After quote-gated or Earn flow, open offer list with multiple rows | Ad before and after **each** option row | | |
| GAP-05 | 1+ | No points for AdSense view | Note points balance; view pages with ads for 2+ minutes; no survey complete | Balance unchanged; no new `AdActivity` for AdSense | | |
| GAP-06 | 1+ | Offer fallback chain | Disable TapResearch temporarily or force chain; ensure AdSense never selected as offer provider | Next reward provider or empty state — never AdSense list | | |
| GAP-07 | 1+ | Mobile + SurveyFrame | Mobile width; start a survey so `SurveyFrame` opens | No AdSense overlay on iframe; layout usable; Start/close still work | | |
| GAP-08 | 1+ | Remount / navigation | Navigate Feed → Earn → Feed; toggle filters; open/close AdGate | No flood of `adsbygoogle` pushes; console free of repeated push errors | | |
| GAP-09 | 1+ | Flag off | Set `display_enabled=false`; hard refresh Feed + Earn + Studio gate | Zero AdSense slots / script optional-off per design | | |
| GAP-10 | 1+ | Adblock / consent | Enable ad blocker; (if consent banner exists) deny then accept | Page usable without crash; with consent accept, slots may load | | |

### Extended checks (optional but recommended)

| ID | Check | Expected | Result |
|---|---|---|---|
| GAP-11 | Density `phase1` with Phase 2 code deployed | No `quote_before` / option-row / after-survey slots | |
| GAP-12 | Missing slot ID for one placement | That placement hidden; others still work | |
| GAP-13 | Missing `ad_client` | All slots hidden; no script errors thrown to user | |
| GAP-14 | Studio gate still unlocks quote after real survey complete | Unlock path unchanged; AdSense before content does not block Start | |
| GAP-15 | Wannads single synthetic offer row | Phase 2 option slots wrap the single wall row without breaking Start | |
| GAP-16 | Public `/q/{slug}` if slots added later | Ads do not break OG/share; N/A if out of scope | |

---

## 5. Phase-gated run order

### When validating Phase 1 only

1. AUTO-01..05  
2. GAP-01, GAP-02 (after only), GAP-03 (before only), GAP-05..10  
3. Mark GAP-04 and Phase 2 parts of GAP-02/03 as **N/A**

### When validating Phase 2

1. Re-run all Phase 1  
2. GAP-02 full before/after, GAP-03 after variants, GAP-04, GAP-11–15 as applicable

---

## 6. Pass / fail criteria

**Phase 1 release gate:** all applicable AUTO-* and GAP-01..03 (phase1), GAP-05..10 are **PASS**. No **FAIL** on reward-chain regressions.

**Phase 2 release gate:** Phase 1 gate + GAP-04 + updated GAP-02/03 + GAP-11 **PASS**, plus product/policy sign-off on density.

Any **FAIL** on GAP-05 or GAP-06 is a hard stop (money/trust).

---

## 7. Evidence template

For each GAP id:

```text
GAP-XX: PASS|FAIL|BLOCKED|N/A
Browser / device:
Density / flag:
Notes:
Screenshot path:
Console notes:
```

Store screenshots under a local QA folder or attach to the PR description (repo may use `docs/provider-expansion/MANUAL_QA_EVIDENCE.md` style — optional mirror file later: `docs/google-ads-placement/MANUAL_QA_EVIDENCE.md`).

---

## 8. Results log (fill when executed)

| Suite | Date | SHA | Outcome |
|---|---|---|---|
| Automated AUTO-01..07 | | | |
| Manual GAP-01..10 | | | |
| Phase under test | | | phase1 / phase2 |

**Overall:** NOT RUN (docs only until implementation)
