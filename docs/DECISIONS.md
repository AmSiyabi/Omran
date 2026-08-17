# سجل القرارات المعمارية — Architecture Decision Log

Running log of decisions the build spec did not make, or where the spec was adapted.
Newest entries at the bottom. Every entry: date, decision, rationale.

---

## 2026-08-15 — Phase 0

### D-001 · Exact stack versions at scaffold time
Verified against Packagist/npm on build day (spec §0 rule 5 — do not trust memory):

| Component | Version |
|---|---|
| PHP | 8.5.9 (Docker `php:8.5-cli`) |
| Laravel framework | 13.25.0 (skeleton `laravel/laravel` 13.9.0, requires PHP ^8.3) |
| Livewire | 4.4.0 |
| Tailwind CSS | 4.3.3 |
| Vite | 8.2.1 |
| Pest | 5.1.1 (+ pest-plugin-laravel 5.0) |
| Larastan | 3.10.0 (level 6) |
| Pint | 1.30.5 |
| MySQL | 8.4 LTS (Docker) |
| Redis | 7 (alpine, Docker) |
| Node | 24.12.0 (host, asset builds only) |

### D-002 · Database: MySQL 8.4 LTS
Spec §3 allows MySQL 8+ or PostgreSQL 15+ and demands a single choice.
Chosen: **MySQL 8.4 LTS**. No abstraction over both.

### D-003 · App runtime is Docker-only; host PHP unusable
Host has PHP 8.2 (XAMPP); Laravel 13 requires ^8.3. All PHP (composer,
artisan, tests) runs inside the `omran-app` image (php:8.5-cli + pdo_mysql,
bcmath, intl, zip, gd, exif, pcntl, opcache, redis + Composer 2).
Dev server: `php artisan serve` inside the container. FrankenPHP/Octane is a
Phase 8 production decision, deliberately not taken now.

### D-004 · Non-default local ports
Another project on this machine occupies 80, 3306, 5173, 6379.
Omran uses: **app 8080** (→8000 in container), **MySQL 3307**, **Redis 6380**,
**Vite dev 5174**. `APP_URL=http://localhost:8080`.

### D-005 · Design tokens live in CSS, not tailwind.config.js
Spec §13 (Phase 0) says "design tokens in `tailwind.config.js`". Tailwind 4 is
CSS-first: tokens are defined in `resources/css/app.css` under `@theme`, which
is the v4-native equivalent. No `tailwind.config.js` exists. Adaptation logged
per spec §0 rule 5.

### D-006 · Real brand assets override the spec's fallback palette and type
Read from `/brand/` (spec §5.1 says real assets win):
- **Palette:** Navy `#16202F`, Ink Navy `#1A2536`, Deep Navy `#0E1622`,
  Gold `#CDA34F`, Light Gold `#E4C67E`, Deep Gold `#B8893A`, Cream `#F4EFE3`,
  Warm Cream `#F1E7D2`, Slate `#6A7383`. The spec's fallback navy/blue palette
  (§5.1) is **not used**.
- **Type:** El Messiri (display) + Tajawal (body) per `brand/typography.png`,
  overriding the spec's §5.3 suggestions (Noto Kufi / IBM Plex Sans Arabic).
- Derived tokens not in the brand sheet: `--color-line #E4DCC9` (borders on
  cream) and four state colors (success/error/warning/info) harmonized with
  the palette.
- The four-pointed star from the logo's م is the only decorative motif
  (empty states, hero mark).

### D-007 · Font strategy: self-hosted per-script subsets, 102,960 bytes total
Google Fonts' per-script subset files (arabic + latin, woff2 only) self-hosted
in `public/fonts/`: El Messiri as a **variable font 400–700** (one file per
script), Tajawal at 400/500/700. Total ≈ 100.5 KB — under the 120 KB budget
(§5.3) — enforced in CI. **Amiri and Reem Kufi (brand sheet: Quran/poetry and
numerals/ornament) are deferred** until a screen actually needs them, to
protect the budget; revisit in Phase 3.
Western digits render from the Latin subsets per §5.3.

### D-008 · Redis client: phpredis extension
Compiled into the app image; faster than predis and removes a Composer
dependency. `REDIS_CLIENT=phpredis`.

### D-009 · Pest replaces PHPUnit; RTL rule enforced twice
`phpunit/phpunit` removed from require-dev (Pest 5 supplies it). The
physical-direction Tailwind ban (§6.2) is enforced in two independent layers:
`tests/Unit/RtlComplianceTest.php` (PHP scan, runs with every test run) and
`scripts/check-rtl.sh` (grep, runs in CI). Both were probe-tested with a
deliberate violation. Note: GNU grep `-P` accepts only a single pattern, so
the script uses one combined alternation.

### D-010 · Livewire assets included explicitly in the base layout
`@livewireStyles` / `@livewireScripts` are placed in
`components/layouts/base.blade.php` rather than relying on auto-injection, so
Livewire's bundled Alpine is available on pages **without** Livewire
components — the modal and toast components are Alpine-driven from Phase 0.
Toast logic lives in `resources/js/app.js` as a plain global (no inline
scripts → CSP-ready for Phase 1's security headers).

### D-011 · Timezone split
`APP_TIMEZONE=UTC` (storage), `APP_DISPLAY_TIMEZONE=Asia/Muscat` added to env
now so the display-layer helper (later phase) has a single source of truth.

### D-012 · Acceptance screenshots via puppeteer-core, not Chrome's --screenshot CLI
Chrome's headless `--screenshot --window-size=375,…` enforces a minimum window
width (~415px) and silently clips the capture — it produced a false "layout
overflow" at 375px. `scripts/screenshot.mjs` (dev-dependency `puppeteer-core`,
drives the system Chrome) emulates exact viewports, asserts
`scrollWidth == innerWidth`, and captures full-page screenshots. Use it for
every phase's responsive acceptance run.
Addendum (Phase 1): pass `captureBeyondViewport: false` — full-page capture of
RTL pages with fixed elements produces a shifted/clipped image otherwise.
Always confirm suspected overflow by measuring `scrollWidth`, not by eyeballing
screenshots.

---

## 2026-08-15 — Phase 1

### D-013 · Session driver: database, not Redis
Spec §3 lists Redis for sessions, but §9.6 requires an active-session list
with per-session revoke — that needs enumerable sessions keyed by user, which
the Redis driver does not provide. Sessions use the `database` driver (the
skeleton's `sessions` table has `user_id`, `ip_address`, `user_agent`,
`last_activity`); cache and queue remain on Redis.

### D-014 · Fortify over Breeze; passkeys disabled
Fortify 1.38 chosen (headless — all views are our own Arabic Blade; built-in
TOTP 2FA with confirmation + recovery codes). The registration feature is
disabled (owners create users), and Fortify 1.38's new passkey support is
disabled (spec §9.5 mandates TOTP; passkeys can be revisited post-v1).
Fortify encrypts `two_factor_secret` / `two_factor_recovery_codes` itself, so
no Eloquent `encrypted` cast is added for them (double encryption would break
Fortify's reads) — §10's requirement is satisfied by Fortify's own layer.

### D-015 · activitylog v5 API + PII kept out of the audit trail
spatie/laravel-activitylog 5.x moved `LogsActivity` to
`Spatie\Activitylog\Models\Concerns` and `LogOptions` to `Support`, renamed
`dontSubmitEmptyLogs()` → `dontLogEmptyChanges()`, and stores diffs in a
dedicated `attribute_changes` column instead of `properties`.
Partner `bank_name` / `bank_account` / `civil_number` are excluded from
activity logging entirely: the logger reads casted (decrypted) values, so
logging them would write plaintext PII into `activity_log`.

### D-016 · Login rate limiting: two layers
Configuring a named `login` limiter makes Fortify silently drop its internal
5-attempt check from the login pipeline (AuthenticatedSessionController line
~86). The pipeline is therefore declared explicitly via
`Fortify::authenticateThrough()` to restore it. Result:
- Inner (Fortify): 5 attempts per email+IP → friendly Arabic lockout message
  on the form (spec §9.6's "5 per minute").
- Outer (middleware `throttle:login`): escalating hard caps 10/min, 20/15min,
  40/hour per email+IP → HTTP 429 (approximates the spec's "exponential
  backoff"). Both layers are covered by tests.

### D-017 · Livewire components are class-based (v3 style)
Livewire 4 defaults to single-file components (PHP inside Blade). We use
`app/Livewire/**` class components + `resources/views/livewire/**` views so
every component action is covered by Larastan level 6 and Pint — the spec's
authorization rules (§9.4) are enforceable by static analysis only if the PHP
lives in `app/`.

### D-018 · Role changes audited via spatie/laravel-permission events
`permission.events_enabled = true`; `App\Listeners\LogRoleChange` writes
role attach/detach into the activity log (log name `roles`) with the role
names resolved. Auto event discovery registers the listener (union type-hint
covers both events). Note: `DatabaseSeeder` must NOT use `WithoutModelEvents`
or seeded role grants would go unlogged.

### D-019 · CSP consequences honored in our own markup
The CSP has no `unsafe-inline` for styles, which also blocks `style=""`
attributes (style-src-attr falls back to style-src). The mobile bottom-nav
column count is therefore expressed with `grid-cols-{n}` class variants
instead of an inline style. `'unsafe-eval'` remains in script-src — Alpine's
expression evaluator requires it; inline scripts/styles stay nonce-only.
Category accent dots use SVG `fill` attributes (not CSP-restricted) instead
of inline background-color styles.

---

## 2026-08-15 — Phase 2

### D-020 · cohorts.distribution_policy_id has no FK until Phase 5
Spec §7.2 declares the column as an FK to `distribution_policies`, but that
table belongs to the Phase 5 financial core and Phase 2 explicitly must not
build finance. The column exists now (nullable, indexed); Phase 5 creates the
table, seeds the three contract policies, and adds the constraint.

### D-021 · Categories/instructors/clients ride on courses.* permissions
Spec §9.2 defines no permissions for these support entities. They follow
`courses.view/create/update/delete`: every role that can see the catalog can
see them; coordinators can create/update; only owner/admin can delete.
Clients are cohort-related but managed by the same people who manage courses.

### D-022 · Models use the legacy `$casts` property, not the `casts()` method
Larastan 3.10 does not read Laravel 13's `casts(): array` method — every
enum/datetime cast was silently typed as `string`, producing dozens of false
level-6 errors. The `$casts` property is fully supported by both Laravel and
Larastan, so it is the project convention. Revisit when Larastan supports the
method form.

### D-023 · Course covers via medialibrary on a symlink-free `media` disk
Course cover uploads use spatie/laravel-medialibrary with non-queued
conversions: AVIF + WebP at 480/960/1440 widths plus a WebP thumb (GD in the
container supports both formats — verified). The `media` disk is rooted at
`public/media` directly because `storage:link` symlinks are unreliable on the
Windows bind mount. The spec's `courses.cover_image_path` column exists but
is unused; the media table is the source of truth. Conversions are non-queued
deliberately: the owner sees the result immediately and no worker process is
required for correctness.

### D-024 · Deliverer weight validation uses integer hundredths
"Weights must sum to 100.00" is checked by summing `round(weight × 100)` as
integers — no float accumulation. Weights like 33.33 + 66.67 pass; 60 + 30
fails with the Arabic message (Phase 2 acceptance). Rows are replaced
atomically in a transaction.

### D-025 · Faker realText() banned from factories
`fake()->realText()` with the Arabic locale builds a huge Markov table and
exhausted the test-suite memory limit. Factories use fixed Arabic copy;
`phpunit.xml` also raises `memory_limit` to 512M for the growing suite.

---

## 2026-08-15 — Phase 3

### D-026 · Public pages are Livewire-free
The public site uses plain controllers + Blade. Its entire JS is
`resources/js/public.js` (0.35KB gzipped): IntersectionObserver reveals and
the mobile nav toggle. Reveal hiding is gated on an `html.js` class set by a
nonce'd inline script, so no-JS users (and crawlers) see full content.
Category filtering is server-side GET links — SEO-crawlable, zero JS.

### D-027 · Laravel 13's @context Blade directive vs JSON-LD
Laravel 13 ships a `@context` directive; a literal `"@context"` inside a
JSON-LD script compiles into an unclosed `if` and breaks the view. All
schema.org keys in Blade are written `@@context` / `@@type` (Blade escape).

### D-028 · robots.txt is a route, not a static file
The `Sitemap:` line must be an absolute URL; a static file cannot know the
host. `/robots.txt` renders from the route table using `route('sitemap')`.

### D-029 · Generated brand assets
The OG image (1200×630, Arabic, on-brand) is rendered from an HTML template
by headless Chrome (`scripts/og-image.mjs`) — re-run it when the thesis line
changes. Public-site logos are downsized WebP variants
(`scripts/optimize-logos.php`, ~15KB vs ~60KB PNG originals); admin keeps the
PNGs.

### D-030 · Dev-server performance findings (measurement honesty)
Two Windows-bind-mount pathologies fixed in the dev image: CLI OPcache is off
by default under `artisan serve` (every request re-parsed the framework), and
`opcache.revalidate_freq=1` caused a ~1.8s stat storm across ~700 files once
per second (now 15s; PHP edits may take up to 15s to appear).
Lighthouse on the landing page: **SEO 100, Best-Practices 100, A11y 96,
Performance 93, CLS 0, TBT 0** with all assets downloaded in <240ms observed.
The remaining gap to the §11.5 budgets (perf ≥95, LCP <1.5s vs 2.9s
simulated) is entirely serving infrastructure: `artisan serve` sends
uncompressed responses over HTTP/1.1 (67KB CSS instead of ~10KB Brotli).
Compression + HTTP/2 are Phase 8 production-runtime work, where the budgets
get enforced in CI per the spec.

### D-031 · "أعمالنا" page added at owner request
Not in the spec's Phase 3 build list. Shows delivered/settled cohorts of
published courses, client names, and live counters. Empty-state friendly
until real deliveries exist.

---

## 2026-08-15 — Phase 4

### D-032 · Token length: 32 chars (spec §7.3 wins over §10)
§7.3 specifies a 32-char token, §10 says base62-encode 32 random bytes
(~43 chars). Implemented 32 base62 chars from `random_bytes` (~190 bits) —
comfortably non-enumerable, matches the schema definition.

### D-033 · Approval-required registrations do not hold a seat
The spec is silent on whether a pending (approval-required) registration
reserves capacity. Decision: it does not — the seat decision happens at
approval time, under the same row locks, granting Confirmed or Waitlisted by
live capacity. Prevents unreviewed spam requests from blocking real seats.
Statuses holding seats: confirmed / attended / no_show. The `approved` enum
value exists per schema but is unused by the current flow.

### D-034 · No auto-promotion from the waitlist
When a confirmed enrollment is cancelled the seat frees up, but nobody is
promoted automatically — the owner/coordinator approves a waitlisted person
explicitly (bulk approve makes this quick). Automatic promotion would email
people without human review; revisit post-v1 if cadence demands it.

### D-035 · Concurrency proven with forked processes, not simulated
`php artisan app:concurrency-probe` forks 50 real OS processes (pcntl inside
the container), each with its own MySQL connection racing on the same
`SELECT … FOR UPDATE` seat counter. Result on 2026-08-15: 10 confirmed /
40 waitlisted / seats_taken 10 / 0 lost — no oversell. The Pest suite covers
the same logic sequentially (SQLite can't share :memory: across forks).

### D-036 · Payment recording UI deferred to Phase 5
Enrollments carry `amount_due/paid_baisa` + `payment_status` (displayed as
badges and exported), but no editing UI yet: recording a payment must write
journal entries, which is exactly Phase 5's `record payment` action. Wiring a
field-edit now would create money data outside the ledger.
