# PROCEEDS WITH POSITIVITY
# COMPLETE HUMAN MANUAL QA & ACCEPTANCE PLAYBOOK

**Version:** 1.0 — 2026-11-19  
**Companion implementation specification:** `01_AI_AGENT_IMPLEMENTATION_MASTER_CONTEXT.md`  
**Repository:** https://github.com/mak-laravel-git/tay_lovett-proceeds-with-positivity  
**Who uses this:** Project owner, QA reviewer, Fiverr delivery reviewer or any non-developer testing the current build in a browser.  
**Coverage:** existing or planned Laravel application modules, user/admin navigation, AI Quote Generation, quote feed, save/copy/share, advertisement demo, provider onboarding, revenue ledger, wallet, withdrawal and security/performance.  
**Important provenance:** Repository contents were not accessible to the author; these are **target acceptance tests** based on client evidence, historical V14 interactive UI and the developer's proposed technical approach. Do not mark a test PASSED simply because it is written here. Run it against the **actual local/staging build** and record actual results. Exact names/URLs can vary if current source differs; log the difference rather than assume functionality exists.

---

## 0. Safety and purpose

These tests serve two distinct purposes:

1. **Product acceptance:** verify that actual users can use the app exactly as the agreed interface describes.
2. **Engineering acceptance:** verify secure permissions, persistence, verified provider callbacks, accurate financial states and graceful errors.

**Do not use real consumer information, real funds, live PayPal/Cash App transactions, live AdSense clicks or artificial ad impressions in testing.** Use test accounts, sandbox provider fixtures and a separate QA database. Do not click a live ad just to test rewards. Never execute load/fraud tests against a real advertising network. All simulated earnings must be explicitly labelled `DEMO` and must never be withdrawable.

**A test is PASS only when** the visible result, backend behavior (as demonstrated by available admin logs/reports), and expected security condition match. `BLOCKED` is not `PASS`; record exact missing provider credential / domain / SMTP / pending client decision.

**Critical requirement versioning:** Client selected **Option B — User Revenue Sharing** in the 2026-11-15 clarification response. That means original viewer reward + wallet is still baseline. Client later asked about creator earnings from quote views but did not formally replace viewer reward; do not fail baseline for lack of unapproved creator monetization and do not accept fake creator earnings as legitimate. Google AdSense ordinary ads must not credit cash to viewers. The latest website-sharing clarification was sharing across social media, email and texts; **not** a request for publicly shareable private quote pages.

## 1. How to use this document

Each test has:

- **ID:** stable case ID, e.g. `AUTH-01`.
- **Status:** `PASS`, `FAIL`, `BLOCKED`, `NOT IMPLEMENTED`, `NOT APPLICABLE`.
- **Priority:** `P0` release-blocking, `P1` high, `P2` polish, `P3` future/unapproved.
- **Preconditions:** account, environment and setup before clicking.
- **Steps:** which page/button/input to use.
- **Expected result:** exactly what must display/change and what must **not** happen.
- **Evidence:** screenshot (redacted), browser log with no secrets, test transaction reference, audit record or API test report.

Record results in `docs/implementation/TEST_EVIDENCE.md` or a spreadsheet. Always include timestamp, environment URL, build SHA, browser/version, tester and test ID.

### Result recording template

```text
Date/time (timezone):
Tester:
Environment: LOCAL / STAGING / QA SANDBOX / PRODUCTION READ-ONLY
Build/commit SHA:
Browser and device:
Test ID:
Expected:
Actual:
Status: PASS / FAIL / BLOCKED / NOT IMPLEMENTED / NOT APPLICABLE
Evidence screenshot/video (secure internal URL):
Bug ID / reproduction URL:
Severity: blocker / critical / major / minor
Notes / suspected dependency:
Retest build:
Retest status:
```

## 2. Test environment and accounts

### 2.1 Test accounts (create locally; do NOT use live client credentials)

| Alias | Role | Purpose | Example placeholder identity |
|---|---|---|---|
| QA-GUEST | No login | Guest/negative tests | Incognito window |
| QA-USER-A | Regular verified user | Generate quote, save, eligible rewards, withdrawals | `qa.user.a@example.test` |
| QA-USER-B | Regular verified user | Cross-user security, viewer analytics | `qa.user.b@example.test` |
| QA-USER-UNVERIFIED | Unverified regular | Email verification guard | `qa.unverified@example.test` |
| QA-USER-SUSPENDED | Suspended account | Account restrictions | `qa.suspended@example.test` |
| QA-ADMIN | Admin/super-admin | Provider, revenue, payout management | `qa.admin@example.test` |
| QA-OPERATOR | Optional limited admin | Check RBAC boundaries | `qa.operator@example.test` |

**Passwords:** generate securely; never record real password in public handoff, issue tracker screenshots or document. Default account names and fake emails are sample test data, not client accounts.

### 2.2 Data fixtures (all SANDBOX)

Create only through safe seeders, admin sandbox tools or approved fixture scripts:

- Categories: Motivation, Kindness, Hope, Growth, Success, Happiness, or actual configured categories from live code.
- 15–30 platform-visible quote items across categories; 3 private user-generated quotes for User A; at least 2 Saved records.
- One valid mock `TapResearch` conversion with USD 30.00 eligible publisher payout, one duplicate, one reversal, one invalid signature; **these are fictional test payloads**. Use provider actual documented payload when implementing fixtures; do not send generic made-up payloads to real external endpoints.
- Two wallet test scenarios: User A `$25.00` available, `$0` reserved; User B `$0.00`. For unsafe production environments do not seed money at all.
- Proposed demo minimum withdrawal `$10.00` for local QA only; actual minimum remains client decision.
- Provider statuses: Disabled, Sandbox Ready, Pending Approval, Active (mock), No Available Offers.

**Before a financial test:** verify environment banner displays SANDBOX and payout API mode is disabled. If this cannot be proven, mark financial test BLOCKED and do not proceed.

### 2.3 Browser / viewport matrix

Minimum manual coverage: Chrome desktop, Firefox desktop, Edge desktop, iOS Safari or Safari simulator, Android Chrome or responsive emulator. Use viewport widths **320, 390, 768, 1024, 1440, 1728 px**; vertical dimensions as actual device allows. Test keyboard-only and reduced motion if app animations exist.

| Width | Typical purpose | Check |
|---|---|---|
| 320 px | Small phone | Form usable, no cut-off CTA, bottom bar accessible |
| 390 px | Standard mobile | Feed cards, share/modal, nav safe area |
| 768 px | Tablet | Sidebar/drawer breakpoint, tabs, wallet panels |
| 1024 px | Narrow laptop | Desktop navigation without overlap |
| 1440 px | Desktop | Sidebar plus content, admin full width |
| 1728 px | Wide desktop | No awkward fixed-width admin column |

### 2.4 QA start checklist

- [ ] Git SHA known and deployment corresponds to it.
- [ ] App reachable by HTTPS on staging or isolated localhost.
- [ ] Test DB and mailer isolated from real customer data.
- [ ] Local test AI key or controlled AI mock available, or AI tests flagged BLOCKED.
- [ ] Provider callbacks in mock/sandbox only.
- [ ] Real payout initiation disabled.
- [ ] Screenshots do not expose personally identifiable data, account credentials or API keys.
- [ ] Test plan read alongside current `FEATURE_MATRIX.md`; known unimplemented items distinguishable from bugs.

---

## 3. Guest, login, registration and account recovery

### AUTH-01 [P0] Guest opens site, sees login first

**Preconditions:** Incognito browser, not authenticated.

1. Paste the app root URL and press Enter.
2. Observe first screen, top menu, form and footer.
3. Try refreshing and using browser Back/Forward.

**Expected:** The initial accessible experience is Login/Register; no marketing landing page. `Log in`, `Create account`, Privacy Policy and Terms are accessible. No wallet, feed or admin content leaks. Historical V14 admin preview must not exist in production. No console fatal error.

### AUTH-02 [P0] Navigate to registration

1. On Login click **Create account**.
2. Observe fields and validation labels.
3. Click the reverse **Log in** tab/link.

**Expected:** Registration form opens; return preserves safe navigation; no phantom user created before submission.

### AUTH-03 [P0] Successful registration

1. Fill each required field with new QA email and valid password/confirmation.
2. Click **Create account** once.
3. Check UI response and local test mailbox.
4. Log out and sign in with new credentials.

**Expected:** Exactly one regular-user account created, never admin; email verification or pending state follows configured workflow; no server error. Profile and email are persistent. Duplicate click does not create another account.

### AUTH-04 [P0] Invalid registration and duplicate email

1. Enter empty name/email/password and submit.
2. Enter malformed email (e.g. `not-an-email`) and submit.
3. Enter password mismatched with confirmation and submit.
4. Attempt to register an already used test email.

**Expected:** Field-specific human-readable errors; failed attempts do not create users. No SQL exception, raw JSON stack trace or exposure of password fields in logs.

### AUTH-05 [P0] Correct login and role redirect

1. Open Login, use QA-USER-A credentials, click **Log in**.
2. Note redirect path and visible sidebar.
3. Logout, log in as QA-ADMIN.

**Expected:** User lands on Inspiration Feed/User Dashboard; Admin on separate full-width Admin Overview. No client-side `Switch to Admin` bypass available in production. Successful login survives a page refresh.

### AUTH-06 [P0] Incorrect credentials and throttling

1. Enter valid username/email with incorrect password; click Login.
2. Repeat several times using the safe test count agreed with developer (do not stress production).

**Expected:** Generic error, no account identity disclosure, suitable throttling or temporary cooldown, and no app crash. Correct credentials later work after normal release.

### AUTH-07 [P0] Logout and protected-route access

1. Login QA-USER-A; copy current Feed or Wallet URL.
2. Click account menu → **Log out**.
3. Paste copied protected route directly, press Enter, then Back.

**Expected:** Redirect to Login/unauthorized; no protected data in content or network cache; browser Back doesn't allow mutations. Session cookie safely invalidated.

### AUTH-08 [P1] Password recovery

1. From Login click **Forgot password**.
2. Enter a QA registered email, submit.
3. Open test mailbox, follow reset link/OTP.
4. Set new password, submit; try old and new passwords.

**Expected:** Email arrives in test mail service, reset expires and is single-use, old password no longer works, new password works. If SMTP not configured, mark as BLOCKED: CLIENT/SMTP and verify local mailer infrastructure, not 'pass'.

### AUTH-09 [P1] Verification

1. Register QA-USER-UNVERIFIED or use seeded account.
2. Open verification message and follow verification link.
3. Follow same link a second time.

**Expected:** First use verifies; subsequent use fails safely/indicates already verified. Restricted operations follow actual policy; no duplicate notification loop.

### AUTH-10 [P0] Unauthorized role escalation

1. Login as QA-USER-A.
2. Type known Admin URL directly or inspect admin-only navigation destination.
3. Attempt safe GET for admin reports; have developer replay a controlled mutation attempt as user.

**Expected:** 403 or appropriate denial **from the server**, no financial/admin data. Frontend-hidden sidebar alone is not enough. No changed state.

### AUTH-11 [P1] Session expiry and two tabs

1. Login and open Feed and Wallet in separate browser tabs.
2. Logout in one tab; refresh the other tab.
3. Open session-expired page/submit a safe action.

**Expected:** Second tab becomes unauthorized gracefully, no silent submission of private data, clear reauthentication path.

---

## 4. Guest legal pages and navigation/UI structure

### NAV-01 [P0] Public legal links

1. As guest open Login.
2. Click **Privacy Policy**; inspect page heading, URL and back navigation.
3. Return, click **Terms of Service**.

**Expected:** Both routes accessible without login, readable on mobile, correctly titled, not empty/404. Legal text may still need client review; record that separately as production dependency.

### NAV-02 [P0] Desktop user sidebar

1. Login QA-USER-A at 1440px width.
2. Click in sequence: `Inspiration Feed`, `Quote Studio`, `My Wallet`, `My Earnings`, `Saved Quotes`, `Withdrawals`.
3. Use browser Back twice and Forward once.

**Expected:** Each action navigates to the correct section, active state moves, URL/routes refresh correctly. Header must not duplicate the entire sidebar navigation. No blank screens.

### NAV-03 [P0] Mobile bottom navigation

1. Set viewport to 390px and then 320px.
2. Click **Feed**, **Create**, **Saved**, **Wallet**, **More**.
3. From More open **My Earnings** and **Withdrawals**.
4. Return to Feed.

**Expected:** All important sections reachable; bottom bar visible and not obscured by OS navigation, keyboard or modal. `More` is functional, not a decorative dead end. No horizontal overflow.

### NAV-04 [P0] Admin layout / responsive drawer

1. Login QA-ADMIN at 1440px, inspect Admin Overview and left sidebar width.
2. Expand `People & Access`, `Content`, `Advertising & Finance`, `Operations`, `System` groups where present.
3. Collapse desktop sidebar and reopen.
4. Switch to 390px, tap hamburger, tap a group and a page, tap backdrop, press Escape.

**Expected:** Full-width main area uses remaining width, not narrow user feed shell; groups expand/collapse; mobile drawer overlays and dismisses accessibly; content and horizontal tables remain usable. Menu status reflected in `aria-expanded` where applicable.

### NAV-05 [P1] Browser keyboard support

1. On Login and Feed navigate using Tab/Shift+Tab and Enter/Space.
2. Open any dialog and press Escape if closing permitted.
3. Inspect visible keyboard focus and tab order.

**Expected:** Every action possible via keyboard or sensible fallback, focus never trapped behind closed dialog, no hidden focusable off-screen overlay.

### NAV-06 [P1] Persist navigation after reload

1. Open Wallet; refresh.
2. Open Saved; refresh.
3. Open Admin users page; refresh.

**Expected:** Correct page/state returns or protected redirect; no 404 from backend on direct deep link, no incorrect role redirect.

---

## 5. AI Quote Studio, feed, categories, save and copy

### QUOTE-01 [P0] Quote Studio opens

1. Login QA-USER-A.
2. Click **Quote Studio** / mobile **Create**.

**Expected:** `AI Quote Studio` page loads with category selector, `Generate Quote`, output area and actionable controls. No blank demo-only placeholder mistaken for a real generated result.

### QUOTE-02 [P0] Generate a motivational quote

1. Select `Motivation` category (or equivalent configured category).
2. Click **Generate Quote** exactly once.
3. Watch loading and output states.
4. Copy returned text into QA notes.

**Expected:** Loading visible, button prevents duplicate request; server-generated motivational quote displayed; no raw provider key, model stack trace or HTML injection. If OpenAI test credentials unavailable, mark BLOCKED for live generation and test a controlled mocked response separately.

### QUOTE-03 [P0] Category controls output and state

1. Change category to `Hope`.
2. Generate again.
3. Change to `Kindness` and generate again.
4. Check category stored/present on resulting quote records if history available.

**Expected:** Valid category recognized end to end; quote metadata/display matches selected category. Never always save as `Motivation` while UI shows another category (historical V14 demo had hard-coded sample behavior).

### QUOTE-04 [P1] Repeated generation / unlimited product expectation

1. Generate multiple quotes in a normal human test (e.g. 5–10) across different categories.
2. Observe duplication, cost guards and error messages.

**Expected:** User has no surprise tiny fixed lifetime quota. Reasonable anti-abuse or temporary rate-limits may apply and should be documented. Quote sequence doesn't inadvertently repeat the exact same hard-coded demo sentences unless explicitly in demo mode. Never run high-volume generation with a paid production key merely for testing.

### QUOTE-05 [P0] AI error handling

1. Have developer simulate provider timeout or invalid test key in sandbox.
2. Generate a quote.
3. Retry after restoring test provider.

**Expected:** Clear, safe failure message; form/category remains usable; no secret or exception details; retry succeeds. No half-saved broken quote.

### QUOTE-06 [P0] Save generated quote

1. Generate a quote, click **Save Quote**.
2. Click **Saved Quotes**.
3. Refresh, log out/in and revisit.

**Expected:** Quote appears once and persists; correct text/category/owner; double-save does not duplicate record. Button reflects saved status.

### QUOTE-07 [P0] Copy quote

1. In Quote Studio click **Copy**.
2. Paste into a local text note.
3. Repeat from Feed card.

**Expected:** Exact displayed quote text copied, no HTML tags, tokens or empty clipboard; success notice shown. If Clipboard API unavailable, an alternative manual-copy UI appears.

### QUOTE-08 [P0] Feed list and filters

1. Click **Inspiration Feed**.
2. Scroll multiple cards; note text/category tags and card count.
3. Click **All**, **Motivation**, **Kindness**, **Hope**, or available categories.
4. Return to All.

**Expected:** Feed stable and filtered by actual data, no confidential/private quotes exposed, pagination/load-more/infinite scroll works, duplicate rows avoided, empty state sensible.

### QUOTE-09 [P1] Save from feed and remove from Saved

1. In Feed save one visible quote using **Save**.
2. Go to Saved; verify appearance.
3. Click **Unsave** (or Saved toggle), refresh.

**Expected:** Per-user save toggles accurately; no ghost item after refresh; no duplicate records.

### QUOTE-10 [P0] Cross-user data privacy

1. Create a private quote as User A.
2. Open a second incognito browser and log in User B.
3. Attempt to open User A's private quote (using the tester's controlled direct ID/link, if present).

**Expected:** User B cannot read private data. If there is a confirmed shared feed of platform items, those are viewable only under approved visibility; no automatic publication of User A's private generated content.

### QUOTE-11 [P1] Quote view tracking is not revenue

1. Open the same Feed quote repeatedly as User A.
2. Refresh, scroll, click Save and Generate Ad in demo.
3. Observe quote view count and wallet activity (or admin test report).

**Expected:** Counts follow documented dedup policy, are not blindly inflated by refresh/clicks, and **do not credit real money** merely because a quote was displayed.

### QUOTE-12 [P1] Accessibility and content safety

1. Use a quote containing punctuation, emoji, ampersand, quotation marks and a harmless `<script>` literal provided by a safe test fixture.
2. Display it in Feed and Saved.

**Expected:** All text is escaped/sanitized and renders safely; no script execution, broken HTML or overlapping text.

---
## 6. Original Generate Ad flow (DEMO ONLY until provider approval)

### AD-01 [P0] Generate Ad appears in quote context

**Precondition:** User Feed has at least one visible quote, demo provider mode.

1. Open Inspiration Feed.
2. Read a quote and inspect its action row / post-footer.
3. Identify **Generate Ad** (or the current equivalent in repository).

**Expected:** Action is clearly associated with quote context and is clickable; user not forced into an infinite ad loop to read other content. If no active live provider, CTA either clearly starts a demonstration or is disabled with explanation. No claim that click itself earned money.

### AD-02 [P0] Full-screen demonstration opens and closes

1. Click **Generate Ad**.
2. Observe overlay title and any `Demo` badge.
3. Press **Close (×)**; then repeat and press Escape (if allowed by demo).
4. Reopen and click **Continue to next quote**.

**Expected:** Accessible full-screen demo, no background scroll, no untrusted advertising content, focus inside dialog. Close removes overlay/focus lock. Continue advances to next eligible quote exactly once. No real earnings created.

### AD-03 [P0] Demo ad never increases wallet

1. Note My Wallet available and pending balances before test (zero unless controlled test ledger seed).
2. Trigger demo ad 3–5 times.
3. Open My Earnings and My Wallet, refresh and check admin transaction report.

**Expected:** No conversion/earnings from demo triggers. No actual payout liability. If UI displays fictional amounts, they are visibly labelled demo and cannot be submitted for withdrawal.

### AD-04 [P1] User closes instead of continuing

1. Open ad preview from Feed; close with ×.
2. Compare current quote location before and after.
3. Open from Quote Studio; close; inspect currently generated quote.

**Expected:** Closing behaves consistently with designed navigation. No duplicate quote generation, no lost saved state, no reward.

### AD-05 [P1] Rapid clicks and navigation

1. Click Generate Ad twice quickly.
2. Press browser Back, scroll, rotate viewport, reload where safe.

**Expected:** At most one active modal, no stacked overlays, no JS crash, no duplicate event. Scroll restored after close.

### AD-06 [P0] No active provider / no fill

1. Set ad provider mode to Disabled or No Inventory using approved sandbox admin control.
2. Open Feed and click available ad action.

**Expected:** Safe explanatory message such as `No advertisement available right now`; user can still read, generate/save quotes and leave ad area. Wallet unaffected. Provider exceptions are not visible to user.

### AD-07 [P1] Fake browser event must not credit rewards

1. In a controlled QA browser, have developer simulate `video ended`, `modal closed` or form POST without valid provider callback.
2. Check wallet/earnings.

**Expected:** No real reward from client-side event. If integration incorrectly credits, file P0 financial bug.

### AD-08 [P1] Design consistency

1. Check dialog at 320, 390, 768, 1440px; include landscape phone.
2. Use keyboard only to open/exit; check focus restoration.

**Expected:** Close and Continue always visible, no UI cropped, body scroll unlocks, labels readable and no unescapable trap.

---

## 7. Website sharing (client clarified social media, email, text)

### SHARE-01 [P0] Share website entry point exists

1. Login as User A.
2. Locate **Share Website** or the agreed site-sharing action in current navigation/action menus.
3. Click it.

**Expected:** Browser native share dialog or accessible fallback. It offers sharing of the canonical **website URL**, not an automatically exposed private quote URL. App state preserved when dismissed.

### SHARE-02 [P0] Copy website link

1. Open share options, click **Copy Link**.
2. Paste into text editor and a browser private window.

**Expected:** Correct public website URL (staging explicitly labelled when on staging), no session ID, internal admin route, bearer token, `?code=` credential or user-specific private ID. Guest opening link reaches intended guest experience.

### SHARE-03 [P1] Facebook, X, WhatsApp, LinkedIn

1. Click each available share option separately.
2. Observe opened target and link included.
3. Cancel share and return to site.

**Expected:** Appropriate share intent/URL; correct site title where platform supports; new window uses safe practices; cancel does not mutate app. Social preview depends on platform metadata/caching; record issues without assuming access to social accounts. Platform login prompts are acceptable, not application bugs.

### SHARE-04 [P0] Email share

1. Click **Email** in share dialog on a device configured with mail app.
2. Inspect draft without sending.

**Expected:** User's email composer opens with website URL and sensible subject; app does not send email automatically. If no email handler installed, fallback/notice provided. No private content.

### SHARE-05 [P0] SMS/Text share

1. Click **Text / SMS** on supported mobile or emulator.
2. Inspect composer without sending.

**Expected:** SMS composer contains site URL. No SMS automatically sent or charged; desktop with no SMS handler fails gracefully or offers copy.

### SHARE-06 [P1] Unsupported platforms and native share fallback

1. In browser with native share API test Share.
2. In desktop browser without native share API test again.
3. Test on Instagram-like environment where direct URL share isn't supported.

**Expected:** Always a useful Copy Link fallback; no fake direct-share button promising unsupported third-party behavior. No crash if `navigator.share` rejects/cancels.

### SHARE-07 [P1] Guest share preview

1. Open copied site URL in guest browser or messaging preview tool if available.
2. Inspect title, icon, description and what a logged-out visitor sees.

**Expected:** Recognizable app preview subject to third-party cache; guest lands on Login-first page, not private content. Meta tags do not contain private quote text/user data.

---

## 8. My Earnings and Wallet UI — ledger-backed financial review

### FIN-01 [P0] View earnings as User A

1. Login QA-USER-A; click **My Earnings**.
2. Check Pending, Available/Eligible and total summary cards.
3. Open activity/transaction history.

**Expected:** Shows user's actual ledger-backed entries or `$0.00 / No verified earnings yet`; no made-up revenue. Pending/Available status distinguished. Records have source/provider, time and amount, without raw credentials.

### FIN-02 [P0] Wallet sums match history

1. Click **My Wallet**.
2. Note Available, Pending, Reserved, Paid (if shown).
3. Compare with accounting fixture and Admin read-only report.

**Expected:** Balances reconcile exactly to transactions. Amount currency USD, two decimals; cannot go negative through ordinary permitted UI. Ledger history intact across logout/refresh.

### FIN-03 [P0] Sandbox provider conversion with one-third share

**Preconditions:** QA environment explicitly sandbox; developer prepared a safe provider-mimicking conversion fixture; no real provider callbacks or funds. Client has not finalized exact rounding convention, so acceptance is against documented sandbox rule (`1/3`, floor cents).

1. Note User A wallet baseline (e.g. `$0.00`).
2. Ask developer/QA-admin to trigger the **sandbox** `verified conversion` of `$30.00` eligible publisher payout for User A.
3. Refresh Earnings and Wallet.
4. Open conversion entry detail and admin event log.

**Expected:** Exactly `$10.00` simulated user allocation if sandbox rule uses exact one-third, with source event trace and `DEMO/SANDBOX` label. It must **not** appear as real withdrawable funds. Remaining `$20.00` is simulation platform remainder. This is NOT a guaranteed amount per ad view.

### FIN-04 [P0] One-cent and rounding edge cases

1. Run sandbox conversion `$1.00` eligible payout.
2. Check allocated user cents and platform remainder.
3. Run 1-cent and 2-cent payout fixture in test mode.

**Expected:** Exact integer-cent math according to documented rule; e.g. `floor(100/3)=33 cents`, platform retains 67 cents; no float drift such as `$0.3300000004` or negative cent balance. Zero allocations handled safely and audibly.

### FIN-05 [P0] Duplicate provider event

1. Trigger the same valid sandbox callback/transaction reference twice, then 10 times.
2. Refresh User A Wallet and Admin conversion list.

**Expected:** Exactly one conversion and at most one allocation. Receiving repeated callback can be logged but does not multiply user earnings. If balance grows each time, mark **P0 release blocker**.

### FIN-06 [P0] Invalid event authentication

1. Developer sends intentionally invalid signature or missing verification field to sandbox callback endpoint.
2. User checks Wallet; admin checks audit/event state.

**Expected:** Event rejected or quarantined, no money added, safe error to source, no API secrets logged.

### FIN-07 [P0] Conversion reversal

1. Trigger one valid sandbox reward event (e.g. $30).
2. Trigger provider-supported reversal of that exact conversion.
3. Open history and balance.

**Expected:** Previous transaction stays visible, new reversing entry recorded, pending/available balances reconciled, no silent deletion, and repeat reversal does not double-debit.

### FIN-08 [P0] User A/B isolation

1. Seed User A reward fixture; User B has zero.
2. Login User B; open Wallet and Earnings.
3. Ask developer to attempt User A transaction ID or wallet URL using User B credentials.

**Expected:** User B never sees/changes User A earnings, payout details or audit entries. Backend rejects cross-user query.

### FIN-09 [P1] Wallet balances across refresh/two tabs

1. Open My Wallet in two tabs.
2. In QA sandbox create a legitimate test ledger credit on server.
3. Refresh both tabs and compare.

**Expected:** Consistent server-truth balance, not stale forever or duplicated local-state-only increment. Browser storage is not accounting source.

### FIN-10 [P0] Google ad view cannot credit cash

1. Configure `Google AdSense` or mock standard-ad slot in QA mode without live network ads.
2. Simulate viewing/loading/dismissing normal ad placement.
3. Check earnings and ledger.

**Expected:** No user cash earnings, no view-completion callback mistaken as reward. Owner-only ad reporting may exist separately, but cannot be converted into user ad-view cash credit.

### FIN-11 [P1] Pending vs confirmed revenue

1. Sandbox fixture emits unconfirmed/pending eligible activity.
2. Open My Earnings and Wallet.
3. Then deliver valid confirmed/settled event using current provider state rules.

**Expected:** Pending amounts not available for withdrawal; transition happens only after verified event/reconciliation rule. If provider cannot distinguish states, document conservative policy rather than assuming immediate payout.

### FIN-12 [P1] Provider settlement versus user withdrawal

1. Admin opens provider revenue dashboard after sandbox conversion.
2. Compare reported eligible revenue, provider settlement state and user allocation.

**Expected:** Reports distinguish owner publisher settlement (money paid by network) from user credited but unpaid liability. Labels avoid showing unsettled amounts as owner bank cash.

---

## 9. Withdrawals: detailed user and admin click walkthroughs

**Only execute these cases in SANDBOX with fake money and an explicitly disabled real payout transport.** If real transfers are reachable, stop and mark financial testing BLOCKED pending safe setup.

### WD-01 [P0] Withdrawal page without earnings

1. Login User B with `$0.00` Available.
2. Click **Withdrawals** from sidebar/More or Wallet.
3. Look for `Request Withdrawal` action.

**Expected:** Shows no available funds and safe threshold explanation; request disabled or rejected with clear message. No request created from zero balance.

### WD-02 [P0] Minimum threshold

1. In sandbox, set configured minimum to `$10.00` as QA-only fixture.
2. Give User A `$9.99` eligible simulated available balance.
3. Open Withdrawals and try Request.
4. Raise available to `$10.00`, retry.

**Expected:** `$9.99` below threshold, cannot request. `$10.00` qualifies subject to provider/payee status and finance rules. Live threshold remains unconfirmed and must not be shown as finalized client policy.

### WD-03 [P0] Valid request reserves funds

1. Seed User A `$25.00` simulated Available, zero Reserved.
2. Click **Request Withdrawal**.
3. Choose available approved manual payout method, enter test destination (fake handle) and `$15.00`.
4. Click **Submit Request** once.
5. Navigate to Wallet and Withdrawals History.

**Expected:** Request `Requested` created, Available `$10.00`, Reserved `$15.00`, no money Paid. Unique request reference present; sensitive destination masked in history. Admin queue shows one new request.

### WD-04 [P0] Double-submit and concurrent request

1. Repeat WD-03 from fresh fixture but double-click Submit rapidly.
2. Open same user in second tab and request available balance concurrently as QA exercise.

**Expected:** One or logically validated non-overlapping reservations, never more than wallet available. No negative balance, no duplicate withdrawal with same idempotency key.

### WD-05 [P0] Reject request and release reservation

1. Admin opens **Revenue & Transfers → Withdrawals / Pending**.
2. Select User A `$15.00` request; click **Review** and **Reject**, provide safe reason.
3. User A refreshes Wallet and history.

**Expected:** Status Rejected; Available restored `$25.00`, Reserved `$0`; user sees rejection reason where appropriate; audit records admin ID/timestamp. No actual transfer executed.

### WD-06 [P0] Approve without paying

1. Create new `$15.00` request.
2. Admin clicks **Approve** but does not perform transfer.
3. Check both dashboards.

**Expected:** `Approved` or `Processing`, **not `Paid`**. Reserved funds remain protected; no automatic external money movement. Click approval twice: idempotent transition, no duplicate actions.

### WD-07 [P0] Manual payout confirmation (sandbox fake)

1. In QA sandbox, Admin opens approved request.
2. Enter test payout reference `QA-MANUAL-001`, recorded date and optional note.
3. Click **Confirm Paid** in sandbox mode only.
4. User A refreshes history and Wallet.

**Expected:** Paid status with fake QA reference/date; Reserved funds reduce by `$15`; Lifetime Paid increments; Available remains `$10`; immutable audit entry; fake payout cannot create real money transfer.

### WD-08 [P0] Invalid withdrawal amount

1. Attempt `$0`, negative number, nonsensical text, `0.001`, huge amount and amount above available using UI or approved test request tools.

**Expected:** Rejected by server validation, no reservations/withdrawals. Monetary precision limited correctly to USD cents (or configured provider rules). No SQL or serialization error.

### WD-09 [P0] Payout destination privacy

1. Enter test PayPal/Cash App-like destination through approved test form.
2. View user's history, Admin detail, browser network payload and logs available to QA.

**Expected:** User sees masked destination where appropriate; Admin sees only necessary data. Plain destination not exposed in list exports, third-party analytics or application logs.

### WD-10 [P0] Paid record cannot revert casually

1. After sandbox Paid state, attempt `Edit amount`, `Reopen` or `Delete` via UI or controlled developer test request.

**Expected:** Protected financial finality; corrective adjustment requires explicit audited admin workflow, not silent balance rewrite.

### WD-11 [P1] Automatic payout option unavailable

1. Check Payout Settings with no approved PayPal Payouts/Cash App API contract.

**Expected:** No false `Send Money Automatically` switch promised to client. Manual flow can still work. External payout activation clearly blocked pending account eligibility and integration.

### WD-12 [P1] Provider conversion reversed after withdrawal

1. Create sandbox earning, then test approved/pending withdrawal.
2. Trigger provider reversal against earning event.

**Expected:** No silent alteration of already paid history; risk/liability/adjustment logic recorded for admin, user balance does not become nonsensical. Escalate if policy not defined.

---
## 10. Advertising provider configurations, surveys and callbacks

### PROV-01 [P0] Admin sees provider list and accurate readiness

1. Login as QA-ADMIN.
2. Open **System → Integrations** or **Advertising & Finance → Ad management** (use actual menu mapping from repo audit).
3. Identify Google AdSense, TapResearch, Wannads and TheoremReach where provider integrations are in scope.
4. Observe every status badge.

**Expected:** Providers display meaningful status such as `Not Configured`, `Sandbox Ready`, `Pending Approval`, `Production Active`, `Disabled`. No provider appears live just because a name/card exists. Unimplemented provider appears `Planned/Not Implemented`, not Active. If a card isn't present, record NOT IMPLEMENTED, not FAIL for unconfirmed integrations.

### PROV-02 [P0] Admin can configure without exposing secrets

1. Open an existing provider's **Configure** action.
2. Enter test/sandbox ID/secret as defined by the real vendor documentation.
3. Click **Save**.
4. Reload page and inspect displayed fields, browser source/devtools and available app logs.

**Expected:** Save success, credentials hidden/masked, secret not returned in full. Only admins can edit. No raw keys appear in client JavaScript bundle, HTML, browser console or log. Do not use real production credentials for this QA run.

### PROV-03 [P0] Provider disabled state

1. In Admin disable TapResearch or chosen sandbox provider.
2. Login QA-USER-A and navigate to **Earn Rewards** if implemented.
3. Refresh, navigate away and back.

**Expected:** Disabled provider CTA is hidden/disabled with explanation; no blank iframe; quote platform still usable; no balance increase.

### PROV-04 [P0] TapResearch offerwall entry, sandbox

**Prerequisites:** developer has configured TapResearch-approved sandbox integration or a clearly labeled local mock, not fabricated production approval.

1. Login User A.
2. Navigate to **Earn Rewards / Surveys**.
3. Click the TapResearch activity entry or **View Available Surveys**.
4. Observe opening/embedded experience and user context.

**Expected:** Supported hosted link/iframe/API experience launches according to actual vendor integration, user mapping uses opaque identifier not email/password. No false claim that each quote shows a standalone video ad. Available surveys, restricted/no-offer states and return navigation handled.

### PROV-05 [P1] No inventory / geo restricted

1. In QA fixture set provider response to `no surveys`.
2. Open user activity page.
3. Then simulate ineligible location/age (if supported by provider fixture).

**Expected:** No empty white space; neutral message and link back to Feed; no reward. The app does not claim to change user's geography or bypass targeting requirements.

### PROV-06 [P0] Verified conversion reaches proper user

1. Using approved local callback fixtures, deliver valid provider reward event for User A.
2. Open User A Earnings; open User B Earnings.
3. Open Admin provider event report.

**Expected:** Transaction belongs only to User A, has provider source + external reference, correct pending/confirmed state; User B unchanged. Server validation evidence available. Repeat with missing/unknown user ID: no credit.

### PROV-07 [P0] Callback security, duplicates and reversals

1. Have developer run feature tests against the sandbox callback route: valid, missing signature, invalid signature, duplicate, out-of-order confirmation, reversal, double reversal.
2. Manually inspect resulting event report and wallet.

**Expected:** Only verified supported events affect ledger; no double-credit or double-debit; unsafe requests quarantined. **Never** manually send these fixtures to a real vendor callback endpoint.

### PROV-08 [P1] Network timeout and refresh

1. Sandbox simulate provider response timeout, unavailable iframe, or 500/503.
2. Open user earning activity page and attempt to return to Feed.

**Expected:** Friendly error, recoverable navigation, not a broken full-screen modal; no reward or duplicate credit.

### PROV-09 [P0] Google AdSense is separated from cash rewards

1. Admin opens Google configuration.
2. Check normal ad slot configuration / enabled/disabled states.
3. In controlled mock mode simulate ad rendered and user click/close.
4. Open User A wallet/earnings.

**Expected:** No cash reward from Google ad viewing, no conversion callback for that action; owner-only ad reporting (if implemented) is separate. No test clicking on real ads. Activation requires valid publisher account/website review, which QA cannot 'pass' merely via local code.

### PROV-10 [P1] Wannads/TheoremReach adapter readiness

1. Open their admin cards if implemented.
2. Inspect integration states, documentation link, required credentials, user ID map and callback diagnostics.
3. Do not supply real credentials to an incomplete adapter.

**Expected:** If not included in agreed current scope, listed as pending/planned; no fabricated integration success. If implemented, independently test own SDK/iframe and callback schema; one vendor's callback is not accepted as another's.

### PROV-11 [P0] Real partner activation gate

1. In admin attempt to activate any provider with missing credentials/approval.
2. Observe readiness checks and logs.

**Expected:** Application refuses production-active state or clearly requires authorized confirmation for all mandatory external conditions. Disabled/incomplete provider cannot create real earnings. Full partner approval and rates remain BLOCKED-EXTERNAL until independently confirmed.

### PROV-12 [P1] Revenue report vs publisher settlement

1. Admin opens Advertising Revenue report.
2. Filter by TapResearch and date range for sandbox fixture.
3. Compare `reported`, `eligible`, `confirmed`, `reversed`, `settled` amounts.

**Expected:** No fabricated weekly payout or owner bank settlement. Revenue figures and user liability trace back to actual/test source event IDs, with demo labels.

---

## 11. Admin Dashboard tests, access, auditing

### ADMIN-01 [P0] Admin Overview uses real or clearly labelled data

1. Login QA-ADMIN.
2. Inspect cards for Users, Quotes, Provider Status, Revenue, Withdrawals.
3. Click each applicable card to drill down.

**Expected:** Correct actual counts where feature implemented; sample values visibly labelled demo. Admin sees separate shell, not user feed with swapped title.

### ADMIN-02 [P0] User management list and filters

1. Open **People & Access → User management**.
2. Search User A and User B by safe test identifiers.
3. Filter Active/Pending/Disabled where available.
4. Open one record.

**Expected:** Search/filter/pagination reliable; correct user details; no raw passwords, reset tokens or full payout destination in grid.

### ADMIN-03 [P1] Suspend/deactivate a test user

1. Find QA-USER-SUSPENDED or create safe test user.
2. Admin selects Suspend/Disable (if supported), confirms.
3. Try to login/use reward endpoint as that user.
4. Reactivate via authorized admin.

**Expected:** Policy enforced server-side, action audited; financial history retained; no silent confiscation of wallet balances. If not in agreed scope, mark NOT IMPLEMENTED rather than assuming.

### ADMIN-04 [P0] Quote moderation and visibility

1. Open **Content → Quote Management**.
2. Search an existing quote and inspect category, owner and state.
3. Archive/hide a QA quote through controlled admin action if implemented.
4. As user, inspect Feed/Saved.

**Expected:** Archived/moderated content no longer appears in public/approved feed; user data and audit are preserved. Private generated quotes never accidentally published.

### ADMIN-05 [P0] AI provider configuration

1. Open Admin **Integrations / AI Settings**.
2. View configured provider names and statuses.
3. Run **Test Connection** using sandbox-safe function if implemented.
4. Try replacing/clearing a test key.

**Expected:** Correct provider state, safe feedback, secret masked, no key to browser. Bad key does not crash entire application; normal user cannot open configuration.

### ADMIN-06 [P0] Admin revenue and withdrawal reports

1. Open **Advertising & Finance → Revenue & Transfers**.
2. Filter pending withdrawals, then paid/rejected.
3. Open request detail, inspect audit events and status.

**Expected:** Correct totals and states; no ability to double-approve or overwrite paid amounts. User cannot see this report via direct URL.

### ADMIN-07 [P0] Unauthorized admin financial action

1. As User A attempt to invoke a controlled admin approve/reject endpoint via safe QA test tool.
2. As limited Admin Operator (if exists), attempt action without finance permission.

**Expected:** Server rejects. No transaction changes. Incident logged appropriately without exposing payload secrets.

### ADMIN-08 [P1] Audit trail completeness

1. Admin changes provider sandbox state.
2. Admin approves/rejects a test withdrawal.
3. Admin performs one safe financial adjustment if feature exists.
4. Open Audit History.

**Expected:** Actor, action, subject, timestamp, reason/trace reference present. Existing record cannot be silently edited/deleted through ordinary admin UI. Sensitive secrets redacted.

### ADMIN-09 [P1] Responsive admin tables

1. Set width 320/390/768px; open Users, Revenue and Withdrawals.
2. Scroll table horizontally within its own container.
3. Operate row actions and modal/drawer.

**Expected:** Whole page does not overflow horizontally; row labels/actions remain accessible, sticky header only if safe; drawer can dismiss.

---

## 12. Security, data quality, browser and failure cases

### SEC-01 [P0] Route authorization by direct URL

1. In guest browser type `/wallet`, `/withdrawals`, `/admin`, API endpoints (actual paths from audit).
2. In User B session attempt User A IDs.

**Expected:** No protected data or state mutation. Server checks session and authorization; correct 401/403/redirect semantics.

### SEC-02 [P0] Injected quote content is safely rendered

1. Use harmless test fixture containing literal HTML tag and punctuation, NOT active exploit.
2. View in Feed/Studio/Admin, use Share/Copy.

**Expected:** Literal text escaped/sanitized; no HTML/javascript executes; UI remains stable.

### SEC-03 [P0] Sensitive key exposure

1. Open browser DevTools → Network on regular-user account.
2. Generate test quote and view provider UI.
3. Inspect safe response headers/payloads and loaded JavaScript artifacts.

**Expected:** No OpenAI private key, provider signing secret, APP_KEY, DB password, payout secret or token. If seen: P0 security blocker, secure/rotate compromised secret as directed by owner.

### SEC-04 [P1] CSRF and unauthorized write

1. Ask developer to run framework feature tests for missing/invalid CSRF session action.
2. Attempt safe duplicate submissions as QA user.

**Expected:** Unauthorized/malformed requests rejected; no wallet or account mutation; browser still usable.

### SEC-05 [P0] Email verification/password reset tokens

1. Check account reset and verification in test mailer.
2. Reuse/expire token and attempt abuse safely.

**Expected:** Secure, one-time/expiring tokens; no account takeover, no leaked reset token in error log.

### SEC-06 [P0] Financial event injection

1. Attempt controlled bad callback and guessed transaction via developer's sandbox tests.
2. Verify wallet and event history.

**Expected:** No credit unless vendor event verification passed and transaction eligible. This is P0 regardless of appearance.

### SEC-07 [P1] Error screens and network failures

1. Set browser offline; refresh Feed or Quote Studio.
2. Restore connection.
3. Ask developer to simulate server 500 on AI/test provider endpoint.

**Expected:** Useful error/retry, no blank page, no full Laravel exception in production mode, no repeated or lost money transaction.

### SEC-08 [P1] Rate limits and spam prevention

1. Repeat login failure using agreed safe small test count.
2. Repeat quote generation rapid double click.
3. Attempt repeated withdrawal submit in QA sandbox.

**Expected:** Sensible protections, user feedback; no duplicate quote charges/withdrawals and no accidental user lockout for normal browsing.

### SEC-09 [P1] Browser console and mobile overflow

1. Open Chrome DevTools Console and Network.
2. Navigate every main screen at 320/390/768/1024/1440/1728.
3. Inspect 404/500 errors and uncaught exceptions.

**Expected:** No app-breaking errors, no missing assets, no horizontal overflow except intentional table area, nav controls fully visible.

### SEC-10 [P1] Accessibility audit by human

1. Navigate Login, Feed and Admin only by Tab/Shift+Tab/Enter/Space.
2. Inspect screen zoom at 200%, color contrast, visible focus and modal close.

**Expected:** Controls correctly labelled, keyboard usable, no focus loss or inaccessible overlay. Report issues with screenshot/device.

### SEC-11 [P1] Privacy & policy links

1. As guest and logged-in user open Privacy and Terms.
2. Review available disclosures for AI, advertising/offerwalls, money/rewards and user data.

**Expected:** Pages accessible. If final legal text absent/not client-approved, record BLOCKED: CLIENT/LEGAL and do not falsely certify production readiness.

---

## 13. Provider/monetization model decision checks (scope protection)

These are **product sign-off tests**, not technical implementation tasks. The human tester must know what is confirmed and what remains open, so features are not misreported to the client.

### DEC-01 [P0] Original Viewer Reward model remains specified

- Confirm features described in approved Option B: wallet, eligible user earnings, withdrawals and admin review are present/in progress, not silently removed because client briefly considered owner-only.
- Check actual build does not credit money from ad popup or quote view without approved revenue source.
- Outcome: Verified implementation of UI and ledger infrastructure **can** pass; live cash monetization remains provider-gated.

### DEC-02 [P0] Creator model is NOT silently switched on

- Client later asked whether quote writers could earn money when others view quotes; this was not final feature approval.
- Inspect app for auto-publishing personal AI quotes or paying users by raw quote view count.
- Expected: not introduced as real production behavior without explicit approval; if preview exists, clearly marked experimental/demo.

### DEC-03 [P1] Provider list matches order scope

- Compare current order scope and written follow-up before treating AdSense + TapResearch + Wannads + TheoremReach as four paid, production-activated commitments.
- App may have provider-neutral adapters/cards without live inventory for every requested network.
- Expected: a documented `Included / Research / Pending Scope / Pending Approval / Active` mapping.

### DEC-04 [P1] User-funded advertiser marketplace excluded

- User-funded campaign creation, prepaid reach limits, user-purchased ads and advertiser dashboard were discussed as future research. No confirmed client approval.
- Expected: not exposed as production deliverable or current paid scope without separate sign-off.

### DEC-05 [P1] Sharing scope correct

- Client said website should be shareable on social media, email, text. He did not explicitly request individual private AI quotes to have public pages.
- Expected: website sharing works, no accidental public private quote leak, no invented native app build.

---

## 14. End-to-end human acceptance journeys (run after individual tests)

### E2E-01 [P0] New-user onboarding through inspiration content

**Step-by-step:**

1. Open new incognito window; landing is Login.
2. Open Privacy and Terms, return to Login.
3. Create QA user and verify via test mailbox (when mailer available).
4. Log in and see Feed.
5. Filter to Hope, open Quote Studio, generate Hope quote.
6. Copy, Save, inspect Saved after refresh.
7. Click Share Website and copy link, verify guest opening.
8. Navigate to Wallet and Earnings; no false payment.
9. Logout; direct link to Wallet denied.

**Expected:** complete sequence succeeds on desktop and mobile, or missing external steps clearly blocked with evidence.

### E2E-02 [P0] User views quote/ad demo and safely returns to feed

1. Login User A.
2. Open first quote in Feed.
3. Click Generate Ad in demo mode.
4. Close; repeat, click Continue to next quote.
5. Visit My Earnings and Wallet, compare original balances.

**Expected:** Flow feels usable, balances unchanged, no provider claim, no stuck modal.

### E2E-03 [P0] Sandbox rewarded activity + wallet + admin review

1. Login User A, navigate to enabled TapResearch sandbox/offerwall access.
2. Trigger documented verified synthetic conversion through developer fixture or provider sandbox.
3. Check User A earnings and wallet pending label; ensure User B unchanged.
4. Confirm eligible settlement in sandbox; assert precise one-third allocation.
5. Request withdrawal using sandbox seeded available funds, Admin reviews and approves.
6. Mark fake manual payout `Paid` **sandbox only**; inspect audit/history.
7. Re-run callback and verify no additional credit or payout.

**Expected:** accurate immutable lineage `Provider event → Allocation → Wallet → Withdrawal → Admin decision`; no real transfer, no fake ads.

### E2E-04 [P0] Provider down, core product stays usable

1. Admin disables/marks provider Unavailable.
2. User tries earning activity (safe empty state).
3. User generates Quote, saves, shares, navigates mobile.
4. Admin sees provider diagnostic without secret exposure.

**Expected:** provider outage does not take whole site down or create false credits.

### E2E-05 [P1] Multi-role isolation and responsive usability

1. Open User A, User B, Admin in separate private sessions.
2. Run auth/route isolation checks.
3. Inspect at 390px mobile and 1440px desktop.
4. Validate User B cannot see A wallet, and A cannot access Admin controls.

**Expected:** role/access boundaries enforce correctly on server, navigation works on each device.

---

## 15. Bug reporting and severity guide

**BLOCKER / P0:** signup/login unusable; ordinary user can access admin/another user's wallet; fake ad credits real cash; callback duplicates credit; invalid callback accepted; negative or double-spent wallet; real payout sent without confirmation; secrets exposed; production migration risks data loss.

**CRITICAL / P0/P1:** quote generation consistently fails despite valid configured provider; mobile bottom nav hides all key actions; withdrawal reservation incorrect; logout leaves private content accessible; provider disconnect crashes application.

**MAJOR / P1:** saved quotes lost on refresh; filter wrong; modal cannot exit by available control; inactive provider appears live; revenue incorrectly labelled settled; email reset fails with configured SMTP; share link leaks staging token.

**MINOR / P2:** inconsistent spacing, incomplete translated wording, toast contrast, minor copy changes, hover or animation discrepancy not blocking action.

**BLOCKED-EXTERNAL:** client hasn't acquired domain/VPS/SMTP or approved ad/payout provider; user data/terms missing; publisher verification pending. A blocked live activation is not evidence that internal mocked/sandbox architecture failed; conversely a simulated implementation is not proof of real provider integration.

### Bug template

```text
BUG ID:
Test ID:
Severity:
Environment + build SHA:
Preconditions:
Exact clicks / steps:
Expected outcome:
Actual outcome:
Screenshot/video (redacted):
Browser console/network observation (redacted):
Reproducible? Always / Sometimes / Once
Who should fix? Dev / Client / Provider / Unknown
Fix commit:
Retest result:
```

## 16. Daily test routine when agent delivers a phase

**Before testing:** read the agent's phase report (`FILES CHANGED`, `ACTUAL FEATURES COMPLETED`, `HOW TO TEST`, `EXTERNAL BLOCKERS`), record commit SHA and environment URL. For any feature marked `EXISTS UNVERIFIED` test first; for `NOT IMPLEMENTED` don't claim it as passed.

**During testing:**

1. Open staging in fresh incognito and capture Login screenshot.
2. Run changed feature's P0 cases on desktop.
3. Run same key interactions at 390px and at 320px.
4. Run negative/security test relevant to changed endpoint.
5. Compare UI state to persisted DB/transaction via approved admin report (not unsupported manual DB edits).
6. Record actual vs expected and evidence.
7. If a P0 bug appears, stop release, return exact steps to agent and retest after fix.
8. Re-run smoke suite AUTH-01/05/07, NAV-02/03/04, QUOTE-02/06/08, AD-02/03, FIN-01/02, PROV-03 and ADMIN-01.

**After testing:** mark each test `PASS/FAIL/BLOCKED/NOT IMPLEMENTED/N/A`, update risks, ask agent to repair only evidenced failures; retain history of prior results, not overwrite it.

## 17. Phase-to-test mapping for the human

| Implementation phase | Human must run before acceptance |
|---|---|
| 0 — Repo baseline | Confirm SHA, environment, test credentials, existing UI inventory |
| 1 — Auth + navigation | AUTH-01..11, NAV-01..06, SEC-01/03/04 |
| 2 — AI quotes + feed | QUOTE-01..12, SHARE-01..07, E2E-01 |
| 3 — Demo ad UX | AD-01..08, FIN-10, E2E-02 |
| 4 — Wallet + withdrawals | FIN-01..12, WD-01..12, ADMIN-06..08 |
| 5 — TapResearch proof | PROV-01..08, PROV-11/12, E2E-03/04 |
| 6 — Google/other provider readiness | PROV-09/10/11, FIN-10, DEC-01..04 |
| 7 — Final QA | All P0 cases, E2E-01..05, viewport matrix, release checklist |

## 18. Release gates and final sign-off form

A release **cannot** be declared monetization-ready merely because a test offerwall can open in a browser. The following gates must be separately evidenced:

### Code/UX readiness

- [ ] Actual repo SHA and deployed build recorded.
- [ ] Secure authentication/role separation verified.
- [ ] AI Quote Generation and required categories functional.
- [ ] Feed, save, copy, website sharing work on desktop/mobile.
- [ ] User and Admin experiences distinct and responsive.
- [ ] Demo ad sequence demonstrable with no real reward.
- [ ] Wallet, earnings and withdrawal request/review tested in sandbox.
- [ ] No P0 financial/security defects.
- [ ] Test evidence/logs and release rollback available.

### Third-party readiness (may remain blocked without preventing non-monetized demonstration)

- [ ] Client-owned production domain and VPS provisioned.
- [ ] SMTP credentials verified in production.
- [ ] Publisher account approval documented **for each** intended provider.
- [ ] Exact provider product supports chosen rewarded activities and cash redemption.
- [ ] Live callback verification and settlement reconciliation approved/tested.
- [ ] PayPal/Cash App payout method eligibility and business verification complete where required.
- [ ] Terms/Privacy and financial eligibility rules approved.
- [ ] No AdSense ordinary ad view → user cash reward path exists.
- [ ] Explicit client decision on Creator Revenue Sharing before any new creator payout feature activates.

### Final sign-off entry

```text
Project: Proceeds With Positivity
Build SHA / deployment URL:
Human tester:
Acceptance date:
P0 tests: __ passed / __ failed / __ blocked
P1 tests: __ passed / __ failed / __ blocked
Critical known defects:
Client-dependent items:
Provider-dependent items:
Accepted for INTERNAL DEMO? YES / NO
Accepted for STAGING REVIEW? YES / NO
Accepted for PRODUCTION CORE (without monetization)? YES / NO
Accepted for LIVE MONETIZATION? YES / NO — only if separate provider gates met
Notes:
```

## 19. Final testing reminder

The QA goal is to verify **working software and safe accounting**, not to make third-party approvals appear complete. When the agent presents a finished module, personally click the UI and verify the persisted result. If the provider hasn't approved a publisher account, test the adapter in safe sandbox and document the external gate. If the client hasn't signed off on creator publishing, keep it off. If the old Git repo had many changes but this new one has only two commits, test the real HEAD behavior rather than trusting commit count.

**End of Human Manual QA & Acceptance Playbook.**
