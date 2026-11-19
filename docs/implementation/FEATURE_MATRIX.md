# FEATURE MATRIX

**Updated:** 2026-11-19  
**Evidence tags:** SOURCE-CONFIRMED | OWNER-DESIGN | REPORTED-EXISTING | PROPOSED | BLOCKED-EXTERNAL | VERIFIED-IMPLEMENTED  
**Status labels:** NOT STARTED | EXISTS UNVERIFIED | IN PROGRESS | IMPLEMENTED TESTING | VERIFIED | BLOCKED: CLIENT | BLOCKED: PROVIDER | OUT OF SCOPE/OPTIONAL

| Req | Client evidence | Code evidence | Status | Test | Blocker | Action |
|---|---|---|---|---|---|---|
| Login-first guest | D-01 OWNER-DESIGN | `routes/web.php` home redirect; auth pages | VERIFIED (code+tests) | Auth/Access/Shell | None | Human AUTH-01 |
| Privacy / Terms public | D-01 | `privacy`, `terms` + legal editor | IMPLEMENTED TESTING | LegalDocumentTest | Legal copy approval | Human NAV-01 |
| User/admin role isolation | D-01 | `administrator` middleware, role enum | VERIFIED | AccessTest, AdminDashboardTest | None | Human AUTH-10 |
| User desktop/mobile nav | V14 / OWNER-DESIGN | `user-layout.tsx` (+ Surveys item) | IMPLEMENTED TESTING | ShellTest | None | Human NAV-02/03 |
| Admin full-width sidebar | V14 | `admin-layout.tsx`, `admin-sidebar.tsx` | IMPLEMENTED TESTING | AdsAdminPagesTest etc. | None | Human NAV-04 |
| AI quote categories + generate | D-02 SOURCE-CONFIRMED | Studio + QuoteGenerator + AI admin | IMPLEMENTED TESTING | StudioQuoteTest, QuoteAgentTest, AiManagementTest | AI credentials / AdGate | Human QUOTE-01..05 |
| Feed + save/copy | D-02 | FeedController, quote-card, clipboard | IMPLEMENTED TESTING | Studio/feed related | None | Human QUOTE-06..09 |
| Website share (social/email/SMS) | D-07 SOURCE-CONFIRMED | `WebsiteShareDialog` + sidebar/header/More; quote share retained | IMPLEMENTED TESTING | Manual SHARE-*; helpers in `quote-share.ts` | Human browser | Human SHARE-01..05 |
| Public quote pages | OWNER-DESIGN (built) | `PublicQuoteController`, OG images | IMPLEMENTED TESTING | QuoteSlugTest, PublicQuoteOgTest | Client: private vs public | Do not remove without decision |
| Demo Generate Ad → next | D-03 SOURCE-CONFIRMED | `AdController` demo sessions | IMPLEMENTED TESTING | Manual + comments | Exit redirects to public quote | Human AD-01..03 |
| Viewer revenue share Option B | D-04/D-06 SOURCE-CONFIRMED | `UserRevenueShare` → pending cents (toggle) + points | IMPLEMENTED TESTING (sandbox) | UserRevenueShareTest, MicrosTest | Live formula / cash-out | Keep toggle off in prod; Human FIN-03..05 |
| Wallet UI | D-05 | `WalletController@wallet`, ledger | IMPLEMENTED TESTING | Ops + share tests | Pending ≠ available | Human FIN-01/02 |
| Withdrawals manual | D-05 | user request + admin approve/reject/paid | IMPLEMENTED TESTING | Operations/Admin | Min threshold / destination privacy polish | Human WD-* |
| Quote views ≠ revenue | PROPOSED / DEC | No view→cash path found | ALIGNED | — | — | Keep |
| Creator pay-per-view | D-10 UNCONFIRMED | Publish exists; no creator payout by views | OUT OF SCOPE/OPTIONAL | — | Client decision | Do not activate |
| Self-serve ad marketplace | D-11 | Absent | OUT OF SCOPE/OPTIONAL | — | — | Exclude |
| TapResearch vertical slice | D-09 preference | Adapter + webhook + fixtures | IMPLEMENTED TESTING (sandbox code) | TapResearchPostbackTest, SurveyFlowTest | Publisher approval | Human PROV-04..07 |
| Google AdSense standard ads | D-08 | Placeholder adapter; no user cash for views | EXISTS UNVERIFIED | ProviderFallback / adapter | Publisher site approval | Keep non-cash |
| Wannads / TheoremReach | Client interest | Not registered | NOT STARTED | — | Scope + credentials | Adapter stubs only after sign-off |
| Survey gate on Quote Studio | OWNER-DESIGN (2026-11-19 note) | `AdGate`, `gate_quote_generation` setting | IMPLEMENTED TESTING | AdGateTest | UX vs “unlimited generate” promise | Document; ensure kill-switch default clear |
| Domain / SMTP / VPS | D-12 | Mail log locally | BLOCKED: CLIENT | — | External | Checklist in DEPENDENCY_REGISTER |

## Decision register (live)

See master context D-01…D-12. Engineering continues Option **B** viewer rewards; creator model remains optional proposal.
