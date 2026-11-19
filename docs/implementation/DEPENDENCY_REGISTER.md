# DEPENDENCY REGISTER

**Updated:** 2026-11-19  
**Rule:** Record whether a dependency exists / is reachable — never paste secret values.

## Runtime

| Dependency | Declared | Local status | Production readiness |
|---|---|---|---|
| PHP ^8.3 (running 8.4.27) | composer | Available | OK for local |
| Node 24 + Vite Plus | package.json | Available | OK for local builds |
| SQLite (default) | `.env.example` | Migrations Ran | Staging may use MySQL — not assumed |
| Composer packages | `composer.lock` | Installed (`vendor/` present) | — |
| pnpm packages | `pnpm-lock.yaml` | Assumed installed | Run `pnpm install` if needed |

## Application packages (selected)

| Package | Role |
|---|---|
| `laravel/framework` ^13 | App framework |
| `inertiajs/inertia-laravel` ^3 + `@inertiajs/react` | SPA-like pages |
| `laravel/fortify` | Auth |
| `laravel/ai` | Quote generation SDK |
| `laravel/wayfinder` | Typed frontend routes |
| `pestphp/pest` | Tests |
| `symfony/html-sanitizer` | Content safety |
| Tailwind 4 + Radix/shadcn | UI |

## External services

| Service | Config surface | Secret storage | Status |
|---|---|---|---|
| OpenAI / AI providers | Admin AI credentials + `.env` patterns | Encrypted DB credentials / env | Local/test only until budgeted |
| Mail / SMTP | App settings + `MAIL_*` | Env / settings | Example uses `log` — **not production-ready** |
| TapResearch | Admin Ads → Providers | Encrypted provider settings | Code ready; account **BLOCKED-EXTERNAL** |
| Google AdSense | Provider adapter | Publisher IDs | Placeholder; site approval **BLOCKED-EXTERNAL** |
| Wannads / TheoremReach | — | — | Not integrated |
| PayPal / Cash App payouts | Withdrawal destination label only | Manual | Automation **BLOCKED-EXTERNAL** |
| Redis | `REDIS_*` | Env | Optional; prior note: local Redis not reachable |
| AWS S3 | `AWS_*` | Env | Optional; local disk default |

## Feature flags / modes observed

| Mode | Where | Notes |
|---|---|---|
| Demo ad sessions | `AdController` | No earnings |
| Local callback simulate | `dev.ads.simulate` when `APP_ENV=local` | Never register in non-local |
| Quote generation gate | `ad_settings.gate_quote_generation` | Kill switch for survey-before-generate |
| Provider enable/order | Admin providers UI | Fallback chain skips unconfigured |
| Maintenance mode | Admin settings | File driver in example |

## Client-owned external checklist (release gates)

- [ ] Production domain + HTTPS  
- [ ] VPS / hosting  
- [ ] Production SMTP  
- [ ] TapResearch (or chosen) publisher approval + reward terms in writing  
- [ ] AdSense publisher approval (owner revenue only)  
- [ ] PayPal / Cash App business eligibility if automated payouts desired  
- [ ] Counsel-approved Privacy / Terms  
- [ ] Confirmed 1/3 vs 33.33% and gross/net rules (`REVENUE_RULES_DECISIONS.md`)
