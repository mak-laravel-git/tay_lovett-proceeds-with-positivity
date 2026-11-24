# PROCEEDS WITH POSITIVITY — AI AGENT IMPLEMENTATION CONTEXT
## Add Wannads + TheoremReach, Preserve and Re-Verify Google AdSense + TapResearch

**Document version:** 1.0 | **Prepared:** 2026-11-24 | **Audience:** Cursor / VS Code / other repository-connected coding AI | **Language:** English

**Repository to inspect:** `https://github.com/mak-laravel-git/tay_lovett-proceeds-with-positivity`

**Production site supplied by owner:** `https://proceedswithpositivity.com/` (DO NOT run destructive tests or send fake rewards to production).

**Required companion baseline files (read in full when available):**
- `01_AI_AGENT_IMPLEMENTATION_MASTER_CONTEXT.md` — original full implementation specification, dated 2026-11-19.
- `02_HUMAN_MANUAL_QA_TEST_PLAYBOOK.md` — original full product QA specification, dated 2026-11-19.
- `02_HUMAN_QA_WANNADS_THEOREMREACH_AND_REGRESSION_PLAYBOOK.md` — companion to this addendum.

**Scope-update evidence:** On **2026-11-23**, the client explicitly requested **Wannads and TheoremReach in addition to Google AdSense and TapResearch**. The developer replied that the integration would be pursued, subject to additional integration requirements. This supersedes the *provider shortlist/deferred* status of Wannads and TheoremReach in the 2026-11-19 baseline specification, but **does not override provider contracts, change the agreed 33.33% economics, or approve any unrelated creator-revenue feature**.

**Repository verification status at document authoring:** The GitHub repository could not be retrieved from this environment (network name resolution failed). The previously reported status that AdSense and TapResearch were implemented is OWNER-REPORTED, NOT an independently confirmed code audit. **Do not assert a provider is working without inspecting repository code and running tests.** The coding agent has the local checkout and is required to establish the actual state. No live publisher account approval, keys, API credentials, callback signing secret or cash-redeemable incentive permission has been supplied here.

---

## 0. Executive engineering mandate (do not skip)

Build **two additional, real, independently configurable web provider integrations** for the existing Laravel + Inertia/React application: (1) **Wannads offerwall** and (2) **TheoremReach rewarded surveys/offerwall**. Preserve the existing **Google AdSense normal display ad path** and **TapResearch rewarded survey path**. Retest **all four provider journeys and shared user/wallet/admin functionality after the change**.

A correct result is not four hardcoded logos, inert buttons, or a dashboard that says “Connected.” It is:

1. Real adapter classes/routes/configuration that match the *current official publisher* documentation.
2. Separate user-access journeys: user chooses eligible provider; launch uses the mapped opaque authenticated user ID; UI handles loading, pending approval, empty inventory, error and back-to-site behavior.
3. Provider-to-server **authenticated completion notifications**; verified event persistence, idempotency and reversal/reconciliation.
4. A single existing financial ledger / wallet / withdrawal mechanism reused **without duplicate credits or corrupting old balances**.
5. Admin operational controls, logs, per-provider status, test mode, kill switch, secure credential storage and approval/cash-reward-policy gates.
6. Explicit compliance review for **coexistence of multiple incentivized providers**, notably TheoremReach's published usage restrictions (see §2).
7. Baseline and post-change regression tests proving **Google AdSense and TapResearch still behave correctly**.
8. Feature proof: actual code paths, screenshots, test evidence, source documents and precise provider approval blockers. **Do not describe sandbox/fixture success as live approved earning.**

**Priority order:** Audit existing code → verify official docs with **Context7 + vendor sources** → resolve compliance questions → implement Wannads → implement TheoremReach → cross-provider ledger/UX → full regression for all four → QA report → staging release → production only after explicit approval and verified permission.

**Non-negotiables:** Do not delete or rewrite existing auth, quotes, wallets, AdSense/TapResearch adapters or client data. Do not modify production DB schema without reviewed migration and backup. Do not credit from frontend events or ad clicks. Do not give cash rewards for ordinary AdSense impressions or clicks. Do not silently turn on TheoremReach alongside competitors without written authorization if required by their current terms. Do not assume credentials are available. Do not expose signing secrets, user email or internal IDs in client-side code/logs.

## 1. Authority, terminology and change control

Use these evidence statuses in a `docs/provider-expansion/FEATURE_MATRIX.md` table:

| Status | Exact meaning |
|---|---|
| `CLIENT-CONFIRMED` | Verbatim client instruction, including four provider selection |
| `OWNER-REPORTED` | Developer reports existing feature implementation; source/testing not yet independently verified |
| `CODE-VERIFIED` | File names/classes/routes exist and pass relevant tests |
| `DOC-VERIFIED` | Feature/parameter/protocol verified against authoritative current vendor documentation |
| `TEST-VERIFIED` | Browser and backend acceptance evidence attached |
| `BLOCKED-EXTERNAL` | Credentials, written provider approval, account status, policy/contract or legal content missing |
| `PROPOSED-UX` | Implementable product design, but not additional signed client scope |
| `NOT-APPROVED` | Must not activate live; configuration/UI may exist in disabled state |

This is a *provider expansion addendum*, not permission to redesign the entire app. Where the older master guide has a generic reference to Wannads/TheoremReach as 'future' or 'deferred', **this addendum takes precedence only for implementing the new pair**. Preserve all other baseline behavior and the core 33.33% eligible reward policy, subject to each provider's contract. The client later *asked about* creators being paid for quote views, but did not unequivocally replace the original viewer reward model. Do not interpret a quote view as payable revenue.

### User and provider roles

- **User/participant:** logs in, browses AI quotes and/or selects approved rewarded activities; sees per-provider earnings and withdrawal balance.
- **Website owner/publisher:** has separate accounts with each provider; receives provider settlement and maintains the website's ledger and payouts.
- **Administrator:** enables/disables provider placements, configures credentials, reviews verified callbacks, reconciliation and wallet/payout operations.
- **AdSense:** *standard ads*, owner-only revenue; **never a viewer cash-earning source**.
- **TapResearch:** publisher-approved rewarded surveys/offers, server-postback evidence.
- **Wannads:** publisher-approved offerwall, server-postback evidence.
- **TheoremReach:** publisher-approved survey/offerwall, server reward callback + separate reversal/ban reconciliation as documented. Contractual coexistence and cash-redemption must be cleared before real launch.

## 2. MANDATORY Context7 + official-doc research protocol

**The coding agent MUST use Context7 before writing integration code.** Do not implement Wannads or TheoremReach from memory, guessed URLs, copy-pasted unofficial snippets, or the mere existence of this context file. Documentation may have changed since its preparation.

### 2.1 Mandatory Context7 procedure

1. Inspect repo `composer.json`, lockfile and frontend packages to determine the **actual installed Laravel, Inertia and React versions**. Historical project context mentions Laravel 13 + Inertia React + TypeScript + Tailwind; verify rather than assume.
2. Call Context7 `resolve-library-id` for **Laravel**, **Inertia.js** and relevant frontend library versions; obtain authoritative IDs. Use `query-docs` for Laravel 13 request validation, signed/CSRF-exempt webhook routes, `DB::transaction`, `lockForUpdate`, unique indexes, queue retries, testing and encryption; for Inertia/React use actual version documentation.
3. Call Context7 `resolve-library-id` for **Wannads** and **TheoremReach publisher integration**, recording outcomes. If no correct vendor documentation is indexed, **DO NOT invent an ID or accept a similarly named unrelated package**. Use the official vendor documentation links below as primary sources, and explicitly record “Context7 publisher source unavailable.”
4. A Context7 result for `/websites/surveys-docs_theoremreach` describes TheoremReach **DIY Researcher/Buyer survey management API**, not the publisher's rewarded-survey wall. **Do not implement the researcher `respondent_result/callbacks` API as the publisher reward callback.** Use TheoremReach's publisher docs and actual partner dashboard for publisher rewards.
5. For every integration contract/parameter, compare Context7 examples against provider's CURRENT official docs; **vendor dashboard values and current vendor documentation take precedence**. Never let Context7 snippets override official provider callback/security requirements.
6. Create `docs/provider-expansion/OFFICIAL_DOCS_VERIFICATION.md` recording: date, URL, document title/version, account product selected, expected request method/params, security/hash procedure, response body, rejection/reversal treatment, tested fixture, reviewer and unresolved conflicts.
7. Where provider docs require application-specific macros, signatures or callback settings, inspect the **approved publisher dashboard**. Do not guess missing fields or hardcode invented signatures. If missing, implement a disabled adapter/config screen + isolated fixture and mark it BLOCKED-EXTERNAL until real contract can be verified.
8. Never feed secrets or private user information into Context7 queries. Use it for public docs only.

### 2.2 Official first-party reference URLs checked November 24, 2026

**Wannads publisher/web:**
- Web HTML/iframe: `https://platform.wannads.com/documentation/offerwall/html`
- Reward-app postback: `https://platform.wannads.com/documentation/postback/app`
- Global postback: `https://platform.wannads.com/documentation/postback/global`
- Offerwall API alternative: `https://wannads.com/documentation/offerwall/api`

**TheoremReach publisher:**
- Publisher documentation hub: `https://docs.theoremreach.com/publisher`
- Web/JavaScript/iframe: `https://theoremreach.com/docs/web`
- Publisher offerwall entry and HMAC: `https://theoremreach.com/docs/offerwall`
- Publisher management API: `https://theoremreach.com/docs/user_demographics`
- Publisher go-live checklist + reversal notes: `https://info.theoremreach.com/publisher-faqs/`
- **Terms of Service (read this before enabling multiple networks):** `https://info.theoremreach.com/terms-of-service/`
- Router models: `https://theoremreach.com/docs/routers`

**Existing provider regression references:**
- Google AdSense policies: `https://support.google.com/adsense/answer/48182`
- Google rewarded ad policy (direct monetary rewards prohibited): `https://support.google.com/adsense/answer/9121589`
- TapResearch official publisher docs: `https://supply-docs.tapresearch.com/`
- TapResearch hosted-link rewards: `https://supply-docs.tapresearch.com/docs/3.x/hosted-link/handling-rewards`

**Current documented examples, to reconfirm:** Wannads supports a web iframe launch at the official `earn.wannads.com/wall` host with `apiKey` and `userId`. Its app postback is documented as an HTTP GET, uses `transaction_id`, `user_id`, `reward`, `status` and `signature`, and the documented legacy signature is MD5 of `user_id + transaction_id + reward + SECRET` (no separators). This is a provider-mandated legacy scheme, **not** a generally secure password hash; add documented source IP filtering, constant-time comparison and short-circuit rejection; prefer more secure official alternatives if the provider offers them. Do not mix `SECRET` with `API SECRET` (different fields). Wannads expects exact `OK` or `1` on successful processing; retries can occur, so idempotency is essential.

TheoremReach offers multiple materially different publisher paths (web iframe/JS SDK/new tab, plus **offerwall signed entry** / routers), potentially with different hashes and callbacks. Its **offerwall entry** documentation describes a URL-safe Base64 HMAC-SHA1 of the complete entry URL and parameters; the **server reward callback signature** must be derived from the app's official dashboard documentation, not guessed from the entry-link hash. In the offerwall product, a callback with `debug=true` is TEST ONLY and **must never credit a real user**. TheoremReach's published FAQ says **reversals are not sent by the Reward Callback**; its separate Event Webhook and/or reconciled exports are needed.

### 2.3 Hard policy/contract approval gates (not optional)

**GATE C1 — Competing rewarded offer/survey providers:** TheoremReach's published Terms of Service, §2.3, include a restriction that, absent specific written approval, the publisher must not make similar incentivized offers from other providers available in the same application; they also restrict modifying/aggregating offers. Therefore the requested combination **TapResearch + Wannads + TheoremReach** cannot be assumed contractually permitted just because the code can display all three. Obtain **written authorization from TheoremReach** for this exact multi-provider use before enabling TheoremReach in production alongside others. Capture approval reference/date and approved scope in admin compliance record. If not confirmed, `theoremreach.production_enabled = false`, with explanation `Pending provider coexistence authorization`.

**GATE C2 — Cash-redeemable rewards:** TheoremReach documentation describes an *in-app virtual currency*, and its web SDK can have provider-managed rewards/cashout paths; this is not automatic authorization for your platform's **PayPal/Cash App cash withdrawals**. Wannads similarly describes reward points/virtual currency; inspect each publisher agreement and obtain account-specific permission for cash-redeemable rewards, withdrawal timing and any minimum/refund/settlement requirements. If not approved, the integration may be technically built/tested, but real **withdrawable** credit is disabled. TheoremReach's reward amounts/rounding and exchange rates must match publisher agreement; do not silently apply 33.33% on top of a provider-configured user award if it results in mispayment.

**GATE C3 — Site compliance:** Before applying to activate: published Privacy Policy, Terms of Service, active terms/privacy acceptance where required, user support contact, legitimate account-based users, bot/anti-fraud controls, country/age eligibility and account/website approval. TheoremReach's official go-live checklist explicitly calls for a working site, policy pages, *active* consent at sign-up, sign-in security, registered accounts and functioning in-app reward currency. The site owner must supply/approve legal text; the developer must not fabricate jurisdiction-specific policies.

**GATE C4 — Google AdSense separation:** Its regular display ad impressions/clicks cannot become a cash reward trigger, nor can users be prompted to click/view AdSense ads to earn money. Do not insert incentivizing copy adjacent to AdSense, mix AdSense reporting with rewarded offer conversion transactions, or use Google ad preview as an earning event.

**GATE C5 — Settlement and fraud:** Publisher revenue is not the same as settled transferable funds. Fraud, duplicate users, chargebacks and postback reversals can occur. Configure pending/hold periods and withdrawal eligibility from approved provider terms. Disallow payout of unverified, test, reversed or externally blocked rewards.

### Admin proof for gates

Add a provider compliance summary with fields `approval_status`, `approved_domain`, `approved_product`, `reward_cashout_permission`, `concurrent_provider_permission`, `source_document_link`/internal reference, `verified_by`, `verified_at`, `expiry/review_at`, `production_enabled`, `notes` and support for attaching internal evidence (redacted). This may live in existing provider settings if an equivalent structure already exists. Defaults = **unverified / false**. No operator can bypass hard gates simply by pressing an Enable button.

## 3. Mandatory repository and environment audit BEFORE coding

Run read-only inventory first. Include evidence, not just descriptions:

```bash
pwd
git status --short --branch
git log -5 --oneline
php -v && composer --version
node -v && npm -v
composer show laravel/framework --locked 2>/dev/null || true
# Use whichever package manager lockfile exists.
```

1. Map all actual routes (`php artisan route:list`), frontend navigation/components, domain models, migrations, financial tables, admin provider settings and scheduled/queue jobs. If artisan cannot boot, diagnose without replacing `.env` or production secrets.
2. Identify existing **AdSense** script/placement settings and whether it is only a simulated placeholder or real provider adapter. Identify existing **TapResearch** hosted link/SDK, callbacks, signature validation, currencies, transactions, reversal handling and status screens.
3. Inventory environment variables and app keys by *names only*; redact values. Check if `APP_ENV`, queue, cache, session and database drivers are suitable for async/webhooks; never echo actual keys.
4. Determine existing uniqueness constraint(s) on provider conversions and if wallet balance is computed from ledger or mutable sum; inspect transaction isolation and withdrawal reserves. Reuse proven foundation where feasible.
5. Locate existing `/earn`, `/wallet`, `/admin/integrations` equivalents by **actual route name**, not guessed URL; record per-screen behaviors. Compare user-facing screenshots to baseline V14 UI without overwriting approved design.
6. Before implementing, run tests/build/lint relevant to existing repo. Record failures as baseline; do not treat pre-existing failures as newly introduced bugs.
7. Back up schema/DB before migrations; identify production/staging credentials without copying them into documentation. Never run `migrate:fresh`, `db:wipe`, `git reset --hard` or mass refactors.
8. Create a `feature/provider-expansion-4networks` branch when appropriate; commit clean bounded milestones after tests.

**Deliver these pre-change artifacts in the repository:**
- `docs/provider-expansion/REPOSITORY_AUDIT.md` with actual paths, existing feature behavior and tests;
- `docs/provider-expansion/BASELINE_4_PROVIDER_MATRIX.md` for old two and new two;
- `docs/provider-expansion/OFFICIAL_DOCS_VERIFICATION.md` from §2;
- `docs/provider-expansion/PROVIDER_COMPLIANCE_GATES.md` with open approvals;
- `docs/provider-expansion/BASELINE_TEST_RESULTS.md`, with current test commands and screenshots;
- `docs/provider-expansion/IMPLEMENTATION_LOG.md`, updated after each subphase.

**Baseline matrix example (initial statuses are hypotheses, not verified results):**

| Provider | Expected role | Existing status | Desired technical result | Go-live dependency |
|---|---|---|---|---|
| Google AdSense | standard ads; owner income | OWNER-REPORTED integrated | existing placements preserved and tested | AdSense domain/account approval |
| TapResearch | survey/offers; eligible user rewards | OWNER-REPORTED integrated | old callback/ledger unchanged & tested | active account, payout/cashout rules |
| Wannads | web offerwall; eligible rewards | NEW client-confirmed | new secure adapter + callback + ledger | publisher account, configured app, approval |
| TheoremReach | rewarded surveys/offerwall | NEW client-confirmed | signed integration + callback + reconciliation | website approval, concurrency & cash permission |

## 4. Product experience: screen-by-screen requirements and click behavior

**UI implementation rule:** Preserve the application's existing navigation labels, layout, React component hierarchy and design tokens unless they conflict with this confirmed four-provider change. The paths below are **illustrative**; agent must bind them to actual audited route names. Prefer an additive integration page or sections rather than introducing unrelated redesigns.

### 4.1 User: sign in and navigate to Earn Rewards

**Precondition:** a real or QA user account is authenticated, verified if required, not suspended, has accepted legally required policies, and has no provider-specific compliance restriction.

**Desktop click sequence:**
1. Open site; if guest, use **Log in** and complete authentication. App displays established Quote Feed/home/dashboard.
2. In the existing user sidebar choose **Earn Rewards**, **Ads & Rewards**, or closest verified equivalent. If no earning page exists, add the minimum nav destination consistent with the baseline master context.
3. On the reward page display a compact explanation: *“Earnings are credited only for eligible completed activities confirmed by the selected provider. Simply opening or viewing an offer does not generate a payout.”*
4. Display three distinct **rewarded provider cards**: **TapResearch**, **Wannads**, **TheoremReach** (only where each is contractually allowed to appear). Display provider name, activity type, status, and **Open Surveys / Explore Offers** button. No fabricated “$ per view” or guaranteed income badges.
5. Google AdSense must **NOT appear as a cash-earning survey/offer provider card**. Its standard ad inventory may remain in existing lawful pages and placements, clearly labelled **Advertisement** where applicable.
6. If a provider is `PENDING_APPROVAL`, `DISABLED`, `COMPLIANCE_BLOCKED` or `NO_OFFERS`, show a meaningful message and disable launch. Do not make a disabled button perform a fake conversion.
7. When user selects an approved provider, create a server-side provider session/access link using the logged-in user and route to a supported iframe/modal/new tab. Do not create the provider access URL purely in the client from an editable user id.
8. After returning, optionally show **“Your activity is being verified. Any eligible reward will appear in your Earnings after confirmation.”** Never claim completion on browser return or popup close alone.
9. Use existing **My Earnings** and **Wallet** pages to show transaction source, status, date and monetary value where legitimately available. No cross-provider duplicate.

**Mobile click sequence:** Tap the established bottom navigation/menu → **Earn** → provider card → official supported web entry view/new tab → return to the site → check transaction list. Keep 320px–430px widths responsive; iframe must not produce sideways page scrolling. Link must be accessible by keyboard/assistive technology.

### 4.2 Provider card status behaviors

| Admin/technical state | User-visible label | Button | Financial side effect |
|---|---|---|---|
| `DISABLED` | “Temporarily unavailable” | Disabled | None |
| `PENDING_APPROVAL` | “Awaiting provider activation” | Disabled | None |
| `COMPLIANCE_BLOCKED` | “Not yet available” | Disabled | None |
| `SANDBOX` | “Test mode — no real earnings” | Staff/QA only | Isolated simulation only |
| `ACTIVE` and approved | “Available” | Launch allowed | Only later verified eligible callback |
| `NO_INVENTORY` | “No activities available right now” | Retry or disabled | None |
| `GEO_RESTRICTED` | “Not available in your region” | Disabled | None |
| `UNAVAILABLE/ERROR` | “Service temporarily unavailable” | Retry later | None |
| `MISSING_CREDENTIALS` | “Setup in progress” | Disabled | None |
| `BANNED/INELIGIBLE` | “Not eligible for this provider” | Disabled | None |

Do not advertise platform-wide guaranteed earnings, manipulate surveys into a compulsory ad-view step, or force provider activities before quote generation. The original quote-generation journey must remain functional regardless of survey availability.

### 4.3 Wannads full user click journey

1. User enters Earn Rewards and sees **Wannads — Offers** card (only if account, approval, rules and configuration permit).
2. Click **Explore Wannads Offers**; frontend asks the backend for an access payload: use a dedicated authenticated action on actual app route (e.g. `GET /earn/wannads/access` as **proposed**, not mandatory path).
3. Backend resolves authenticated app user → **opaque provider user ID** (stable/unique, no email/name). Confirm provider enabled, approved domain, supported region and policy gate; generate allowlisted URL matching official Wannads page: `https://earn.wannads.com/wall?apiKey=<PUBLIC_APP_KEY>&userId=<OPAQUE_USER_ID>` with URL encoding. The `apiKey` may be visible if vendor docs identify it as public **app key**; never expose app `SECRET` or `API SECRET`.
4. Render a responsive iframe **if current vendor settings permit**, else new tab using official URL. Show a loading skeleton, fallback **Open in a new tab**, and **Back to Rewards**. Treat browser tracking-prevention/no cookies gracefully; avoid iframe hacks around provider restrictions.
5. Wannads chooses eligible offers by device/locale; don't promise offers for every user. User may click an offer, complete approved advertiser-defined actions, or close. Closing = no reward.
6. Wannads calls the site callback from its server after a qualifying offer transaction; only backend can verify and credit. UI does not receive/compute reward from iframe events.
7. Wallet/Earnings display `Wannads`, transaction status and amount only after callback verification, policy rules and hold/settlement logic. Repeated callback does not re-credit. Rejection results in correction/reversal, not deletion of financial history.
8. On refresh, display persisted result; if provider never sends a callback, no credit and user sees an appropriate pending/no-activity state. Link to support/report issue when applicable.

### 4.4 TheoremReach full user click journey

**Pick ONE supported product path based on the approved app dashboard and documented requirement; avoid hybridizing inconsistent paths.** Preferred low-risk web option is official **signed offerwall entry/new tab** if that product is enabled for the account and backend can sign correctly. TheoremReach web iframe or official JS SDK is an alternative when publisher approval permits it. Do not put a fabricated provider UI over a third-party iframe.

1. User enters Earn Rewards, sees **TheoremReach — Surveys** only when **C1 multi-network written permission** and other gates are satisfied. Otherwise show “Pending provider activation” and no launch.
2. Click **Take Surveys** → backend prepares an approved user access record and returns the server-signed URL/launch instructions. Required offerwall params must match official `theoremreach.com/docs/offerwall` and account settings (e.g. `api_key`, `user_id`, `transaction_id`, `currency_name_plural`, `exchange_rate`, `external_id`, `partner_id`, `hash`). Use actual unique tracking IDs and correct URL-safe Base64 HMAC-SHA1 over the **exact complete pre-hash URL** per current documentation. Do not build hash over a reordered/decoded version.
3. If using standard web direct-link/JS SDK instead, use that guide's exact credentials, `userId`, and events; JS reward/close events are UI hints, **never authoritative wallet credits**. Note provider's current web docs describe login popups and browser-storage limitations for iframes. Permit required popup behavior if using iframe; avoid illegal cross-origin scripting.
4. Survey wall opens in official TheoremReach surface. User completes onboarding/consent required by provider, sees eligible surveys or an empty state. Do not prefill invented demographics or promise guaranteed payouts.
5. On provider `success`, `partial` or other approved rewarded event, site receives S2S callback. Validate callback against **current app dashboard's documented hash/signature**, event ID, user and reward fields; capture raw event separately and apply approved conversion/exchange settings.
6. **Any callback carrying `debug=true` is a test event.** Persist in isolated testing/audit records if needed, but do not increment real wallet. Likewise, a browser redirect to “success” alone never increments wallet.
7. TheoremReach's official FAQ distinguishes **reward callbacks** from **reversals**. Implement separate Event Webhook (if available to account) and/or authenticated reconciliation against provider exports/API for reversals and banned users. Until reliable reversal handling is verified, keep real rewards pending and payouts off if necessary.
8. Wallet/Earnings show source **TheoremReach**, verified amount/status, optional hold and reversal. If user is restricted/banned or no surveys available, show truthful message; do not invalidate quotes or other providers.

### 4.5 User Earnings / Wallet behaviors (all four coexist)

When user clicks **My Earnings**:
- Existing summary cards remain (Total Eligible, Pending, Available) unless existing approved UI differs.
- Add filter by source: `All / TapResearch / Wannads / TheoremReach` plus any other *approved* earning source. **AdSense should not be in a cash reward filter**.
- Each row shows date/time, provider name, **verified transaction reference masked where appropriate**, earned amount and `Pending / Available / Reversed / Held`. Display `Test` flag on any simulation.
- If user clicked an offer but has no verified callback, **show no earned amount**. Optional `Activity launched` record must be explicitly separate from financial earnings.

When user clicks **Wallet**:
- Reuse actual wallet/ledger. Balances must be derived consistently from verified financial entries. Pending/held funds are **not withdrawable**.
- A reversal appears as a compensating ledger event, not overwritten balance history. If reversal would make a user overdrawn, flag/admin-resolve; never silently create a negative cash payout or consume unrelated user's funds.
- Existing **Withdrawal Request** rules apply to the combined eligible **available** balance, subject to per-provider holds and legal approval. A test balance or blocked provider credit never becomes withdrawable.

### 4.6 Admin: Advertising / Integrations screen

Click path (route determined by audit): **Admin** → **Advertising** or **Integrations** → **Providers**. Display all four providers as rows/cards with **separate integration types**:

| Provider | Type | Config controls | Status | Admin action |
|---|---|---|---|---|
| Google AdSense | `standard_ads` | publisher ID, slot config, approval flags | Configured / Pending / Approved | View settings + inspect slots |
| TapResearch | `rewarded_survey` | web link, publisher config, callback verification | Configured / Pending / Approved | Test current callback path |
| Wannads | `offerwall` | app public API key, app SECRET securely stored, product mode, postback URL, cashout gate | Disabled / Pending / Sandbox / Active | Configure, verify, run test, enable when gates met |
| TheoremReach | `rewarded_survey_offerwall` | app/API key, placement/partner ID, server secret, currency/exchange rate, callbacks, compliance evidence | Disabled / Compliance Blocked / Pending / Sandbox / Active | Configure, verify, test, enable only after approval |

**Individual controls:**
1. **Configure** opens provider-specific form, with sensitive values masked (`********`), validation on save, and explicit **Save Settings** confirmation. Never send stored secret plaintext back to Inertia props. Allow **Replace Secret** without exposing prior secret.
2. **Test Configuration / Test Callback** invokes internal sandbox fixture or official vendor-supplied test (where safely permitted). It reports `Config OK`, `Signature verified`, `Callback received`, `Ledger sandbox check passed`, each independently. **Never falsely label “Provider Approved” because a test callback worked.**
3. **Enable / Disable** has server-side authorization, audit record and gate checklist. Enable blocked if C1–C5 incomplete. Disabled instantly hides user launch CTA but must preserve historical earnings, callbacks, reversals and reporting.
4. **View Transactions** filters raw inbox and ledger by provider, includes date, external transaction ID, normalized event status, amount/currency, verification outcome, retry/duplicate/reversal flag, with PII redacted.
5. **View Compliance** shows evidence of approval, cash reward permission, provider coexistence permission and date; no production enable if fields unverified. Restricted access for superadmin as required.
6. **Audit Log** records operator, change, timestamp, old/new *non-secret metadata*, correlation ID; never log keys or full user identifiers in normal logs.

**Admin status distinctions must be explicit:** `Adapter Implemented` ≠ `Configured` ≠ `Technically Tested` ≠ `Provider Approved` ≠ `Production Active` ≠ `Revenue Settled`. A dashboard chip claiming “Active” must mean all production requirements have been fulfilled, not only a green local test.

### 4.7 Admin: Finance, revenue, reconciliations, withdrawals

- Existing revenue/withdrawal page gains filters `Provider: All | TapResearch | Wannads | TheoremReach` and transaction states; do not disturb existing behavior.
- Clicking a transaction displays source-specific details: exact original provider payout (as permitted), user reward issued, conversion formula, currency, source reference, postback verification and reversal state. Private secrets must never display.
- Clicking **Reconcile** runs an approved batch/report or lets admin upload provider-certified export following audit/logging; **no free-form “mark verified” for fabricated external conversions**.
- Clicking **Hold Payouts for Provider** prevents that provider's associated balances from becoming withdrawable while preserving other clean balances; transaction-safe evaluation required.
- Approving withdrawals uses the original audited financial state machine and cannot debit the same amount twice. Admin manually marks paid only with real payment reference; code must not claim automated PayPal/Cash App payouts unless APIs really exist.

### 4.8 Error, empty, disconnected and mobile UX examples

| Situation | Required visible response | Backend expectation |
|---|---|---|
| Wannads approved but no offers in user's country | “No offers available right now. Please check back later.” | No fabricated payout |
| TheoremReach approval pending | “TheoremReach is being prepared for launch.” | No launch or wallet credit |
| Provider iframe refuses to load | “Can't load this offerwall here. Open in a new tab.” | Valid approved URL or safe disabled fallback |
| Session expired between page open and launch | Login/expired session prompt | No anonymous user ID created |
| User opens two provider tabs | Both can operate independently where contract permits | Isolated provider sessions and event keys |
| Callback arrives during site downtime | Provider retries or reconciliation finds event | No lost/duplicate credit after recovery |
| Callback arrives before browser returns | Wallet shows transaction on next refresh | UI doesn't need client callback to authorize |
| User is suspended | Provider launch disabled, financial history retained | No new eligible credit without policy handling |
| Admin disables provider during session | Existing legitimate callback processed/held per agreed policy | Do not silently drop financial obligations |
| High latency/third-party outage | Skeleton, timeout, retry/back | Quote Feed unaffected |

---

## 5. Backend component design — adapt to actual repo architecture

### 5.1 Preferred separation of responsibilities

If equivalent services already exist, **extend them**. Illustrative names only:

```text
app/
  Contracts/Rewards/ProviderAdapter.php
  Data/Rewards/ProviderEntry.php
  Data/Rewards/VerifiedEvent.php
  Services/Rewards/ProviderRegistry.php
  Services/Rewards/WannadsAdapter.php
  Services/Rewards/TheoremReachAdapter.php
  Services/Rewards/RewardEventProcessor.php
  Services/Rewards/RewardAllocator.php
  Services/Rewards/ProviderComplianceGate.php
  Services/Rewards/ProviderUserIdentity.php
  Services/Rewards/RewardReconciliationService.php
  Http/Controllers/Rewards/ProviderLaunchController.php
  Http/Controllers/Webhooks/WannadsWebhookController.php
  Http/Controllers/Webhooks/TheoremReachWebhookController.php
  Http/Controllers/Admin/ProviderConfigController.php
  Jobs/Rewards/ProcessVerifiedRewardEvent.php
  Jobs/Rewards/ReconcileProviderReversals.php
resources/js/Pages/Rewards/*
resources/js/Pages/Admin/Integrations/*
tests/Feature/Rewards/*
tests/Feature/Webhooks/*
```

These are *proposed* module boundaries, NOT claims that these paths exist. Reuse existing namespaces/casing, route conventions, service container, TS types, existing tests and database schema. Avoid creating duplicate reward-processing systems.

### 5.2 Provider-neutral interface and normalized event envelope

```php
// ILLUSTRATIVE pseudocode, not a drop-in implementation.
interface RewardProviderAdapter
{
    public function key(): string;
    public function integrationType(): string; // rewarded_survey|offerwall|standard_ads
    public function launchFor(User $user): ProviderLaunchResult;
    public function verifyCallback(Request $request): VerifiedProviderCallback;
    public function normalize(VerifiedProviderCallback $callback): NormalizedRewardEvent;
    public function capabilities(): ProviderCapabilities;
}
```

Only a successful `verifyCallback()` can produce a ledger-eligible event. `Normalization` must carry provider-specific proof/reference, not silently invent payout.

```json
{
  "provider": "wannads",
  "event_id": "server-generated-unique-uuid",
  "external_transaction_id": "sample-txn-1001",
  "external_user_id": "opaque-user-token",
  "event_type": "credited",
  "raw_reward_units": "100",
  "raw_publisher_payout_usd": "0.75",
  "event_currency": "provider_points",
  "signature_verified": true,
  "is_test_event": true,
  "provider_timestamp": null,
  "raw_event_reference": "encrypted-or-redacted-payload-id"
}
```

This JSON is an **example of an internal canonical envelope**, not Wannads' wire format. Do not accept a browser-submitted version of this envelope as verification. For real events, use the provider's actual postback field types/semantics and verified mapping.

### 5.3 Data persistence (only additive migrations if needed)

Inspect existing schema before adding anything. Suggested *logical* entities:

- `monetization_providers`: unique key (`google_adsense`, `tapresearch`, `wannads`, `theoremreach`), category, environment, state and feature flags.
- `provider_configurations`: provider/account scope, public config, encrypted secrets, approved domain, current schema version, last verified configuration time.
- `provider_compliance_approvals`: approval references for domain, user incentive type, cash redemption and provider coexistence.
- `provider_user_mappings`: `(provider, internal_user_id)` UNIQUE, immutable opaque partner user ID; prevent raw user email leakage.
- `provider_launch_sessions` (if product requires it): provider, user, correlation/transaction id, created/expiry times, mode.
- `provider_callback_inbox`: provider, external transaction ID, event classification, signature verification, raw payload digest/secure payload, processing state, first/last seen timestamps and attempt count.
- `reward_transactions` / existing ledger: *unique provider-event identity*, user, approved payout basis, user allocation, currency, status, source reference; immutable entries.
- `wallet_ledger_entries`: double-entry or immutable signed delta model already used by project; no unvalidated manual balance updates.
- `provider_reconciliations`: import/export identifiers, provider settlement totals, audit trail, reversals/bans.
- `provider_audit_events`: admin changes, callback anomalies, compliance state transitions.

**Uniqueness key:** `(provider_key, external_transaction_id, event_type_or_version)` or equivalent model allowing exactly one initial credit and a distinct reversal of **the same external transaction ID**. A second identical `credited` callback is idempotent no-op. A separate `rejected/reversed` event is eligible once. **Distinct providers may use identical transaction IDs without collision.** Any provider event lacking a safe unique transaction identifier must not create cash reward until the provider's documented uniqueness scheme is verified.

**Legacy TapResearch migration:** Do not migrate existing TapResearch conversions to a new schema unless proven necessary. If changing constraints or introducing unified inbox, maintain backward-compatible reads and data mapping; migration tests must cover real historical rows and wallet balances. Existing AdSense reporting remains separate, not recorded as user reward credit.

### 5.4 Provider account IDs, user IDs, secret handling

- Use stable, pseudonymous provider-specific user IDs generated on the server; mapping is persisted to ensure callback user resolution. Never use `auth()->id()` blindly in a public URL if an opaque mapping already exists. Never pass e-mail, full name, payout address, or session cookie value into partner URL.
- Public app API key vs private callback signing secret are **different concepts**; keep private secrets encrypted at rest using existing conventions and never render in Inertia props or JS bundles. For Wannads official docs distinguish `SECRET` from `API SECRET`.
- Only allow official provider domains/hosts when building outbound URLs; reject arbitrary operator-supplied open redirect targets. Use safe URL building and encode inputs.
- Record provider/account identifier with each mapping so changing account credentials doesn't reassign existing transactions to wrong user.
- Implement credential rotation with grace period only when vendor supports it; never silently disable validation to get tests to pass.
- Separate sandbox from production secrets/databases and dashboards; never execute vendor test callbacks against real wallet tables.

## 6. Wannads integration — exact engineering specification

### 6.1 Choose product and configure the publisher account

1. In the approved Wannads publisher dashboard, create/select the actual **web app/site** under the client's account and register the correct approved domain.
2. Choose the **HTML offerwall** path for a straightforward web launch, unless the account specifically requires its Offerwall API. Do not implement a native Android/iOS SDK into this Laravel website.
3. Record separately the **public app/API key** used in official offerwall URL and **private postback `SECRET`**. Inspect the dashboard for supported test mode, app-level callback, accepted domains and client country limitations.
4. Prefer **Reward App Postback** if user-level wallet rewards are intended. **Global Postback** is documented differently and may not supply the same user mapping/reward currency. Do not merge them into one assumption; implement the exact approved product and test accordingly.
5. Configure the provider-side callback URL with all needed vendor macros and application-specific names. Illustrative *template only*:

```text
https://<QA-HTTPS-OR-APPROVED-DOMAIN>/integrations/wannads/postback
  ?user_id={user_id}
  &transaction_id={transaction_id}
  &reward={reward}
  &status={status}
  &payout={payout}
  &signature={signature}
```

The app determines its **actual** route and provider configuration. The provider *expands the macros*; do not assume the literal braces are sent. Add goal/offer metadata if documented and necessary. `payout` and `reward` are different; confirm the proper publisher vs user unit conversion.

### 6.2 Web user launch

- Backend checks active state, the user's eligibility, compliance gate and account configuration, then generates or returns an approved host URL using the stored opaque provider ID.
- If using iframe: sandbox and CSP configuration must permit only necessary provider behavior, based on official iframe integration and browser testing. If iframe is broken due to third-party cookies, supported new-tab fallback is acceptable.
- Do not give Wannads your application's session token or private signing secret.
- Treat affiliate tracking sub-IDs (`aff_sub`, etc.) as optional and restrict content/length according to vendor docs; never insert raw unbounded user-supplied strings.

### 6.3 Server postback verification

For the documented **Reward App Postback**:

1. Receive HTTPS GET in the **vendor webhook controller**, separated from authenticated user routes.
2. Require `user_id`, `transaction_id`, `reward`, `status`, `signature` and any other fields required by current vendor docs; validate types/length/currency, reject malformed/unbounded values.
3. Verify user ID maps to valid known user for the **Wannads account**, not just any app user.
4. Validate documented signature before trusting event. Current docs show:

```php
// PSEUDOCODE referencing official Wannads app-postback docs.
$expected = md5($userId . $transactionId . $rawRewardString . $wannadsAppSecret);
if (! hash_equals(strtolower($expected), strtolower($providedSignature))) {
    // Reject; NEVER credit the wallet.
}
```

Preserve the exact **raw reward string** semantics required by the documented signature. Do not normalize numeric formatting (`100` vs `100.0`) **before** signature verification. `SECRET` is NOT `API SECRET`. Re-check current app dashboard for updated algorithms. Vendor docs list a source IP; enforce the current vendor allowlist if operationally reliable, respecting trusted proxies/CDN headers and never trusting arbitrary `X-Forwarded-For` from the internet. Note IP addresses can change; get confirmed up-to-date list and keep it configurable.
5. Classify `status = credited` versus `status = rejected` (older migrated accounts can use numeric statuses per vendor docs). Configure the status mapping by verified account variant. Do not treat an unrecognized status as credited.
6. Validate `transaction_id` uniqueness under provider namespace. Store authenticated callback atomically and return exact documented acknowledgement (`OK` or `1`) **only after durable acceptance**. If asynchronous processing is used, ensure inbox write succeeds before acknowledging.
7. On credit: if not already credited and not already reversed, create one eligible provider event and one allocation/ledger movement (possibly `pending` until settlement).
8. On rejection: correlate to same original `transaction_id`; create **one compensating reversal** if original credit exists; if rejection arrives first, store terminal or awaiting-match state preventing later invalid credit. Do not subtract using both negative `reward` and a second reversal; handle both the reward sign and status carefully.
9. If duplicate credit arrives, no additional wallet entry; a valid repeat acknowledgement can still return `OK` to stop retries. If same `transaction_id` has conflicting positive amounts, quarantine and alert rather than pay whichever arrives last.
10. If invalid signature, unknown user, impossible amount or unsupported currency, reject/alert; don't leak user existence or secret in HTTP response. Provide admin diagnostics for safe reconciliation.

### 6.4 Wannads tests and examples

**Example W-01 (QA fixture; not live provider request):** valid signed `credited`, `transaction_id=w-qa-100`, `reward=100` provider points, `payout=0.75` USD; app creates exactly one pending accounting event, with conversion determined by approved client policy; no real cash in QA.

**Example W-02:** same signed event sent 5 times. The stored transaction and wallet credit count remain **one**.

**Example W-03:** `rejected` with **same** `transaction_id`; create one reversal and mark original reversed. Repeat rejection → no new reversal.

**Example W-04:** receive reversal BEFORE original credit, then later positive callback. Result remains zero active credit; do not re-credit reversal-flagged transaction.

**Example W-05:** sign `100` but send `1000`; signature fails and Wallet stays unchanged.

**Example W-06:** invalid user ID / other provider's user ID; reject, no money. The endpoint is accessible for vendor but must not allow an unauthenticated attacker to forge credits.

## 7. TheoremReach integration — exact engineering specification

### 7.1 Choose correct account product and documentation

1. Check client publisher account, web domain approval and **written simultaneous-provider authorization**. If absent, implement code with status `COMPLIANCE_BLOCKED` and give client explicit requirement; do not enable the live surveys card.
2. Confirm whether client account is configured for web **JS SDK**, direct `respondent_entry/direct`, dedicated **offerwall** with signed entry, or **router**. Each has different query params and callback assumptions; pick one legitimate integration and document why.
3. Prefer the official web SDK/direct entry for a conventional survey experience; where provider requires signed offerwall entry link because exchange rate is user-controlled, use offerwall guide's full canonical string and signature implementation. If the chosen account doesn't expose documented secrets/placement IDs, stop live activation pending dashboard access.
4. Register approved site, currency/exchange-rate, postback URLs, legal consent and required account metadata in the publisher dashboard. Do not spoof demographic answers.
5. Confirm reward currencies, `partner_id`/placement, conversion rules, `debug` behavior, signed entry and webhook security from the current approved account's docs.

### 7.2 Build official signed entry path if using TheoremReach offerwall

Illustrative from official offerwall guide (placeholders, not a final account URL):

```text
https://theoremreach.com/respondent_entry/direct?
 api_key={YOUR_API_KEY}
 &user_id={OPAQUE_USER_ID}
 &transaction_id={UNIQUE_LAUNCH_ID}
 &currency_name_plural={CONFIGURED_NAME}
 &exchange_rate={ACCOUNT_APPROVED_RATE}
 &external_id={OPAQUE_USER_ID_OR_APPROVED_REFERENCE}
 &partner_id={APPROVED_PARTNER_ID}
 &hash={SERVER_COMPUTED_URLSAFE_B64_HMAC_SHA1}
```

- Docs describe `exchange_rate`, `currency_name_plural`, `transaction_id`, `external_id`, `partner_id` and `hash` as required for **offerwall** product. Other web routes may not need all of them. Never blend contradictory schemas.
- Compute the vendor signature over the **complete, pre-hash URL string exactly as assembled** per current dashboard guide. In PHP, a hypothetical helper using `hash_hmac('sha1', $urlBeforeHash, $secret, true)` and URL-safe base64 encoding matches the documented *algorithmic shape*, but verify actual parameter ordering and base64 padding rules from official dashboard test vectors before production.
- Ensure `https://theoremreach.com/` host allowlist and HTML-escape/URL-encode output, never leave user-controlled redirect injection.
- Include `debug=true` only in isolated approved tests; never deploy it in production entry links.
- If official JS SDK path is selected: initialize with public app API key and opaque `userId`; use official `showRewardCenter()` button when initialized. Browser `onReward(...)` callback is **informational**, never an accounting source.
- If direct iframe: ensure required popup/sandbox permissions for the provider's login flow, or use supported new-tab alternative. No mutation or aggregation of provider's supplied survey content.

### 7.3 Server reward callback verification and processing

- Configure publisher **server-to-server reward callback** according to chosen product's **dashboard-tested hash specification**. TheoremReach offerwall docs show example callback fields `transaction_id`, `currency`, `reward`, `partner_id`, `hash`, and an optional `debug=true`; this does **not** define a universal validation algorithm for all product variants. **Agent must confirm exact wire format and hash construction in the current publisher dashboard.**
- Validate signed/authenticated incoming reward, unique transaction, approved app/user mapping, event status, positive amount, correct currency and environment. `debug=true` must never generate real cash or available-balance entries.
- If callback is authenticated and account-approved, record event and translate provider reward units to our platform's financial units using **approved exchange rate**, never binary floating-point guesses.
- Note provider's published documentation says integer reward units can be rounded in the user's favor. If a 33.33% publisher revenue split is desired, configuration must be consistent with the approved exchange rate **before user-facing survey starts**, with no double-discount or underpayment.
- Source-of-truth for wallet credit is signed server reward event and reconciled approved revenue, not redirect `success`, JS `onReward`, iframe close or `surveys_available` response.
- Reconcile cancellations/fraud through **TheoremReach Event Webhook or official exports/API**. Their published FAQ explicitly says reversal events are **not sent through the standard Reward Callback**. A robust implementation must implement/plan the correct separate mechanism and block early cashout of unreconciled funds.
- Handle banned user and quota/full / no-inventory statuses without granting wallet money. Remember a disqualification may yield **partial reward only if the provider actually confirms an eligible partial payout**.
- Secure provider API requests; official Publisher Management API supports HMAC signing and rate limits—respect current limits, cache availability results where appropriate and never build requests with user-supplied IP headers unless validated.

### 7.4 TheoremReach go-live checks directly from published publisher FAQ

Check and record:

- Working website, registered users and active check-box consent to privacy terms during sign-up.
- Privacy Policy + Terms of Service + user contact information published and linked.
- Signup abuse protection/CAPTCHA or other required security measures.
- Functional in-app reward currency and callback processing, **with account-approved redemption model**.
- Provider account go-live request submitted and provider approval received; lack of approval is a blocker, not “code incomplete.”
- Written permission to coexist with Wannads and TapResearch on the same application, if required by current contract.
- Separate Event Webhook/reversal export reconciliation and hold policy.
- Accurate real vs test reporting; no debug data cashed out.

### 7.5 TheoremReach examples

**TR-01:** QA user clicks Take Surveys → an approved signed entry link with opaque user ID is opened. Closing returns to site with **$0 earned**.

**TR-02:** Mock callback with `debug=true` and correct signature → `SANDBOX` audit event but **no production ledger credit**.

**TR-03:** Production-shaped test fixture with valid vendor-specific hash and credit ID → exactly one test ledger record; duplicates do not credit twice.

**TR-04:** Invalid hash, wrong placement, unrecognized user, wrong `transaction_id` → no wallet update.

**TR-05:** User disqualified with no approved partial reward → zero; qualified partial reward with valid callback → only verified amount, with status and hold.

**TR-06:** Reversal arrives through separate verified Event Webhook/export → one compensating ledger reversal and admin flag. If no reversal feed available, hold/approval gate remains BLOCKED.

**TR-07:** TheoremReach simultaneous-provider permission absent → launch blocked, Wannads and TapResearch remain unaffected. Do not disguise this as technical error.

---

## 8. Cross-provider revenue mathematics, ledger and withdrawal policy

### 8.1 Never conflate three monetary fields

1. **Publisher payout:** amount network pays/owes website owner for eligible events; may later be reversed/settled.
2. **Provider reward units:** provider-defined points/virtual currency delivered via callback, often determined by dashboard `exchange_rate`; may not be equal to USD.
3. **User's internal eligible cash/credit:** platform ledger allocation, ONLY when provider account explicitly permits this redemption model.

The original business model mentions user share of **33.33% of eligible revenue**. Exact definition of eligible *gross vs net* publisher payout, provider exchange rate and hold period must be documented as approved configuration. Do not assume that `reward=100` means `$1.00` or that `payout=0.75` is already user's share. If the provider sends points, store points, currency, conversion rate and its effective date separately. Use integer minor units or high-precision decimal types; no JS float for dollars.

**Illustrative finance example (not a live configured rate):** publisher receives eligible verified `$3.00`, user share is 33.33% ≈ `$1.00`, owner remainder ≈ `$2.00`, after proper rounding. With payout `$0.03`, one-third ≈ `$0.01`. If payout is too small to allocate a cent per event, use a deterministic deferred fractional accrual or approved rounding policy; never silently create/delete funds. Account for holds, reversals and fees. An explicit provider exchange rate may already incorporate the publisher/user split; **do not apply it twice**.

### 8.2 Transaction lifecycle

```text
provider callback received
  -> cryptographically / contractually verified
  -> raw event inserted (durable inbox; unique provider/event key)
  -> normalized and reconciled to known provider user/account
  -> financial policy + environment + cashout permission checked
  -> record PENDING / HELD (or TEST_ONLY)
  -> settlement/hold/fraud checks
  -> AVAILABLE eligible balance
  -> withdrawal reserved & reviewed
  -> actual payout reference recorded by admin

late invalidation/rejection
  -> reversal event linked to original
  -> balance liability adjusted once
  -> if already withdrawn, record admin recovery case, NOT fabricated negative payout
```

**Concurrency safety:** Use DB unique indexes, transactions and row locking on affected wallet/ledger or reserve rows, following installed Laravel version documentation. For exact-once semantics with asynchronous queues, inbox insert must be idempotent and durable before queue dispatch; queue retry must not produce duplicate credit. Reconciliation jobs must also be idempotent.

**Approval gate:** In an account that authorizes **only non-cash virtual rewards**, never represent points as PayPal/Cash App withdrawable cash. If consent/policy/approval missing, show approved non-cash experience only if contract allows and client explicitly accepts; otherwise disable provider launch. Do not implement a workaround via cash-equivalent gift cards or hidden off-platform transfers.

### 8.3 Edge cases across all four providers

- `wannads:txn-123` and `tapresearch:txn-123` are **different** events.
- `wannads:txn-123:credited` duplicate x5 = one credit.
- `wannads:txn-123:rejected` after credit = one reversal.
- TheoremReach reward callback ≠ reversal webhook; reconcile separately.
- Browser close or “offer complete” UI ≠ evidence of reward.
- AdSense impressions/clicks ≠ user cash payout.
- Test callbacks/test mode ≠ real earnings.
- Currency mismatch (USD vs provider points) = quarantined until correct mapping.
- Unknown signature/key, account, user or placement = reject/quarantine, zero wallet change.
- Suspended users, privacy-consent revocation, provider ban, region restriction = prohibit new launches and determine payout treatment by verified legal/account policy.
- Provider dispute/reversal after user withdrew = admin exception and audit; do not auto-deduct another provider's settled earnings without explicit policy and correctness.

## 9. Routes, validation, browser security and observability

**Routes below are proposals only; reuse actual existing routes and conventions** after audit; never add conflicting routes just because these names are printed here.

```text
Authenticated USER actions:
GET  /earn                             -> Reward providers page
GET  /earn/wannads/launch              -> Server-created approved access path
GET  /earn/theoremreach/launch         -> Server-created approved signed entry
GET  /wallet                           -> Existing wallet, source filter
GET  /earnings                         -> Existing earnings, source filter

Server-to-server postbacks (no user session requirement):
GET  /webhooks/rewards/wannads          -> Exact documented app postback GET
GET|POST /webhooks/rewards/theoremreach -> Exact dashboard-documented method
POST /webhooks/rewards/theoremreach/events -> If separately documented for reversals/bans

Admin, server-authorized actions:
GET   /admin/integrations/providers
PATCH /admin/integrations/providers/{provider}
POST  /admin/integrations/providers/{provider}/test
POST  /admin/integrations/providers/{provider}/enable
GET   /admin/integrations/providers/{provider}/transactions
```

- Route method for TheoremReach callbacks must follow the selected product's actual current dashboard specification. Do not implement both GET/POST indiscriminately for convenience.
- CSRF: in installed Laravel 13, use current documented middleware exclusion for **only official postback paths** that do not carry CSRF tokens, not a blanket `/api/*` bypass. Every such route requires its own provider verification. Rate-limit without blocking legitimate vendor retries.
- Ensure reverse proxies and CDN maintain original query string (signature-sensitive) and provide trusted client IP configuration. Reject open redirects and SSRF-friendly admin URLs.
- Verify appropriate session/auth and role middleware on admin and user routes; callback routes do **not** accept arbitrary user IDs from user sessions as proof.
- CSP/frame/iframe policies must be tested for official iframe integrations while preserving existing AdSense and TapResearch scripts; never weaken all `frame-src`, `script-src` or `connect-src` to `*` just to make an iframe work.
- Production server logs: log `provider`, `correlation_id`, `transaction_hash/masked_id`, validation state, non-sensitive amount units, latency, event type and result. **Never log entire signed URLs, API keys, app secrets, personal identifiers, or respondent demographic data.**
- Alerts: sustained invalid callback signatures, duplicate burst, unusual payout amount, missing reversals/reconciliation, provider outages, payout reserve mismatch, go-live gate override attempts.
- Test webhook rate/timeout/retry with local fixtures; do not stress vendor production systems.

## 10. Mandatory full regression of previously implemented AdSense and TapResearch

**This is not optional and must be executed BOTH before and after implementing Wannads/TheoremReach.** If old provider code is broken at baseline, log defect + fix as appropriately scoped before claiming complete success. Do not “fix” old providers by disabling them or deleting UI.

### 10.1 Google AdSense regression contract

**Code + admin checks:**
1. Find current AdSense configuration, publisher ID handling, script/slot components, route gating and existing approved placements.
2. Verify normal ads remain classified `standard_ads` and **do not create any cash-reward ledger event**.
3. Verify loading Wannads/TheoremReach scripts or adjusting Content Security Policy has not blocked AdSense domains, broken component lifecycle or duplicated ads/script injection.
4. Existing Quote Feed/Ad placements load without white screen when AdSense is disabled, approved, pending approval or blocked by ad blocker; do not force user to click ads or offer cash incentives next to them.
5. Ensure no “Earn from watching Google ads” copy; never have a callback route that credits from Google ad click/display events.
6. Verify Admin AdSense settings were not deleted/renamed and remain properly permission-guarded and secret-safe.
7. **Do not perform fake/live ad clicks, auto-refresh impressions, browser automation against real AdSense inventory or accidental traffic inflation.** Use non-live test slot/unit and DOM/config checks where suitable.

### 10.2 TapResearch regression contract

1. Identify and document actual web integration path (hosted link vs SDK/API), approved account settings and callback version.
2. Verify a logged-in user can still launch TapResearch exactly as before; new provider UI may add a card, but must not alter its opaque user ID mapping.
3. Test TapResearch's **own** provider signature/verifier, postback payload mapping and status semantics, not Wannads or TheoremReach verifier.
4. Test single valid SANDBOX postback → single pending/test ledger entry; repeated identical event → no double credit; rejected/reversed event → compensating outcome according to current official TapResearch version and approved rules.
5. Test no-surveys and region-ineligible states; quote feed still works.
6. Verify new provider migration cannot change TapResearch transaction uniqueness, money units, wallet allocation, historical rows or withdrawal reserves.
7. Ensure existing admin config and logs remain accessible, with legacy TapResearch data preserved.
8. Verify no uncontrolled `provider=...` parameter can impersonate TapResearch when hitting a new webhook endpoint.

### 10.3 Shared feature regression

- Guest login-first flow, registration, password reset UI (SMTP might remain externally blocked), logged-in quote feed, OpenAI quote generation, save/copy/share and pagination.
- Admin user roles and permission boundaries, Finance dashboard, wallet balance math, minimum withdrawal, history and manual review/approval.
- Existing dashboards, graphs and timestamps; responsive navigation and accessibility.
- Provider outage must not stall quote generation; queue and webhook exception handling must not block all site traffic.

## 11. Test design and automated acceptance suite

Use installed Pest/PHPUnit and existing React test runner. Don't upgrade Laravel or test frameworks just for this task. Add clear fixture builders with signable provider mock requests. Tests must not use real third-party callbacks/ads except provider-sanctioned sandbox verification in isolated environment.

### 11.1 Wannads feature tests

- `W-001`: user launch creates correct approved iframe/new-tab URL, opaque mapped ID; host validated.
- `W-002`: disabled/pending/blocked provider cannot launch; user sees correct status.
- `W-003`: valid authenticated app-postback credit inserts one event/ledger row; response matches provider ack.
- `W-004`: wrong signature / altered amount / secret mismatch rejects, zero credit.
- `W-005`: unknown user ID, provider account mismatch, missing tx ID, invalid reward rejects safely.
- `W-006`: duplicate callback after retry and concurrent identical posts produce one ledger entry.
- `W-007`: `rejected` with same tx creates exactly one reversal; repeated rejection no-op.
- `W-008`: rejection before credit prevents out-of-order invalid credit.
- `W-009`: legacy numeric statuses supported ONLY when documented/configured; unknown status quarantined.
- `W-010`: source IP filtering/proxy handling correct when official provider allowlist configured.
- `W-011`: public app key may appear in allowed URL; private `SECRET` cannot appear in browser payload/log.
- `W-012`: production test fixture cannot become real withdrawable reward.

### 11.2 TheoremReach feature tests

- `T-001`: pending coexistence authorization prevents launch, despite technically configured key.
- `T-002`: approved account/QA mode builds correctly signed entry; reference hash vector matches dashboard.
- `T-003`: generated link and callback reference identify correct opaque user, one provider session.
- `T-004`: malformed/reordered/tampered signed URL fails vendor-safe validation or test-vector comparison; never use generated unverified URL for production.
- `T-005`: correct verified sandbox reward event creates only test/held entry; duplicates no extra credits.
- `T-006`: `debug=true` never appears as real available wallet funds or admin revenue.
- `T-007`: invalid callback hash, wrong placement, unknown user or invalid amount rejects.
- `T-008`: `onReward`, redirect success and iframe close cannot credit wallet.
- `T-009`: provider reversal via verified separate channel causes one compensating adjustment.
- `T-010`: missing reversal webhook/reconciliation keeps early cashout disabled.
- `T-011`: no survey/ineligible/banned user states displayed correctly.
- `T-012`: approved consent/Privacy/Terms and CAPTCHA preflight shows nonbypassable go-live gate.

### 11.3 Existing provider and financial regression tests

- `G-001`: AdSense displays/loads normal ad component only where intended, with no user-wallet credit.
- `G-002`: no AdSense click/view trigger or misleading incentivized CTA.
- `G-003`: AdSense config/slot preserved after changes; graceful block/no-fill behavior.
- `P-001`: existing TapResearch user launch and opaque ID unchanged.
- `P-002`: valid signed TapResearch sandbox postback processed once; duplicate idempotent.
- `P-003`: TapResearch invalid/reversed/region/no offers states correct.
- `P-004`: legacy TapResearch ledger totals preserved after migration.
- `X-001`: same external tx ID from Wannads and TapResearch coexist as distinct events.
- `X-002`: adding/disabling a provider does not delete past transactions.
- `X-003`: all provider earnings filtered correctly; source label accurate.
- `X-004`: decimals/units and 33.33% split never double-applied; result deterministic.
- `X-005`: concurrent callback + withdrawal cannot overdraw available wallet.
- `X-006`: per-provider hold/reversal only affects correct user/provider liabilities.
- `X-007`: guest/regular user cannot configure providers, view secrets, read others' earnings.
- `X-008`: provider callback receives no session and still authenticates cryptographically; CSRF exception is narrow.
- `X-009`: full original quote generation/AI, save/copy/share, auth, mobile and admin regression pass.
- `X-010`: production env has no fake callbacks, debug flags, test wallet credits or exposed secrets.

Tests for real provider onboarding with missing credentials are `BLOCKED-EXTERNAL`, NOT `PASS`. Code-level adapter tests may still pass with documented fixtures.

## 12. Implementation phases and proof required after each phase

### PHASE A — Audit + Context7 official-doc confirmation

- No feature changes until baseline tests run and current code mapped.
- Identify actual old AdSense/TapResearch implementation; record real code paths and UI screenshots.
- Context7 lookups + official publisher docs; create signed document map, status matrix and compliance checklist.
- **Acceptance:** evidence files created and existing provider baseline result table populated.

### PHASE B — Provider registry/finance foundation (minimal additive)

- Extend current registry/capabilities and encrypted config as needed; add schema only if required, migrate safely.
- Create normalized event, callback inbox, status/approval gates without touching existing historical balances.
- **Acceptance:** existing two providers pass baseline; new flags disabled by default; migrations rollback-safe.

### PHASE C — Wannads integration

- Implement documented web offerwall launch, signed app postback validation, credit/rejection/retry behavior, settings and user card.
- **Acceptance:** W-001..W-012 + human WAN cases pass in QA; no real money without approved account.

### PHASE D — TheoremReach integration

- Determine approved web product; implement signed entry/official JS path and verified S2S callback; add separate reversal reconciliation and compliance gate.
- **Acceptance:** T-001..T-012 + human THEO cases pass in QA; TheoremReach remains blocked in live if concurrency/cash approval absent.

### PHASE E — Unified user/admin presentation and ledger reconciliation

- Four-provider admin dashboard; three reward cards only when permitted; AdSense normal ads separated; earnings source filters; consistent status UX.
- **Acceptance:** X-001..X-008 and zero duplicate credits or permission leaks.

### PHASE F — MANDATORY full regression of Google AdSense + TapResearch

- Run G-001..G-003 and P-001..P-004 again after full code integration. Compare before/after screenshots, callbacks, balances, migration counts and existing features.
- **Acceptance:** no new P0/P1 regression, prior balances unchanged, both older integrations usable. If a pre-existing defect persists, disclose it and separate scope; do not falsely mark passed.

### PHASE G — User acceptance, approvals and production readiness

- Complete companion human QA. Add provider onboarding guide with exact client-required credentials/docs, compliant CTA explanations, deployment/rollback notes.
- Maintain production-disabled flags until approval; test backups/monitoring. Release incrementally; recheck after deploy without creating live ad clicks or fake callbacks.
- **Acceptance:** signed-off go/no-go matrix, staging links/screenshots, provider status proof, support handoff, clean Git branch.

### Deployment outcome classifications

Use one label **per provider**:

1. `INTEGRATION_CODE_READY` — adapter and UI exist; tests pass.
2. `SANDBOX_VERIFIED` — official/test fixtures and QA human flow pass.
3. `PENDING_PROVIDER_APPROVAL` — waiting for account review, cash rewards/coexistence permission, or credentials.
4. `PRODUCTION_CONFIGURED` — approved real keys/URLs/gates configured.
5. `LIVE_VERIFIED` — real authorized callback/reconciliation observed safely and owner account is approved; user payout permitted if allowed.

Do not collapse these five steps into a single “Completed” checkbox.

## 13. Mandatory documentation and final answer from the coding agent

Create/update in repository:

```text
docs/provider-expansion/
  REPOSITORY_AUDIT.md
  BASELINE_4_PROVIDER_MATRIX.md
  OFFICIAL_DOCS_VERIFICATION.md
  PROVIDER_COMPLIANCE_GATES.md
  IMPLEMENTATION_LOG.md
  API_AND_CALLBACK_CONTRACTS.md
  FINANCIAL_ALLOCATION_RULES.md
  CREDENTIALS_AND_APPROVAL_HANDOFF.md
  TEST_FIXTURE_GUIDE.md
  AUTOMATED_TEST_RESULTS.md
  MANUAL_QA_EVIDENCE.md
  RELEASE_AND_ROLLBACK.md
  FINAL_4_PROVIDER_STATUS.md
```

The final status must clearly identify actual commit SHA, migrations, touched source paths, tests run/results, before-vs-after old provider regression, Wannads functionality, TheoremReach functionality, approval/cash-reward/coexistence blockers, missing credentials, staging test URL and rollback procedure. Never include live credentials. Provide client-friendly explanation: “Four integrations can be prepared, but individual provider approval and permitted reward models govern activation.”

### Example progress-report row (fill from real testing)

```text
Provider: TheoremReach
Code: IMPLEMENTED / UNVERIFIED / NOT STARTED
UI: VERIFIED / BLOCKED
Server callback verification: VERIFIED / BLOCKED
Reversal path: VERIFIED / BLOCKED
Cash-redeemable reward contract: WRITTEN APPROVAL / NOT YET VERIFIED
Multi-network coexistence permission: WRITTEN APPROVAL / NOT YET VERIFIED
Sandbox evidence: [internal report path]
Production enable flag: OFF unless all required gates pass
Previous providers regression: AdSense PASS/FAIL/BLOCKED; TapResearch PASS/FAIL/BLOCKED
```

## 14. Rollback and release safety

1. Make a release tag/known-good Git SHA before deploying. Back up DB according to existing operations procedures; ensure reversible migration plan without data loss.
2. Default new providers to `OFF`. Deploy additive code first, run smoke tests, then enable sandbox staff-only provider paths.
3. If TheoremReach/Wannads integration fails, flip provider kill switch; **do not** disable Google AdSense, TapResearch, AI quote feed or existing wallet merely to hide new bug. Verify rollback flags are effective immediately.
4. Don't remove/overwrite real earned transaction history. If rolling back schema, preserve newly written callback/ledger data and restore forward-compatible code if necessary; prefer feature flags rather than destructive database downgrade.
5. Verify post-deploy: user login; create quote; earn page; existing TapResearch launch; existing AdSense normal slot; Wannads/TheoremReach pending/active status as approved; admin provider settings; callback endpoint negative tests in QA, not production.
6. Distinguish code bug from missing external approval; escalate policy/contract blockers with exact provider documentation. Production provider settings change requires superadmin and audit log.
7. Do not modify DNS, production server env, domain settings, live payment accounts or publisher dashboard permissions without explicit authorized operator approval.

## 15. Acceptance / definition of done

All checks required for **code-level completion**:

- [ ] Local checkout audited; real paths and baseline test results documented.
- [ ] **Context7 actually consulted**, valid Laravel/Inertia versions researched; provider official documentation and account variants verified; no fake Context7 Wannads source.
- [ ] Wannads web UI entry is functional and server verified postbacks are idempotent and reversible.
- [ ] TheoremReach selected official publisher web path is functional, authenticated callback is verified and reversal treatment exists or is explicitly BLOCKED with payouts disabled.
- [ ] TheoremReach **coexistence restrictions** evaluated and enforced by disabled-by-default feature gate pending written approval.
- [ ] Reward cashout policy verified per provider or cash payouts disabled for that provider.
- [ ] No test/unauthenticated/browser-only events can create real withdrawable earnings.
- [ ] Existing AdSense and TapResearch working before/after in regression evidence; no wallet pay from AdSense views.
- [ ] Shared wallet exact arithmetic, transaction uniqueness, audit and withdrawal rules hold under retries and concurrency.
- [ ] Admin can configure and see truthful status for all four without revealing private credentials.
- [ ] User sees appropriate approved provider choices and honest inactive/empty states on desktop and mobile.
- [ ] Automated targeted + existing relevant suite pass; manual companion QA executed; existing quote/auth/shared features unaffected.
- [ ] Deploy/rollback and client approval dependency report completed.

**Critical release decision:** If code passes but TheoremReach does not yet authorize offering competing incentivized networks, the correct result is **TheoremReach built and test-verified, production activation BLOCKED**. Do not violate provider terms just to claim four live networks. Likewise, real cash withdrawals must remain disabled for any provider without an approved cash-redemption program.

---

## 16. Exact startup prompt for the receiving AI coding agent

```text
You are working on the existing Proceeds With Positivity repository.

READ COMPLETELY:
- Existing 2026-11-19 AI Agent Master Implementation Context
- Existing 2026-11-19 Human Manual QA Playbook
- 01_AI_AGENT_WANNADS_THEOREMREACH_IMPLEMENTATION_CONTEXT.md
- 02_HUMAN_QA_WANNADS_THEOREMREACH_AND_REGRESSION_PLAYBOOK.md

Client has approved adding Wannads and TheoremReach alongside the already
reported Google AdSense and TapResearch integrations.

MANDATORY: Before editing code, audit the checked-out repository and run
baseline tests. Use Context7 to look up current Laravel/Inertia docs and
resolve both vendor sources. Read current OFFICIAL Wannads and TheoremReach
PUBLISHER integration, security, callback and terms documentation directly.
TheoremReach DIY Researcher API is NOT the publisher reward integration.

Implement incrementally, preserve all existing functionality, and always
verify BOTH original providers again after new implementation.

Do not claim production approval, provider compatibility, genuine revenue,
or withdrawable user money unless verified. TheoremReach's currently
published terms restrict competing incentivized offers absent specific
written permission, so keep a non-bypassable compliance gate.

Start with PHASE A. Create documented repo audit, provider contracts,
baseline regression results and phase plan. Then implement phases B–G.
After each phase run automated tests, update evidence and give a concise
factual progress report including blocked external dependencies.
```

**End of AI-agent context.**
