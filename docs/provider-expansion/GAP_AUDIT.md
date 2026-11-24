# Gap audit vs implementation + QA guides (2026-11-24, post-fix pass)

Honest review against `01_AI_AGENT_…` and `02_HUMAN_QA_…`.

## Fixed in this follow-up pass

| Gap | Fix |
|---|---|
| Compliance gate auto-bypassed in `testing` env | Removed; fixtures must set sandbox or coexistence |
| `allowsCashout()` unused (GATE C2) | Wired into `UserRevenueShare` |
| Wannads fetchOffers ignored launchState | Now checks compliance gate |
| `surveys/start` only UI-gated | Server-side `launchState` 403 |
| Earnings lacked provider filter / reversed labels | Added All/TR/Wannads/TheoremReach filter |
| Missing `FEATURE_MATRIX.md` | Added |
| Missing W-010 IP test / T-001b launch block | Added |

## Remaining gaps (intentional or still open)

### P1 — still incomplete vs guide

1. **TheoremReach reward-callback hash algorithm** — interim HMAC on query/URL; **dashboard test vector still BLOCKED-EXTERNAL** before production.  
2. **Admin “Hold Payouts for Provider”** dedicated control — not built; workaround = disable provider + `reward_cashout_permission=false`.  
3. **Settlement / pending hold period** before points or cash become usable — not implemented (points still credit on verified callback immediately).  
4. **Human QA playbook** (browser smoke, mobile, screenshots) — **NOT RUN** (needs staging URL + accounts).  
5. **Google AdSense** — still placeholder adapter (CODE-VERIFIED as non-earning). Not a full live AdSense integration audit with publisher ID slots; G-001/G-003 browser checks not run.

### P2 — partial

6. Admin “View Compliance” is embedded in credentials dialog, not a separate compliance evidence attachment UI.  
7. Not every W-*/T-*/X-* automated ID from §11 has its own named Pest case (core money/security paths covered; X-005 concurrent withdrawal race not newly added).  
8. Nav label still routes `/surveys` (page title is “Earn Rewards”); guide said preserve existing nav — OK.  
9. iframe new-tab fallback UX exists via survey frame; dedicated empty/error fixture messaging for Wannads inventory is generic “no surveys”.  
10. `production_enabled` bypasses in `local`/`testing` for non-TR providers — intentional for fixtures.

### External blockers (correctly not claimed PASS)

- Live Wannads/TheoremReach/TapResearch credentials  
- TheoremReach written multi-network coexistence authorization  
- Cash-redeemable reward contract per provider  
- Staging URL / human evidence pack  

## Verdict

**Core code path for Wannads + TheoremReach is substantially aligned with the guide** (adapters, signatures, idempotency, reversals, compliance kill-switch, AdSense separation, TapResearch regression).  

It is **not** “fully done” per definition-of-done §15 until: dashboard hash confirmation, human QA on staging, and external approvals. Do not treat fixture PASS as live earning.
