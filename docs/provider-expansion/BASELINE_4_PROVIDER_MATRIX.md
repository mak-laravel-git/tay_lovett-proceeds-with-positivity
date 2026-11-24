# Baseline four-provider matrix

**Date:** 2026-11-24

| Provider | Expected role | Pre-change status | Desired technical result | Go-live dependency |
|---|---|---|---|---|
| Google AdSense | standard ads; owner income | CODE-VERIFIED placeholder adapter; frontend presenter exists; never cash-rewards users | Preserve; no wallet credit from views/clicks | AdSense account/domain approval |
| TapResearch | surveys; eligible user points | CODE-VERIFIED adapter + Pest suite (29 tests PASS baseline) | Unchanged callback/ledger; regression after expansion | Live publisher account |
| Wannads | web offerwall; eligible rewards | NEW | Adapter + MD5 postback + OK ack + admin/UI | Publisher app keys + domain approval |
| TheoremReach | surveys/offerwall | NEW | Signed entry + callback + event webhook + compliance gate | Website approval + **written coexistence** + cashout policy |

## Evidence statuses used

CLIENT-CONFIRMED four-provider selection (2026-11-23). Production credentials: BLOCKED-EXTERNAL.
