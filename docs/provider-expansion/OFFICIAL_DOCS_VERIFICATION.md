# Official docs verification

**Reviewer:** coding agent  
**Date:** 2026-11-24  
**Stack docs:** Context7 `/laravel/docs/__branch__13.x` (CSRF except for webhooks, `lockForUpdate`, transactions)  
**Inertia:** Context7 `/inertiajs/docs` resolved (React adapter available)

## Context7 publisher sources

| Vendor | Context7 result | Action |
|---|---|---|
| Wannads | **No correct publisher library indexed** (unrelated matches only) | Recorded: Context7 publisher source unavailable. Use first-party Wannads docs. |
| TheoremReach | `/websites/surveys-docs_theoremreach` = **DIY Researcher/Buyer API only** | **Not used** for publisher reward callbacks (per implementation mandate). |

## Wannads (DOC-VERIFIED from first-party pages, 2026-11-24)

| Item | Value | Source |
|---|---|---|
| HTML offerwall | `https://earn.wannads.com/wall?apiKey={PUBLIC}&userId={OPAQUE}` | platform/wannads offerwall HTML docs |
| Postback method | HTTP GET | Reward App Postback |
| Required verify fields | `user_id`, `transaction_id`, `reward`, `signature` (+ `status`, `payout` as used) | Reward App Postback |
| Signature | `md5(user_id . transaction_id . reward . SECRET)` — **SECRET ≠ API SECRET**; raw reward string before numeric normalize | Reward App Postback |
| Status | `credited` / `rejected` (legacy migrated: `1` / `2`) | Reward App Postback |
| Ack body | Exact `OK` or `1` after durable accept | Reward App Postback / postback macros sheet |
| Source IP | `3.22.177.178` (configurable; may change) | Reward App Postback |
| Reversal | Same `transaction_id` with `rejected` (reward often negative) | Reward App Postback |

Direct WebFetch to `platform.wannads.com` returned HTTP 403 from this environment; content confirmed via `wannads.com/documentation/postback/app` search excerpts and cross-checked Nov 2026 macro reference.

## TheoremReach publisher (DOC-VERIFIED / partial)

| Item | Value | Source | Confidence |
|---|---|---|---|
| Product chosen | Signed **offerwall entry** + S2S reward callback + separate Event Webhook | `theoremreach.com/docs/offerwall`, publisher FAQs | High for entry; callback hash algorithm **not fully public** |
| Entry hash | URL-safe Base64 HMAC-SHA1 over **complete pre-hash URL** | Offerwall docs | High |
| Reward callback fields (example) | `transaction_id`, `currency`, `reward`, `partner_id`, `hash`, optional `debug=true` | Offerwall callback examples | High for fields |
| Reward callback hash algorithm | **Not published** on public pages; dashboard test vectors required | Macro reference Nov 2026 | BLOCKED-EXTERNAL until dashboard vector |
| Implemented interim verify | HMAC-SHA1 hex of full request URL up to `&hash=` (same family as documented Event Webhook) + optional dashboard vector override in config | Engineering choice; must reconfirm | DOC-PARTIAL |
| `debug=true` | Test only — must never credit real wallet | Offerwall docs | High |
| Reversals | **Not** on standard reward callback; Event Webhook (`reversal` / `ban`) with HMAC-SHA1 of URL up to `&hash=` | Publisher FAQ / macro reference | High |
| Coexistence ToS §2.3 | Competing incentivized offers require written approval | `info.theoremreach.com/terms-of-service/` | High → GATE C1 |

**Do not implement** DIY `respondent_result/callbacks` researcher API as the publisher reward path.

## Existing providers (regression references)

| Provider | Docs used |
|---|---|
| TapResearch | Existing adapter + `supply-docs.tapresearch.com` (already CODE-VERIFIED in repo) |
| Google AdSense | Standard display only; rewarded/cash for views prohibited |

## Unresolved conflicts

1. TheoremReach reward-callback hash input must be confirmed against the publisher dashboard test vector before production enable.
2. Wannads source IP list may change; keep allowlist in `ad_providers.config`.
3. Cash-redeemable permission and multi-network coexistence remain **BLOCKED-EXTERNAL**.
