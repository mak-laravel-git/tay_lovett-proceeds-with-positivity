# API and callback contracts

## Shared

| Method | Path | Auth |
|---|---|---|
| GET | `/api/webhooks/{provider}/reward` | Provider signature (no CSRF / no session) |
| GET\|POST | `/api/webhooks/theoremreach/events` | TheoremReach event HMAC |

## Wannads Reward App Postback

- Query: `user_id`, `transaction_id`, `reward`, `status`, `payout`, `signature`, optional `aff_sub` (activity ULID), `offer_id`
- Signature: `md5(user_id + transaction_id + reward + SECRET)` — raw reward string; SECRET ≠ API SECRET
- Ack body: exact `OK` (text/plain) after durable accept; `ERROR` on reject
- Optional IP allowlist default `3.22.177.178` via `config.allowed_callback_ips`

## TheoremReach

- Entry: `https://theoremreach.com/respondent_entry/direct?...&hash=` URL-safe Base64 HMAC-SHA1 of pre-hash URL
- Reward callback: fields per dashboard; verify HMAC-SHA1 of raw query without `hash` (and/or full URL). **Confirm dashboard test vector before production.**
- `debug=true` → `test_only`, never wallet credit
- Event webhook: `event_type` + `reversed_transactions`; HMAC on query; any 2xx ack

## User launch

| Method | Path |
|---|---|
| GET | `/surveys` (Earn Rewards cards) |
| POST | `/surveys/offers` body `{ provider?: string }` |
| POST | `/surveys/start` |
