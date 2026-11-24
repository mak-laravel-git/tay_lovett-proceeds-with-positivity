# Provider compliance gates

**Defaults:** all gates unverified / `production_enabled = false`.

## Gate checklist

| Gate | Provider | Requirement | Status |
|---|---|---|---|
| C1 Coexistence | TheoremReach | Written authorization to run alongside TapResearch + Wannads | PENDING / BLOCKED-EXTERNAL |
| C2 Cash redeemable | Wannads, TheoremReach, TapResearch | Account permits cash-withdrawable rewards (vs points-only) | PENDING — app currently credits **points** + optional sandbox pending wallet share |
| C3 Site compliance | All | Privacy, Terms, consent, support contact, anti-abuse | OWNER responsibility |
| C4 AdSense separation | Google AdSense | No cash CTA / no click-to-earn | ENFORCED in code (adapter never awards) |
| C5 Settlement / fraud | All | Holds, no payout of test/reversed | Test/debug events never credit; reversals reverse points once |

## Admin config fields (stored in `ad_providers.config`)

```json
{
  "approval_status": "unverified",
  "approved_domain": null,
  "approved_product": null,
  "reward_cashout_permission": false,
  "concurrent_provider_permission": false,
  "source_document_link": null,
  "verified_by": null,
  "verified_at": null,
  "review_at": null,
  "production_enabled": false,
  "notes": null,
  "allowed_callback_ips": ["3.22.177.178"]
}
```

TheoremReach user launch requires `concurrent_provider_permission = true` **or** non-production sandbox fixture mode. Enabling the provider switch alone is insufficient.
