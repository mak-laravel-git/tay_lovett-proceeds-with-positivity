# PROCEEDS WITH POSITIVITY — HUMAN MANUAL QA PLAYBOOK
## Wannads + TheoremReach New Integration and Mandatory Google AdSense + TapResearch Regression

**Version:** 1.0 | **Date:** 2026-11-24 | **Audience:** project owner, PM, client-side tester, QA analyst | **Format:** click-by-click browser and admin verification guide

**Application under test:** `https://proceedswithpositivity.com/` is the owner's production domain, **not the default QA test environment**. Use the actual QA/staging URL identified by coding agent, with test users, test signing keys and isolated DB. NEVER fabricate traffic or send monetary testing callbacks to production.

**Companion agent specification:** `01_AI_AGENT_WANNADS_THEOREMREACH_IMPLEMENTATION_CONTEXT.md`

**Read earlier baseline QA guide too:** `02_HUMAN_MANUAL_QA_TEST_PLAYBOOK.md` (2026-11-19). This document adds deep four-provider testing; it does NOT replace login, quote, wallet or original product tests.

**Requirement source:** The client confirmed on 2026-11-23 that **Wannads and TheoremReach must be integrated in addition to Google AdSense and TapResearch**. The existing two providers are reported implemented by the project owner; code-level status must still be confirmed by the agent. TheoremReach and Wannads real availability, cash reward permission and approval are external dependencies—not things a browser test can manufacture.

---

## 0. QA mission and PASS / BLOCKED standard

We must prove **both**:

1. **New capability works safely:** Wannads and TheoremReach real integration surfaces, secure server callbacks, ledger/wallet actions, control states and reversal/reconciliation.
2. **No regression:** the original **Google AdSense and TapResearch** integrations, main Quote Feed, AI generation, login/registration, admin settings, wallets and withdrawals still behave as before.

The test team should never accept only a screenshot of four provider logos, a local green badge, or a developer statement as proof of working financial integration. Distinguish code readiness, sandbox verification and live provider approval. Actual vendor financial actions require real publisher agreement and authorized testing.

**Status values:** `PASS` (steps executed, evidence captured, correct result), `FAIL` (expected behavior absent or wrong), `BLOCKED-EXTERNAL` (requires approval, keys, account permissions, legal pages or third party), `NOT IMPLEMENTED`, `NOT APPLICABLE` (with reason), `NOT RUN`. **Do not mark a test PASS just because functionality is described in an AI context file.**

**Severity:** `P0` high-risk money/security/major regression; `P1` core provider or business flow broken; `P2` UX/edge case; `P3` cosmetic/optional.

**No-go criteria:** any unverified callback increases real wallet; duplicate credit; a negative/reversal ignored while funds become withdrawable; AdSense views generate cash rewards; TheoremReach live coexistence without required written approval; provider secrets exposed; wrong-user payments; user/admin role isolation broken; old TapResearch provider disabled unexpectedly; production test funds withdrawable.

## 1. Prepare a safe test environment

### 1.1 Required evidence and accounts

Ask the developer for the following in a **secure internal handoff**, not in client chat or public screenshots:

- Staging URL, branch/commit SHA, test deploy time and which database/queue it uses.
- Basic admin and user QA account access via approved secure sharing method, no real client password in this document.
- Vendor **sandbox/test products or signed mock fixture simulator** and explicit indication whether a callback is `LOCAL_FIXTURE`, `PROVIDER_SANDBOX` or approved `PRODUCTION`.
- Provider approval/compliance status, including documented written authorization for TheoremReach to coexist with other incentivized providers and permission to issue withdrawable cash rewards; if missing, live tests must be `BLOCKED-EXTERNAL`.
- Admin-only provider-settings screen, transaction/reconciliation logs, wallet/withdrawal pages and previous baseline regression results.
- One clearly labelled fixture for each: valid award, invalid signature, duplicate, reversal, malformed event, unknown user, and test/debug callback.

### 1.2 QA identities (example identifiers, not real accounts)

| Name | Role | Purpose |
|---|---|---|
| `QA-ADMIN` | Admin/superadmin | Configure providers, inspect financial events, audit logs, compliance gates |
| `QA-USER-A` | Standard verified user | View offerwall, complete simulated earned activity, wallet checks |
| `QA-USER-B` | Different verified user | Check user separation and money isolation |
| `QA-NEW` | New unverified user | Registration, policies, email verification state |
| `QA-SUSPENDED` | Suspended user | No new offer launches |
| `QA-GUEST` | Logged-out/incognito | No unauthorized provider access |
| `QA-STAFF` | Limited admin if present | Ensure secrets, approvals and payout controls restricted |

For all example emails use `@example.test`, not any real person's address. Never use personal PayPal/Cash App data, live ad clicks, unapproved survey manipulation or real card/payment transfers in testing. User rewards in QA must be clearly separated from withdrawable production cash.

### 1.3 Environment checklist — record before clicking

```text
Test session ID:
Tester and role:
Date / timezone:
QA/staging URL:
Git commit SHA / deployed build ID:
Backend and frontend version:
Browser / version:
Viewport / device:
Database: ISOLATED QA (yes/no):
Queue worker active (yes/no):
Provider fixtures enabled (yes/no):
AdSense environment: TEST/CONFIG READ ONLY:
TapResearch environment: SANDBOX/MOCK:
Wannads environment: SANDBOX/MOCK:
TheoremReach environment: SANDBOX/MOCK:
TheoremReach coexistence written permission: YES/NO/PENDING:
Cash-redeemable rewards permitted per provider: YES/NO/PENDING:
Baseline provider tests completed: YES/NO:

IMPORTANT: any NO for isolated QA DB means STOP all financial callback tests.
```

### 1.4 General navigation convention

The actual repository's route names must be confirmed by the developer. References below to **Earn Rewards**, **My Earnings**, **Wallet**, **Admin > Integrations** and **Admin > Finance** mean the equivalent existing visible screen if its wording differs. QA records the real label/path in the report rather than treating an assumed URL as proof a feature is missing. The client's original design is login-first with a Quote Feed and separate admin; preserve existing production-like layout.

## 2. Fast smoke test before provider-specific QA (10–15-minute checklist, not a time promise)

Run *before* the new code deploy and *again after* the full new integration.

| ID | Click / action | Expected | Priority |
|---|---|---|---|
| `S-01` | Fresh incognito → website | Login-first/expected guest interface, no broken app | P0 |
| `S-02` | Valid QA-USER-A login | Dashboard/Quote Feed opens, no error | P0 |
| `S-03` | Feed → Generate Quote → choose category → Generate | Legitimately generates or clearly reports AI service unavailable | P1 |
| `S-04` | Quote → Save/Unsave | Same quote persists after refresh correctly | P1 |
| `S-05` | Quote → Copy/Share | Copies correct URL/text, social/mail/SMS fallback safe | P1 |
| `S-06` | Earn Rewards page | Three rewarded providers conceptually visible if enabled; pending statuses truthful | P1 |
| `S-07` | Normal AdSense placements | Existing standard ad locations structurally intact; no cash CTA | P0 |
| `S-08` | TapResearch card → launch in QA | Existing provider launch still works as it did before | P0 |
| `S-09` | Wallet → history | Existing balances and transactions unchanged unexpectedly | P0 |
| `S-10` | Admin → Integrations | All four provider config/status rows identifiable; no secret printed | P0 |
| `S-11` | Admin → Finance / Withdrawals | Existing history and controls still functional | P0 |
| `S-12` | Narrow mobile width → menu → Earn | No horizontal scroll, cards/buttons usable | P1 |

**Comparison method:** capture `before` screenshot and route summary for old AdSense and TapResearch, then `after` screenshots. Include any differences in `BASELINE_VS_AFTER.md` or QA evidence tracker.

---

## 3. Functional tests: user-facing provider selection

### UX-01 — Provider cards and correct ad type (P0)

**Preconditions:** `QA-USER-A` logged in, statuses configured for QA.

**Click path:**
1. Go to main Quote Feed/dashboard.
2. Click **Earn Rewards** (or app's corresponding nav item).
3. Read all visible provider headings and descriptions.
4. Find **TapResearch**, **Wannads** and **TheoremReach** (or see appropriately labelled pending/unavailable state if policy blocks display).
5. Find any **Google AdSense** normal placement elsewhere on site.

**Expected:** user reward choices are provider-specific and accurately labelled. Google AdSense is **not** advertised as a cash-reward offerwall; no “watch Google ad, earn dollars” CTA. An inactive TheoremReach must not open surveys just because the provider card exists. The other two providers' cards must not disappear accidentally after adding TheoremReach.

**Evidence:** screenshot of provider page and AdSense normal ad region with sensitive information hidden; note exact UI labels.

### UX-02 — Provider unavailable / approval pending (P1)

1. Admin changes Wannads to `PENDING_APPROVAL` or `DISABLED` in QA.
2. QA-USER-A refreshes Earn Rewards.
3. Click the Wannads launch button if shown.
4. Repeat for TheoremReach with `COMPLIANCE_BLOCKED` (no written coexistence permission).

**Expected:** launch disallowed server-side; display readable status (`Pending provider approval`, `Not yet available` etc.); **no iframe/new tab** and no new cash transaction. Refresh and direct route visit must enforce same rule. Active TapResearch and Quote Feed must remain usable.

### UX-03 — Loading, empty and error states (P1)

1. Developer uses controlled QA fixture to simulate provider with no inventory.
2. Open the provider page/iframe.
3. Simulate slow response or provider outage through approved mock.
4. Click **Retry** if present, then **Back to Rewards**.

**Expected:** loading indicator, meaningful no-offers message, safe retry/back, no blank screen or unhandled error; no fake pending/available earnings.

### UX-04 — Cross-user isolation and route security (P0)

1. Log in as QA-USER-A; visit Earn Rewards and My Earnings; note masked transaction IDs.
2. Log out. Log in as QA-USER-B in a separate private session.
3. Try to open the exact earnings link/ref from A (only with safe QA data).
4. As guest try to open the provider launch URL directly.

**Expected:** B cannot read A's transactions or receive A's provider token; unauthorized accesses are denied; guest goes to login or gets 401/403; no provider launch is tied to arbitrary URL user ID.

### UX-05 — Mobile and accessibility (P1)

1. Browser responsive mode at 320×700, 375×812 and 430×932.
2. Open mobile menu/bottom navigation, enter Earn Rewards.
3. Tab using keyboard (desktop emulation), click provider cards, use Back to Rewards.
4. Test screen reader accessibility where available; inspect focus, labels and contrast.

**Expected:** no inaccessible/covered buttons, no sideways scrolling, loading status announced, keyboard controls usable, iframe fallback visible and focus does not get permanently trapped.

---

## 4. Wannads — step-by-step UI testing

### WAN-01 — Admin configuration form works (P1)

**Precondition:** Admin test environment, official test account info or a fixture configuration.

1. Log in as `QA-ADMIN`.
2. Click **Admin > Advertising/Integrations > Providers > Wannads > Configure**.
3. Verify separate fields for account/app key, signing secret or secure input, callback URL, environment and approval statuses.
4. Save a **test** public key and private signing secret using approved secure mechanism.
5. Reload page, view saved configuration.
6. Click **Test Configuration** if supported.

**Expected:** config persists; private secret is masked/not returned in HTML or JS payload; callback route and test result displayed independently; correct `Pending Approval` status if vendor has not approved production. A successful local config test **must not** automatically turn live mode on.

### WAN-02 — Legitimate offerwall launch (P1)

1. Admin places Wannads into `SANDBOX`/approved QA-enabled state, with policy gates satisfied in QA.
2. Log in as QA-USER-A.
3. Click **Earn Rewards → Wannads → Explore Offers**.
4. Observe embedded offerwall or supported new tab, its loading/fallback and return route.
5. Close provider view without completing an offer.
6. Visit **My Earnings**, **Wallet**.

**Expected:** official `earn.wannads.com` page is used when actual test account is available; pseudonymous user ID consistent; no private secret leaks in URL; closing without completing anything generates **zero reward**. In pure fixture mode the UI must say test/demo, not falsely claim external activity succeeded.

### WAN-03 — Device/region no offers (P2)

1. Use an authorized test location/provider no-offer fixture, not VPN fraud against a live network.
2. Open Wannads and inspect empty/offers-unavailable state.

**Expected:** friendly message; no false earning, page remains responsive, user can navigate back and use other permitted providers.

### WAN-04 — Pending/disabled cannot launch, even by direct URL (P0)

1. Set Wannads `DISABLED`.
2. QA-USER-A clicks any remaining CTA and also tries bookmarked former launch route.

**Expected:** server denies active launch, no real offer URL returned, no new rewards; historical earned records still visible.

---

## 5. Wannads — postback, duplicate, reversal and money safety

**Tester note:** The following actions run with an agent-provided **safe QA callback simulator** or vendor-sanctioned test, never guessed live production requests. The simulator must generate valid signatures using the configured QA secret; tester should only click predefined fixture buttons, not paste real secrets into browser tools. Capture postback event ID/result and ledger state in admin UI.

### WAN-05 — Valid signed completion (P0)

1. Reset QA-USER-A wallet to a clean **QA ledger baseline** using approved QA reset/seed tool (never delete real records).
2. Admin → Wannads → **Test Callback** → select fixture `Valid credited conversion W-100`.
3. Run once; note callback result (`OK` expected for accepted Wannads app postback) and raw reward unit.
4. Open Admin > Transactions filtered `Wannads`, then QA-USER-A > Earnings/Wallet.

**Expected:** exactly **one** accepted provider event, correct mapped user, accurate amount/unit and approved 33.33% user allocation or appropriately held/test state; no real withdrawable funds. If the sample sends payout USD and reward points, they are not conflated. Status corresponds to QA environment (`SANDBOX`/`PENDING`).

### WAN-06 — Duplicate same transaction five times (P0)

1. From WAN-05 fixture, click **Resend** 5 times with the **same** `transaction_id`, status and signature.
2. Refresh Admin Transactions and User Wallet.

**Expected:** duplicate log entries/attempt count may increase but original financial credit remains **exactly one**, never 6x. Provider acknowledgements do not invent balances.

### WAN-07 — Tampered reward/signature (P0)

1. Execute predefined `Invalid Signature` fixture: original signed amount is changed while signature remains unmodified.
2. Check Admin webhook event and user wallet.

**Expected:** event rejected/quarantined; alert/log states signature mismatch; **zero new wallet credit**. The API must not reveal signing secret in response or error page.

### WAN-08 — Unknown/foreign user ID (P0)

1. Send QA fixture with a non-mapped partner `user_id` or a TheoremReach/TapResearch opaque ID.
2. Inspect results.

**Expected:** refused/quarantined; cannot credit user A/B. A matching numeric ID alone is not authorization.

### WAN-09 — Rejection after successful credit (P0)

1. Complete WAN-05 valid test setup.
2. Select fixture `Rejected W-100`, same original transaction ID with proper authenticated status.
3. Inspect original transaction, new adjustment and wallet.
4. Resend the same rejection.

**Expected:** original credit marked reversed/invalidated with **one** compensating ledger row; available amount corrected once; repeat rejection does nothing additional. Credit history is not deleted.

### WAN-10 — Rejection before credit (P0)

1. Use a new transaction ID in test fixture and send `rejected` **first**.
2. Then send an apparently valid `credited` for the **same** transaction ID.

**Expected:** no erroneous final positive available balance; out-of-order state handled safely or explicitly quarantined pending review.

### WAN-11 — Negative reward vs rejected status (P0)

1. Execute vendor-documented test variant where rejected callback has negative reward or `status=rejected`.
2. Inspect ledger.

**Expected:** never double subtract due to processing both negative amount and rejected status; correct variant chosen from official provider docs. For a legacy numeric-status account, only configured mapping is valid.

### WAN-12 — Duplicate collision across providers (P0)

1. Process Wannads QA transaction `shared-ref-777`.
2. Process separate TapResearch QA transaction `shared-ref-777` with its **own valid test signature**.
3. Filter Admin transactions by source.

**Expected:** two independently verified provider events, correct separate users/amounts if configured, no accidental dedup across different providers. A single provider can't spoof another through a `provider=` query flag.

### WAN-13 — Retry while queue/broker paused (P0)

1. In isolated staging, developer safely pauses the test queue worker.
2. Send a valid Wannads callback once and observe acknowledged inbox status.
3. Resume worker, replay callback and inspect ledger.

**Expected:** either durable inbox accepted and processed once, or provider receives retry-safe failure. Never acknowledge-and-lose event or credit twice. This is a controlled QA environment test, not production.

---

## 6. TheoremReach — user and configuration testing

### THEO-01 — Provider compliance gate before activation (P0)

1. Log in as QA-ADMIN.
2. Go to **Admin > Integrations > TheoremReach**.
3. Verify status of account approval, website/domain, cash-reward permission and **written approval to coexist with TapResearch + Wannads**.
4. Leave coexistence evidence as `NOT CONFIRMED`.
5. Try **Enable Production** as admin.
6. Log in as QA-USER-A and try TheoremReach launch.

**Expected:** Enable denied with clear missing requirement; no live TheoremReach survey access; user sees `Pending provider activation` or equivalent. **No simulated checkbox** can replace required written approval. Google AdSense, Wannads and TapResearch remain unaffected.

### THEO-02 — TheoremReach configured but not approved (P1)

1. Admin enters approved public QA key/placement, stores private signing key securely.
2. Click Save Settings and **Test Connection**.
3. Reload page, inspect status.

**Expected:** technical config/test can succeed but **Provider Approved** remains false; environment modes separate. Secret masked, never in browser HTML/props.

### THEO-03 — Signed offerwall URL generation (P0)

1. Use an official sandbox account/fixture with required permissions in QA.
2. Click **Earn Rewards → TheoremReach → Take Surveys**.
3. Inspect the launched host and visible browser behavior (without exposing link signature to public logs).
4. Developer validates generated HMAC against an official publisher-dashboard test vector; QA inspects `Signature Match: PASS` evidence, not raw secret.

**Expected:** official TheoremReach host, unique launch transaction, opaque mapped user, approved exchange rate; valid official signed URL where offerwall product selected. If actual account uses official JS SDK instead, follow documented path and demonstrate correct initialized modal. Do not mix web SDK and offerwall params indiscriminately.

### THEO-04 — No completion = no earnings (P0)

1. Open provider using an authorized sandbox account.
2. Close immediately without completing survey.
3. Return to Wallet/Earnings and refresh.

**Expected:** **no real or test credit** merely from page open, redirect, iframe close or JS onReward-like UI event unless independently verified S2S event exists. No guaranteed payout copy.

### THEO-05 — No surveys, disqualification and partial rewards (P1)

1. Developer configures no-inventory test response; click TheoremReach card.
2. Repeat with disqualified/no-reward outcome.
3. Repeat with official test callback that confirms an eligible partial reward.

**Expected:** no inventory/disqualification without payout shows zero; partial reward only when signed provider callback verifies it, with proper amount, hold and test label. Browser outcome alone is not financial evidence.

### THEO-06 — Mobile popup / third-party iframe behavior (P1)

1. On 375px mobile, open provider using configured iframe or new-tab experience.
2. If iframe supports TheoremReach login popup, test expected popup path; close and return.
3. Use supported browser restrictions/third-party cookie modes.

**Expected:** provider surface opens or safe new-tab alternative; no disabled popup due to over-restrictive iframe sandbox; no broken browser navigation or horizontal overflow. Never bypass cross-origin restrictions to make login work.

---

## 7. TheoremReach — signed callback, debug/reversals and finance tests

### THEO-07 — Valid signed sandbox reward (P0)

1. Admin selects official TheoremReach **Test Callback** or dashboard-confirmed fixture for the approved product variant.
2. Ensure QA-USER-A mapped ID/placement is correct.
3. Trigger once; inspect provider inbox and wallet ledger.

**Expected:** hash/signature verified using the current publisher dashboard contract, correct user/placement, one pending/test financial event; no real available cash. Record actual callback fields/method in secure test evidence. If vendor signature contract is unavailable, test status = `BLOCKED-EXTERNAL`, not PASS.

### THEO-08 — Debug callback cannot pay (P0)

1. Trigger official/fixture TheoremReach callback with `debug=true` and otherwise valid hash.
2. Open User A Wallet, Admin Finance and withdrawal UI.

**Expected:** callback may appear in admin sandbox audit, but **zero production-redeemable credit**, no withdrawal increase, clearly marked debug/test. Production entry link must not include `debug=true`.

### THEO-09 — Duplicate verified callback (P0)

1. Repeat THEO-07 valid test fixture 5 times using exact same unique reward transaction ID.
2. Refresh wallet and audit.

**Expected:** one ledger credit only; duplicates acknowledged/recorded safely without double allocation.

### THEO-10 — Invalid hash / altered exchange rate (P0)

1. Run wrong-hash callback fixture.
2. Run attempted signed entry URL tampering/exchange-rate edit fixture vetted by developer (do not attack real provider).

**Expected:** invalid callback cannot credit; URL signing test detects tampering; secret not exposed; unexpected rate cannot be used to increase earnings. QA is allowed to verify outcome without seeing secret itself.

### THEO-11 — Wrong user / partner / placement (P0)

1. Run callback fixtures with unknown user, mismatched partner ID or different site account.
2. Inspect ledger and admin anomaly log.

**Expected:** reject/quarantine, zero user credits and appropriate alert/audit note.

### THEO-12 — Reversal/ban via separate official event channel (P0)

1. Process a verified, isolated QA reward.
2. Trigger the **separate** official Event Webhook reversal or import a provider-authenticated QA reconciliation export with the *same source transaction*.
3. Inspect account/transaction state and Wallet. Repeat reversal.

**Expected:** one compensating event, pending/available adjusted per safe rules, no double reversal. A revoked/blocked user cannot launch further surveys. If Event Webhook or official reconciler is not available, record `BLOCKED-EXTERNAL` and ensure these funds cannot be prematurely paid out. The standard TheoremReach Reward Callback itself **is not** a replacement for reversal handling.

### THEO-13 — Exchange rate/rounding correctness (P0)

1. Developer loads a signed TheoremReach QA fixture with known official point-to-dollar exchange rate and currency labels.
2. Compare displayed reward points, equivalent eligible user money (if approved), and provider publisher payout.
3. Repeat with small units and partial rewards.

**Expected:** exact decimal math; no 33.33% double deduction; no use of raw points as USD; no confusing `3 coins` with `$3`. Where cash redemption not authorized, show points as nonwithdrawable or provider disabled, as approved.

### THEO-14 — Provider approval / consent prerequisites (P1)

1. Admin tries to enable TheoremReach without Privacy Policy/Terms or required active sign-up consent evidence.
2. Open public policy links and account signup form.
3. Inspect checkbox behavior, linked text and form submission.

**Expected:** live enable blocked until reviewed legal policies and required consent/anti-abuse controls are present; checkbox not prechecked where consent must be active, validation clear; no invented policy text assumed approved.

---
## 8. MANDATORY Google AdSense regression — existing provider #1

**IMPORTANT:** Do not click live ads, ask friends to click, automate ad refreshes, simulate invalid impressions or promise revenue. AdSense supports normal publisher advertising, NOT cash payouts to users for ordinary ad viewing. For an approval-pending account, inspect code/config and DOM safely; the lack of live fill may be an external blocker, not a code failure.

### ADS-01 — Original page/slot placement remains present (P0)

**Before & after comparison:**
1. Log in as QA-USER-A and open the same Quote Feed/main page used in the pre-change screenshot.
2. Scroll through spots where normal AdSense ads were configured.
3. Check ad component container, loading/empty state and page alignment in test mode.
4. Toggle provider in admin-approved QA mode; refresh and repeat.

**Expected:** existing normal-ad component is not removed, duplicated or shifted to a prohibited flow after new offerwall code. Ads may not fill if publisher not approved; proper empty state must not break app.

### ADS-02 — No AdSense-triggered wallet credit (P0)

1. Note QA-USER-A wallet amount in clean QA database.
2. Navigate Quote Feed, view the standard ad placeholder or test slot, switch tabs/refresh as normal.
3. Reopen My Earnings and Wallet.

**Expected:** **zero additional user reward** from page view, ad load, ad visibility, clicking any local **non-live** test placeholder or waiting. There is no `Google AdSense Cash Reward` financial row.

### ADS-03 — No misleading user CTA (P0)

1. Check all text adjacent to normal ad slots and on Earn Rewards page.
2. Search UI labels for “watch ads and get paid”, “click Google advertisement” and generic mixed reward statements.

**Expected:** no incentivized AdSense click/view instruction, no unapproved cash reward text; standard ad placements clearly separated from TapResearch/Wannads/TheoremReach activity offers.

### ADS-04 — Scripts not broken by new CSP/integrations (P1)

1. Developer/admin enables approved test AdSense component; open browser console/network in QA.
2. Compare before/after script inclusion and Content Security Policy behavior (QA does not need real publisher access).
3. Toggle new providers enabled/disabled in QA, refresh feed.

**Expected:** new iframe domains or SDK changes do not break AdSense or create duplicate script injection; no new runtime errors that prevent feed. If provider approval is missing, mark ad-fill verification BLOCKED and code-level loading PASS only when evidenced.

### ADS-05 — Admin settings and secrets preserved (P1)

1. Admin > Integrations > Google AdSense.
2. Verify publisher ID/slot settings still accessible; toggle an existing QA option and save.
3. Verify there is no cash reward exchange rate field mistakenly added to AdSense.

**Expected:** old config and permissions unchanged; no Google ad-view-to-wallet backend code added. User cannot open the admin settings page.

### ADS-06 — Ad blockers and no-fill state (P2)

1. Use authorized QA environment with normal ad-blocking browser mode.
2. Visit Quote Feed, open Quote Studio and navigate to Earn Rewards.

**Expected:** app stays usable, no uncaught error/empty screen, no fake payment promise, other provider cards remain independently accessible if approved.

**AdSense sign-off:** Mark `AdSense regression PASS` only if ADS-01 through ADS-05 pass at code/UI level and all provider-dependent live-fill tests are truthfully recorded. **Do not run real ad-interaction tests.**

---

## 9. MANDATORY TapResearch regression — existing provider #2

### TAP-01 — Original launch and provider ID unchanged (P0)

1. Load QA-USER-A in existing Earn Rewards screen.
2. Click **TapResearch → Take Surveys**.
3. Observe provider's official approved hosted web link/SDK view or QA mock.
4. Return to website, refresh.

**Expected:** old TapResearch web flow still opens as before; new provider adapters have not overwritten TapResearch URL, its stored user ID map, app credentials, or route. No errors or duplicate reward on return.

### TAP-02 — No offers/ineligible country fallback (P1)

1. Choose official QA no-offers fixture and open TapResearch.
2. Choose controlled restricted/ineligible-user fixture.

**Expected:** appropriate unavailable message; no false reward; user can return to Feed or select another approved provider.

### TAP-03 — Valid TapResearch signed test reward (P0)

1. Log in as QA-ADMIN, find **TapResearch → Test Callback**.
2. Execute the provider version's **officially documented** valid QA postback fixture.
3. Inspect TapResearch event and QA-USER-A Earnings/Wallet.

**Expected:** one authenticated, correctly mapped TapResearch event with accurate money units/test state and one matching ledger entry. No Wannads/TheoremReach signature algorithm is used for TapResearch; the actual version's official callback requirements must remain intact.

### TAP-04 — Duplicate TapResearch event (P0)

1. Repeat TAP-03 identical event several times.
2. Reopen ledger filter = TapResearch.

**Expected:** one award only; duplicate attempt count/audit may increase.

### TAP-05 — Invalid auth/unknown user (P0)

1. Use safe `Invalid Signature/Authentication` and `Unknown User` TapResearch fixtures.
2. Refresh wallet.

**Expected:** no wallet increase, anomaly recorded, no public leaking of verification secret or other users' data.

### TAP-06 — TapResearch reversal and status mapping (P0)

1. Process a valid QA test event.
2. Trigger official version-supported reversal/rejection or reconciliation fixture.
3. Repeat correction event.

**Expected:** original amount corrected **once** in ledger; history retained; no invalid available reward. If reversal mechanism differs by TapResearch version, agent must document exact current contract and use the correct one—not invent a Wannads-style `status=rejected` without source support.

### TAP-07 — Existing historical earnings after migrations (P0)

1. Before the new integration, record **anonymized total counts/balances** of QA TapResearch transactions and wallet derived balances.
2. After migrating new provider tables, rerun same read-only report.
3. Open same anonymized QA user history.

**Expected:** unchanged historical totals and settled balances, except explicitly documented and correct migration effects. No lost or reclassified records.

### TAP-08 — TapResearch dashboard unchanged (P1)

1. Admin > Integrations > TapResearch > Configure.
2. Verify existing settings, callback verification status and historical transactions.
3. Run built-in QA config test if one existed.

**Expected:** old provider config persists; authorization and secret masking intact; new providers' `Enable` flags do not turn TapResearch off.

**TapResearch sign-off:** TAP-01 + TAP-03 + TAP-04 + TAP-05 + TAP-06 + TAP-07 are P0; all must PASS (or external-only portions appropriately BLOCKED with code tests performed) before claiming the new integration caused no regression.

---

## 10. Cross-provider integration and financial integrity

### CROSS-01 — All four admin rows show truthful status (P1)

1. Admin > Advertising / Integrations.
2. Read names, types, current modes/statuses for all four.
3. For each, open settings then return.

**Expected:** Google AdSense is `standard_ads`; TapResearch, Wannads and TheoremReach are rewarded/survey/offerwall where permitted. Every status distinguishes `Code ready`, `Tested`, `Approved`, `Live`, `Blocked`. Production status is not fabricated.

### CROSS-02 — One provider disabled does not break others (P0)

1. Disable Wannads in QA.
2. Open Quote Feed, TapResearch, AdSense component and (if approved) TheoremReach.
3. Re-enable Wannads in QA then disable TheoremReach.
4. Repeat original routes and wallet checks.

**Expected:** disabling only hides or blocks target provider's new launches; all unrelated modules stay functional and historic events intact. Provider callbacks for past legitimate activity are handled/held by policy rather than silently lost.

### CROSS-03 — Earnings source filters accurate (P0)

1. Seed one verified **QA-only** transaction from TapResearch, Wannads and TheoremReach as allowed.
2. Log in as QA-USER-A → My Earnings.
3. Select `All`, `TapResearch`, `Wannads`, `TheoremReach` filters one by one.
4. Check Wallet total and provider source labels.

**Expected:** filters isolate the right rows, sum correctly without double counting, preserve dates and statuses, and do not show Google AdSense as viewer cash source.

### CROSS-04 — 33.33% allocation and rounding (P0)

1. Create signed QA events with known approved publisher payout basis `$3.00`, `$0.90`, `$0.03` (or their exact approved equivalents).
2. Open finance detail and inspect formula / provider reward units / user share.
3. Compare to account-configured exchange rate and documented rounding policy.

**Expected:** consistent deterministic result at **33.33% of correct eligible payout basis**, no double 33.33% applied when provider virtual exchange rate already accounts for split, no units treated as USD without conversion. For small values, fractional remainder handled according to documented policy; never create unbacked credit.

### CROSS-05 — Withdrawal reserve cannot include held/test/reversed rewards (P0)

1. Ensure QA-USER-A has one `TEST`, one `HELD`, one `REVERSED` and one legitimate QA `AVAILABLE` entry under controlled fixture.
2. Open Wallet → Withdraw.
3. Observe maximum available / minimum withdrawal calculation.
4. Try to request more than available (use simulation, not real funds).

**Expected:** test/held/reversed amounts excluded; insufficient funds and minimum restrictions enforced; submit cannot overdraw or bypass front-end validation by direct request.

### CROSS-06 — Concurrent reward event and withdrawal (P0)

1. Developer uses two controlled QA requests to submit payout reserve simultaneously with a pending provider reversal/credit.
2. QA observes resulting finance history.

**Expected:** no race permits same cents to be withdrawn twice, no negative money balance from duplicate reserve, consistent transaction states.

### CROSS-07 — User B cannot view User A's provider earnings (P0)

1. QA-USER-A gets test provider transaction.
2. Copy masked page path or QA-only transaction reference.
3. Log in QA-USER-B and try accessing A's detail route.

**Expected:** 403/404 or a safe denied result, zero cross-user data, no permission to change provider user mapping.

### CROSS-08 — User cannot access admin provider settings (P0)

1. As QA-USER-A, directly request known admin settings/POST paths from a safe QA session.
2. Verify no secret or approval status can be modified.

**Expected:** 403/redirect with no mutation and no admin data disclosed. As QA-STAFF with limited privileges, sensitive approvals/secret replacement are denied if role model requires superadmin.

### CROSS-09 — Provider-specific callback verification cannot be bypassed (P0)

1. Use fixture signed with Wannads QA secret, deliver it to TheoremReach test handler (through controlled local test harness).
2. Reverse the mismatch.
3. Change `provider=...` query string on a test callback and repeat.

**Expected:** mismatched callback cannot credit; no generic `verified=true` flag accepted from client/browser; provider-specific namespace/security is enforced.

### CROSS-10 — Provider outage isolation (P1)

1. Developer uses controlled failed response for Wannads entry/SDK.
2. Visit Quote Studio and run AI generation in allowed mock/test mode.
3. Visit TapResearch page and Wallet.

**Expected:** a failed new provider does not crash other pages, long-hang general requests, corrupt a queue or lose old provider state.

### CROSS-11 — Manual withdrawal admin flow (P0)

1. In isolated QA wallet, seed legitimate simulated *available* funds with a simulated payout environment, approved policy, and a visible QA label.
2. Open user Wallet → Withdrawal → select existing method (e.g. PayPal/Cash App as configured) → enter permissible test amount → Submit.
3. Admin > Withdrawals → inspect request → Approve/Reject in test environment.
4. Check user's transaction detail and reserve status.

**Expected:** request is reserved exactly once, no duplicated reserve; admin status is auditable; reject releases appropriately, approved/paid status requires real payment evidence in production; no simulation is presented as actual money transfer.

### CROSS-12 — Provider fees/holds/settlement reconciliation (P0)

1. Set a provider test payment as pending/held per approved settings.
2. Use reconciliation mock to mark an approved settled state (no real transfer).
3. Trigger subsequent reversal/dispute fixture.
4. Inspect finance dashboard and withdrawable amount.

**Expected:** pending is separate from available and from publisher settlement; reversal creates auditable compensating adjustment; if payout already recorded, admin exception (not silent cross-provider debit) appears.

### CROSS-13 — Normal user data/privacy (P1)

1. Open browser network panel while launching Wannads and TheoremReach in safe QA environment.
2. Observe outgoing query param *names and data categories*, without copying any real token.
3. Check absence of user email/name/payment details in URL; use opaque IDs only.

**Expected:** site reveals only vendor-required non-secret public key and opaque mapping. In admin test logs, secrets are masked/redacted. Provider demographic handling respects privacy/country requirements.

### CROSS-14 — Browser session expires mid-provider flow (P1)

1. Start an approved QA offerwall session.
2. Expire local app login in QA through developer-controlled fixture.
3. Return and try opening Wallet, then test server callback for previously launched verified event.

**Expected:** user must authenticate again to view account, wallet cannot be accessed as guest; server callback still correctly identifies legitimate historical user via mapped ID and follows approved policy (not session cookie), no impersonation.

### CROSS-15 — Admin audit logs and kill switch (P0)

1. Admin changes Wannads mode from Sandbox to Disabled.
2. Admin changes TheoremReach compliance gate from pending to still pending without approval.
3. Open Admin Audit Log and refresh user page.

**Expected:** state changes recorded with operator/time, secrets not logged, frontend update/launch-block immediate, no partial inconsistent activation. Simulated tampering cannot bypass approval gate.

---

## 11. Original product regression that must still be tested

After new provider rollout, complete earlier **full human QA** document. At minimum execute and attach evidence for the following workflows:

### CORE-01 — New registration, privacy/terms consent and email dependency (P1)

1. Open site in incognito; click Register.
2. Enter a QA email/password and complete any policy consent checkbox required.
3. Submit. If SMTP is not configured, record external blocker and use safe QA verification fixture.
4. Log in; log out; test password recovery screen where possible.

**Expected:** validation meaningful, account creation safe, verified gate operates according to actual app, no accidental login bypass. TheoremReach go-live requires proper site policies and active consent; missing legal content/SMTP must be a visible dependency, not fake complete status.

### CORE-02 — Generate and save AI quote (P1)

1. Log in as QA-USER-A → Quote Studio.
2. Choose category, generate sample quote through configured AI QA/test service.
3. Save quote, refresh, check Saved Quotes.
4. Copy quote, paste into safe scratch editor.

**Expected:** correct quote content stored/copied; no ad provider script breaks AI generation; no cash reward from simply generating quote.

### CORE-03 — Social/email/text sharing (P1)

1. Open site share action.
2. Test native share API where available, then Copy Link.
3. Test E-mail (mailto) and SMS/text link on supported device.

**Expected:** legitimate intended site URL, no private token/user data; graceful fallback on browsers without share API; no new provider changes sharing behavior.

### CORE-04 — Admin user management and finance still available (P1)

1. Admin logs in, opens Users, Quote Management, Provider Management and Withdrawals.
2. Filter/search and navigate back/forward.

**Expected:** old admin dashboard/navigation remains stable; new provider rows are additive; menu/permissions still correct.

### CORE-05 — Original quote → demo-ad flow (P1)

1. Generate quote; click existing **Generate Ad** demo action if present.
2. Observe demo sequence and return/next quote behavior.
3. Check Wallet.

**Expected:** demo is clearly fake/sandbox and **cannot generate real earnings**; no redirection to AdSense as a compensated watch-to-earn. If this flow was replaced with provider-specific legal offers after approval, document change and verify it preserves client-confirmed intent safely.

### CORE-06 — Device/layout/browser regression (P2)

1. Test desktop (Chrome/Edge/Firefox as available) and one real/simulated mobile Safari/Chrome.
2. Resize 320, 375, 768, 1024, 1440px.
3. Visit Feed, Studio, Earn, Wallet, and Admin.

**Expected:** no console fatal errors, overlapping modal, missing button, horizontal page scroll or unreadable finance labels. Third-party iframe failures have fallback rather than crashing main app.

---
## 12. Exact test execution order and exit gates

**Always run in this order; do not jump directly from four provider cards to delivery sign-off.**

| Stage | Do this | Must be true before next stage |
|---|---|---|
| `QA-0` | Confirm isolated QA DB, test credentials, Git SHA, initial snapshot and no real-money actions | Safety PASS |
| `QA-1` | Original Google AdSense regression ADS-01..ADS-06 and TapResearch TAP-01..TAP-08 *against baseline build* | Baseline report captured; defects categorized |
| `QA-2` | New provider status/admin UX UX-01..UX-05, WAN-01..WAN-04, THEO-01..THEO-06 | All P0 status/authorization checks pass |
| `QA-3` | Security and ledger callbacks WAN-05..WAN-13, THEO-07..THEO-14 | No unauthorized/duplicate/test cash |
| `QA-4` | Cross-provider financial/permission CROSS-01..CROSS-15 | All P0 pass; no balance corruption |
| `QA-5` | Retest ADS-01..ADS-06, TAP-01..TAP-08 on **integrated final build** | AdSense and TapResearch not regressed |
| `QA-6` | Full original core suite CORE-01..CORE-06 + older complete QA playbook | Existing app still working |
| `QA-7` | Provider account compliance, legal site policies, staging demo, release gates | Either code READY with external BLOCKED, or authorized live READY |
| `QA-8` | Safe production smoke after approved deployment | No live simulated money/ad-click tests |

### 12.1 Evidence capture example for every test

```text
Test ID: WAN-06
Tester: <QA analyst>
Date/time: <with timezone>
Environment: https://<QA-STAGING-DOMAIN>
Commit SHA: <real SHA>
Browser: Chrome  [version]
Role: QA-ADMIN / QA-USER-A
Fixture: Wannads signed duplicate credit W-100, QA ONLY
Preconditions: 1 valid prior sandbox transaction stored
Steps actually executed: fixture sent 5x; opened provider inbox; refreshed wallet
Expected: 1 financial allocation, 5 additional duplicate attempts max
Observed: <actual value and screenshots>
Provider callback acknowledgement: <only if known>
Ledger starting balance (QA): <value>
Ledger ending balance (QA): <value>
Result: PASS | FAIL | BLOCKED-EXTERNAL | NOT RUN
Defect ID: <if needed>
Retest build/date: <if fixed>
Evidence paths: <internal QA screenshot/log IDs; redact secrets>
```

### 12.2 Formal bug ticket example

```text
BUG ID: REWARDS-P0-001
Title: Duplicate Wannads callback generates two wallet credits
Severity: P0
Environment: QA build <sha>
Test case: WAN-06
Actual: two +$0.50 ledger rows for one vendor transaction ID
Expected: one idempotent financial credit
Reproduction:
  1. QA user launches Wannads test session.
  2. Admin sends valid W-100 fixture twice (same ID/signature).
  3. Inspect wallet ledger source=Wannads.
Evidence: <internal redacted screenshots and query reference>
Data risk: Withdrawal liability doubled
Immediate mitigation: Disable Wannads production feature flag; hold affected rewards
Developer resolution: <file/commit>
Retest: WAN-05, WAN-06, WAN-09, CROSS-05, TAP-04
Status: OPEN / FIXED / VERIFIED
```

### 12.3 Provider accountability and approval record

For EACH provider fill one page:

```text
Provider name:
Selected product: Standard Ads / Hosted Survey / Offerwall / JS SDK / Router
Public domain approved: YES / NO / UNKNOWN
Publisher account created: YES / NO / UNKNOWN
Current provider approval: APPROVED / PENDING / REJECTED / UNKNOWN
Configuration complete: YES / NO
Test/sandbox completed: YES / NO
Callback signed validation: PASS / FAIL / BLOCKED / N/A
Duplicate credit prevention: PASS / FAIL / BLOCKED / N/A
Reversal handling: PASS / FAIL / BLOCKED / N/A
Cash withdrawal of user rewards explicitly permitted: YES / NO / PENDING / N/A
TheoremReach concurrent offer-provider written approval: YES / NO / PENDING / N/A
Privacy/Terms/consent prerequisites: VERIFIED / PENDING
Production enabled: YES / NO
Evidence link/reference: <redacted internal link>
Overall: CODE_READY / SANDBOX_VERIFIED / APPROVAL_BLOCKED / LIVE_VERIFIED
```

**Special TheoremReach answer to client:** The code can support TheoremReach, but its current published terms contain a restriction on competing incentivized providers unless specifically approved in writing. This means `Approval blocked` may be the correct final QA status even if all Laravel tests pass. Never rewrite that restriction in the dashboard as a mere configuration inconvenience. See `https://info.theoremreach.com/terms-of-service/`.

## 13. Pass / fail decision rules for the owner

### 13.1 Technical implementation acceptance

Accept **code implementation** only if:

- [ ] Wannads official web access + signed app postback implemented and documented.
- [ ] Wannads valid, invalid, duplicate, reject and out-of-order cases all pass.
- [ ] TheoremReach official publisher web flow + signed callback implemented and documented (not researcher API).
- [ ] TheoremReach debug never creates real money; reversals through separate documented channel or correctly flagged blocked with payout hold.
- [ ] Common wallet, provider transaction inbox and source filtering are consistent, tenant/user secure, and 33.33% rules follow approved terms.
- [ ] Admin secure config and provider compliance gates exist for all four providers.
- [ ] **Google AdSense still works as standard ads**, with NO viewer cash credit or incentivized ad clicks.
- [ ] **TapResearch launch and callback still work**, including duplicate protection and correct historical balances.
- [ ] Quotes, auth, admin, wallet and withdrawals still work after changes.
- [ ] Tests and evidence are actual results on a recorded build, not AI narrative.
- [ ] Rollback switch can disable *new* providers without disabling old ones or erasing wallet history.

### 13.2 Production monetization go-live acceptance

A provider may be set `PRODUCTION_ACTIVE` ONLY when:

- [ ] Provider has approved the client's website/account and web integration product.
- [ ] Correct production credentials, signed callback and real domain URL verified securely.
- [ ] The agreed user rewards / virtual-currency-to-cash redemption are explicitly authorized for that account if platform will offer cash withdrawals.
- [ ] **For TheoremReach** the provider has specifically approved competing incentivized providers on the same application where required by current contract.
- [ ] Legal/privacy/terms/support/consent and fraud safeguards required by that provider are in place.
- [ ] Reconciliation, reversals, settlement and payout holding are implemented and tested.
- [ ] Admin explicitly enables approved integration with audit record and monitors initial real transactions.

A code-level pass does not prove real ad fill, survey availability, provider payments or website approval. In the QA report, such cases should be `BLOCKED-EXTERNAL` until authoritatively verified.

### 13.3 Final matrix — fill with actual evidence

| Test group | Status | Evidence / notes | Release blocker? |
|---|---|---|---|
| Repository audit + Context7/official docs | NOT RUN | Required agent documentation | Yes for code sign-off |
| Wannads UI/offerwall | NOT RUN | WAN-01..04 | Yes if broken |
| Wannads callback/security/reversal | NOT RUN | WAN-05..13 | **P0** |
| TheoremReach UI/permission gate | NOT RUN | THEO-01..06 | **P0** if gate bypass |
| TheoremReach signed reward/reconciliation | NOT RUN | THEO-07..14 | **P0** |
| Google AdSense baseline + final regression | NOT RUN | ADS-01..06 | **P0** |
| TapResearch baseline + final regression | NOT RUN | TAP-01..08 | **P0** |
| Cross-provider finance/security | NOT RUN | CROSS-01..15 | **P0** |
| Quote/Auth/Wallet original baseline | NOT RUN | CORE-01..06 + earlier QA | Yes if core broken |
| Live publisher approval and cash permission | BLOCKED-EXTERNAL pending evidence | Provider contracts/dashboard | Live go-live blocker |
| Production deployment smoke | NOT RUN | No fake real ads/rewards | Delivery release check |

### 13.4 Owner sign-off template

```text
PROJECT: Proceeds With Positivity
SCOPE: Add Wannads and TheoremReach; preserve Google AdSense + TapResearch
QA build/commit:
Testing location and date:
Tester:

Wannads technical implementation: PASS / FAIL / BLOCKED
TheoremReach technical implementation: PASS / FAIL / BLOCKED
Google AdSense regression: PASS / FAIL / BLOCKED
TapResearch regression: PASS / FAIL / BLOCKED
Wallet/withdrawals: PASS / FAIL / BLOCKED
Authentication/quotes/sharing: PASS / FAIL / BLOCKED
Provider compliance/cashout permission: VERIFIED / BLOCKED-EXTERNAL
TheoremReach coexistence approval: VERIFIED WRITTEN / BLOCKED-EXTERNAL
P0 defects remaining:
P1 defects remaining:
External approvals pending:

DECISION:
[ ] ACCEPT CODE; KEEP LIVE PROVIDER FLAGS OFF PENDING APPROVAL
[ ] ACCEPT CODE AND APPROVED LIVE ACTIVATION
[ ] REJECT / FIX P0/P1 DEFECTS AND RETEST

Signed by project owner / date:
```

---

## 14. Short nontechnical owner checklist — what to physically click

When the developer says “four ad networks are implemented,” you can perform this sequence without reading PHP:

1. **Admin Login** → Open **Integrations** → verify all four provider names and separate statuses.
2. Open **Wannads → Configure** → verify secret masked → **Test Callback** (QA only) → check one credit in QA ledger.
3. Repeat exact Wannads callback → verify balance **does not increase** a second time.
4. Use Wannads **Reversal** fixture → verify balance/transaction history correctly adjusts once.
5. Open **TheoremReach → Compliance** → verify missing competing-provider written approval **blocks** production enable.
6. Open **TheoremReach → Test Callback** → send `debug=true` fixture → verify real/withdrawable balance **does not increase**.
7. Run signed TheoremReach QA completion and reversal fixtures → check once-only ledger and separate reversal path.
8. User login → **Earn Rewards** → verify TapResearch and Wannads; TheoremReach only if approved; no AdSense cash reward card.
9. User login → **Quote Feed** → make a quote → save, copy, share. Verify no regressions.
10. **TapResearch** → QA survey launch → signed postback → duplicate test: balance should increase only **once** in QA ledger.
11. **Google AdSense** → inspect normal ad placement only; no adsense-watch-to-cash link. **Never click a live ad** for a test.
12. User → **Wallet / Withdrawal** → ensure pending/test/reversed earnings cannot be cashed out and old records still exist.
13. Repeat core flows on narrow mobile screen. Ask developer for test report and screenshots for P0 financial cases.
14. Review final sign-off matrix. If a provider hasn't approved the account, mark production activation **BLOCKED**, not complete.

**End of Manual QA Playbook.**
