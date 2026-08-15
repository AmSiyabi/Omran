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
