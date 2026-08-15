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
