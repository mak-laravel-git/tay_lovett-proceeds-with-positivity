# Credentials and approval handoff

**Do not paste live secrets into chat or git.**

## Wannads (publisher dashboard)

1. Create/select web app for approved domain  
2. Copy **public API key** → admin `api_token`  
3. Copy app **SECRET** (not API SECRET) → admin `api_secret`  
4. Set postback URL to `/api/webhooks/wannads/reward` with macros for user_id, transaction_id, reward, status, payout, signature, aff_sub  
5. Confirm source IP allowlist  

## TheoremReach

1. Website approval + Privacy/Terms/consent per go-live checklist  
2. API key → `api_token`; secret → `api_secret`  
3. Partner ID, currency plural name, exchange rate in admin config  
4. Reward callback → `/api/webhooks/theoremreach/reward`  
5. Event webhook → `/api/webhooks/theoremreach/events`  
6. **Written coexistence authorization** before `concurrent_provider_permission` + production  
7. Confirm reward-callback hash with dashboard test vector  

## Google AdSense / TapResearch

Existing admin rows; no change to credential shape.
