# PROCEEDS WITH POSITIVITY
# MASTER IMPLEMENTATION CONTEXT — FOR AN AI CODING AGENT

**Version:** 1.0 — 2026-11-19  
**Repository provided by project owner:** https://github.com/mak-laravel-git/tay_lovett-proceeds-with-positivity  
**Primary purpose:** Make the existing, partially implemented web application independently testable, robust and ready for third-party provider onboarding. Implement confirmed baseline requirements while isolating unapproved monetization models and external dependencies.  
**Companion file:** `02_HUMAN_MANUAL_QA_TEST_PLAYBOOK.md`  
**IMPORTANT ACCESS LIMITATION:** The author of this document attempted to open the GitHub repository and query its remote references on 2026-11-19, but could not retrieve source files. GitHub access was unavailable from this environment. Consequently, **no statement in this file constitutes a verified claim about what the live repository currently implements**. Existing work, framework versions and installed packages must be validated against the actual local checkout. This document is a product/engineering handoff and rigorous *repo-first* implementation specification, not an as-built code audit. The code-editor AI has the checkout and must perform that audit itself.

---

## 0. Required operating protocol: read first, do not skip

You are the AI coding agent operating on the actual repository. Your first responsibility is to **preserve completed work**, establish an evidence-based baseline, and implement safely. The repo has only about two commits because it was moved from an older Git repository; **two commits do not mean that only two units of development have occurred.** Treat HEAD as containing all accumulated work, not as a scaffold to overwrite.

**Non-negotiable rules:**

1. Work from the repository supplied above / the developer's actual checked-out working tree. Do not create a fresh Laravel starter or rebuild the project until a deliberate gap analysis proves that necessary; the default is incremental implementation.
2. Never infer what is complete from the tiny Git history. Inspect the **working tree, migrations, controllers, services, components, tests and runtime**.
3. Before any code change: record Git branch, HEAD SHA, working tree status, `composer.json`, `composer.lock`, `package.json`, lockfiles, PHP/Node versions and database driver. Do **not** commit environment secrets.
4. First produce `docs/implementation/AUDIT_BASELINE.md`, `FEATURE_MATRIX.md`, `ARCHITECTURE_MAP.md`, `DEPENDENCY_REGISTER.md`, and `IMPLEMENTATION_PROGRESS.md` **inside the repository**. These are living documents; actual file paths and statuses must be derived from inspected code.
5. No destructive resets, mass deletes, `migrate:fresh` on real data, unreviewed data migrations, key rotation, or global dependency upgrades. Never overwrite a populated production/staging DB to make tests pass.
6. Existing feature behavior has priority over prototype assumptions where that behavior is demonstrably intentional, secure and client-approved. When a mismatch is material, document it before modifying.
7. Every change must include targeted automated tests, browser/manual test instructions, acceptance evidence, rollback note and a Git commit on a feature branch when appropriate.
8. **No real monetary earnings from demo ads, frontend click events, guessed ad impressions or unverified quote views.** All synthetic scenarios are identified `DEMO`/`SANDBOX` and cannot be cashed out.
9. Implement provider-neutral monetization infrastructure, not fake integrations. Real provider credentials/permissions and supported web products are external prerequisites.
10. A new *Creator Revenue Sharing* model, *self-service advertiser campaign marketplace*, likes/comments/followers, or public quote pages have **not been finally approved by the client**. Treat as optional proposals; do not silently add them to the committed scope.
11. If a prior project-specific `AGENTS.md` or Laravel Boost rules exist, read and comply. This document extends rather than replaces compatible repository conventions.
12. The development goal is a working demonstration and clearly tracked blockers. Do not defer all development to advertising research or wait indefinitely for external approvals.

**Required evidence vocabulary:**

- **SOURCE-CONFIRMED:** Seen in a client message, meeting transcript, or later direct confirmation.
- **OWNER-DESIGN:** The developer/project owner's chosen UI or technical direction.
- **REPORTED-EXISTING:** Claimed to have been built in earlier updates, but must be inspected and tested.
- **PROPOSED:** Detailed default implementation behavior supplied by this handoff, not a new client acceptance.
- **BLOCKED-EXTERNAL:** Requires provider approval, approved credentials, domain/SMTP or written business rules.
- **VERIFIED-IMPLEMENTED:** Only after the coding agent inspects real source AND passes tests against the checked-out code.

Use these tags in the feature matrix. **Never convert PROPOSED into SOURCE-CONFIRMED or VERIFIED-IMPLEMENTED without evidence.**

## 1. Product identity and business context

**Name:** Proceeds With Positivity.  
**Audience:** primarily USA-based registered web users.  
**Core motivation:** an inspirational AI-quote web platform with advertising-driven monetization.  
**Brand direction:** black / dark navy, light blue and red; existing V14 design reference shows approximate `#0A1018`, `#76D8F8`, `#ED2948`; inspect application CSS/design tokens before changing.  
**Public site concept:** login-first, not a marketing/landing page. Guests can reach Login, Register, Privacy Policy and Terms of Service.  
**Navigation:** Facebook-*inspired* scrolling quote browsing, not a Facebook social network. Desktop user left sidebar and mobile bottom navigation. Admin is a separate scalable, full-width workspace with expandable left navigation.

**Original client requirement:** User generates or reads AI motivational quotes, sees a `Generate Ad` action, accesses a real advertisement, and earns an agreed share of eligible ad revenue; earnings appear in a wallet, with withdrawal requests and admin-managed payouts. Client stated **one-third (approximately 33.33%)**, US users, Cash App and PayPal preferred, manual and automated payout requested, and mobile-friendly website. They selected OpenAI for initial quote generation and requested categories, unlimited user-facing quote generation, save/copy, and social sharing.

**Later decisions, in chronological order:**

- On 2026-11-08 meeting, user was presented Creator vs Viewer earning models; client explicitly chose **the second / Viewer Reward** at that time. Google AdSense was suggested by client but no compatible reward approval established.
- Research later covered Playwire, Google AdSense, Brave Ads, Adscend/PixelPointTV, ayeT-Studios and offerwall/survey networks. Provider suggestions and research reports are **not signed contracts or live publisher approvals**.
- Around 2026-11-12, client said an **owner-only normal advertising network** could also be considered, especially if it pays more/frequently. This was openness to an option, **not definitive replacement of the original one-third viewer revenue-share**.
- Client expressed interest in Wannads, TheoremReach, CPX Research and TapResearch in screenshots/messages, and asked specifically about **Google AdSense + TapResearch**, and then about **Wannads + TheoremReach + TapResearch**. The four-network list was a proposed shortlist, not unambiguous proof all four are funded, contracted and in fixed-price scope.
- On 2026-11-15 the client replied **“B”** to the three-option clarification document where **Option B = User Revenue Sharing**, not the alternate older A/B/C labels. Preserve this exact decision. The client also clarified: **“I mean able to be shared on all social media platforms as well as through emails and texts.”** This concerns **website sharing**; do not invent public quote pages from it.
- On 2026-11-15/16 client asked: **“What happened to the model where the user writes quotes and get paid from how many people view them, as far as Adsense?”** This **reopens a Creator Revenue Sharing question** but does **not** explicitly replace the previously confirmed Viewer Reward flow. This distinction is project-critical. Quote views alone are not ad network revenue; an ad-funded creator program requires attributable eligible revenue and provider/legal permission.
- Latest developer instruction: stop letting research block delivery; build/test existing core while ad provider activation remains external.

### Explicit decision register (must remain visible to agent)

| ID | Statement | State as of 2026-11-19 | Engineering consequence |
|---|---|---|---|
| D-01 | Login-first web app; separate user/admin areas | OWNER-DESIGN | Do not add default landing page |
| D-02 | Quotes via OpenAI, categories, copy/save, feed | SOURCE-CONFIRMED | Finish and test now |
| D-03 | Quote → Generate Ad → fullscreen ad → next quote | SOURCE-CONFIRMED original UX | Preserve demo path; real provider formats may vary |
| D-04 | One-third user earnings from eligible advertising | SOURCE-CONFIRMED original rule | Ledger and earnings logic; no unverified credit |
| D-05 | User wallet & withdrawals; manual/scheduled requested | SOURCE-CONFIRMED goal | Manual request/review now; automated transfer gate |
| D-06 | Monetization Option B (User Revenue Sharing) | SOURCE-CONFIRMED 2026-11-15 | Don't delete wallet/rewards based on owner-only *alternative* |
| D-07 | Website sharing on social media, email, texts | SOURCE-CONFIRMED latest | Implement site URL sharing; no assumed public quote URLs |
| D-08 | Google AdSense interest | CLIENT-PREFERENCE | Normal ad adapter; never pay cash for its views |
| D-09 | TapResearch, Wannads, TheoremReach interest | CLIENT-PREFERENCE | Integrations evaluated individually; first proof TapResearch |
| D-10 | Quote creators paid based on others viewing quotes | CLIENT QUESTION / UNCONFIRMED CHANGE | Isolate behind pending decision; build no creator payout by default |
| D-11 | User-funded self-service ad campaigns | RESEARCH PROPOSAL ONLY | Exclude from current build unless separately approved |
| D-12 | Domain/VPS/SMTP/publisher accounts | BLOCKED-EXTERNAL | Build locally/staging; display dependency checklist |

**Source references available with this conversation, NOT necessarily inside the Git repository:**

- `PWP_Project_Context_V14.md` / `PROCEEDS_WITH_POSITIVITY_PROJECT_CONTEXT.md`: Nov 1–2 evidence, long Zoom transcript and UI decision log.
- `Proceeds_With_Positivity_Login_First_V14.html`: standalone UI prototype, historical—not current application code.
- `Proceeds_With_Positivity_Proposed_Development_Scope_Confirmation_2026-11-15.docx`: proposed modules / three monetization models (client later replied B).
- `Proceeds_With_Positivity_Client_Monetization_Clarifications_2026-11-12.docx`: earlier alternatives; earlier option labels do not supersede Nov 15 response.
- Uploaded November 15/16 Fiverr screenshots and meeting excerpts in the project conversation.

If these artifacts are absent from the local checkout, use this file's requirements to continue, and log unavailable reference assets; do not hallucinate actual repository file paths.

## 2. Stack discovery and respect for existing architecture

Earlier implementation conversations **report** Laravel 13, Inertia with React 19, TypeScript, Tailwind 4, shadcn/ui, Laravel AI SDK, multiple AI provider configuration and Redis queues. **This is not verified against the new Git repository.** Check package manifests before proceeding. Reuse installed packages; avoid replacing Inertia with a separate SPA or adding a new back-end.

**Repository audit commands (run in the actual local project; do not expose secrets in logs):**

```bash
pwd
git status --short --branch
git rev-parse HEAD
git branch -a
git log -5 --oneline
ls -la
cat composer.json
cat package.json
find routes app resources/js database/migrations tests -maxdepth 3 -type f 2>/dev/null | sort
php -v
node -v
php artisan --version
php artisan route:list
php artisan test
npm run  # list available package scripts
```

**Do not:** dump `.env`, `APP_KEY`, provider secrets, auth tokens, service-account credentials or real DB connection strings to the answer or audit logs. Record only whether variables exist and whether services can be reached. Avoid printing customer email addresses or payout details.

**Report exact findings in `ARCHITECTURE_MAP.md`:** real bootstrapping, PHP framework version, Inertia adapter, frontend components/pages, route method/URI/name/permission, authorization mechanism, model relationships, migration inventory, seeders, mail provider, AI provider drivers, queue driver, cache/Redis, storage permissions, test coverage, deployment setup and features currently implemented. For each app capability record `Existing files`, `Endpoint`, `UI entry point`, `Data source`, `Auth policy`, `Test`, `Gap`.

**Development environment gate:** Find the project's existing documented install/bootstrap workflow and use that. Suggested fallback only *if compatible with manifests*:

```bash
composer install
npm ci
cp .env.example .env  # only in a new local environment, never overwrite a real .env
php artisan key:generate  # only new local app key, not on shared/prod environment
php artisan migrate --status
php artisan optimize:clear
npm run build
php artisan test
```

Use an isolated test database, separate local credentials, and safe test queue/mail backends. Do not assume a clean checkout has production DB contents.

## 3. Exact user roles, permissions and navigation

### 3.1 Roles

- **Guest:** can open login, registration, public Privacy Policy, Terms; cannot access any authenticated feed, wallet, admin, provider settings or revenue endpoints.
- **Regular User:** generate quotes, browse approved feed, save/copy, share website, view only own activities, eligible rewards, wallet and withdrawals. Cannot edit others' quotes or financial balances or access admin routes.
- **Admin:** manage users, quote moderation/settings, provider configurations, revenue reports, payout review, audit trail. May only do financial adjustments through auditable service methods, never direct unsafe balance edits.
- **Optional permissions:** if repository already defines granular admin roles, preserve and enforce them. Do not introduce a new role solely to simulate Creator/Viewer—one regular user can be creator of their own quote and viewer of another's if publishing is ultimately approved.

**Auth rules:** Server-side authorization on every endpoint. Valid email/password auth, hashed passwords, email verification flow (if enabled), throttled login/reset, CSRF, session rotation and safe logout. No demo role-switcher in production. Signed-in admin goes to Admin Overview; user goes to Inspiration Feed. Refresh must retain auth and route; guest deep link returns to login and can navigate back to intended allowed page.

### 3.2 User navigation (V14 design reference)

Desktop left sidebar, menu labels (adapt exactly to existing repository naming if already intentionally implemented):

`Inspiration Feed` | `Quote Studio` | `My Wallet` | `My Earnings` | `Saved Quotes` | `Withdrawals`.

Mobile bottom navigation in V14: `Feed`, `Create`, `Saved`, `Wallet`, `More` (More leads to earnings and withdrawals, not an inaccessible dead end). Top area contains brand/account/logout as needed; do not duplicate full desktop menu in header. Active state and keyboard/ARIA navigation must work.

### 3.3 Admin navigation (V14 owner-design)

Full-width Admin Overview with left sidebar; expandable groups:

- **People & Access:** User Management, Roles & Permissions (only implement granularity if consistent with existing package/requirements).
- **Content:** Quote Management.
- **Advertising & Finance:** Ad Management, Revenue & Transfers.
- **Operations:** Reports & History, Support & Activity (may be labelled placeholders if not in signed scope).
- **System:** Integrations, Application Settings.

On desktops: sidebar collapse expands content without overlay. On narrow screens: menu opens as a drawer with backdrop and closes via outside click, close control and Escape. Group collapse should preserve active route and honor `aria-expanded`. User cannot see Admin navigation.

## 4. Core UI: every major screen and interaction

These behaviors are the **target implementation specification**, not a claim that current source already does them. The agent must inspect existing pages and adjust minimally.

### 4.1 Guest — Login

**Initial state:** `GET /` or the existing guest root presents Login-first, not a landing page. Place Proceeds With Positivity branding and black/light-blue/red design. Display `Log in` and `Create account` actions, email and password input, submit, forgot password, and guest-accessible `Privacy Policy` and `Terms of Service` links. The V14 demo also includes `Preview Admin Dashboard`; remove/hide this on a real deployment.

**Click details:**

1. User enters email and password, clicks **Log in**.
2. Disable submit during request, prevent double-submit, show validation errors per field without revealing whether account exists unnecessarily.
3. On success: server establishes authenticated session; redirect User to Inspiration Feed, Admin to Admin Overview.
4. On invalid credentials: remain on login, display generic failure; do not reveal role/registration state.
5. `Create account` navigates to registration; Privacy and Terms show real public legal routes and Back to Login.
6. `Forgot password` sends a controlled reset/OTP email using existing mail infrastructure; expose errors safely, avoid account enumeration and request spam.

**Expected negative cases:** empty input, bad email, bad password, unverified email behavior consistent with existing app, throttling, expired session, back button after logout, CSRF mismatch, browser refresh.

### 4.2 Guest — Register / verification / password recovery

Register with minimally required fields (name if existing app requests it, email, password and confirm password). A newly registered user is never admin. Validate server-side uniqueness, format, secure password policy, and user-friendly errors. Ensure no duplicate accounts after repeated clicks. Verification email and reset email should be sent through queued transactional mail with retry/visibility. If SMTP not yet purchased, use a local test mailer and show **not production-ready** in dependency report; do not prevent all other features from working.

Reset link/OTP must expire, be single-purpose and not expose password reset tokens. Test verified vs unverified and role default. No provider API keys or payout identity in public response.

### 4.3 Inspiration Feed

**Opening the page:** After user login or click `Inspiration Feed` / mobile `Feed`, display a vertically scrolling list of motivational quote cards with category labels, clear quote text, save/copy/share actions and the original post-quote ad action when demo mode is enabled. V14 prototype's filter tabs were `All`, `Motivation`, `Kindness`, `Hope`; `Growth`, `Success`, `Happiness` appeared in Quote Studio category selection. Align categories with DB/backend, not hard-coded disconnected lists.

**Click behavior / data:**

- `All` shows all eligible feed items. Category tab filters without exposing unpublished/private quotes. Show skeleton while loading; empty state if no matching content.
- Next page / infinite scroll fetches paginated records; no duplicate cards, stable order and accessible load-more fallback. Do not fetch unlimited records into memory.
- Each card shows quote, category, creator/AI attribution **only as supported by actual publishing model**. No fabricated community metrics. If user quotes are private, feed uses platform/approved quotes until client approves creator publishing.
- `Save`: toggles saved state with immediate feedback, persists per user across refresh. `Saved` on own account only. Clicking again unsaves. No duplicate save rows.
- `Copy`: copies quote text only (attribution if specified by existing app), shows accessible success notice; failure fallback if clipboard blocked.
- `Share`: distinguish **client-confirmed WEBSITE sharing** from the older prototype's `Share Quote`. A website-sharing control opens native share sheet if available, or offers Copy Link / Facebook / WhatsApp / X / LinkedIn / Email / SMS where supported. Destination is the canonical website URL, not invented public quote URL. Do not require Instagram browser to support direct generic web share URLs—it often does not. Provide Copy Link fallback. Never leak logged-in routes, auth tokens or private quote IDs.
- `Generate a Quote` or composer button navigates to Quote Studio.
- `Generate Ad` demo button opens a clearly labelled **Demo Advertisement** full-screen dialog, only if demo mode is on. It **never** credits real earnings. There is always a controlled exit; `Continue to next quote` advances to next eligible quote without auto-triggering a second impression.
- An ad provider may have no fill, error, or geo restriction. Show `No advertisement available right now. Continue reading.` without blocking quotes.

**Quote view metric definition (PROPOSED):** record a lightweight *content view* only after content is actually displayed, with anti-duplication/cooldown, per-user and quote IDs. This is **analytics, not revenue**; do not promote to payable impression. Define recorded view at backend rather than trusting front-end increment events. Exact view-eligibility window is a configurable rule pending product sign-off.

### 4.4 Quote Studio — create AI Quote

**Default:** Title `AI Quote Studio`, inspiration category selector (prototype: Motivation, Hope, Kindness, Growth, Success, Happiness), clear `Generate Quote` button. When a quote is generated, show the full text in an accessible quote card, then actions `Save`, `Copy`, `Share` where applicable, and original `Generate Ad` demo action. Keep `Regenerate` behavior only if already in repository or clearly equivalent to Generate.

**Click `Generate Quote`:**

1. Ensure user authenticated, category valid and supported by backend.
2. Button goes to loading state, prevents duplicate submissions; progress indicator appears.
3. Laravel server invokes configured AI provider through approved server-side SDK/client. Never invoke OpenAI or other AI providers directly from browser using secret key.
4. Apply structured, bounded prompt: motivational text, non-harmful, suitable for general audiences, category-specific, moderate length; do not instruct AI to fabricate false authority/attributions.
5. Validate result, trim size, moderate unsafe content, persist generation with user id, category, prompt/template version, model/provider metadata, token-cost metadata if available, visibility `private` or configured platform state. Avoid storing secret system prompts in public fields.
6. UI shows result and success; keep response on refresh or accessible via quote history if existing design supports it.
7. If provider errors/timeout/rate-limit/budget-exhausted, show safe retry and preserve category/form; never show stack trace or API secret. Queue/timeout behavior must be consistent with current app architecture.

**Unlimited generation nuance:** Client requested unlimited user-facing quotes. Do **not** display an arbitrary small hard daily quota as a surprise, but do implement abuse protection, concurrency controls, service-level cost ceilings, per-minute rate limiting, alerting and admin kill switch. A hard cap that materially changes promised user experience must be approved. Cache deterministic safe results only where product behavior makes sense; don't return same quote due to mistaken caching.

**Click `Save`:** persists user/quote relation once; subsequent click toggles or shows Already saved depending on established UI. **Click `Copy`:** copies visible text. **Click `Share`:** see website sharing vs quote sharing distinction; if existing quote share is built, do not break it, but avoid exposing private quote URLs.

### 4.5 Saved Quotes

Navigate `Saved Quotes` / mobile `Saved`; show paginated only-this-user saved quotes. Each card provides copy/share and unsave. Empty state says `You have not saved any quotes yet` with `Browse quotes` action. After unsave, row disappears or updates consistently. Saving from Feed should appear here without reload, and remain after a new login.

### 4.6 Full-screen Demo Advertisement

**Purpose:** UX acceptance of the original client's `Generate Ad` sequence before provider acceptance. A DEMO AD is not a network-provided advertisement and has no revenue value.

**Open:** user reads quote; clicks `Generate Ad`. Dialog/overlay occupies supported viewport, clearly says `Demo advertisement — no real earnings`. Focus moves into dialog, background content is inert/scroll-locked, accessible Close. Provider-specific ad durations/skip rules must **not** be simulated as monetizable achievements. If demo provides `Continue to next quote`, click closes overlay and scrolls/renders next quote.

**Close:** close icon, allowed Escape/back actions, restore focus to trigger; remove scroll lock. If user closes before `Continue`, stay with current quote or follow established UX; **no wallet change** either way. Refresh/open another tab cannot produce a reward transaction.

**Real provider display:** Use provider's documented web format as delivered (e.g., TapResearch hosted surveys/offerwall may be opened via provider link/iframe; not a generic custom full-screen video). Do not claim TapResearch is equivalent to `Generate Ad` video after each quote. Separate the user action `Explore available earning activities` from fake impression counting and map onto the approved provider experience.

### 4.7 My Earnings

User click `My Earnings`: show only own breakdown: `Pending earnings`, `Available earnings`, `Lifetime credited`, `Reversed/Adjusted` where relevant, source/provider, activity reference, date, status and ability to open related detail. If no live provider: show `$0.00` and `No verified earnings yet`; optionally separate *Simulation* panel with explicit demo label and no withdraw. Display clear difference between `quote views`, `eligible conversions`, `provider revenue` and `user share`.

An earnings row includes provider/earning source, external transaction reference (masked), user, gross eligible payout (if contract permits), percentage rule, allocated amount, timestamp, settlement state, possible reversal. Never show other users' data.

### 4.8 My Wallet

Click `My Wallet`: show user-specific `Available Balance`, `Pending Earnings`, `Reserved for Withdrawal`, `Lifetime Paid`, and transaction timeline. Balances come from ledger-backed accounting, **never** trust a client-submitted amount. Amount format USD with two decimals; no floating-point money math. Include status text explaining pending/available distinction. `Request Withdrawal` routes to Withdrawals only when eligibility rules allow; in demo mode disable cash-out.

### 4.9 Withdrawals

User opens `Withdrawals`: displays current eligible balance, configured minimum threshold, payout method choices (only supported/approved methods), saved payout destination (masked), request amount, submit and request history.

**Click Request Withdrawal, PROPOSED safe procedure:**

1. Backend verifies session, email verification if required, configured minimum, currency, payout eligibility, anti-fraud rules and current available wallet funds.
2. User enters amount, selects approved method and supplies necessary payee details via secure form; tokenize/encrypt sensitive data and don't expose in logs.
3. On submit, a DB transaction locks relevant wallet/ledger row and atomically reserves the request amount. Concurrent duplicate submits must not reserve twice.
4. UI displays `Requested` with tracking ID. Available balance reduces; Reserved balance increases, but no `Paid` state yet.
5. Admin reviews, approves or rejects with audit reason. Rejected/cancelled funds return to Available through an auditable ledger movement.
6. For manual payout: admin must perform transfer outside system and enter a reference and confirmation only after actually sending; mark `Paid` with timestamp. For API payouts: `Processing` until provider success webhook/verified lookup; on failure release or retry based on reconciled status.
7. No negative available balances, duplicate payout events, or hidden status modifications. Completed records cannot be rewritten without audit.

**Payout methods:** Cash App and PayPal were client preferences, not confirmed automatic APIs. Manual handling is a safe technical milestone; automated PayPal Payouts or Cash App requires provider-specific business eligibility/technical confirmation. Never fake automatic payment buttons.

### 4.10 Website sharing across social platforms / email / text

Client said **“website ... shared on all social media platforms as well as through emails and texts”**. Implement a practical **share website** affordance, without inventing individual public quote pages.

**Click Share Website:** if browser `navigator.share` supported, use native dialog with canonical site URL, site title and short safe description. If not, show a small popover/dialog with Copy Website Link, Facebook, X, WhatsApp, LinkedIn, Email and SMS/Text, where valid share URL handlers exist. On platforms that do not accept arbitrary website deep links (e.g. Instagram), show copy-link instructions rather than a dead button. Email opens user's mail compose intent with URL; SMS opens device compose intent with URL. Never send automated SMS or emails on user's behalf just by clicking Share. No leakage of auth tokens, referrals or user-specific private pages. Open Graph preview should be meaningful for website URL if a public guest route legally serves metadata, but do not create a marketing landing page without agreement.

**Failure modes:** popup blockers, no share API, desktop SMS handler absent, offline state, missing canonical production domain (use staging link only in staging, label environment), share metadata caching. Social platform 'all' is a user-level aspiration; implement mainstream handlers and a universal Copy Link fallback rather than claim every provider has a direct integration.

### 4.11 Admin Overview

Admin login displays full-width Overview with actual backend data **only where available**: total users, active users, quote generations/views, current provider status, verified eligible publisher revenue, outstanding user liabilities, pending withdrawals and system alerts. Demo/test metrics clearly labeled; never fabricate `128 users` or `$10,000 revenue` as real.

Each card/drilldown should navigate to corresponding list with appropriate filters. Charts can show empty/no data states. Every admin action requires server-side authorization.

### 4.12 Admin — Users

`People & Access > User management`: paginated/searchable users table with name/email (appropriately masked in screenshots), verified state, status, registration date and safe actions (view, disable/reactivate where confirmed). Suspend user must block new earning activities and login according to security policy, but avoid silently confiscating balance. Hard delete financial histories is forbidden; use compliant deactivation/anonymization strategy. Role elevation restricted to authorized super-admin and audited. No user creation feature is promised unless already implemented or necessary for operations.

### 4.13 Admin — Quote Management

`Content > Quote management`: list generated/published quotes, category, owner, created date, state; search/filter, detail, moderation/reject/archive where product permits. Client has not conclusively approved user-to-user public quote publishing or creator-pay model; keep `private`/`platform-feed` behavior and publishing status gated until decision. If quote publishing is required for existing feed, use safe first-party platform publishing, not a public social network. Remove/flag unsafe content without deleting user financial history.

### 4.14 Admin — Advertising / provider management

`Advertising & Finance > Ad management` or `System > Integrations`: provider cards/configuration with `Not configured`, `Credentials saved`, `Sandbox ready`, `Pending external approval`, `Active`, `Degraded`, `Disabled` states. Show only configured, approved providers as real ad sources; no misleading badges.

**Click Configure provider:** admin sees fields supported by exact official integration documents (for example Google AdSense publisher/ad-unit IDs, TapResearch approved placement/SDK/hosted-link config, per-provider postback URL and signing secret). Sensitive secret inputs masked, encrypted server-side, never sent back as plaintext; test-connection action calls safe diagnostic endpoint where supported. Show copyable webhook callback URL; do **not** invent generic signature formulas.

**Activate provider:** require all provider-specific setup prerequisites, valid credentials, client-owned publisher account, tested callback/placement and explicit business model permission. Active flag controls UI inventory; secrets must not be bundled into front-end builds. Feature flag for sandbox versus production must be visible to admins. If provider inactive/unavailable, user sees a graceful empty state.

**Provider candidates / deployment ordering:**

- **TapResearch**: developer stated initial integration priority; currently proposed to start here. Web surveys/offers with hosted/API integration and provider-supported server-to-server reward event verification. Provider approval/cash reward terms and actual callback fields must be validated from current docs/account dashboard.
- **Wannads**: reward offerwall, web iframe/API if available to approved account; backend user mapping and verified postbacks. Do not treat all offers as video ads.
- **TheoremReach**: survey offerwall SDK/iframe/API subject to account eligibility; verified callbacks and reversals as documented.
- **Google AdSense**: normal website ad placements for owner revenue; **do not credit users cash for viewing Google ads**. Crawler/site approval and content policy review remain independent; no claim that AdSense tracks user cash entitlement.
- **Other researched options** (Adscend Media/PixelPointTV, ayeT-Studios, Lootably, Torox, AdGem, BitLabs, etc.) are research candidates, **not automatically included integrations**.

**Multi-network design:** provider adapters may coexist in code, each with independent approved contract, configuration, user ID mapping, callback authentication, transaction namespace and settlement. Actual four-provider launch is not guaranteed or assumed in the present order price. Prioritize one fully testable integration vertical slice, then add separately signed-off adapters.

### 4.15 Admin — Revenue & Transfers

Show separately: publisher revenue (reported / confirmed / settled), user reward liability, pending balances, available balances, paid withdrawals, reversals and adjustments. `Revenue` does not mean `bank cash already settled`. Filters by provider/date/status/user; export only if safe and approved. A human-readable audit history shows who approved a payout, when and what reference was recorded. Admin can initiate manual financial adjustment only through dual-checked, auditable workflow with explicit reason and permission; never directly set `wallet.balance = X`.

### 4.16 Admin — AI integrations and settings

Show AI provider selection/default model, health status, budget/cost metrics if existing; credentials saved/replaced without plaintext reveal; safe 'Test provider' feature with limited prompt and no user charge, proper timeouts. Support existing Laravel AI SDK implementation and provider fallbacks if already built; do not reinvent a new compatibility layer for no reason. Controls for quote category management, request rate limiting, moderation settings and maintenance/reward toggles if consistent with existing app. Changes audited. AI keys never appear in JavaScript bundles or HTML.

---
## 5. Domain model and database planning (PROPOSED, not existing table names)

**Critical:** Inspect migrations and models first. The names below are *conceptual*; reuse existing normalized tables and naming conventions. A migration is justified only when mapping identifies a missing persistence requirement. Create migrations additive, reversible where feasible; backfill in batches; define constraints and indexes; preserve older transaction history. Money must never be a floating-point JS/PHP/SQL decimal calculation without precise decimal library or integer minor units.

### 5.1 Identity and accounts

- `users`: existing authentication identity; unique normalized email, verified_at, status, role/permission relation. Avoid direct plain role assignment from a user-controlled form.
- `user_profiles`: optional if actual repo separates profile data; avoid duplicate user/profile state.
- `payout_destinations`: user ID, provider type, masked handle, encrypted/tokenized actual handle if legally necessary, verification state; no sensitive payout account secrets in logs or audit payloads.
- `audit_events`: actor ID, action, subject type/id, before/after *safe nonsecret summaries*, timestamp, request ID and reason. Redact financial account numbers, API keys and private email fields.

### 5.2 Quote domain

- `quote_categories`: ID, name, slug, enabled, sorting if configurable.
- `quotes`: ID (UUID/ULID as per repo convention), owner user ID nullable for platform-generated feed, body, category, source `ai`/`admin`/other verified, provider/model metadata only when safe, visibility `private`/`platform_feed`/`published` as actually approved, moderation state, created/updated timestamps and version for optimistic updates.
- `saved_quotes`: composite unique `(user_id, quote_id)` and timestamps.
- `quote_view_events` or aggregate: viewer_id, quote_id, valid/content-view status, time bucket, session/correlation ID, fraud indicators. Keep privacy/retention in mind; do not log raw IP indefinitely.
- `ai_generation_jobs` or existing usage: request ID, user, model, category, duration, tokens, estimated cost, failure class and retry count. Never include provider keys or raw reset tokens.

**Publishing policy:** Current latest UI demo shows platform authored `AI Inspiration` cards; client has asked about quote creators but not finally approved creator monetization. Do not make all private generated quotes public automatically. If public publishing is approved, add explicit Publish action, moderation/visibility model and view accounting; tag as new scope.

### 5.3 Provider infrastructure

- `monetization_providers`: provider key, supported modality (`standard_ads`,`survey`,`offerwall`,`rewarded_video`), integration state, environment, admin-created config reference; no plain secrets.
- `provider_user_mappings`: `(provider_id, user_id)` to opaque external identifier and status, unique per provider.
- `provider_raw_events`: provider, external event ID, event type, signature/verification status, received timestamp, payload hash, minimal redacted payload, processing state, error code, idempotency unique index `(provider,event_identifier,event_type_or_sequence)` using provider-specific semantics. Do not persist unnecessary personal data.
- `provider_conversions`: normalized conversion reference, provider, mapped user, reported payout currency/minor units, rewardable status, pending/approved/reversed, effective date, provider-approved source, raw event link. Distinguish estimated and settled amounts.
- `provider_settlements`: settlement/batch ID, currency, reported gross/net payout, fees/reversals, payout status, period, import identity, reconciliation state; authoritative records only from provider.
- `ad_delivery_events` (if provider supports): placement ID, request/session metadata, served/unfilled/error, **no claim of eligible revenue** unless a verified payout event proves it.

### 5.4 Financial ledger and withdrawals

**Prefer a proper double-entry or at least append-only ledger**: never treat `wallet.balance` as the only source of truth.

Conceptual tables:

- `wallet_accounts` per user/currency; derived or materialized balance maintained transactionally.
- `wallet_journal_entries`: immutable event/type/source/currency/amount minor units, debit/credit accounts, posted timestamp, status; unique source event and reference; adjustments are new reversing entries, not updates to historical amounts.
- `user_earning_allocations`: conversion/event source, user, eligible amount, allocation formula version, exact ratio, resulting integer cents, pending/confirmed/reversed, unique `(source_event,user,allocation_type)`.
- `withdrawal_requests`: user, requested integer cents, currency, method, state, created/approved/paid dates, masked destination, idempotency key, rejection reason safe for user.
- `withdrawal_attempts`: request, provider or manual, initiation idempotency key, external transfer ID, status, reconciliation timestamps, error classification; never duplicate transfer because of retries.
- `financial_audit_events`: append-only admin decisions and system changes with correlation IDs.

### 5.5 Revenue allocation example with exact rounding

Client described **one-third** and **33.33%** as near-synonyms; those are numerically different for some minor-unit amounts. Default *proposal* for safe demo: exact fraction `1/3`, rounded **down to nearest cent**, remainder assigned to platform as rounding residue. Do not enable live formula until client confirms whether true 1/3 or exactly 33.33%, eligible gross vs net, fee timing, reversals and payout currency.

Example in *sandbox only*: provider confirms eligible payout of USD 30.00 (3000 cents). `floor(3000 / 3) = 1000 cents` user allocation, 2000 cents platform remainder. For USD 1.00 (100 cents): `floor(100 / 3) = 33 cents` user, 67 cents platform. This is not a guaranteed earning per view and does not follow from a quote view count alone. **Do not allocate a fraction of Google AdSense ordinary ad impressions to cash-viewing users.**

### 5.6 Withdrawal state machine

```text
Eligible funds (Available)
  -> Withdrawal Requested (funds reserved)
  -> Under Review
     -> Rejected / Cancelled (reservation reversed to Available)
     -> Approved
        -> Processing (manual off-site payment OR verified API transfer)
           -> Paid (confirmed reference)
           -> Failed (clear to retry/release only once after reconciliation)
```

Rules:

- State changes must be server-authorized and pass a transition validator; no direct arbitrary status update from the browser.
- A withdrawal must be idempotent and reserve exactly once in a database transaction with locking.
- Mark `paid` only after actual evidence; don't infer success from clicking `Approve`.
- API failure retries can be uncertain; query provider status before resending. Keep payout attempt IDs unique.
- When a provider reverses a reward after it has been paid, record liabilities/negative pending adjustment according to approved rules; do not silently alter prior paid records.
- When no verified earnings exist, wallet shows zero and Request button disabled or safely rejected.

## 6. Provider adapter architecture and explicit integration gates

### 6.1 Adapter contracts (conceptual; use actual framework conventions)

```php
// Illustrative interface shape, NOT prescribed framework code.
interface MonetizationProviderAdapter {
    public function key(): string;
    public function mode(): string; // standard_ads, survey, offerwall, rewarded_video
    public function configurationRequirements(): array;
    public function createUserAccess(User $user): ProviderAccessResult;
    public function verifyCallback(Request $request): VerifiedProviderEvent;
    public function normalizeEvent(VerifiedProviderEvent $event): NormalizedRevenueEvent;
    public function healthCheck(): ProviderHealthResult;
}
```

**Never implement a `verifyCallback()` that returns true unconditionally.** A provider may not have a signature, may use an IP allowlist, or may require an authenticated API lookup. Follow **that provider's current official documentation**; if no secure method exists, mark unsupported for cash credit until the provider approves an alternative. Frontend can only request/start an activity; the *server callback* is the accounting evidence, if documented.

### 6.2 Google AdSense standard ad adapter

- Provider mode: `standard_ads`; owner revenue only in this module.
- Config: publisher/client IDs, approved domain/placement IDs and enabled ad slots; render official scripts only in eligible contexts where ad policy allows.
- App must distinguish `placement-configured` vs `provider-approved` vs `ad-filled` vs `revenue-report-available`.
- Never present Google ordinary ad `onload`, view, click or dismissal as a cash reward event. Google's reward formats cannot be converted into withdrawable cash rewards by waiting or routing payments through owner accounts.
- AdSense reporting is aggregate and may not supply a quote-level or user-level cash entitlement. If client later wants creator payouts, obtain authorization and design valid attribution/reconciliation; no guessed per-view wallet credits.
- Authenticated pages may require AdSense crawler access configuration; do not promise approval. Keep feed usable while ad blocked.

Official references for agent to revalidate before implementation:

- https://support.google.com/adsense/answer/48182
- https://support.google.com/adsense/answer/9121589
- https://support.google.com/adsense/answer/1348725

### 6.3 TapResearch — first provider proof-of-integration

**Integration goal:** a working web *survey/offers* integration path using official currently available product documentation and test environment. Not necessarily a 15-second fullscreen video after every quote.

**User flow (proposed):** `User Dashboard` → `Earn Rewards` (when enabled and approved) → `TapResearch available surveys` → provider-hosted/embedded supported experience → user finishes eligible survey → provider sends verified callback → transaction appears Pending/Available according to confirmed terms. If provider has no offers, show friendly empty state; user may return to Quote Feed without any earned amount.

**Implementation steps:**

1. Find current official TapResearch supply integration docs; confirm web hosted link/SDK/API and supported cash-redeemable publisher business model *in writing*. Do not code against invented endpoint/parameter names.
2. On admin configuration page, supply approved publisher IDs/API credentials/secret in encrypted form. Show a unique user identifier map, not user email embedded in public iframe URLs.
3. Create a provider-specific adapter service and controller. Construct access URL or embed according to official web integration docs and current signed-in opaque user id.
4. Expose narrowly scoped callback route; validate origin/auth/signature or approved verification method; validate all required user, payout and event status fields; normalize currency using exact decimal parser.
5. Insert raw event once and process transactionally. Duplicates return success/no-op if already processed. Invalid/rejected event records diagnostics without user credit. Provider reversal creates ledger reversal with audit trail.
6. Add sandbox fixture/test transport according to official docs. Rehearse valid callback, invalid signature, duplicate callback, reversal and out-of-order statuses using **fake data clearly labelled test**.
7. In React, display live provider *availability only when verified*, plus loader, no-offers state, restricted-country state, error retry. Don't use browser event to award money.
8. Report admin outcome and provider-mode state. Do not mark production-active until client account and reward setup are approved.

Official docs start page for verification: https://supply-docs.tapresearch.com/ . Before coding, re-check that all endpoints, webhook fields and product eligibility remain current; this is a link, not proof the account is approved.

### 6.4 Wannads and TheoremReach — deferred adapter contracts

Both have browser/web offerwall/survey product documentation, but **account approval, included scope and cash reward terms are not confirmed**. Do not code all adapters as active simultaneously without written scope agreement. Build common interface and one proven vertical slice, then use separately tracked implementation tasks.

For each later provider: approved account → product selection → per-user opaque mapping → iframe/SDK/hosted link → callback authentication → normalized payout/reversal → financial ledger → user-facing status → admin reconciliation. Never reuse another provider's signature algorithm or status semantics. Create isolated fixtures for each.

Docs to verify directly in agent environment:

- Wannads: https://platform.wannads.com/documentation/offerwall/html
- TheoremReach: https://docs.theoremreach.com/publisher

### 6.5 Provider not available and compliance modes

App feature modes:

- `DISABLED`: no CTA to external earning activities, clear explanation if user navigates to earnings.
- `DEMO`: modal/fixture for testing only; visibly simulated, no payouts.
- `SANDBOX`: verified test callbacks allowed in isolated testing DB; never leave residual withdrawable funds.
- `PENDING_APPROVAL`: integration configuration complete but live placement cannot start; tell admin what's missing, do not expose operational keys.
- `PRODUCTION_ACTIVE`: real, approved credentials and business model; monitored and reconciled.
- `DEGRADED`: outage/no offers/no fill; preserve quote platform; queue reconciliation and alerts.

**Provider capability matrix in DB/config:** `supports_web`, `supports_rewarded_cash` (account-specific verification!), `supports_callback`, `reports_publisher_payout`, `supports_reversal`, `settlement_frequency`, `verified_approval_at`, `source_of_approval_reference`. Default capability flags to false/unverified, not optimistic true. Never let admin set `supports_rewarded_cash` solely from marketing claims without verification.

### 6.6 Conflict prevention when multiple providers coexist

- Ordinary AdSense ads cannot be credited as user cash earnings. Keep revenue source classification explicit and separate at DB and application layers.
- A site with incentivized activities may face overall traffic/placement restrictions from standard networks; do not assume placing ads on a different tab automatically satisfies their rules.
- Provider IDs namespace each external conversion. Duplicate transaction IDs across two companies must not collide.
- Conversion callbacks may be delayed, repeated, refunded, reversed or come out of order; state machine must remain consistent.
- Publisher *settlement to owner* and user *withdrawal liability* are different events. Pending user rewards must be funded by verified eligible revenue and controlled reserves.
- The developer cannot guarantee third-party ad supply, USA rate, weekly publisher settlement or approval.

## 7. AI services, cost, concurrency and safety

**Reported existing work:** OpenAI key supplied and a working generation call was demonstrated; Admin AI configuration for multiple providers was reported. Verify real implementation with isolated tests. Do not expose keys or reproduce client credentials.

Service behaviors:

- Provider config lookup and fallback using installed Laravel AI SDK or current equivalent. Admin default provider/model selection; test to server from Admin, no plaintext secret response.
- Prompt templates versioned and category-specific; generated content validated for reasonable length, no prohibited/restricted instructions, user-facing error on model failure.
- Rate limiting/abuse controls that do not silently redefine client's requested unlimited generation. Track token usage and costs if provider supports; alert for sudden spikes and budget thresholds.
- `Generating...` pending button state; abort/retry handling; no duplicate charged requests on double-click; idempotency key or client request ID where warranted.
- Queue expensive operations when appropriate; Redis/queue worker monitoring; don't assume synchronous route will always finish within request timeout. Use existing job queues if installed.
- If two AI providers exist, failure of one may trigger controlled failover only where contractually/technically supported and within configured budgets. Don't leak provider error body/keys.
- Generated text belongs to user as saved or history according to current app's data model; admins can moderate inappropriate content with traceable action.

## 8. HTTP routes and API contract examples (PROPOSED — do not overwrite existing route names)

**MANDATORY:** After `php artisan route:list`, map actual routes to these capabilities. Only add routes needed; prefer existing Inertia actions/REST patterns. The following are suggested semantics, **not verified existing application URLs**.

| Capability | Suggested method/route | Auth/permission | Main behavior |
|---|---|---|---|
| Guest login form | `GET /login` | Guest | Show login screen |
| Login | `POST /login` | Guest / rate limit | Rotate session, role redirect |
| Register | `POST /register` | Guest | Validate and create user |
| Guest legal pages | `GET /privacy`, `/terms` | Public | Real links, no app data |
| Quote feed | `GET /feed` | User | Paginated eligible quote list |
| Quote generation | `POST /quotes/generate` | User | Validate, AI generate/persist |
| Saved list | `GET /saved-quotes` | User | Own saved list |
| Toggle save | `POST/DELETE /quotes/{id}/save` | User | Own save relation |
| Analytics view | `POST /quotes/{id}/view` | User | Dedup content view; no pay |
| Website sharing metadata | `GET /share` or canonical root | Public metadata | Share website URL, no tokens |
| Earnings | `GET /earnings` | User | Own ledger-backed activity |
| Wallet | `GET /wallet` | User | Own balances |
| Withdrawal requests | `GET/POST /withdrawals` | User | List / reserve funds |
| Admin provider list | `GET /admin/integrations` | Admin | Config state |
| Admin ad reports | `GET /admin/advertising` | Admin | Verified provider data |
| Admin withdrawals | `GET /admin/withdrawals` | Admin finance | Approval queue |
| Admin withdrawal decision | `POST /admin/withdrawals/{id}/approve` | Admin finance | Audited state transition |
| TapResearch provider entry | Per approved SDK/URL | User | Redirect/embed eligible offerwall |
| TapResearch callback | `POST /integrations/tapresearch/callback` | Provider only | Validate, idempotent event |

Security contract examples:

- Unauthorized user requests to admin endpoints return 403 or redirect appropriately; not a React-only hidden menu.
- Pagination, category and currency inputs must be validated; bounded page sizes; no unbounded queries.
- Quote IDs belonging to other users may be visible only if visibility permits. Edit/save/reward mutations enforce ownership.
- Every write endpoint protected by CSRF for session-auth, or a proper webhook verification scheme for machine callers.
- Provider callback routes are excluded from CSRF only if framework requires for webhooks, and have **stronger vendor authentication/verification** instead.
- All API response errors consistent with Inertia/JSON patterns already in the codebase.

## 9. Security, privacy and operational requirements

1. Authentication: hashed passwords, proper reset, verified email if configured, rate limiting, session rotation, CSRF and authorization policies.
2. Secrets: `.env`, secret manager/encrypted DB, never in frontend bundle, `localStorage`, Git commits, screenshots or provider callback query logs.
3. Provider callbacks: authenticated/signature-verified as documented, idempotent, timestamp/freshness checks if supported, replay-safe, normalized currency, proper response codes and alerting.
4. Financial: immutable ledger, atomic reservation, state transitions, role-based admin approval, payout idempotency, no double spend, reasoned adjustments, settlement reconciliation.
5. Users: suspension policy, quote moderation, privacy notices about AI + ad/offerwall data, retention and export/delete workflows subject to legitimate accounting retention.
6. Content: escape/untrusted quote text, prevent XSS in Markdown/HTML, no arbitrary HTML injection from model outputs, safe external share URLs and provider iframes with appropriate restrictions.
7. Infrastructure: HTTPS, secure cookies, CORS/CSRF, CSP where providers permit, verified webhook TLS, queues and retry policies, mail transport, backup/restore drills, structured error monitoring.
8. Logging: redact secrets, payout handles, emails when possible; correlation IDs for AI generation and provider events; logs should not become a separate PII warehouse.
9. Role testing: attempt direct URLs and crafted requests as user, guest, suspended user and admin. Admin-only UI hiding is not authorization.
10. Sandbox isolation: synthetic transactions must never become available for real payout. Use separate database/namespace and positive proof of production mode before activation.
11. Abuse prevention: quote-generation bot protection, offerwall fraud rules (no IP spoofing or client-side reward fabrication), suspicious repeat requests, payout velocity checks and admin review.
12. Background jobs: idempotent handlers, backoff, dead-letter/failure dashboard if installed, no duplicate credit on retry.
13. Accessibility: visible focus, dialog semantics, keyboard navigation, reduced-motion support where appropriate, descriptive errors and readable color contrast.
14. Account and application ownership: domain, hosting, publisher and payout accounts client-owned when available; no vendor lock-in. Document external services and fees honestly.
15. Financial/legal launch gate: required terms, eligibility, taxes/KYC/AML where applicable and payment processor contracts must be reviewed; developers should not invent legal approvals.

## 10. Phased implementation order (delivery-first, not research-first)

**Do not implement phases blindly: each phase starts by inspecting actual code and marking already implemented behaviors. The goal is to reuse existing features, not rebuild.**

### PHASE 0 — Repository baseline (MANDATORY FIRST)

1. Confirm clean working tree and baseline HEAD; safely branch.
2. Run startup/tests/build; map env dependencies; note blockers without exposing secrets.
3. Inspect current routes, frontend pages/components, data migrations, auth/authorization, AI SDK, queues, provider integrations, wallet/withdrawal logic and tests.
4. Write feature matrix with columns `requirement`, `source/evidence`, `code paths`, `status`, `gap`, `test result`, `scope/approval`, `next action`.
5. Capture *actual* screenshots of login, user feed, quote studio, wallet, admin and mobile; mark broken flows.
6. Provide prioritized plan, estimated relative complexity and risks. Do not pause indefinitely for scope clarification; implement independent confirmed features.

**Acceptance:** reliable run/build/tests, no source assumptions, baseline documents complete.

### PHASE 1 — Auth + user/admin shells + navigation

Ensure registration/login/session/verification, policies, role isolation, login-first guest, responsive user/app shells and admin drawer. Only change broken/missing features. Mobile baseline 320/390/768/1024/1440/1728 widths. No demo role bypass in production.

**Acceptance:** distinct user/admin login, denied cross-role direct URLs, guest legal routes and complete mobile navigation.

### PHASE 2 — Quote generation and feed

Repair AI config, generation, categories, loading/errors, persistence, feed pagination, saved items, copy and share website workflow. Ensure frontend React state reflects backend truth. For client creator question, document a **separate proposed public quote publishing change**, do not silently publish private quotes.

**Acceptance:** test user generates 2 different quotes, saves/unsaves across refresh, filters feed, copies, shares website; AI error handled without leak.

### PHASE 3 — Demo ad interaction and provider status UI

Preserve quote → Generate Ad → fullscreen **demo** ad → next quote. Add clear `No real earnings` labels; no ad loop trapped without fill. Separate `Earning activities` placement from generic video popup. Admin sees `Not configured/Pending approval` provider states. No wallet credit on demo events.

**Acceptance:** modal keyboard accessible, next quote works, wallet remains unchanged, unavailable provider does not block feed.

### PHASE 4 — Financial ledger and manual withdrawal path

Build/refactor immutable ledger, user-wise records, precise 1/3 demo allocation, wallet page, transaction history, minimum withdrawal configuration (demo default only), withdrawal reservation and admin approval/rejection/manual confirmation. Use an **admin sandbox tool limited to local/test environments** to generate synthetic provider events; production must reject it.

**Acceptance:** simulated $30 eligible event → $10 user allocation under exact one-third sandbox formula; duplicate event credits once; simultaneous withdrawals cannot overspend; no real transfer, all tests isolated.

### PHASE 5 — TapResearch integration vertical slice

Use current official approved web integration methods; provide provider config/admin status, user-specific session/link, signed/authenticated postback ingestion, idempotency, reversal and reporting. Sandbox first. If no approved credentials, show `Pending external approval`; leave fully testable fixtures, documentation and callbacks without fabricating live income.

**Acceptance:** unit/feature tests for signature invalid/valid, duplicate, reversal, wrong user, zero/negative amount, out-of-order statuses; end-to-end approved fixture generates correctly classified pending wallet event in test only.

### PHASE 6 — Standard AdSense adapter and other provider readiness

Implement separate normal AdSense placements/config (owner revenue, **no user ad-view cash reward**), crawl/site readiness checklist and flags. Prepare common adapter interfaces for Wannads/TheoremReach; implement real adapters only once inclusion, provider permission and credentials established. Report per-provider readiness.

**Acceptance:** no cash reward path from AdSense; provider disabled gracefully; provider statuses documented accurately.

### PHASE 7 — Sharing, QA and production readiness

Share website via social/email/SMS/copy/native share; ensure correct canonical URL and no token leak. Finish responsive/accessibility, automated tests, logs, queues, mail test, backup plan, staging deployment documentation, dependency checklist, report of completed vs externally blocked. Never deploy new migration without rollback/backups.

**Acceptance:** tester can complete a documented walkthrough using test accounts; every blocked item explicitly recorded and has an owner.

### OPTIONAL PHASE (NOT PART OF BASELINE) — Creator revenue sharing

**Only after explicit client written decision and confirmed provider agreement**: user publishing, creator attribution, public/registered viewer visibility, eligible unique view counting, monetization attribution, creator earnings pool, revenue allocation and clear provider source. The scenario “creator generated 10k quotes views so Google owes payment” is **false**; creators earn only from eligible monetized activity tied to their content, not plain quote views. AdSense third-party sharing permission and measurement constraints apply. Estimate separately; do not silently activate.

### OPTIONAL PHASE (NOT PART OF BASELINE) — User-funded advertising marketplace

Direct advertiser campaigns, prepaid funding, approval, reach/frequency goals, verified activity rewards, refunds, fraud controls, advertiser dashboard and independent settlement are **out-of-scope research proposal** unless later accepted and funded. Never infer it is included because it appeared in research reports.

---
## 11. Cross-module behavioral examples and edge-case requirements

### Scenario S-01 — New visitor discovers platform

1. Visit site URL in private/incognito window. Show Login immediately; no guest access to feed.
2. Click `Create account`. Show registration form; filling it creates a non-admin user and handles verification appropriately.
3. Click `Privacy Policy`; it opens without login. Click `Back to login` returns correctly. Repeat for Terms.
4. Incorrect password returns a normal validation error; no stack trace.
5. Successful user login goes to Feed; refresh or opening a separate tab retains session.
6. `Log out` clears user session; old direct `/wallet` link is rejected.

### Scenario S-02 — Quote generates, persists, is saved, and is shared

1. Logged-in user opens `Quote Studio` and chooses `Hope`.
2. Click `Generate Quote` once; loading state appears; backend invokes AI; response text visible and category reflected.
3. Click `Copy`; pasted text matches visible quote text (no markup or secret metadata).
4. Click `Save Quote`; success indication; reopen `Saved Quotes`, item present after refresh.
5. Click `Share Website`; native share or copy/share dialog includes the website URL. If an older quote-share button remains, it must not leak any protected quote URL.
6. Unsave and confirm disappearance after reload. Network timeout produces retry without duplicate saved records.

### Scenario S-03 — Original ad experience in DEMO

1. On Inspiration Feed read first card; click `Generate Ad`.
2. Full-screen demo opens with **not real ad** label; can close or choose `Continue to next quote`.
3. Next quote appears, no endless modal loop; focus returns to content and background scrolling unlocks.
4. Open My Earnings and Wallet. Balances remain $0.00; no event was recorded as verified revenue.
5. Repeat clicks rapidly, switch tabs, reload. No credit and no lockups.

### Scenario S-04 — User explores approved survey provider, but no inventory available

1. Open `Earn Rewards` if provider adapter enabled.
2. If approved provider responds `no surveys available`, show neutral empty state and link back to Feed. No reward appears.
3. Change account country/eligibility fixture as required and reload; no unauthorized data leakage.
4. Log provider unavailability server-side without exposing private credentials.

### Scenario S-05 — Provider sandbox reward event + duplicate + reversal

1. User A is mapped to opaque provider ID `usr_test_a` in isolated sandbox.
2. Valid signed test callback contains provider transaction `test-conv-001`, eligible publisher amount `$30.00`, status completed, source `tapresearch`. This **exact payload shape is illustrative**; adapt to current official provider schema.
3. Backend validates and records one raw event/conversion; if cash rewards permitted in *test configuration*, computes one-third => `$10.00` user pending entry, with source reference.
4. Identical callback repeated 10 times yields exactly one allocation.
5. Reversal for same external transaction issues a corresponding immutable reversal; balance recomputes consistently. No negative payout creating silently.
6. A callback with wrong signature or unknown `user_id` is rejected/quarantined, no credit.
7. Demo funds cannot be withdrawn from real payout service.

### Scenario S-06 — Safe withdrawal reserve, reject, approve and pay

1. Seed a user with `$25.00` of **sandbox-eligible** Available balance, simulated minimum `$10.00`.
2. User requests `$15.00`, payout destination masked and validated. Available becomes `$10.00`, Reserved `$15.00`, Withdrawals shows Requested.
3. User submits same form twice in rapid succession. Single request/reservation only.
4. Admin clicks Review and Reject with reason. Reserved returns to zero; Available returns to `$25.00`; history shows Rejected.
5. Re-request `$15.00`; admin Approves but no transfer has been sent yet, so status is Approved/Processing, **not Paid**.
6. In sandbox, admin records confirmed fake transfer reference and marks Paid. User history shows Paid, Lifetime Paid `$15.00`, Available `$10.00`.
7. In production, exact same flow may be executed only with real verified funds, authorized admin and validated real payment reference. Do not use sandbox/test balance for real transfers.

### Scenario S-07 — Two users and authorization

1. User A generates/saves a quote. User B logs in separately.
2. User B cannot fetch User A's private generated quote, saved list, wallet or withdrawal record by changing URL IDs or JSON payload.
3. User B can see User A's quote only if intentionally published into eligible feed and allowed by visibility policy.
4. User A cannot call `/admin/*` endpoints; server returns 403 or proper redirect.
5. Admin can view records according to role permissions and actions are audited.

### Scenario S-08 — Provider outage and incomplete setup

1. Admin disables TapResearch via provider config. User-facing provider activity control unavailable; no broken iframe.
2. React app still displays AI quotes, saved list and wallet history.
3. Configuration changes take effect without exposing credentials. Admin status clearly explains disabled vs pending approval.
4. Queue/retry mechanisms do not create any new reward credits from a disconnected provider.

### Scenario S-09 — Client creator idea, but not authorized

1. Client asks: “Can user A publish quote and get paid when B views it?”
2. Show existing actual features and **proposal** with clearly marked “not active / pending approval.”
3. If quote visibility remains private, no public creator feed is silently created.
4. If developer builds an internal demo of creator revenue attribution, use synthetic provider-confirmed revenue, explicitly labelled `Creator monetization demo`; never imply Google pays for quote views.
5. Production creator wallet allocation toggle remains off until client scope and provider permission documented.

### Scenario S-10 — Mobile site sharing

1. User opens Feed on iPhone/Android-sized viewport and clicks `Share Website`.
2. Native share API opens if supported; otherwise fallback with copy, email and SMS intents.
3. Close share action; underlying feed state remains intact.
4. On a browser without installed SMS app, button must fail safely or direct to copy fallback; no exception overlay.
5. Sharing **does not** create an unapproved public page for private quote text.

## 12. Automated test plan (must integrate with existing suite)

**Choose Pest or PHPUnit according to the existing repo. Do not install another test runner solely out of preference. For React use installed Vitest/Jest/Playwright/Cypress only if present or justified.**

### Backend tests

| Area | Essential automated assertions |
|---|---|
| Auth | valid/invalid login, new user default role, logout, verification/reset expiry, rate limiting |
| Authorization | guest cannot visit feed, regular user cannot visit admin, users cannot access others' wallet/withdrawals |
| Quote AI | valid category, API failure timeout, malformed response, retries, saving persistence, cost secret redaction |
| Feed | paging stable, category filter, private quotes excluded, XSS escaped, repeated views dedup as configured |
| Save/copy | unique saved record, toggle, ownership/visibility checks; frontend clipboard tested separately |
| Provider config | encrypted secrets, admin-only changes, disabled provider prevents activation |
| Callback | unknown provider/user, invalid signature, duplicate, zero/negative payout, wrong currency, reversal, out-of-order, retry |
| Revenue | one-third fraction precise rounding, unique allocation, gross/net rules recorded, no AdSense impression cash credit |
| Wallet | derived balances correct, concurrent operations safe, no floating-point drift |
| Withdrawal | minimum, zero/negative, double-submit, insufficient funds, admin approval/rejection, payout retry, no false paid |
| Sharing | canonical URL safe, private data not exposed in share metadata |
| Admin | data isolation, audit trail on settings/financial decisions |

### Browser and accessibility checks

- Login-first on fresh guest browser, no landing page.
- Responsive user sidebar and mobile bottom nav; admin full-width shell and drawer.
- Generate Quote loading/results/save/copy/share flows.
- Demo Generate Ad fullscreen, focus trap and next quote; $0 real earnings.
- Wallet/earnings/withdrawals pending vs available badges.
- Provider offerwall unavailable/approved-sandbox states.
- Native share API fallback and email/SMS links.
- Browser refresh, back/forward, auth expiration and deep links.
- No horizontal overflow or hidden submit buttons at 320px.

### Performance and reliability

- Paginated feeds don't use unbounded N+1 queries; inspect query log/test dataset.
- AI generation timeout is controlled; app does not hang on provider outage.
- Callback ingestion responds in bounded time; durable queue/retries and transaction commit.
- Two concurrent withdrawal submissions cannot both reserve same available money.
- Test DB is isolated from staging/production; test callbacks never hit live payout endpoints.
- External network failure creates graceful provider state and alert, not white screen.

## 13. Definition of Done / quality gates

A phase can be called **COMPLETED** only when all are true:

1. Code actually exists on named branch/commit; working tree known.
2. Correct permissions; no secret exposure.
3. Targeted tests and full relevant suite pass; test evidence recorded.
4. Build/lint/type checks pass or known unrelated failures are documented with baseline comparison.
5. At least one manual browser demonstration at desktop and mobile has been performed for affected UI.
6. Loading, empty, error and authorization states are covered.
7. Migrations safe on existing data; rollback documented and tested where feasible.
8. Provider-dependent work is clearly marked `sandbox`, `pending approval` or `real/live`—never ambiguous.
9. `IMPLEMENTATION_PROGRESS.md` updated with actual route/file paths and timestamp.
10. Human QA file's corresponding cases can be executed by a non-coder with expected visible results.

**Feature state labels to use in reports:** `NOT STARTED`, `EXISTS UNVERIFIED`, `IN PROGRESS`, `IMPLEMENTED TESTING`, `VERIFIED`, `BLOCKED: CLIENT`, `BLOCKED: PROVIDER`, `OUT OF SCOPE/OPTIONAL`.

## 14. Mandatory deliverables and reporting template

Inside the repository create/update:

```text
docs/implementation/
  AUDIT_BASELINE.md
  ARCHITECTURE_MAP.md
  FEATURE_MATRIX.md
  DEPENDENCY_REGISTER.md
  IMPLEMENTATION_PROGRESS.md
  TEST_EVIDENCE.md
  RELEASE_READINESS.md
  REVENUE_RULES_DECISIONS.md
```

Suggested `FEATURE_MATRIX.md` row example:

| Req | Client evidence | Code evidence | Status | Test | Blocker | Action |
|---|---|---|---|---|---|---|
| Quote generation categories | Nov 1 direct client | `app/...` (fill after audit) | EXISTS UNVERIFIED | `tests/...` | None | Verify AI response and DB |
| TapResearch callback | Client preference/first provider target | N/A until audit | NOT STARTED | Pending | Publisher credentials | Build verified sandbox adapter |
| Creator ad-revenue shares | Client Nov 15 question | Unknown | OUT OF SCOPE/OPTIONAL | N/A | Final model/provider approval | Proposal only |

**At the end of every phase, report exactly:**

```text
PHASE: <name>
BASELINE COMMIT: <sha>
CURRENT COMMIT: <sha>
FILES CHANGED: <list>
ACTUAL FEATURES COMPLETED: <list with clickable paths/URLs>
AUTOMATED TESTS: <command and pass/fail>
MANUAL BROWSER TESTS: <steps and result>
DATABASE/MIGRATION IMPACT: <details>
KNOWN ISSUES: <list>
EXTERNAL BLOCKERS: <provider/client details>
WHAT HUMAN TESTER SHOULD CLICK NEXT: <exact test IDs from companion file>
ROLLBACK: <revert/migration instructions>
```

Record a screenshot/video for **working functionality**, not only code diffs. Be transparent if no browser automation is available; hand off manual test commands to the human.

## 15. Rollback and environment safeguards

- Create a feature branch for each phase and make a baseline tag or record SHA before work.
- Keep a backup/snapshot of persistent data before destructive schema or production changes. Database `down()` rollback is not always safe after data backfills; explicitly state restore path.
- New ad providers behind flags; rollback by toggling off provider first, then revert code only after pending postbacks/reconciliation considered.
- Never drop finance/ledger tables or rewrite paid history on rollback. For accounting adjustments, append correcting journal entries.
- Migrations should be backward-compatible in rolling deploy; add column nullable/default, backfill, then enforce constraints separately after validation.
- When reverting deployed code, ensure old code can read new fields or schedule coordinated downtime; don't leave provider callbacks routed to nonexistent controllers.
- Stop scheduled/queue jobs before dangerous operations, inspect failed jobs after restart, replay callbacks with idempotency.
- Restore AI/provider secrets only via secure settings or environment, not from a committed file.
- Automated payout feature flag default off. Emergency kill switch halts new payout initiation but leaves accurate audit/status inquiry functional.

## 16. Implementation exclusions and unresolved decisions — do not invent answers

**Must not be implemented as client-approved without further written confirmation:**

1. Whether Creator Revenue Sharing replaces Viewer Rewards, complements them, or is just an inquiry.
2. Whether users may publish their generated quotes for other users, and whether publication requires moderation.
3. Whether quote views alone qualify for any earning (they do **not** inherently create ad-network revenue).
4. Gross vs net eligible revenue; true 1/3 vs fixed 33.33%; reversals and payout reserve rules.
5. Minimum withdrawal amount; exact supported cash-out rails and automation timing.
6. Whether four named providers are all included in existing paid order scope. Client interest does not prove that.
7. Whether any particular offerwall provider accepts cash withdrawals and the exact login-only site/quote-following flow.
8. Whether any provider will approve a new US traffic site or settle weekly.
9. Any direct advertiser campaign builder, public ad marketplace, public quote URLs, comments, likes/follows, native apps, paid memberships, or charity program.
10. Content of legally reviewed Terms/Privacy, domain/hosting provider and production SMTP credentials.

**Safe progress despite unresolved items:** secure auth, UI, quote generation, feed, saved, site sharing, demo ad UX, ledger structures, sandbox callback proof, manual withdrawal state machine and admin integrations shell. Do not repeatedly ask the client to reconfirm already established requirements. Record truly blocking decisions and keep building independent work.

## 17. Final release-readiness checklist for the coding agent

- [ ] Audited actual repo; architecture and feature matrix reference real paths.
- [ ] Existing staging/prod environments documented without revealing secrets.
- [ ] Auth, roles, email verification and reset tested.
- [ ] Login-first screen + guest privacy/terms accessible.
- [ ] User desktop and mobile navigation / Admin full-width navigation tested.
- [ ] AI quote categories, generation, save, copy, feed and negative states tested.
- [ ] Social/email/SMS website sharing tested without token/private URL leakage.
- [ ] Demo ad overlay visibly non-monetized; next quote tested.
- [ ] Provider states accurate; no false production activation.
- [ ] TapResearch sandbox adapter, secure callback and idempotency tested if implementation in scope.
- [ ] AdSense standard ads are entirely separate from user cash-reward attribution.
- [ ] Ledger, one-third sandbox calculation, withdrawals and reversals tested.
- [ ] No synthetic balance can be paid out.
- [ ] Provider credentials, publisher contracts and payee method approvals recorded as external gates.
- [ ] Responsive, accessibility and security regression complete.
- [ ] Release/rollback plan and project handover updated.
- [ ] Creator-earnings proposal is explicitly marked **unapproved**, not quietly delivered as the original requirement.

## 18. Instructions to the receiving editor AI — start here

1. Read this entire file and companion human QA file; inspect repository root instructions and actual code.
2. Start with **PHASE 0** now; do not replace source with a generic app or treat historic V14 as current code.
3. Deliver the baseline audit and a testable `Completed / In Progress / External Approval` matrix. If code already fulfills parts of future phases, mark them verified and skip redundant implementation.
4. Continue with the highest-priority **confirmed, independent** incomplete feature (auth/AI quote/feed/wallet), not an endless advertising provider hunt.
5. Use the client's **Option B** latest formal selection as the baseline viewer-reward business model; keep creator compensation separate as an open later question.
6. Use TapResearch as the first **proposed** provider integration proof, not as a guarantee of publisher approval or video inventory. AdSense is standard non-cash ad placement only.
7. At each phase end, show the human exact UI clicks and acceptance tests from `02_HUMAN_MANUAL_QA_TEST_PLAYBOOK.md`, plus the implementation evidence needed to validate them.
8. Any missing access or approval blocks only the relevant live activation, not independent coding/testing.

**End of AI Agent Master Implementation Context.**
