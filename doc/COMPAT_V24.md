# Compatibility report — Dolibarr v24

- **Module:** scrumboard
- **Current version:** 2.8 → **released as 2.8.1**, then 2.8.3 (ticket fix below)
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

## Field feedback `[Ticket]`

### Time spent from a scrumboard tile — "Task is mandatory"

| # | Compat point | Status | Severity | Evidence / Fix |
|---|---|---|---|---|
| 1 | AJAX add-timespent resolved the task through the core hidden `id` field | **FIXED** | HIGH | `pop_time()` (`script/scrum.js.php:510`) loads the core `createtime` form then rebuilds the POST by hand, reading `input[name=id]`. Core resolves the task by `$id`/`$ref` **or** `taskid` only (`projet/tasks/time.php:235-244`); the hidden `id` of the timespent list form was dropped in v23 (core `8a2faed41f7`, re-added on branch 24.0 by `c4375354fc7` on 2026-05-30 only), while the `createtime` block emits `taskid` (`time.php:1820`) which the module never posted. Result: `id` undefined → 0, no `taskid` → `ErrorFieldRequired("Task")`. **Fix:** payload now sends `id` **and** `taskid` from the `id_task` argument already passed to `pop_time()`, so it no longer depends on any core hidden field (works v23 → v24). |

Residual (not part of this ticket): `pop_comment()` (`script/scrum.js.php:611`) reads `input[name=id]` from
`projet/tasks/comment.php` the same way — same fragility if that page loses the field.

## Baseline
- `php -l` sweep: clean (0 syntax errors)
- Descriptor sanity: version `2.8` → `2.8.1` (`core/modules/modscrumboard.class.php:66`)

## Summary
- ChangeLog v24: 9 items, **0 affected**
- Extra `csrf-token`: 4 points, **1 affected → 1 fixed** (JS-built add-resource link)
- Release: **2.8 → 2.8.1**
- Field ticket: **1 affected → 1 fixed** (timespent `taskid`) → **2.8.3**
