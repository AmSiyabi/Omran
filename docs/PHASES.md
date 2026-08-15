# سجل المراحل — Phase Completion Log

---

## Phase 0 — Foundation ✅

**Completed:** 2026-08-15

### Built
- Laravel 13.25.0 on PHP 8.5.9, scaffolded and run entirely through Docker
  (`docker compose up -d` → app :8080, MySQL 8.4 :3307, Redis 7 :6380).
- Livewire 4.4.0 installed; assets included explicitly in the base layout so
  bundled Alpine powers the modal/toast components from day one.
- Tailwind CSS 4.3.3 (CSS-first `@theme`) + Vite 8.2.1.
- `.env.example` complete including forward-looking vars (backup, Sentry,
  Thawani placeholders, display timezone).
- RTL shell: `<html lang="ar" dir="rtl">` base layout
  (`resources/views/components/layouts/base.blade.php`), brand palette tokens,
  El Messiri (variable 400–700) + Tajawal (400/500/700) self-hosted subsets,
  preloaded, `font-display: swap`.
- Base component library (11): `x-button`, `x-input`, `x-select`,
  `x-textarea`, `x-modal`, `x-toast`, `x-spinner`, `x-card`, `x-badge`,
  `x-empty-state`, `x-skeleton` — all RTL-logical, Arabic-labelled, 44px tap
  targets, `prefers-reduced-motion` respected globally.
- `lang/ar/common.php` with Appendix-A terminology.
- Component showcase on `/` (temporary; replaced by the real landing in Phase 3).
- CI (`.github/workflows/ci.yml`): Pint, Larastan level 6, RTL grep
  (`scripts/check-rtl.sh`), Pest, asset build, font-budget gate.
- RTL rule enforced twice (Pest test + grep); both probe-tested with a
  deliberate violation and confirmed to fail on it.
- `scripts/screenshot.mjs` (puppeteer-core): viewport-accurate screenshots +
  horizontal-overflow assertion, reusable for later phase acceptance runs.

### Acceptance results
| Criterion | Result |
|---|---|
| `php artisan serve` renders styled Arabic RTL page, correct fonts | ✅ HTTP 200, `lang="ar" dir="rtl"`, El Messiri/Tajawal render (screenshots) |
| No physical-direction Tailwind utility; CI check passes | ✅ Both layers green; both fail correctly on a probe violation |
| Components render at 375 / 768 / 1440px | ✅ Screenshots at all three; `scrollWidth == innerWidth` (no overflow) at all three |
| Lighthouse ≥ 95 on the shell | ✅ Performance **100**, Accessibility 96, Best Practices 100 (mobile emulation; LCP 1.3s, CLS 0) |
| Font payload < 120KB | ✅ 102,960 bytes total (8 woff2 subset files) |
| CI green | ✅ Pint clean · Larastan L6 no errors · Pest 2/2 · RTL grep clean · Vite build clean |

### Notes / deviations
- Design tokens live in `resources/css/app.css` `@theme` (Tailwind 4 CSS-first)
  instead of `tailwind.config.js` — see DECISIONS D-005.
- Brand `/brand/` assets used (navy/gold/cream + El Messiri/Tajawal), overriding
  the spec's fallback palette and font suggestions — D-006.
- Amiri and Reem Kufi deferred to protect the font budget — D-007.
- Headless Chrome's `--screenshot` CLI has a minimum-window-width artifact at
  375px (clips ~40px); acceptance screenshots use puppeteer-core viewport
  emulation instead. Not a layout bug — overflow measured at exactly 0.

**Not built (per spec):** auth, models, business logic — Phase 1 next.

---

## Phase 1 — Identity and access ✅

**Completed:** 2026-08-15

### Built
- **Identity schema** (spec §7.1): `users` (name_ar/name_en, phone, locale,
  last-login tracking, is_active, soft deletes), `partners` (ownership %,
  effective dates, encrypted bank/civil fields), `invoicing_entities`,
  `user_devices` (new-device detection), Fortify 2FA columns, sessions table.
- **Auth**: Fortify 1.38, Arabic views for login / 2FA challenge / password
  reset / confirm / email verification. No public registration. Passkeys off.
- **2FA**: TOTP with QR + manual key, confirm-before-active, recovery codes
  shown exactly once, regenerate. Mandatory for owner/admin via `2fa`
  middleware — an unconfirmed owner is boxed into the setup page (verified in
  tests and a real browser run).
- **Roles/permissions** (spec §9.1–9.2): 4 roles, 32 permissions, exact
  matrices seeded idempotently. Role attach/detach audited (D-018).
- **Session security** (spec §9.6): two-layer login rate limiting (D-016),
  active-session list with revoke (database sessions, D-013), new-device
  login email (Arabic), password policy min-12 + HaveIBeenPwned.
- **Security headers** (spec §10): nonce-based CSP without unsafe-inline,
  X-Frame-Options DENY, nosniff, strict Referrer-Policy, Permissions-Policy,
  HSTS in production. Verified via curl and asserted in tests.
- **Admin shell**: navy sidebar (desktop) / bottom nav (mobile, 44px+
  targets, safe-area padding), both permission-filtered; dashboard
  placeholder; security page; more page; permission-guarded placeholder
  routes for courses (Phase 2) and finance (Phase 5).
- **Audit log**: activitylog v5 on users/partners/invoicing entities with
  old+new values; encrypted PII excluded from the trail (D-015).
- **Seeders**: Hamad + Ammar as owners (passwords from env or generated and
  printed once — never committed), partner records at 50/50, default
  invoicing entity placeholder per §1.1.
- `lang/ar/auth.php` complete; new admin strings in `common.php`.

### Acceptance results
| Criterion | Result |
|---|---|
| Unauthenticated request to any /admin/* route redirects to login | ✅ tested across all 6 admin routes |
| A coordinator requesting a finance route gets 403 | ✅ tested (viewer too) |
| An owner without confirmed 2FA cannot reach any route except 2FA setup | ✅ tested + verified in real browser (redirects to /admin/security/two-factor) |
| Login rate limiting triggers after 5 failed attempts, tested | ✅ Arabic lockout at 6th attempt + HTTP 429 hard cap at 11th |
| APP_DEBUG=false produces no stack trace on a forced 500 | ✅ tested |
| All security headers present (curl -I) | ✅ verified live + asserted in tests |
| Every role change appears in the activity log | ✅ attach + detach tested |
| Pest test per role asserting reachable routes | ✅ exact status matrix per role |

**Checks:** Pint clean · Larastan level 6, 0 errors · Pest **40/40** ·
RTL grep clean · assets build clean.

### Notes / deviations
- Session driver switched to database — D-013.
- Two-layer rate limiting design — D-016 (Fortify quirk documented).
- User management UI (owners creating users) is not in the Phase 1 build
  list; users are seeded/created via code for now. It will ride along with a
  later phase's settings area.
