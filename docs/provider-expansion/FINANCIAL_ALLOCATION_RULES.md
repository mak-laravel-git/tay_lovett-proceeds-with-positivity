# Financial allocation rules

1. **Publisher payout** (`publisher_revenue_micros`) — from verified callback only.  
2. **Provider reward units** — stored as `provider_payout_amount` / points currency.  
3. **User points** — from `ad_reward_rules` via `RewardService` (idempotent ledger key `reward:{provider}:{tx}`).  
4. **Optional sandbox 1/3 cash share** — `UserRevenueShare` → pending wallet only when admin toggle on; never AdSense.  
5. **Reversals** — one compensating `PointLedgerEntry` TYPE_REVERSAL; activity → `reversed`.  
6. **Test/debug** — zero revenue micros; `test_only` result; not withdrawable.  
7. Do not apply exchange rate and 33.33% twice. TheoremReach exchange_rate is entry configuration; points rules remain separate until cashout permission is verified.
