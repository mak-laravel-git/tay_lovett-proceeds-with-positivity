# Google Ads Placement

Planning artifacts for **Google AdSense display placements** (quotes, surveys, offerwalls).  
**Status:** SUPERSEDED (2026-11-26) — regular ads are implemented as admin-managed ad units. See
[docs/implementation/QUOTE_WORKFLOW.md](../implementation/QUOTE_WORKFLOW.md). The plan below is kept for history;
its slots inside the survey dialog and beside each survey option were **not** built, because AdSense forbids ads in
pop-ups and such placements invite accidental clicks.

## Documents

| File | Purpose |
|---|---|
| [PLACEMENT_PLAN.md](./PLACEMENT_PLAN.md) | What to build, placement IDs, phases, config, tracking gaps, risks |
| [TEST_PLAN.md](./TEST_PLAN.md) | Automated regressions + manual QA matrix (GAP-01..10) |

## Related code (current state)

- Placeholder adapter: `app/Services/Ads/Providers/GoogleAdSense/GoogleAdSenseAdapter.php`
- Placeholder presenter: `resources/js/components/ads/google-adsense-presenter.tsx`
- Reward chain skip: `app/Services/Ads/AdProviderManager.php` (`not_reward_provider`)
- Provider expansion context: `docs/provider-expansion/`

## Hard rules (do not break when implementing)

1. AdSense is **standard display ads only** — never a rewarded earn / points source.
2. Earn Rewards page must continue to show only TapResearch, Wannads, TheoremReach as cash/points cards.
3. No wallet/points credit from AdSense impressions (`UserRevenueShare` already excludes this model).

## Next step

Execute [PLACEMENT_PLAN.md](./PLACEMENT_PLAN.md) Phase 1 in Agent mode when credentials and slot IDs are available; validate with [TEST_PLAN.md](./TEST_PLAN.md).
