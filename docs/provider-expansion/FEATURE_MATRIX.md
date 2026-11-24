# Provider expansion feature matrix

| Requirement | Status | Evidence |
|---|---|---|
| Client wants 4 providers (AdSense + TapResearch + Wannads + TheoremReach) | CLIENT-CONFIRMED | 2026-11-23 |
| Context7 + official docs before coding | DOC-VERIFIED | `OFFICIAL_DOCS_VERIFICATION.md` |
| Wannads offerwall + MD5 postback + OK ack | CODE-VERIFIED + TEST-VERIFIED | Adapter + WannadsPostbackTest |
| Wannads credit / duplicate / reverse / reject-before-credit | TEST-VERIFIED | W-003..W-008 |
| Wannads IP allowlist | CODE-VERIFIED + TEST-VERIFIED | W-010 |
| TheoremReach signed entry | CODE-VERIFIED + TEST-VERIFIED | T-002 |
| TheoremReach reward callback | CODE-VERIFIED + TEST-VERIFIED (interim HMAC) | T-005..T-007; dashboard vector still BLOCKED-EXTERNAL |
| TheoremReach event webhook reversals | CODE-VERIFIED + TEST-VERIFIED | T-009 |
| TheoremReach coexistence gate | CODE-VERIFIED + TEST-VERIFIED | T-001 / T-001b |
| Cashout permission gates sandbox 1/3 share | CODE-VERIFIED + TEST-VERIFIED | UserRevenueShareTest |
| Earn Rewards provider cards | CODE-VERIFIED | `/surveys` |
| Earnings provider filter (no AdSense) | CODE-VERIFIED | `/earnings?provider=` |
| Admin four-provider rows + secrets masked | CODE-VERIFIED | `/admin/ads/providers` |
| TapResearch regression | TEST-VERIFIED | Ads suite |
| Google AdSense as cash earn path | NOT-APPROVED / never | Placeholder adapter only |
| Live publisher credentials | BLOCKED-EXTERNAL | |
| TR written coexistence approval | BLOCKED-EXTERNAL | |
| Cash-redeemable contracts | BLOCKED-EXTERNAL | |
| Human staging QA playbook | NOT RUN | Needs staging |
| Admin “Hold Payouts for Provider” control | PROPOSED-UX / NOT IMPLEMENTED | Use disable + cashout flag today |
| Per-provider settlement hold period | NOT IMPLEMENTED | Points credit immediately; cash share pending-only when gated |
| Browser screenshots baseline | NOT RUN | |
