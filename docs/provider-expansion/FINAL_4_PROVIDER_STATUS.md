# Final four-provider status

**Branch:** `feature/provider-expansion-4networks`  
**Date:** 2026-11-24

| Provider | Code | UI | Callback | Reversal | Cashout contract | Coexistence | Production enable |
|---|---|---|---|---|---|---|---|
| Google AdSense | Placeholder preserved (`standard_ads`) | Presenter exists; **not** an earn card | N/A (must not credit) | N/A | N/A | N/A | Owner ads account |
| TapResearch | CODE-VERIFIED + regression PASS | Earn card + survey list | Unchanged signed postback | Outcome reject path unchanged | Points (+ optional sandbox 1/3 pending) | N/A | Existing account |
| Wannads | **INTEGRATION_CODE_READY** + Pest PASS | Earn card → Explore Offers | MD5 postback + `OK` | Same tx `rejected` → one reverse | BLOCKED-EXTERNAL | N/A | OFF until keys + approval |
| TheoremReach | **INTEGRATION_CODE_READY** + Pest PASS | Earn card (blocked without permission) | HMAC callback (dashboard vector PENDING) | Event Webhook | BLOCKED-EXTERNAL | **BLOCKED-EXTERNAL** (hard gate) | OFF / cannot force without coexistence or sandbox |

## Client-facing summary

Four integrations can be prepared in code. Individual provider approval, cash-redemption permission, and TheoremReach multi-network written authorization govern activation. Sandbox/fixture success is not live approved earning.
