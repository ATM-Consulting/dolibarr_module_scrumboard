# Compatibility report — Dolibarr v24

- **Module:** scrumboard
- **Current version:** 2.8 → **released as 2.8.1**
- **Target:** Dolibarr 24.0
- **Branch:** FIX/COMPATV24 (based on the latest version branch `2.8`)
- **Date:** 2026-06-03
- **Scope:** ChangeLog v24 WARNING items (9) + complementary check `csrf-token`

## Findings — ChangeLog v24 (9 items)

| # | Breaking change (v24) | Status | Severity | Evidence |
|---|---|---|---|---|
| 1-9 | USF / Login API / signature salt / PAYMENT_SECURITY_TOKEN_UNIQUE / DEPOSIT rename / __MYCOUNTRY_ID__ / info_admin hook / jeditable / Paybox | N-A | — | No occurrence of any (no `fetchAll`, no Paybox/jeditable) |

## Complementary checks `[Extra]`

### csrf-token — MAIN_SECURITY_CSRF_WITH_TOKEN = 3 (default in v24)

| # | Compat point | Status | Severity | Evidence / Fix |
|---|---|---|---|---|
| 1 | POST form missing the CSRF token field | N-A | — | `admin/scrumboard_setup.php` 2↔2, `scrum.php` 5↔5, `script/scrum.js.php` emits AJAX tokens |
| 2 | State-changing GET action link without a token | **FIXED** | LOW | `script/scrum.js.php:272` built a JS `href` to `action=addressourcetotask` with no token. Normal click is intercepted by a handler doing a tokenized AJAX (`pop_contact`, :486), so the workflow worked — but the bare href 403s on direct navigation and was inconsistent with the tokenized story equivalent (`scrum.php:937`). **Fix:** appended `&token='.newToken().'` to the built href (mirror of :937). |
| 3 | List / mass-action form without a token | N-A | — | No `massaction` |
| 4 | Module emits no token at all | N-A | — | Tokens widely emitted (forms + AJAX) |

Note: this finding was a **JS-built** action link (`.attr("href", "...action=...")`), which a literal
`href=` grep misses — the `csrf-token` detection was extended to scan `.js`/`.js.php` for
`.attr("href"` / `location.href` / `url:` containing `action=`.

## Baseline
- `php -l` sweep: clean (0 syntax errors)
- Descriptor sanity: version `2.8` → `2.8.1` (`core/modules/modscrumboard.class.php:66`)

## Summary
- ChangeLog v24: 9 items, **0 affected**
- Extra `csrf-token`: 4 points, **1 affected → 1 fixed** (JS-built add-resource link)
- Release: **2.8 → 2.8.1**
