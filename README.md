# AI Voice Agent Dashboard

A client-facing operations dashboard and CRM for an outbound voice-agent
campaign — built on Laravel 12 with the existing Zentic Bootstrap theme.

The voice agent itself (speech recognition, reasoning, speech synthesis,
turn-taking, knowledge base, tools and telephony) lives entirely in the voice
platform and is **not** reimplemented here. This application is the control,
CRM, reporting and automation layer around it.

```
Browser
   ↓
Laravel application  (auth, CRM, campaigns, reporting)
   ↓
App\Services\SarvamVoiceService   ← the only code that talks to the vendor
   ↓
Voice Agents REST API → existing agent → telephony → customer

Completion webhook → /api/webhooks/sarvam/{token} → database → dashboard
```

The vendor is never exposed to the client: no branding, URLs, credentials or
payload shapes reach the browser.

---

## Requirements

| | |
|---|---|
| PHP | 8.2+ with `zip`, `gd`, `pdo_sqlite` (dev) or `pdo_mysql` (production) |
| Composer | for dependencies (`phpoffice/phpspreadsheet` handles .xlsx/.xls) |
| Database | SQLite for development; MySQL/PostgreSQL in production via env |
| Queue worker | required — imports and campaign dispatch run as queued jobs |
| Cron | required — the scheduler drives daily automations |

---

## Configuration

All credentials are environment variables. **They are never written to the
database, rendered into Blade, sent to JavaScript, or written to logs.**

```dotenv
SARVAM_API_KEY=
SARVAM_BASE_URL=https://apps.sarvam.ai
SARVAM_ORG_ID=
SARVAM_WORKSPACE_ID=
SARVAM_APP_ID=
SARVAM_APP_VERSION=
SARVAM_CONNECTION_ID=
SARVAM_AGENT_PHONE_NUMBER=
SARVAM_CAMPAIGN_ID=
SARVAM_WEBHOOK_TOKEN=
APP_TIMEZONE=Asia/Kolkata

# Publicly reachable base URL the platform posts call results back to.
# Falls back to APP_URL when blank.
SARVAM_WEBHOOK_URL=

# Conservative calling defaults (all optional)
SARVAM_ATTEMPTS_PER_SECOND=1.0
SARVAM_MAX_CALLS_PER_RUN=200
SARVAM_DEFAULT_LANGUAGE=Hindi
SARVAM_WINDOW_START=09:00
SARVAM_WINDOW_END=19:00
```

### The API key must be a **Voice Agents** key

The Voice Agents API on `apps.sarvam.ai` authenticates with the **`X-API-Key`**
header — *not* the `api-subscription-key` header used by the model API on
`api.sarvam.ai`. The two products also use **different keys**:

| | Model API (`api.sarvam.ai`) | Voice Agents (`apps.sarvam.ai`) |
|---|---|---|
| Header | `api-subscription-key` | `X-API-Key` |
| Key | subscription key (`sk_…`) | key created in the Voice Agents dashboard |

A model-API `sk_…` key put into `SARVAM_API_KEY` will authenticate happily
against `api.sarvam.ai` while `apps.sarvam.ai` rejects it with:

```json
{"error":{"message":"(401) Unauthorized","data":{"details":"Invalid API key format."}}}
```

Create the right key in the Voice Agents dashboard under **Settings → API Key**.

`SARVAM_WEBHOOK_TOKEN` should be a long random string:

```bash
php -r "echo bin2hex(random_bytes(24));"
```

`config/sarvam.php` reads every one of these and is also the **single place**
that defines:

- **`agent_variables`** — which customer field feeds which agent variable.
- **`output_variables`** — which agent output variable maps to which column.
- **`connectivity_map` / `disposition_map`** — how vendor statuses become our
  canonical vocabulary. An unrecognised disposition becomes `unknown`, never a
  negative outcome.
- **`import_aliases`** — heading synonyms used to auto-suggest a column mapping.

Retune any of these without touching code.

---

## Installation

```bash
composer install
cp .env.example .env
php artisan key:generate
# fill in the SARVAM_* values
php artisan migrate --seed      # creates the admin user and the renewal automation
```

Development:

```bash
php artisan serve
php artisan queue:work          # required — imports/dispatch are queued
```

---

## Production deployment

**1. Queue worker** — imports and campaign dispatch are queued, so a worker must
be running. With supervisor:

```ini
[program:voice-agent-worker]
command=php /path/to/app/artisan queue:work --sleep=3 --tries=1 --timeout=1800
autostart=true
autorestart=true
numprocs=1
```

**2. Scheduler** — one cron entry drives every automation:

```cron
* * * * * cd /path/to/app && php artisan schedule:run >> /dev/null 2>&1
```

The scheduler runs `calls:dispatch-due` daily at 08:00 Asia/Kolkata with
`withoutOverlapping()` and `onOneServer()`; it only *queues* work, so the tick
stays fast. Automations with a different `run_at` get their own daily tick
automatically.

**3. Webhook reachability** — the platform must be able to POST to:

```
https://your-domain/api/webhooks/sarvam/<SARVAM_WEBHOOK_TOKEN>
```

Set `SARVAM_WEBHOOK_URL` if the public host differs from `APP_URL`.

**4. Caches**

```bash
php artisan config:cache && php artisan route:cache && php artisan view:cache
```

---

## Operating the dashboard

| Page | What it does |
|---|---|
| **Dashboard** | Today's KPIs (live-polled while calls are in flight), all-time totals, 14-day call chart, outcomes, hot leads, campaign progress, upcoming callbacks, recent calls. |
| **My Agents / Create Agent** | Register agents that already exist in Sarvam (agent ID + version). New Call can pick one; calls are recorded against it. Blank agent ID = the workspace agent from `.env`. Instructions / first message are a reference copy only — they are not pushed to Sarvam. |
| **New Call** | Instant single call (same `/calls` endpoint). Bulk calling points to Imports → Campaigns. Inbound is marked Coming Soon. |
| **Campaigns** | Bulk runs with progress, completed / not reached / remaining; pause/resume/cancel. |
| **Customers** | Searchable CRM with latest call and outcome; detail page shows call history, campaigns and callback. CSV export (formula-injection safe). |
| **Imports** | Upload → map columns → queued processing → results with per-row validation errors. |
| **Leads** | Hot / Qualified / Follow-up tabs. Qualification = the agent's `lead_generated` output; next action from structured outputs only. |
| **Call Logs** | Every attempt, filterable by connectivity, outcome, agent and date. Detail shows metadata, result and transcript. |
| **Callbacks** | Overdue / today / week / upcoming, with reason, status and the previous call. |
| **Automations** | Trigger, action, matching count, status, last run; edit or create in a modal; preview (dry run) or run now. |
| **Analytics / Usage** | Range-based aggregates, calls over time, outcomes, campaign and per-agent performance; monthly calls and minutes. |
| **Providers / Phone Numbers** | Safe status only (Configured / Connected / Error / Not configured) derived from real call results. Never shows credentials. |
| **Knowledge Base / Tools** | Shown as *not connected* — they are managed on the agent in Sarvam, not in this app. |
| **Settings** | Tabs: Workspace, Agent, Calling, Providers, Automation, Usage. |

The client-facing pages (`/`, `/login`, dashboard, calling, leads, call logs) remain white-labelled: the vendor name and platform identifiers never appear there. Vendor details are shown only on the admin pages (Agents, Providers, Phone Numbers, Settings).

### Connectivity vs. outcome

These are deliberately separate columns:

- **Connectivity** — `connected`, `no_answer`, `busy`, `failed` (did the phone
  connect?)
- **Business outcome** — `interested`, `callback`, `not_interested`,
  `already_renewed`, `wrong_person`, `do_not_call`, `escalated`, `unknown`

The outcome comes **only** from the agent's explicit `call_disposition` output
variable. Transcript text is never parsed to guess a disposition, and an
unclear call is recorded as `unknown` rather than anything negative.

### Importing customers

Upload `.csv`, `.xlsx`, `.xls`, or a `.zip` containing exactly one of those.
The flow is upload → preview → **map columns** → queued import, so a client's
headings never have to match ours. Rows are reported as valid, rejected
(missing/malformed phone) or duplicate, with a per-row reason. Phone numbers
are normalised to E.164; day-first dates and Excel date serials are both
handled.

ZIP archives are checked for path traversal, executables, nested archives,
uncompressed size and compression ratio, extracted to a temp directory under a
generated name, and deleted after parsing.

---

## Commands

```bash
php artisan calls:dispatch-due               # run enabled automations
php artisan calls:dispatch-due --dry-run     # show who would be called, dispatch nothing
php artisan calls:dispatch-due --automation=1
php artisan calls:dispatch-due --force       # ignore the time-of-day window
```

`--dry-run` is the safe way to check a rule before it places real calls.

### Diagnosing the outbound integration

```bash
php artisan sarvam:test-outbound +919182790602            # sends one real call
php artisan sarvam:test-outbound +919182790602 --dry-run  # validate config only
```

Bypasses the application layer and calls the API directly, so a failure here
isolates configuration/credentials from controller code. It prints the resolved
config, the exact URL and payload, the upstream HTTP status and body, and the
`attempt_id` on success. The API key is never printed — only `configured: yes/no`
and its last 4 characters.

### Upstream error mapping

Each upstream status gets its own message and its own local HTTP status. A
misconfiguration is reported as a server error, not a gateway error:

| Upstream | Operator message | Returned to browser |
|---|---|---|
| 401 | Invalid or missing Sarvam API key. | 500 |
| 403 | API key does not have access to this organisation/workspace. | 500 |
| 404 | Configured Sarvam resource was not found. | 500 |
| 422 | Voice agent configuration is invalid. Check agent version, connection ID, caller number, or required variables. | 422 |
| 429 | Sarvam rate limit or usage limit reached. | 429 |
| 5xx | Sarvam service is temporarily unavailable. | 502 |
| unreachable | Could not reach the voice service. | 502 |

The full upstream body is written to `storage/logs/laravel.log` under
`sarvam.api`; the raw detail reaches the browser only when `APP_DEBUG=true`.

---

## Security

- Secrets live only in the environment; `SarvamVoiceService` is the sole holder
  of the API key and never logs it. Diagnostics log operation, path shape,
  status and duration only, with tenant IDs stripped.
- All browser forms are CSRF-protected. CSRF is **not** disabled globally — the
  webhook is stateless by living in `routes/api.php`.
- The webhook authenticates with a constant-time (`hash_equals`) path-token
  comparison, verifies `app_id` when present, validates payload shape, is rate
  limited to 600/min per IP, and is idempotent on `attempt_id`.
- Manual calls (30/min), uploads (20/min) and logins (10/min) are throttled;
  a double-submitted call modal is additionally blocked by an idempotency lock.
- Uploads are validated on both extension and MIME type.
- Transcripts and customer data are escaped in Blade — never rendered as HTML.
- CSV exports prefix cells starting with `=`, `+`, `-`, `@` to defeat formula
  injection.
- `do_not_call` is honoured in the UI, in campaign dispatch and in automations.

---

## Tests

```bash
php artisan test          # or: php vendor/bin/phpunit
```

112 tests, 291 assertions. Every test is hermetic — `Http::preventStrayRequests()`
fails any unmocked outbound request, so the suite never contacts a real API.

| Suite | Covers |
|---|---|
| `SarvamVoiceServiceTest` | Request shape against the documented API, auth header, error mapping, no-retry on 4xx, cohort chunk limit, rate clamping — all against mocked HTTP |
| `SarvamWebhookTest` | Token/app validation, payload validation, **idempotency** (replays don't duplicate attempts or double-count customers), status and disposition mapping |
| `CustomerImportTest` | Upload validation, column mapping, rejections/duplicates, E.164 + date parsing, ZIP safety (traversal, executables, nested archives) |
| `CallNowTest` | Validation, do-not-call refusal, double-submit protection, failure recording, and that no key/endpoint leaks into a response |
| `AutomationDispatchTest` | Eligibility filters, calling window, per-run caps, dispatch job behaviour |
| `PhoneNumberTest` / `CallStatusTest` | E.164 normalisation; outcome mapping never turns unclear into negative |

---

## Key files

```
app/Services/SarvamVoiceService.php   the only vendor-facing code
app/Services/CallResultProcessor.php  idempotent webhook application
app/Services/CustomerImporter.php     mapping, validation, dedupe
app/Services/SpreadsheetReader.php    csv/xlsx/xls + hardened ZIP
app/Services/DashboardMetrics.php     every dashboard number (all real aggregates)
app/Support/CallStatus.php            canonical connectivity + outcome vocabulary
app/Support/PhoneNumber.php           E.164 normalisation
app/Jobs/ImportCustomersJob.php
app/Jobs/DispatchCampaignJob.php
app/Console/Commands/DispatchDueCalls.php
config/sarvam.php                     credentials + all mappings
routes/api.php                        the single public webhook route
```
