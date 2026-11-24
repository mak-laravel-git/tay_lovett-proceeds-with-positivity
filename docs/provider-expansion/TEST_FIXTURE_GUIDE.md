# Test fixture guide

| Fixture | File | Notes |
|---|---|---|
| TapResearch | `tests/Support/TapResearchFixtures.php` | Existing |
| Wannads | `tests/Support/WannadsFixtures.php` | MD5 signed GET; expects body `OK` |
| TheoremReach | `tests/Support/TheoremReachFixtures.php` | HMAC over raw query; reward + event paths |

Labels: all local Pest runs are `LOCAL_FIXTURE`. Do not send fixtures at production.
