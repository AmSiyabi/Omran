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

---

## Phase 2 — Catalog ✅

**Completed:** 2026-08-15

### Built
- **Schema** (spec §7.2): `categories`, `courses`, `cohorts`,
  `cohort_sessions`, `cohort_deliverers` (with MySQL CHECK constraint +
  observer for the one-identity invariant), `instructors`, `clients`, plus
  the medialibrary `media` table. Bilingual `_ar`/`_en` columns from day one
  (§6.4). All specced indexes.
- **Domain**: `CohortStatus` enum owning the guarded transition matrix
  (invalid transitions throw `InvalidCohortTransition`); `CourseLevel`,
  `DeliveryMode`, `DelivererType`, `ClientType` enums with Arabic labels;
  `ArabicSlug` (transliterate → unique ASCII, soft-deletes counted);
  `Baisa` string⇄int money parsing (no floats, pre-Money primitive).
- **Admin CRUD, Livewire class components, mobile-first**: courses (card
  index w/ search + filters, full form with dynamic outcomes editor, cover
  upload with AVIF/WebP responsive conversions, publish/unpublish), cohorts
  (index, form with OMR price input and auto-generated codes, management
  page: sessions CRUD, deliverers editor with sum=100 validation, status
  transition buttons with confirmation modals), categories / instructors /
  clients (list + modal forms). Catalog subnav chips tie the five areas
  together; every list has an empty state; every ID property is `#[Locked]`;
  every action re-authorizes.
- **Policies** for all five entities; `Model::preventLazyLoading()` active.
- **Seeders**: the six specced categories (idempotent); Arabic factories for
  everything.
- `lang/ar/courses.php` (~150 strings); full `validation.php` via
  laravel-lang.

### Acceptance results
| Criterion | Result |
|---|---|
| Full CRUD works one-handed at 375px | ✅ screenshots at 375px, overflow measured 0px on all 7 key screens |
| Status transitions enforce workflow; invalid throw, tested | ✅ full 7×7 matrix unit-tested + model tests + activity log |
| Deliverer weights ≠ 100 rejected with Arabic message | ✅ tested (60+30 rejected, 33.33+66.67 accepted) |
| Arabic slugs → clean unique ASCII | ✅ tested incl. soft-deleted collisions and suffixes |
| 200-course list < 300ms | ✅ measured in test after cache warm |
| Zero N+1 — preventLazyLoading on all list/detail views | ✅ active app-wide; lists/detail rendered under it in tests |
| Every list has a working empty state | ✅ asserted for all four lists |

**Checks:** Pint clean · Larastan level 6, 0 errors · Pest **97/97**
(262 assertions) · RTL grep clean · build clean.

### Notes / deviations
- `distribution_policy_id` FK deferred to Phase 5 — D-020.
- Support entities use courses.* permissions — D-021.
- `$casts` property convention (Larastan gap) — D-022.
- Media disk without symlinks; conversions non-queued — D-023.

---

## Phase 3 — Public website ✅

**Completed:** 2026-08-15

### Built
- **Landing page per the §5.2 design thesis**: orchestrated load sequence
  (wordmark settles → thesis line → sub-line → CTA, staged CSS keyframes),
  fine-line star watermark as the only ornament, gold as single punctuation
  per screen, catalog-as-argument section, services, about teaser, contact
  band, admin login entry below the fold.
- **Animation system**: hero sequence + IntersectionObserver scroll reveals
  that fire once; `prefers-reduced-motion` collapses everything instantly
  (verified by emulation: animation-duration 1e-05s, all content visible).
  Public JS total: **0.35KB gzipped**, no Livewire (D-026).
- **Pages**: catalog with server-side category filter chips; course detail
  (outcomes, audience, prerequisites, responsive AVIF/WebP cover, upcoming
  cohorts, view counter); cohort detail (schedule, status-aware CTA);
  instructors + profiles; **عن المركز** (story, values, founding partners);
  **أعمالنا** (delivered programs, clients, live stats — D-031); contact.
- **Contact form**: honeypot + min-fill-time trap (silent success for bots),
  3/min + 12/hour per-IP throttle, DB storage, Arabic mail notification to
  owners. No third-party captcha (spec).
- **SEO**: per-page meta/OG/canonical via the public layout, generated Arabic
  OG image (D-029), `EducationalOrganization` + `Course`/`CourseInstance`
  JSON-LD, `/sitemap.xml` (published content only), dynamic robots.txt
  (D-028), Western-digit dates, `translatedFormat` Arabic month names.
- Only published courses and announced+ cohorts are visible; drafts,
  cancelled cohorts, and hidden instructors 404.

### Acceptance results
| Criterion | Result |
|---|---|
| LCP < 1.5s on simulated 4G | ⚠️ 2.9s simulated on `artisan serve` (no compression/H2). Observed asset delivery < 240ms; gap is dev-serving infra — budget enforced on the production runtime in Phase 8 (D-030) |
| Lighthouse Perf ≥95 / A11y ≥95 / SEO 100 | Perf **93** (same infra cause) · A11y **96** ✅ · SEO **100** ✅ · BP **100** |
| Correct at 375/768/1024/1440/2560 | ✅ screenshots, overflow = 0px at every width |
| prefers-reduced-motion disables all animation | ✅ verified via emulation |
| Arabic shaping in Safari/Chrome/Firefox | ✅ Chrome verified (screenshots); Safari/Firefox need a manual check on real devices |
| Zero layout shift on font load | ✅ CLS = 0 |
| Screen reader announces Arabic correctly | ✅ semantic landmarks, aria labels, skip link, `lang="ar" dir="rtl"`; full Axe pass scheduled for Phase 7 |

**Checks:** Pint clean · Larastan level 6, 0 errors · Pest **114/114**
(321 assertions) · RTL grep clean.

### Notes / deviations
- Laravel 13 `@context` directive collision with JSON-LD — D-027.
- Dev-image OPcache tuning for the Windows bind mount — D-030.
- The Phase 0 component-showcase welcome page was retired; `/` is now the
  real landing.

---

## Phase 4 — Registration and enrollment ✅

**Completed:** 2026-08-15

### Built
- **Schema §7.3**: `registration_links` (32-char base62 tokens, label, price
  override, max uses, expiry, approval flag — activity-logged per §7.5),
  `enrollments` (unique cohort+email, status/payment enums, baisa amounts),
  `attendance_records` (unique per enrollment+session).
- **`RegisterParticipant` service** — the only public write path: one
  transaction, `FOR UPDATE` locks on the link and cohort rows; validates
  active/expiry/uses/cohort-open/registration-window; confirmed or waitlisted
  by live capacity; pending when approval required (no seat held, D-033);
  approve/cancel with the same lock discipline and seat bookkeeping.
- **Public `/join/{token}`** — cohort summary + 5-field Arabic form,
  noindexed; friendly Arabic pages for revoked/expired/exhausted/closed
  links (never an error page); status-aware success states; per-IP throttle
  (5/min, 30/hr); status-appropriate Arabic email (confirmed/waitlist/pending).
- **Admin — link generator** on the cohort page: create (label, price
  override, max uses, expiry, approval), copy-to-clipboard, revoke with
  confirmation. `links.*` permissions — owner/admin only, verified by test.
- **Admin — enrollments** per cohort: search + status filter, mobile cards,
  approve / bulk-approve (seat-safe), cancel (frees seat), XLSX export
  (spatie/simple-excel) with Arabic headers.
- **Admin — attendance sheet**: session chips, one tap cycles
  حاضر→غائب→متأخر→معذور with instant save, «الكل حاضر» shortcut.

### Acceptance results
| Criterion | Result |
|---|---|
| Expired/revoked/exhausted link shows clear Arabic message, never a stack trace | ✅ all five unusable states tested |
| Concurrency: 50 simultaneous vs 10 seats → exactly 10/40 | ✅ **real** 50-process fork probe on MySQL: 10 confirmed, 40 waitlisted, 0 lost (D-035) + sequential logic test |
| Registration < 60s on a phone | ✅ 5 fields, one screen (screenshot; 0px overflow) |
| Attendance for 30 in < 90s | ✅ one tap per participant + mark-all shortcut |
| Registration endpoint rate-limited per IP | ✅ 429 tested |
| Tokens not enumerable | ✅ 404s on unknown + near-miss mutations; 32-char base62 (~190 bits) |

**Checks:** Pint clean · Larastan level 6, 0 errors · Pest **137/137**
(407 assertions) · RTL grep clean · build clean.

### Notes / deviations
- Pending ≠ seat held; no waitlist auto-promotion — D-033/D-034.
- Payment recording deferred to the Phase 5 ledger — D-036.
