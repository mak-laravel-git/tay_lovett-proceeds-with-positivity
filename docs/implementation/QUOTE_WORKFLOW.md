# Quote workflow and Google ads: setup and operation

Flow: **rewarded ad → survey → AI quote → rewarded ad → publish**. Every step and every fallback is set in the
admin panel. Regular (display) Google ads are separate and keep working when the workflow or rewarded ads are off.

## Deploying

1. Run `php artisan migrate`. The new tables are required by every page, so run it as part of the same release.
   The migrations only add tables and two nullable columns. The old `ad_settings.gate_quote_generation` column is
   left in place and no longer read; its value is copied to the new "Require a survey" setting.
2. Build the front end (`npm run build`).
3. Keep the scheduler running: `workflow:maintain` runs hourly (expires abandoned runs, prunes logs).

## First-time configuration (admin panel)

| Page | What to set |
| --- | --- |
| Advertising → Providers | Survey provider credentials, enable, order, compliance flags. TapResearch also has base URL and timeout. |
| Google ads → Settings | Rewarded / regular switches, AdSense client ID, network code, test mode, `ads.txt`. |
| Google ads → Ad units | Rewarded units (Ad Manager only) and regular units with their page location. |
| Quote workflow → Settings | Step switches, continue/block rules, publish mode, survey points, daily skip limit. |
| AI Management | A working AI route and daily budget, as before. |

Both Google switches start **off**. Until rewarded ads are switched on, both ad steps are skipped. The survey step is
on by default, so with no usable survey provider users stay on it unless "Survey API failed" is set to continue.

**Test mode** serves Google's sample Ad Manager units (`/22639388115/rewarded_web_example`, `/6355419/Travel`) and
marks AdSense requests with `data-adtest`.

## Default rules

| Condition | First ad | Second ad | Survey |
| --- | --- | --- | --- |
| Nothing available | continue | block | block |
| Failed to load / API error | block | block | block |
| Closed early / screened out | block | block | retry |

At most 3 "continue" skips per user per day (0 = unlimited). Publish mode: automatic. Survey points: off.

A run keeps the rules it started with. An administrator can release a waiting quote or cancel an unstarted run under
Quote workflow → Runs.

## Logs

| Page | Content |
| --- | --- |
| Logs → Rewarded ads | Every ad attempt and how it ended. |
| Logs → Activity & errors | User steps, errors, fallbacks and configuration changes. |
| Advertising → Provider requests | Calls to survey APIs with a redacted response excerpt. |
| Advertising → Survey activity / Callbacks | Survey transactions and signed postbacks. |

Regular ad impressions and revenue are only in Google's dashboards.

## Limits you should know about

- **Rewarded ad completion is reported by the browser.** The server enforces ownership, event order, a minimum watch
  time, expiry and one-time use, but a determined user can still forge it. Surveys are verified by signed provider
  postbacks. Ad Manager offers a server-side verification URL for web units; matching its callback to one attempt is
  not documented for web, so it is not implemented.
- **Ads never award points or money.** With "also award points" on, an ad leads to a cash-out reward, which may
  breach Google's rewarded ad policy.
- **Google approval is not guaranteed**: the site pays for surveys, and AdSense forbids compensating users for
  viewing ads.
- Regular ads are deliberately not placed in dialogs, beside an open survey, or on admin and sign-in pages. Adding a
  new location needs code (`App\Enums\AdLocation` and a `<DisplayAdSlot>`).
- Visitors in the EEA, UK and Switzerland need a Google-certified consent message, enabled in the Google account.

## Verification status (2026-11-26)

Verified: automated tests for the flow, rules, abuse cases and admin pages; a headless Chrome run of the user flow
and admin pages with stand-ins for Google, the survey feed and the AI provider; Google's public sample rewarded unit
through the application (ready → granted → closed).

**Not verified** (needs real accounts): live ad fill and revenue, AdSense rendering, a real survey postback, a real
AI generation in this flow, and the migrations on a MySQL server.

Before going live: confirm with each survey provider that surveys without points are allowed, update the Privacy
Policy (admin legal editor), publish `ads.txt`, enable the consent message, and turn test mode off.

## Code map

- `app/Services/QuoteWorkflow/` state machine, fallback rules, ad attempts, presenter, logger
- `app/Http/Controllers/Studio/WorkflowController.php` JSON endpoints under `/studio/workflow`
- `resources/js/components/quote-workflow/`, `resources/js/lib/rewarded-ad.ts` user interface and GPT rewarded ad
- `resources/js/components/ads/display-ad-slot.tsx`, `app/Services/GoogleAds/DisplayAds.php` regular ads
- `tests/Feature/QuoteWorkflow/` tests

## Official references

- [Rewarded ads for web](https://support.google.com/admanager/answer/9116812)
- [Policies for ad units that offer rewards](https://support.google.com/admanager/answer/7496282)
- [GPT rewarded sample](https://developers.google.com/publisher-tag/samples/display-rewarded-ad)
- [AdSense program policies](https://support.google.com/adsense/answer/48182)
- [Consent management requirement](https://support.google.com/adsense/answer/13554116)
