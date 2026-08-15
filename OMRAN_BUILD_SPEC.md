# مركز عمران — Platform Build Specification
### Laravel + Livewire · Arabic-first · Phased build
**Document version:** 1.0 · **Prepared:** August 2026
**Audience:** Claude Code (implementation agent) + the two partners (Hamad, Ammar)

---

## 0. How to use this document

You are building a production platform for a two-partner training and consulting business in Oman. This document is the contract between the spec and the code. Read the whole thing before writing a line.

**Rules of engagement:**

1. **Build in phases, in order.** Do not start Phase N+1 until Phase N passes its acceptance criteria. At the end of each phase, stop and report: what was built, what tests pass, what you had to decide that this document did not cover.
2. **Never skip the acceptance criteria.** Each phase has a checklist. Run it. If something fails, fix it before moving on.
3. **When this document conflicts with your instincts, this document wins.** When this document is silent, make the simplest choice that does not paint the project into a corner, and log the decision in `/docs/DECISIONS.md` with date and rationale.
4. **Do not invent numbers.** Every financial rule in Section 8 comes from a signed partnership contract. Do not "improve" the percentages.
5. **Version policy:** Use the latest stable Laravel, Livewire, Tailwind, and PHP available at build time. Do **not** trust version numbers quoted from memory. Run `composer show`, check `laravel/framework` on Packagist, and read the current docs before scaffolding. If a major version changed in a way that breaks an approach in this spec, adapt and log it in `/docs/DECISIONS.md`.
6. **Arabic is not a translation layer. It is the source language.** See Section 6. Every string ships in Arabic first. English exists only on the public marketing site.
7. **Money is never a float.** See Section 8.1. If you write `float` anywhere near a currency value, you have introduced a bug.

**Repository conventions:**
- `/docs/DECISIONS.md` — running architecture decision log
- `/docs/PHASES.md` — phase completion log with dates and test results
- `/docs/FINANCE.md` — human-readable explanation of the ledger for the two owners
- `/brand/` — logo files, brand guideline PDF (supplied by the owners)

---

## 1. Business context

**The entity:** مركز عمران للتدريب والاستشارات — a training and consulting center operating as a digital-first partnership between two equal partners, **حمد** and **عمار**, each holding 50% of the center.

**What it sells:**
- Training courses and workshops (Islamic studies, prayer and worship, AI and technology, professional development, and more categories over time)
- Talks and lectures
- Consulting engagements
- Some work is delivered by the partners personally; some is delivered by external trainers the center recruits and markets

**Who uses the software:**
- **The two partners** — daily, heavily on mobile. They run the whole operation from this system.
- **The public** — browse the site, see upcoming courses, register via generated links.
- Possibly later: an assistant/coordinator with restricted access.

**Design constraint that shapes everything:** the owners are not accountants. The financial system must be correct enough to survive a tax audit and simple enough that a busy person can record a 40 OMR ad spend from a phone in fifteen seconds while walking.

### 1.1 Legal structure — open risk

The partnership contract describes the entity as a **"شراكة افتراضية رقمية"** (virtual digital partnership). This is an internal moral and operational charter between two people. It is **not** a legal form registered with MOCIIP, and it does not by itself produce a commercial registration (CR), a tax card, or the ability to issue a legally valid invoice to a government ministry.

**Software consequence:** revenue will, in practice, be invoiced through *some* legal vehicle — a partner's personal civil number, an existing company owned by one of them, or a CR registered later. Tax liability, VAT thresholds, and audit exposure attach to **that vehicle**, not to "مركز عمران."

Therefore the data model includes an `invoicing_entities` table and **every revenue transaction records which entity invoiced it**. This is not optional. Without it, the tax reports in Section 8.9 are fiction.

---

## 2. Product scope

### 2.1 Public website (Arabic first, English later)
- Landing page with brand identity, animated, responsive, distinctive (Section 5)
- Course and workshop catalog, filterable by category and status (upcoming / open / closed / archived)
- Individual course pages: description, outcomes, instructor, schedule, location or online, price, seats remaining, CTA
- Instructor profiles
- About the center
- Contact / inquiry form
- Public registration pages reached via **generated join links** (Section 7.5)
- Admin login entry point below the fold on the landing page

### 2.2 Admin panel (Arabic only, no locale switcher)
- Dashboard: cash position, month-to-date revenue, upcoming courses, unpaid receivables, VAT threshold gauge, each partner's current-account balance
- Course and cohort management
- Registration link generation and tracking
- Enrollment management, attendance, capacity
- Financial: record income, record expense, record trainer payout, record partner drawing
- Per-course profitability
- Settlement engine (Section 8.5)
- Partner statements
- Reports: income statement, cash flow, receivables aging, per-course P&L, VAT threshold monitor
- Document vault: receipts, contracts, invoices
- Audit log
- Settings: categories, chart of accounts, distribution policies, invoicing entities, users and roles

### 2.3 Explicitly out of scope for v1
Do not build these. If they seem necessary, log the argument in `/docs/DECISIONS.md` and ask.
- Online payment gateway integration (record payments manually; the schema anticipates Thawani later)
- Video hosting / LMS / course content delivery
- Email marketing automation
- Multi-tenancy — there is exactly one organization
- Mobile native apps — the responsive web app is the mobile app
- Automatic bank feed / statement import

---

## 3. Stack

| Layer | Choice | Note |
|---|---|---|
| Language | PHP, latest stable | |
| Framework | Laravel, latest stable | |
| UI | Livewire, latest stable + Alpine.js | Alpine ships with Livewire |
| CSS | Tailwind CSS, latest stable | Logical properties mandatory — Section 6.2 |
| Build | Vite | |
| DB | MySQL 8+ or PostgreSQL 15+ | Pick one, state it, do not abstract over both |
| Cache / Queue / Session | Redis | |
| Runtime | FrankenPHP or Laravel Octane | Section 11 |
| Auth | Laravel Fortify or Breeze, customized | 2FA required |
| Permissions | `spatie/laravel-permission` | |
| Audit | `spatie/laravel-activitylog` | |
| Media | `spatie/laravel-medialibrary` | Receipts, course images |
| Backup | `spatie/laravel-backup` | |
| Testing | Pest | |
| Static analysis | Larastan level 6+ | |
| Formatting | Laravel Pint | |

**Do not add:** Filament, Nova, Jetstream, or any admin scaffolding package. The admin UI is Arabic-first and mobile-first in ways these packages fight. Build it in Livewire.

---

## 4. Non-negotiable engineering laws

These apply to every phase. Violating one is a defect regardless of whether a test catches it.

1. **Money is `BIGINT` baisa.** Never float, never decimal-as-string in business logic. See 8.1.
2. **Every Livewire action method authorizes.** `mount()` authorization is not enough — every public method on a Livewire component is a callable HTTP endpoint. See 9.4.
3. **Every ID property held on a Livewire component is `#[Locked]`.**
4. **Financial records are append-only.** Posted journal entries are never edited or deleted. Corrections happen through reversing entries. See 8.3.
5. **No `ml-`, `mr-`, `pl-`, `pr-`, `left-`, `right-`, `text-left`, `text-right` in Tailwind classes.** Logical properties only. See 6.2.
6. **No hardcoded user-facing strings in Blade or PHP.** Everything through `__()` and lang files.
7. **`Model::preventLazyLoading()` enabled in local and CI.** N+1 queries fail tests.
8. **Every DB query that filters, sorts, or joins has a covering index.**
9. **Every destructive action has a confirmation modal. Every submit button has a loading state.** See 12.
10. **Every migration is reversible and every seed is idempotent.**

---

## 5. Brand and design direction

### 5.1 Read the real brand assets first

The owners have supplied logo files and a color theme in `/brand/`. **Read them before designing anything.** Extract the actual palette, logo lockups, clear-space rules, and any typographic direction. What follows is the direction and the fallback palette — the real assets override it.

**Fallback palette** (extracted from the partnership document's own visual identity, so it is at minimum consistent with existing materials):

```css
--omran-navy:      #1a365d;  /* primary — headers, structure */
--omran-blue:      #2b6cb0;  /* secondary — interactive, links */
--omran-gold:      #c59b27;  /* accent — sparingly, marks significance */
--omran-ink:       #1a202c;  /* body text */
--omran-mist:      #f7fafc;  /* page background */
--omran-tint:      #ebf8ff;  /* subtle fills, active states */
--omran-line:      #e2e8f0;  /* borders, dividers */
```

### 5.2 The design thesis

**Do not make a generic SaaS landing page.** No hero gradient with a big number and three feature cards. No floating glassmorphic panels. No stock illustration of people pointing at a laptop.

The name carries the direction. **عمران** is Ibn Khaldun's term — **علم العمران البشري**, the science of human civilization and social organization. That is the intellectual lineage of a center that teaches Islamic knowledge and artificial intelligence in the same catalog. The design should feel like it belongs to that lineage: **rigorous, structured, textual, confident, unhurried.**

Concrete direction:

- **The hero is a thesis, not a banner.** Lead with the center's proposition in Arabic type set large and well — the typography itself is the visual. Arabic script at scale, properly kerned, on generous white space, is more striking than any illustration. Let the word عمران carry weight.
- **Structure as ornament.** Islamic geometric tessellation is the obvious reference and the obvious trap — do not paste an arabesque PNG. Instead derive structure from it: a strict underlying grid, ratios that repeat, a rule system for how blocks divide. Use a single geometric motif as a fine line-weight element (section dividers, a watermark at low opacity behind the hero, the shape of the category chips). Restraint reads as authority; density reads as a template.
- **Gold is punctuation, not decoration.** It marks one thing per screen — the primary CTA, or the active state, or a rule under a section title. Never a gold gradient. Never gold body text.
- **Motion serves reading.** One orchestrated page-load sequence: the Arabic wordmark settles, then the thesis line, then the catalog rises. Scroll-triggered reveals on section entry, subtle, once — never re-triggering. Hover states on course cards that lift by 2–4px with a shadow change and nothing else. Respect `prefers-reduced-motion` absolutely. Scattered animation on every element is the single loudest signal of machine-generated design.
- **The catalog is the product.** Islamic studies sitting beside artificial intelligence in one grid is the center's actual argument. Design the catalog so that juxtaposition is legible and intentional — consistent card structure, category color-coding via a restrained accent bar, not wildly different treatments per topic.

### 5.3 Typography

Arabic type choice carries more personality than layout. Pair deliberately:

- **Display / headings:** a Kufi-derived Arabic face with geometric structure — `Noto Kufi Arabic`, `Kufam`, or `Bahij Nassim`. This is where the civilizational register lives.
- **Body / UI:** a highly legible naskh-based UI face — `IBM Plex Sans Arabic` or `Readex Pro`. IBM Plex Sans Arabic is preferred: it has a genuine Latin companion, which matters when the English site arrives in Phase 9.
- **Numerals:** Western Arabic digits (0–9) everywhere in the financial UI. This is standard in Omani business practice and non-negotiable in tables.

**Performance warning:** Arabic webfonts are heavy — full character sets run 200–400KB per weight. Subset aggressively with `pyftsubset` to the Arabic block plus Latin basic plus punctuation, serve `woff2` only, preload the two faces you actually use above the fold, and set `font-display: swap`. Budget: total font payload under 120KB. See Section 11.

### 5.4 The admin panel is a different design problem

The public site persuades. The admin panel is a tool used every day, often one-handed, often standing up. Design it for **speed and density on mobile**, not beauty. Same palette and type, but: larger tap targets, higher information density, fewer animations, no scroll reveals, no decorative elements at all.

---

## 6. Arabic-first architecture

### 6.1 Locale rules

- Default app locale: `ar`. Default direction: `rtl`.
- `<html lang="ar" dir="rtl">` is the default state of the layout, not a conditional override.
- **Admin panel: Arabic only.** No locale switcher. Do not build one.
- **Public site: Arabic at `/`, English at `/en/*`.** English is Phase 9. Until then `/en/*` does not exist — do not stub it.
- All lang files complete in `ar` before any `en` file is created. `lang/ar/*.php` with domain-split files: `common.php`, `auth.php`, `courses.php`, `finance.php`, `validation.php`, `reports.php`.

### 6.2 RTL implementation

Tailwind logical properties, always:

| Never use | Always use |
|---|---|
| `ml-4` / `mr-4` | `ms-4` / `me-4` |
| `pl-4` / `pr-4` | `ps-4` / `pe-4` |
| `left-0` / `right-0` | `start-0` / `end-0` |
| `text-left` / `text-right` | `text-start` / `text-end` |
| `border-l` / `border-r` | `border-s` / `border-e` |
| `rounded-l-*` / `rounded-r-*` | `rounded-s-*` / `rounded-e-*` |

Add an ESLint/stylelint rule or a CI grep that fails the build if a physical-direction utility appears in a Blade file. This is cheap and it will save hours.

Icons that imply direction (arrows, chevrons, back buttons) must mirror in RTL. Icons that do not imply direction (clock, checkmark, user) must not.

### 6.3 Numbers, dates, currency

- **Currency:** Omani Rial has **three** decimal places. 1 OMR = 1000 baisa. Display as `640.500 ر.ع.` — three decimals always, never two, never rounded to two for display.
- **Formatting helper:** one `Money` value object with `->format()`. Nothing else formats currency.
- **Dates:** Gregorian primary, Hijri secondary where it adds value (course dates for Islamic-studies courses, Ramadan scheduling). Store UTC, display Asia/Muscat.
- **Validation messages, error bags, empty states, toasts, confirmation dialogs, email subjects, PDF exports** — all Arabic.

### 6.4 Content model bilinguality

Course titles, descriptions, and instructor bios need `_ar` and `_en` columns from the start, even though `_en` sits empty until Phase 9. Adding them later means a migration across live content. Use `title_ar`, `title_en`, `description_ar`, `description_en`, and an accessor that falls back to `_ar` when `_en` is null.

---

## 7. Domain model

Full schema. Column types are indicative; adapt to your chosen DB. Every table gets `id`, `created_at`, `updated_at`, and soft deletes **except** journal tables (Section 8.3) which are append-only.

### 7.1 Identity

**`users`**
```
id, name_ar, name_en (nullable), email (unique), phone (unique, nullable),
password, avatar_path (nullable), locale (default 'ar'),
two_factor_secret (encrypted, nullable), two_factor_recovery_codes (encrypted, nullable),
two_factor_confirmed_at (nullable), last_login_at, last_login_ip,
is_active (bool, default true), email_verified_at
```

**`partners`** — a partner is a user with an ownership stake. Separate table because ownership is time-varying and users are not.
```
id, user_id (FK, unique), display_name_ar, display_name_en (nullable),
bio_ar (text), bio_en (text, nullable), photo_path,
ownership_percent (decimal 5,2, default 50.00),
effective_from (date), effective_to (date, nullable),
is_active (bool), public_profile_visible (bool, default true),
bank_name (encrypted, nullable), bank_account (encrypted, nullable), civil_number (encrypted, nullable)
```

**`instructors`** — external trainers. A partner delivering a course is referenced through `partners`, not here.
```
id, name_ar, name_en (nullable), email, phone, bio_ar (text), bio_en (text, nullable),
photo_path, specialization_ar, is_public (bool), notes (text, nullable)
```

**`invoicing_entities`** — which legal vehicle issued the invoice. Critical, see 1.1.
```
id, name_ar, name_en (nullable), type (enum: individual, establishment, llc, other),
cr_number (nullable), tax_card_number (nullable), vat_number (nullable),
vat_registered (bool, default false), vat_registered_from (date, nullable),
is_default (bool), notes (text, nullable)
```

Roles and permissions live in `spatie/laravel-permission` tables. See Section 9.

### 7.2 Catalog

**`categories`**
```
id, slug (unique), name_ar, name_en (nullable), description_ar, description_en (nullable),
accent_color (hex, nullable), icon (nullable), sort_order, is_active
```
Seed: `اسلامية`, `عبادات`, `ذكاء-اصطناعي`, `تقنية`, `تطوير-ذات`, `مهارات-مهنية`.

**`courses`** — the reusable definition of a training product.
```
id, slug (unique), category_id (FK), title_ar, title_en (nullable),
summary_ar (string 500), summary_en (nullable),
description_ar (longtext), description_en (nullable),
outcomes_ar (json array), outcomes_en (json, nullable),
target_audience_ar (text), prerequisites_ar (text, nullable),
duration_hours (decimal 5,1), level (enum: beginner, intermediate, advanced, all),
cover_image_path, is_published (bool), published_at (nullable),
meta_title_ar, meta_description_ar, view_count (unsigned int, default 0)
```

**`cohorts`** — a specific delivery of a course. **All financial activity attaches to a cohort, never to a course.** This distinction matters: "دورة الذكاء الاصطناعي للعاملين في المجال الديني" is a course; the September 2026 delivery for وزارة الأوقاف is a cohort with its own revenue, costs, and distribution.
```
id, course_id (FK), code (unique, e.g. AI-RELIG-2026-09),
title_override_ar (nullable), delivery_mode (enum: onsite, online, hybrid),
venue_ar (nullable), venue_url (nullable), city_ar (nullable),
starts_at (datetime), ends_at (datetime), timezone (default 'Asia/Muscat'),
capacity (unsigned int, nullable), seats_taken (unsigned int, default 0),
price_baisa (bigint, default 0), is_free (bool, default false),
client_id (FK nullable -> clients), invoicing_entity_id (FK),
distribution_policy_id (FK -> distribution_policies),
status (enum: draft, announced, open, closed, delivered, settled, cancelled),
registration_opens_at (nullable), registration_closes_at (nullable),
internal_notes (text, nullable)
```
Indexes: `(status, starts_at)`, `(course_id, starts_at)`, `(client_id)`.

**`cohort_sessions`** — individual days/sessions within a cohort.
```
id, cohort_id (FK), session_number, title_ar (nullable),
starts_at, ends_at, venue_override_ar (nullable), notes (nullable)
```

**`cohort_deliverers`** — who actually delivers, and their weight in the trainer share. Supports co-delivery.
```
id, cohort_id (FK),
deliverer_type (enum: partner, external),
partner_id (FK nullable), instructor_id (FK nullable),
share_weight (decimal 5,2)  -- weights across a cohort must sum to 100.00
```
Constraint: exactly one of `partner_id` / `instructor_id` is non-null, matching `deliverer_type`. Enforce in a model observer and in a DB check constraint if supported.

**`clients`** — organizations that commission work (ministries, companies).
```
id, name_ar, name_en (nullable), type (enum: government, private, ngo, individual),
contact_name, contact_email, contact_phone,
cr_number (nullable), vat_number (nullable),
address_ar (text, nullable), notes (text, nullable)
```

### 7.3 Registration and enrollment

**`registration_links`** — the "generate a link to join" feature.
```
id, cohort_id (FK), token (unique, 32-char random, indexed),
label_ar (nullable, e.g. "رابط وزارة الأوقاف"),
price_override_baisa (bigint, nullable), max_uses (unsigned int, nullable),
uses_count (unsigned int, default 0),
expires_at (nullable), requires_approval (bool, default false),
is_active (bool, default true), created_by (FK users)
```
The public URL is `/تسجيل/{token}` (or `/join/{token}` — pick one and be consistent; Arabic slugs are valid but URL-encode ugly, so **use `/join/{token}`** and put Arabic in the page, not the path).

**`enrollments`**
```
id, cohort_id (FK), registration_link_id (FK nullable),
full_name_ar, email, phone, organization_ar (nullable), job_title_ar (nullable),
status (enum: pending, approved, confirmed, waitlisted, cancelled, attended, no_show),
amount_due_baisa (bigint), amount_paid_baisa (bigint, default 0),
payment_status (enum: unpaid, partial, paid, waived),
notes (text, nullable), enrolled_at, approved_at (nullable), approved_by (nullable)
```
Unique index on `(cohort_id, email)`.

**`attendance_records`**
```
id, enrollment_id (FK), cohort_session_id (FK), status (enum: present, absent, late, excused), marked_at, marked_by
```

### 7.4 Documents

**`documents`** — receipts, contracts, invoices. Use medialibrary or a direct table; a direct table is simpler here.
```
id, documentable_type, documentable_id (polymorphic: cohort, transaction, client, settlement),
type (enum: receipt, invoice, contract, quote, other),
file_path (private disk), original_filename, mime_type, size_bytes,
uploaded_by (FK users), notes (nullable)
```
Files go to a **private** disk. Access only through a signed temporary URL generated by a controller that authorizes first.

### 7.5 Audit

`spatie/laravel-activitylog` on: users, partners, cohorts, journal entries, settlements, distribution policies, registration links, invoicing entities. Log old and new values. Never let the activity log table be writable from the UI.

---

## 8. Financial architecture

This section is the heart of the system. Read it twice.

### 8.1 Money

- **Storage:** `BIGINT` columns, unit = **baisa**. 1 OMR = 1000 baisa. 640.500 OMR = `640500`.
- **In code:** a `Money` value object wrapping an `int`. Immutable. Methods: `add`, `subtract`, `multiplyByPercent`, `allocate(array $weights)`, `format()`, `isNegative()`, `isZero()`.
- **Column naming:** every money column ends in `_baisa`. No exceptions. This makes float bugs visible in code review.
- **Casting:** a custom Eloquent cast `MoneyCast` converts int ⇄ `Money`.
- **Never** use `round()`, `floor()`, `/`, or `*` on a money value outside the `Money` class.

### 8.2 Chart of accounts

Fixed, seeded, editable only by an owner. Numeric codes so reports sort naturally.

**Assets (1xxx)**
| Code | Arabic | English |
|---|---|---|
| 1010 | الصندوق (نقد) | Cash on hand |
| 1020 | حساب المركز البنكي | Omran bank account |
| 1030 | محافظ إلكترونية | Digital wallets |
| 1100 | ذمم مدينة (مستحقات على العملاء) | Accounts receivable |
| 1200 | مصروفات مدفوعة مقدماً | Prepaid expenses |

**Liabilities (2xxx)**
| Code | Arabic | English |
|---|---|---|
| 2010 | ذمم دائنة | Accounts payable |
| 2020 | مستحقات مدربين خارجيين | External trainer payable |
| 2100 | ضريبة القيمة المضافة المستحقة | VAT payable *(dormant until registered)* |
| 2200 | إيرادات مقبوضة مقدماً | Deferred revenue |

**Equity (3xxx)**
| Code | Arabic | English |
|---|---|---|
| 3010 | رأس المال — حمد | Capital — Hamad |
| 3011 | رأس المال — عمار | Capital — Ammar |
| 3020 | الحساب الجاري — حمد | Partner current account — Hamad |
| 3021 | الحساب الجاري — عمار | Partner current account — Ammar |
| 3090 | الأرباح المحتجزة | Retained earnings |

**Revenue (4xxx)**
| Code | Arabic | English |
|---|---|---|
| 4010 | إيرادات الدورات والورش | Course and workshop revenue |
| 4020 | إيرادات الاستشارات | Consulting revenue |
| 4030 | إيرادات أخرى | Other revenue |

**Direct costs (5xxx)** — attributable to a specific cohort
| Code | Arabic | English |
|---|---|---|
| 5010 | حصة الشريك المنفذ | Delivering-partner share |
| 5020 | أتعاب مدرب خارجي | External trainer fee |
| 5030 | إعلانات مأجورة ودعاية مباشرة | Paid advertising and direct promotion |
| 5040 | قاعات وضيافة | Venue and hospitality |
| 5050 | مواد تدريبية وطباعة | Materials and printing |
| 5060 | سفر وإقامة | Travel and accommodation |
| 5090 | تكاليف مباشرة أخرى | Other direct costs |

**Operating expenses (6xxx)** — center-level, not attributable to one cohort
| Code | Arabic | English |
|---|---|---|
| 6010 | اشتراكات برمجية ومنصات | Software and platform subscriptions |
| 6020 | استضافة ونطاقات | Hosting and domains |
| 6030 | رسوم بنكية وبوابات دفع | Bank and payment fees |
| 6040 | تصميم ومحتوى | Design and content |
| 6050 | رسوم حكومية وتراخيص | Government fees and licences |
| 6060 | تسويق عام (غير مخصص لدورة) | General marketing |
| 6090 | مصروفات إدارية أخرى | Other administrative expenses |

**Note on 5010:** treating the delivering partner's 80%/70% share as a **direct cost** is deliberate. It makes each cohort's P&L show the center's true margin, and it routes the partner's entitlement into their current account (3020/3021) as a payable rather than as an immediate cash outflow. This is what makes "profit allocated but not yet paid" visible — which is the single most useful number in a two-person partnership.

### 8.3 The journal

Double-entry underneath, **never exposed as debit/credit in the UI**. The owners record business events; the system generates balanced journal lines.

**`journal_entries`** — append-only. No `updated_at`, no soft deletes.
```
id, entry_number (unique, sequential, e.g. JE-2026-000142),
entry_date (date), description_ar (string),
reference_type, reference_id (polymorphic: cohort, enrollment, settlement, expense, manual),
cohort_id (FK nullable, denormalized for fast per-cohort reporting),
invoicing_entity_id (FK nullable),
status (enum: posted, reversed),
reversed_by_entry_id (FK nullable),
created_by (FK users), created_at
```

**`journal_lines`** — append-only.
```
id, journal_entry_id (FK), account_id (FK -> accounts),
debit_baisa (bigint, default 0), credit_baisa (bigint, default 0),
partner_id (FK nullable),        -- for 3020/3021/5010 attribution
cohort_id (FK nullable),         -- for per-cohort P&L
memo_ar (nullable), line_order
```

**Invariants, enforced in a database transaction and asserted in tests:**
1. For every entry, `SUM(debit_baisa) = SUM(credit_baisa)`.
2. Every line has exactly one of debit or credit non-zero.
3. No line has a negative amount.
4. A posted entry is never updated or deleted. To correct: create a reversing entry, link it via `reversed_by_entry_id`, and set the original's status to `reversed`.
5. Entry numbers are gapless and sequential per year. Generate inside the transaction with a row lock.

**Guard:** an Eloquent observer that throws on `updating` and `deleting` for both journal tables. Plus DB-level `REVOKE UPDATE, DELETE` on those tables for the app user if your deployment permits it.

### 8.4 Event → journal mapping

The UI presents these as five simple actions. Each generates the journal lines shown.

**A. Record revenue for a cohort** (invoice issued / income earned)
```
Dr 1100 ذمم مدينة            [amount]
   Cr 4010 إيرادات الدورات والورش      [amount]
```
If VAT-registered and the supply is standard-rated, split:
```
Dr 1100                       [gross]
   Cr 4010                              [net]
   Cr 2100 ضريبة القيمة المضافة المستحقة  [vat]
```

**B. Record payment received**
```
Dr 1020 حساب المركز البنكي     [amount]
   Cr 1100 ذمم مدينة                   [amount]
```

**C. Record a direct cohort cost** (ads, venue, materials)
```
Dr 5030 إعلانات مأجورة        [amount]   (cohort_id set)
   Cr 1020 حساب المركز البنكي           [amount]
```

**D. Record a center operating expense** (subscription, hosting)
```
Dr 6010 اشتراكات برمجية       [amount]   (cohort_id null)
   Cr 1020 حساب المركز البنكي           [amount]
```

**E. Settlement** — generated by the distribution engine, Section 8.5.

**F. Partner payout** (moving allocated profit out of the current account into their pocket)
```
Dr 3021 الحساب الجاري — عمار   [amount]
   Cr 1020 حساب المركز البنكي           [amount]
```

**G. Capital contribution** (partner funds the center)
```
Dr 1020 حساب المركز البنكي     [amount]
   Cr 3011 رأس المال — عمار             [amount]
```

### 8.5 The distribution engine

This encodes **المادة الثانية** of the partnership contract. It is the most business-critical code in the system and must be pure, deterministic, and exhaustively tested.

**`distribution_policies`** — seeded with the contract's three cases, editable by owners, versioned.
```
id, code (unique), name_ar, description_ar,
deliverer_share_percent (decimal 5,2, nullable),
external_fee_mode (enum: none, fixed),
deduct_direct_costs_first (bool, default true),
center_split_mode (enum: by_ownership, custom),
is_active, effective_from, effective_to (nullable),
version (unsigned int)
```

**Seeded policies:**

| Code | Arabic name | Deliverer | Center | Notes |
|---|---|---|---|---|
| `EXTERNAL_INVITATION` | العمل الخارجي (دعوة شخصية لأحد الشريكين) | 80% to the delivering partner | 20% | Direct costs deducted first |
| `OMRAN_ORGANIZED` | دورة من تنظيم عمران (تقديم أحد الشريكين) | 70% to the delivering partner | 30% | Paid advertising deducted first — explicit in contract |
| `EXTERNAL_TRAINER` | استقطاب مدرب خارجي (تنظيم وتسويق عمران) | Fixed fee (Fix Fee) | Remainder | Fee agreed in advance based on estimated profit |

**Algorithm** — `App\Finance\DistributionEngine::compute(Cohort $cohort): DistributionResult`

```
1. gross_revenue      = SUM of 4xxx credits for this cohort
2. direct_costs       = SUM of 5020–5090 debits for this cohort
                        (EXCLUDES 5010, which is the output of this calculation)
3. net_distributable  = gross_revenue - direct_costs

4. IF net_distributable <= 0:
       deliverer_share = 0
       center_share    = net_distributable        // negative → loss to center
       flag as LOSS, require explicit owner confirmation to settle
       GOTO 7

5. SWITCH policy.external_fee_mode:
     CASE 'fixed':
         deliverer_share = cohort.external_fee_baisa
         IF deliverer_share > net_distributable:
             flag OVERCOMMITTED, block settlement, require owner override
         center_share = net_distributable - deliverer_share
     CASE 'none':
         deliverer_share = allocate(net_distributable, policy.deliverer_share_percent)
         center_share    = net_distributable - deliverer_share

6. Split deliverer_share across cohort_deliverers by share_weight
   using largest-remainder allocation (8.7)

7. Split center_share across active partners by ownership_percent
   using largest-remainder allocation

8. ASSERT: SUM(all deliverer allocations) + SUM(all partner allocations)
           == net_distributable    // exact, to the baisa
```

**Journal entries produced on settlement:**

```
For each deliverer allocation:
    IF partner:
        Dr 5010 حصة الشريك المنفذ       [amount]  (partner_id, cohort_id)
           Cr 302X الحساب الجاري — <partner>       [amount]
    IF external instructor:
        Dr 5020 أتعاب مدرب خارجي         [amount]  (cohort_id)
           Cr 2020 مستحقات مدربين خارجيين          [amount]

For each partner's share of center profit:
    Dr 3090 الأرباح المحتجزة            [amount]
       Cr 302X الحساب الجاري — <partner>          [amount]
```

**`settlements`**
```
id, settlement_number (unique), type (enum: cohort, monthly),
cohort_id (FK nullable), period_start (nullable), period_end (nullable),
gross_revenue_baisa, direct_costs_baisa, net_distributable_baisa,
deliverer_total_baisa, center_share_baisa,
center_opex_allocated_baisa, distributable_profit_baisa,
status (enum: draft, confirmed, posted, reversed),
journal_entry_id (FK nullable), computed_at, confirmed_by, confirmed_at,
notes_ar (nullable), snapshot (json)   -- full computation frozen at confirmation
```

The `snapshot` column stores the entire computation as JSON at the moment of confirmation. If a policy changes later, historical settlements still explain themselves. **This is not optional** — it is what makes the system auditable.

**Workflow:** compute (draft, recalculable) → owner reviews the breakdown on screen → confirm (freezes snapshot, posts journal entry, becomes immutable) → payouts recorded separately as they happen.

Contract Article 3.3 requires settlement after each training activity **or** at month end. Support both: `type = cohort` and `type = monthly`.

### 8.6 Center operating expenses — the decision the contract does not make

**Read this to the owners before shipping.**

The contract deducts only *direct* course costs before splitting. It is silent on indirect costs — subscriptions, hosting, design, bank fees. Under a literal reading, the center's 20%/30% is split 50/50 and operating expenses come from nowhere.

**Default implemented:** center operating expenses for the period are charged against the center pool **before** the 50/50 split. The monthly settlement screen shows both figures:

```
حصة المركز من الدورات (حسب العقد)          255.000 ر.ع.
ناقص: مصروفات تشغيلية للفترة               -42.500 ر.ع.
─────────────────────────────────────────────────────
الأرباح القابلة للتوزيع                     212.500 ر.ع.
   حمد (50%)                               106.250 ر.ع.
   عمار (50%)                              106.250 ر.ع.
```

Make this configurable via a setting `opex_charged_to_center_pool` (default `true`). If set to `false`, opex accumulates in retained earnings and is settled annually. **Get both partners to agree explicitly and record the date in `/docs/DECISIONS.md`.**

### 8.7 Rounding

Percentage splits produce fractions of a baisa. Use **largest-remainder allocation** so shares always sum exactly to the total.

```php
public function allocate(int $total, array $weights): array
{
    $sum = array_sum($weights);
    $shares = [];
    $remainders = [];
    $allocated = 0;

    foreach ($weights as $key => $weight) {
        $exact = $total * $weight / $sum;
        $floor = intdiv((int)($total * $weight), $sum);
        $shares[$key] = $floor;
        $remainders[$key] = $exact - $floor;
        $allocated += $floor;
    }

    $leftover = $total - $allocated;
    arsort($remainders);           // largest remainder gets the extra baisa first
    foreach (array_keys($remainders) as $key) {
        if ($leftover <= 0) break;
        $shares[$key]++;
        $leftover--;
    }

    return $shares;                // guaranteed: array_sum($shares) === $total
}
```

Handle negative totals (loss allocation) by allocating the absolute value and negating the result.

### 8.8 Golden test cases

These must pass exactly. Write them as Pest tests in Phase 5 before writing the engine.

**Test 1 — External invitation, no direct costs** *(the وزارة الأوقاف engagement)*
```
Policy: EXTERNAL_INVITATION
Gross revenue: 640.000 OMR   (640000 baisa)
Direct costs:    0.000
Deliverer: Ammar (100% weight)

Expected:
  net_distributable   = 640000
  Ammar (deliverer)   = 512000   (80%)
  center_share        = 128000   (20%)
  Hamad (center 50%)  =  64000
  Ammar (center 50%)  =  64000
  ─────────────────────────────
  Ammar total         = 576000   (576.000 ر.ع.)
  Hamad total         =  64000   ( 64.000 ر.ع.)
  Sum                 = 640000  ✓
```

**Test 2 — Omran-organized course with advertising**
```
Policy: OMRAN_ORGANIZED
Gross revenue: 1000.000 OMR
Direct costs (5030 ads): 150.000 OMR
Deliverer: Hamad (100% weight)

Expected:
  net_distributable   = 850000
  Hamad (deliverer)   = 595000   (70%)
  center_share        = 255000   (30%)
  Hamad (center 50%)  = 127500
  Ammar (center 50%)  = 127500
  ─────────────────────────────
  Hamad total         = 722500
  Ammar total         = 127500
  Sum                 = 850000  ✓
```

**Test 3 — External trainer, fixed fee**
```
Policy: EXTERNAL_TRAINER
Gross revenue: 2000.000 OMR
Direct costs (ads): 300.000 OMR
External fee: 800.000 OMR

Expected:
  net_distributable   = 1700000
  External trainer    =  800000  → Cr 2020 مستحقات مدربين خارجيين
  center_share        =  900000
  Hamad               =  450000
  Ammar               =  450000
  Sum                 = 1700000 ✓
```

**Test 4 — Rounding, indivisible amount**
```
Policy: OMRAN_ORGANIZED
net_distributable = 333333 baisa (333.333 OMR)

Expected:
  deliverer (70%)     = 233333
  center (30%)        = 100000
  Hamad               =  50000
  Ammar               =  50000
  Sum                 = 333333 ✓   — no baisa lost, no baisa invented
```

**Test 5 — Loss**
```
Policy: OMRAN_ORGANIZED
Gross revenue: 200.000 OMR
Direct costs:  300.000 OMR

Expected:
  net_distributable   = -100000
  deliverer share     =       0
  center_share        = -100000
  Hamad               =  -50000
  Ammar               =  -50000
  Settlement flagged LOSS, blocked pending explicit owner confirmation
```

**Test 6 — Co-delivery**
```
Policy: OMRAN_ORGANIZED
net_distributable = 1000000
Deliverers: Hamad 60% weight, Ammar 40% weight

Expected:
  deliverer pool      = 700000
    Hamad             = 420000
    Ammar             = 280000
  center_share        = 300000
    Hamad             = 150000
    Ammar             = 150000
  Hamad total         = 570000
  Ammar total         = 430000
  Sum                 = 1000000 ✓
```

**Test 7 — Overcommitted fixed fee**
```
Policy: EXTERNAL_TRAINER
net_distributable = 500000
External fee      = 800000

Expected: settlement blocked, flag OVERCOMMITTED, owner override required,
          center_share would be -300000
```

**Test 8 — Journal balance invariant**
```
For every settlement posted in tests 1–7 (where posting is permitted):
  SUM(debit_baisa) === SUM(credit_baisa) for the generated journal entry
```

### 8.9 Tax layer (Oman)

**Verified as of August 2026. Verify again before relying on it — see the caveats.**

#### VAT
- Standard rate **5%**.
- **Mandatory registration** when taxable supplies exceed **OMR 38,500** over a rolling 12-month period (looking back 12 months, or forward if expected to be exceeded).
- **Voluntary registration** available from **OMR 19,250**.
- Filing is quarterly through the OTA portal.
- Registration must be applied for promptly on crossing the threshold; penalties for late registration are significant.

**Build a VAT threshold monitor.** Dashboard widget, always visible to owners:
```
الإيرادات الخاضعة للضريبة — آخر 12 شهراً
████████████░░░░░░░░░░░░  18,420 / 38,500 ر.ع.  (47.8%)

⚠️ حد التسجيل الاختياري: 19,250 ر.ع. — متبقٍ 830 ر.ع.
```
Rolling 12-month sum of taxable supplies, recomputed nightly and on every revenue posting. Alert states: green below 19,250 · amber 19,250–30,000 (voluntary registration becomes worth considering) · orange 30,000–38,500 (prepare to register) · red above 38,500 (**register now**).

**VAT treatment field on every revenue line:** `standard` (5%) · `zero_rated` · `exempt` · `out_of_scope`. Default `standard`. Even while unregistered, record it — it makes the threshold calculation correct and gives you history if you register later.

> **Caveat to raise with a tax advisor, not to decide in code:** Oman's VAT exemption covers education including vocational and technical training, but the exemption attaches to *licensed educational institutions*. Commercial workshops and consulting delivered by an unlicensed center are almost certainly standard-rated. Note also that *exempt* is worse than *standard* for a business with costs: exempt supplies block input VAT recovery, while standard-rated supplies allow it. Do not assume the exemption is good news.

#### Corporate income tax
- Standard rate **15%**.
- **Reduced 3% rate** for Omani establishments and LLCs meeting all of: registered capital not exceeding **OMR 60,000**, gross income not exceeding **OMR 150,000** in the tax year, and average employees not exceeding **25** — and not operating in excluded sectors (air/sea transport, natural resource extraction, banking, insurance, financial services, public utility concessions).
- SMEs on the 3% rate still file annual returns.

> **Note the discrepancy:** older published sources state the thresholds as OMR 50,000 capital / OMR 100,000 gross income / 15 employees. The figures above are from PwC's Worldwide Tax Summaries as updated July 2026 and appear to reflect a raise. **Confirm the current figures with the Oman Tax Authority or an advisor before relying on the tax estimate screen.** Store all three thresholds as editable settings, not constants.

#### Personal income tax
Royal Decree No. 56/2025, published 30 June 2025: a **5% tax on individuals whose annual gross income exceeds OMR 42,000**, effective **1 January 2028**. Executive regulations were due within 12 months of publication — check whether they have been issued and what they say about business income for individuals.

**Why this matters to the software now:** if the partnership never registers a company and each partner takes distributions as personal income, PIT lands on the individual from 2028. Partner statements need to accumulate annual distribution totals per partner from day one, so that in 2028 each partner already has three years of clean records. Build the annual partner income summary in Phase 6.

#### E-invoicing
- Oman Tax Authority Decision **No. 189/2026** (9 August 2026) set the mandatory rollout: **Phase 1 from 1 April 2027** for VAT-registered taxpayers with annual supplies above OMR 5 million; **Phase 2 from 1 October 2027** for VAT-registered taxpayers at or below OMR 5 million.
- A voluntary pilot with roughly 100 large taxpayers runs from late August 2026.
- The obligation is **tied to VAT registration**. Businesses below the VAT threshold are out of scope.
- Format: PINT-OM (Peppol-aligned UBL XML), transmitted through an accredited service provider to the OTA's **Fawtara** portal. Electronic archiving mandatory for **10 years**.

**Build now, cheaply:** structure the `invoices` table with PINT-OM-shaped fields — seller identifiers, buyer identifiers, unique invoice number, issue date, line-level tax categories, currency code, and a stable document identifier. You are not integrating with Fawtara. You are making sure that when you register for VAT and Phase 2 catches you, the data already exists in the right shape.

#### The tax estimate screen
A single admin page, updated live:
```
تقدير الالتزامات الضريبية — السنة المالية 2026

الإيرادات الخاضعة للضريبة (12 شهراً متحركة)     18,420.000 ر.ع.
حالة تسجيل القيمة المضافة                        غير مسجل
   حد التسجيل الإلزامي                          38,500.000 ر.ع.
   المتبقي حتى الحد                             20,080.000 ر.ع.

صافي الربح المقدر للسنة                          9,340.000 ر.ع.
شريحة ضريبة الدخل المتوقعة                       3% (منشأة صغيرة)
التقدير الأولي للضريبة                             280.200 ر.ع.

⚠️ هذا تقدير تقريبي وليس إقراراً ضريبياً. راجع مستشاراً ضريبياً.
```
That disclaimer line is mandatory and must not be removable.

### 8.10 Reports

| Report | Content | Notes |
|---|---|---|
| **قائمة الدخل** (Income statement) | Revenue − direct costs − opex = net profit, by period | Both cash and accrual view, toggle |
| **التدفق النقدي** (Cash flow) | Direct method: cash in, cash out, net change, closing balance | Simplest correct form |
| **ربحية الدورة** (Per-cohort P&L) | Revenue, each direct cost line, deliverer share, center share, margin % | The most-used report |
| **كشف حساب الشريك** (Partner statement) | Opening balance, allocations, payouts, closing balance, per period | Per partner |
| **الذمم المدينة** (Receivables aging) | 0–30 / 31–60 / 61–90 / 90+ days | Ministries pay slowly. This matters. |
| **مراقب حد القيمة المضافة** (VAT monitor) | 8.9 | Dashboard widget + full page |
| **الملخص السنوي للشريك** (Annual partner income) | Total distributions per calendar year | For PIT readiness from 2028 |

Every report exports to **XLSX** (owners will send these to an accountant) and **PDF** (Arabic-shaped, RTL, with the logo). Use a PDF library with proper Arabic shaping — test with a real Arabic string containing a lam-alef ligature and Arabic-Indic diacritics before committing to a library.

---

## 9. Authentication and authorization

### 9.1 Roles

| Role | Arabic | Capabilities |
|---|---|---|
| `owner` | مالك | Everything, including settlements, distribution policies, user management, chart of accounts |
| `admin` | مدير | Courses, cohorts, enrollments, record income and expenses. **Cannot** confirm settlements, change distribution policies, or view partner statements other than aggregate |
| `coordinator` | منسق | Courses, cohorts, enrollments, attendance. **No financial access at all** |
| `viewer` | مطلع | Read-only on courses and cohorts. No financial access |

Hamad and Ammar are both `owner`. Do not build a "super admin" above owner.

### 9.2 Permissions

Granular, via `spatie/laravel-permission`. Minimum set:
```
courses.view courses.create courses.update courses.delete courses.publish
cohorts.view cohorts.create cohorts.update cohorts.delete
enrollments.view enrollments.manage enrollments.export
links.create links.revoke
finance.view finance.record_income finance.record_expense
finance.settle finance.reverse finance.manage_accounts finance.manage_policies
partners.view_own_statement partners.view_all_statements partners.record_payout
reports.view reports.export reports.tax
users.view users.manage roles.manage
settings.manage audit.view
```

### 9.3 Route protection

Three layers, all present:
1. **Route middleware:** every admin route group behind `['auth', 'verified', 'role:owner|admin|coordinator|viewer', '2fa']`.
2. **Policies:** a policy class per model. `authorize()` in every controller action.
3. **Livewire component authorization:** see 9.4.

Admin routes live under a distinct prefix (`/admin` or `/لوحة` — use `/admin`, keep URLs ASCII). Public routes never share a middleware group with admin routes.

### 9.4 Livewire authorization — the critical vulnerability

**Every public method on a Livewire component is a directly callable HTTP endpoint.** An attacker who reaches the component can call any public method with any arguments, regardless of what the rendered UI shows. Authorizing in `mount()` protects nothing.

Mandatory pattern:

```php
class CohortSettlement extends Component
{
    #[Locked] public int $cohortId;          // Locked: cannot be tampered from the browser

    public function mount(int $cohortId): void
    {
        $this->authorize('settle', Cohort::findOrFail($cohortId));
        $this->cohortId = $cohortId;
    }

    public function confirmSettlement(): void
    {
        // Re-authorize. Every time. mount() is not a gate on this method.
        $cohort = Cohort::findOrFail($this->cohortId);
        $this->authorize('settle', $cohort);
        // ...
    }
}
```

Rules:
- `#[Locked]` on **every** property holding an ID, a price, a percentage, or a status.
- Re-authorize inside **every** action method, not only in `mount()`.
- Never pass an Eloquent model as a public property when an ID will do.
- Never trust a value that arrived from the browser as an authorization input.
- Add a Pest test per financial component asserting that a `coordinator` calling the action method directly receives a 403.

### 9.5 Two-factor authentication

- **Mandatory for `owner` and `admin`.** Enforced by middleware — a user with those roles and no confirmed 2FA is redirected to setup and cannot reach any other route.
- TOTP via authenticator app. Recovery codes generated, shown once, stored encrypted.
- Optional for `coordinator` and `viewer`.

### 9.6 Session and account security

- Session lifetime 120 minutes, sliding. `SESSION_SECURE_COOKIE=true`, `SameSite=Lax`.
- Login rate limiting: 5 attempts per email+IP per minute, then exponential backoff.
- Active session list with device, IP, last activity, and a revoke button.
- Email notification on login from a new device.
- Password: minimum 12 characters, checked against the HaveIBeenPwned API via Laravel's `Password::uncompromised()`.

---

## 10. Security requirements

- **Headers:** CSP (no `unsafe-inline`; use nonces for Livewire's inline scripts), HSTS with preload, `X-Frame-Options: DENY`, `X-Content-Type-Options: nosniff`, `Referrer-Policy: strict-origin-when-cross-origin`, `Permissions-Policy` denying camera/microphone/geolocation.
- **`APP_DEBUG=false` in production.** Verify in a deployment smoke test, not by trusting the `.env`.
- **File uploads:** validate real MIME by content not extension, cap at 10MB, store on a **private** disk outside the web root, serve only via signed temporary URLs from an authorizing controller. Reject SVG uploads entirely.
- **Mass assignment:** `$guarded = []` is forbidden on any financial model. Explicit `$fillable` everywhere.
- **Encrypted casts** on `bank_account`, `civil_number`, `two_factor_secret`, `two_factor_recovery_codes`.
- **Registration link tokens:** 32 bytes from `random_bytes()`, base62-encoded. Rate limit the public registration endpoint per IP. Never sequential, never guessable.
- **Audit log** on every financial mutation and every permission change. Immutable, viewable by owners only.
- **Backups:** `spatie/laravel-backup` daily, database plus the private uploads disk, encrypted, shipped off-server. **Test a restore before go-live and write the result in `/docs/DECISIONS.md`.** An untested backup is not a backup.
- **Secrets** in environment variables only. No credentials in the repository, ever, including in seeders.

---

## 11. Performance

Target: **LCP under 1.5s on 4G mobile** for the public landing page; **under 400ms** for admin page transitions.

### 11.1 Server
- **FrankenPHP or Laravel Octane** in production. Watch for state leakage between requests — audit singletons and static properties.
- `php artisan config:cache route:cache view:cache event:cache` in the deploy script.
- OPcache enabled with `opcache.validate_timestamps=0` in production.
- Redis for cache, session, and queue.

### 11.2 Database
- Index every column used in `WHERE`, `ORDER BY`, or `JOIN`. Composite indexes in the order the query uses them.
- `Model::preventLazyLoading(! app()->isProduction())` in `AppServiceProvider`.
- `select()` only the columns needed. Never `SELECT *` on a list view.
- **Materialized summary tables** for financial dashboards. `cohort_financial_summaries` holds pre-computed gross revenue, direct costs, deliverer share, center share, and margin per cohort. Refresh via a queued job on journal write. Dashboards read summaries, never aggregate the journal live.
- Paginate everything. `simplePaginate()` where a total count is not needed.
- Queue every report export, every PDF generation, every email.

### 11.3 Livewire
- `wire:navigate` on all internal admin links — SPA-like navigation without an SPA.
- `#[Lazy]` on heavy dashboard widgets so the shell paints immediately and widgets stream in.
- `#[Computed(persist: true, seconds: 300)]` for expensive derived values.
- `wire:model.blur` or `wire:model.live.debounce.400ms` — **never bare `wire:model.live`** on a text input.
- `wire:key` on every element inside a loop.
- `wire:loading.attr="disabled"` plus `wire:target` on every submit button.
- Keep component payloads small — a component holding a large collection in a public property re-serializes it on every request.

### 11.4 Frontend
- Vite with code splitting; defer non-critical JS.
- **Arabic font subsetting is the biggest single win.** Subset to Arabic + Latin basic + punctuation, `woff2` only, `<link rel="preload">` the two above-the-fold faces, `font-display: swap`. Budget under 120KB total fonts.
- Images: AVIF with WebP fallback, responsive `srcset`, `loading="lazy"` below the fold, explicit `width`/`height` to prevent CLS.
- Brotli compression, HTTP/2 or HTTP/3.
- Critical CSS inlined for the landing page.
- CDN for static assets (Cloudflare is fine and cheap).

### 11.5 Budgets — enforce in CI with Lighthouse
| Metric | Public | Admin |
|---|---|---|
| LCP | < 1.5s | < 2.0s |
| CLS | < 0.05 | < 0.05 |
| INP | < 150ms | < 200ms |
| JS bundle (gzipped) | < 120KB | < 200KB |
| Fonts | < 120KB | < 120KB |
| Lighthouse Performance | ≥ 95 | ≥ 90 |

---

## 12. UI/UX system

### 12.1 Mobile-first is literal

The owners run the business from phones. Design every admin screen at **375px first**, then scale up. If a feature is unusable one-handed on a phone, it is not done.

- **Bottom navigation bar** on mobile admin: الرئيسية · الدورات · المالية · المزيد. Fixed, thumb-reachable.
- **Tables become cards below 768px.** Never a horizontally scrolling table on mobile. Each row becomes a card with the two most important fields prominent and the rest collapsed.
- **Primary actions in the bottom third of the screen** where thumbs reach. Not top-right.
- **Sticky action bar** on forms — save button always visible, never requiring a scroll to the bottom.
- Minimum tap target **44×44px**.
- `inputmode="decimal"` on money fields so phones show a numeric keypad.
- Date pickers must be usable with a thumb. Test on a real phone, not a browser emulator.

### 12.2 Feedback — required on every interaction

**Toasts** — one global Livewire component, dispatched via events.
```php
$this->dispatch('toast', type: 'success', message: __('finance.expense_recorded'));
```
Types: `success` (green), `error` (red), `warning` (amber), `info` (blue). Auto-dismiss after 4s except errors, which persist until dismissed. Positioned bottom-center on mobile, top-start on desktop. Stack, do not overlap.

**Button loading states** — every single submit:
```blade
<button wire:click="save" wire:loading.attr="disabled" wire:target="save"
        class="btn-primary">
    <span wire:loading.remove wire:target="save">{{ __('common.save') }}</span>
    <span wire:loading wire:target="save" class="flex items-center gap-2">
        <x-spinner class="w-4 h-4" />
        {{ __('common.saving') }}
    </span>
</button>
```

**Required across the app:**
- Skeleton loaders for lazy components — never a blank area, never a bare spinner on a full page
- Empty states with an illustration, an Arabic explanation, and a primary action ("لا توجد دورات بعد — أنشئ أول دورة")
- Confirmation modals for every destructive action, naming what will be deleted
- Inline field validation in Arabic, appearing on blur not on every keystroke
- Optimistic UI for toggles and quick status changes, with rollback on failure
- `wire:dirty` unsaved-changes warning before navigating away from a form
- Success states that confirm what happened, not just that something happened ("تم تسجيل مصروف بقيمة 42.500 ر.ع. لدورة الذكاء الاصطناعي")

### 12.3 Minimize friction

The owner's instruction was "minimize functionality" — meaning fewer clicks, not fewer features.

- **Quick-add expense from anywhere:** a floating action button on mobile opens a sheet with amount, category, cohort (optional), photo of receipt. Four taps to record. This is the single most-used flow — optimize it above everything else.
- **Smart defaults everywhere:** today's date, most-used category, last-used cohort, default invoicing entity.
- **Receipt photo → attach.** Do not build OCR. Just attach the photo and let the human type the amount.
- **Command palette** on desktop (`Cmd/Ctrl+K`) for jumping to any course, cohort, or report.
- **One-tap settlement review:** the settlement screen shows the full breakdown on one screen with a single confirm button. No wizard.

---

## 13. Build phases

Each phase: **Goal** · **Build** · **Acceptance criteria** · **Do not build yet**.
Stop at the end of each phase and report before continuing.

---

### Phase 0 — Foundation

**Goal:** a running, RTL-correct, Arabic-first Laravel + Livewire application with the design system in place and nothing else.

**Build**
- Laravel project, latest stable. State the exact versions in `/docs/DECISIONS.md`.
- Livewire, Alpine, Tailwind, Vite configured.
- Docker Compose for local dev: app, database, Redis.
- `.env.example` complete with every variable the project will eventually need.
- RTL layout shell: `<html lang="ar" dir="rtl">`, Arabic fonts loaded and subsetted, base typography scale.
- Design tokens in `tailwind.config.js` from `/brand/` assets (fallback palette in 5.1 if assets are unreadable — say so in your report).
- Base component library: `x-button`, `x-input`, `x-select`, `x-textarea`, `x-modal`, `x-toast`, `x-spinner`, `x-card`, `x-badge`, `x-empty-state`, `x-skeleton`. All RTL-correct, all Arabic-labelled.
- `lang/ar/common.php` with the base string set.
- CI: Pint, Larastan level 6, Pest, and a grep that fails on physical-direction Tailwind utilities.
- `/docs/DECISIONS.md`, `/docs/PHASES.md` initialized.

**Acceptance**
- [ ] `php artisan serve` renders a styled Arabic RTL page with correct fonts
- [ ] No physical-direction Tailwind utility appears anywhere — CI check passes
- [ ] Every base component renders correctly at 375px, 768px, and 1440px
- [ ] Lighthouse performance ≥ 95 on the blank shell
- [ ] Total font payload under 120KB
- [ ] CI green

**Do not build yet:** auth, models, any business logic.

---

### Phase 1 — Identity and access

**Goal:** nobody gets into the admin panel who should not, and everything they do is logged.

**Build**
- `users`, `partners`, `invoicing_entities` tables and models
- Fortify/Breeze auth, customized: Arabic login, no public registration route (users are created by owners)
- `spatie/laravel-permission` with the full role and permission set from 9.1–9.2
- Mandatory 2FA for `owner` and `admin`, with the enforcing middleware
- Session security per 9.6: rate limiting, session list, new-device email, password policy
- `spatie/laravel-activitylog` configured
- Admin layout shell: sidebar on desktop, bottom nav on mobile, user menu, logout
- Seeder: two owner users (Hamad, Ammar), two partner records at 50% each, one default invoicing entity
- Security headers middleware per Section 10

**Acceptance**
- [ ] An unauthenticated request to any `/admin/*` route redirects to login
- [ ] A `coordinator` requesting a finance route gets 403
- [ ] An `owner` without confirmed 2FA cannot reach any route except 2FA setup
- [ ] Login rate limiting triggers after 5 failed attempts and is tested
- [ ] `APP_DEBUG=false` produces no stack trace on a forced 500
- [ ] All security headers present — verify with `curl -I`
- [ ] Every role change appears in the activity log
- [ ] Pest: a test per role asserting exactly which routes are reachable

**Do not build yet:** courses, finance.

---

### Phase 2 — Catalog

**Goal:** the full course and cohort domain, manageable from the admin panel on a phone.

**Build**
- `categories`, `courses`, `cohorts`, `cohort_sessions`, `cohort_deliverers`, `instructors`, `clients` tables and models with relationships
- Admin CRUD for all of the above, Livewire, mobile-first
- Cohort status workflow with guarded transitions: `draft → announced → open → closed → delivered → settled`, plus `cancelled` from any state before `settled`
- Cohort deliverer assignment with weight validation (must sum to 100.00)
- Category seeder
- Course image upload with automatic AVIF/WebP conversion and responsive variants
- Slug generation from Arabic titles — transliterate to ASCII, ensure uniqueness

**Acceptance**
- [ ] Full CRUD works one-handed at 375px
- [ ] Cohort status transitions enforce the workflow — invalid transitions throw and are tested
- [ ] Deliverer weights that do not sum to 100 are rejected with an Arabic validation message
- [ ] Arabic slug generation produces clean unique ASCII slugs
- [ ] Course list with 200 seeded courses loads in under 300ms
- [ ] Zero N+1 queries — `preventLazyLoading` passes on every list and detail view
- [ ] Every list has a working empty state

**Do not build yet:** public site, enrollments, finance.

---

### Phase 3 — Public website

**Goal:** the public face of the center, Arabic, fast, and visually distinctive.

**Build**
- Landing page per the design thesis in Section 5.2 — this is the phase where the design risk gets taken. Do not ship a template.
- Course catalog with category filtering
- Course detail pages
- Cohort detail with schedule and CTA
- Instructor profiles
- About page
- Contact form with spam protection (honeypot + rate limit, not a third-party captcha)
- Admin login entry point below the fold on the landing page
- SEO: Arabic meta tags, Open Graph with a proper Arabic OG image, `schema.org/Course` and `schema.org/EducationalOrganization` JSON-LD, XML sitemap, `robots.txt`
- Page-load animation sequence and scroll reveals, with full `prefers-reduced-motion` support

**Acceptance**
- [ ] LCP under 1.5s on simulated 4G mobile
- [ ] Lighthouse: Performance ≥ 95, Accessibility ≥ 95, SEO 100
- [ ] Correct at 375 / 768 / 1024 / 1440 / 2560px
- [ ] `prefers-reduced-motion: reduce` disables all animation
- [ ] Arabic text renders with correct shaping and ligatures in Safari, Chrome, and Firefox
- [ ] Zero layout shift on font load
- [ ] Screen reader announces Arabic content correctly with correct reading order

**Do not build yet:** English site, enrollment forms, finance.

---

### Phase 4 — Registration and enrollment

**Goal:** generated join links that work, and enrollment management that does not require a laptop.

**Build**
- `registration_links`, `enrollments`, `attendance_records` tables and models
- Link generator: label, optional price override, optional max uses, optional expiry, optional approval requirement
- Public registration page at `/join/{token}` — Arabic, mobile-first, minimal fields, clear success state
- Capacity enforcement with a race-condition-safe seat counter (row lock or atomic increment — a naive `seats_taken++` will oversell)
- Waitlist when capacity is reached
- Admin enrollment list: filter, search, bulk approve, export to XLSX
- Attendance marking per session, optimized for a phone in a classroom
- Enrollment confirmation email in Arabic

**Acceptance**
- [ ] An expired, revoked, or exhausted link shows a clear Arabic message, never a stack trace
- [ ] Concurrency test: 50 simultaneous registrations against 10 seats produces exactly 10 enrolled and 40 waitlisted
- [ ] Registration completes in under 60 seconds on a phone
- [ ] Attendance for 30 participants can be marked in under 90 seconds on a phone
- [ ] Registration endpoint is rate-limited per IP
- [ ] Tokens are not enumerable — test that sequential guesses fail

**Do not build yet:** payment processing, finance.

---

### Phase 5 — Financial core and the distribution engine

**Goal:** a correct, auditable ledger and an exactly correct implementation of the partnership contract.

**Build**
- `Money` value object with full test coverage, including `allocate()` per 8.7
- `MoneyCast` Eloquent cast
- `accounts` table seeded with the full chart from 8.2
- `journal_entries`, `journal_lines` with all invariants and the append-only guard from 8.3
- Gapless sequential entry numbering, transaction-safe
- The five simple recording actions from 8.4: record revenue, record payment, record direct cost, record operating expense, record partner payout
- Quick-add expense flow per 12.3 — this is the flow that gets used daily, build it properly
- Receipt photo attachment to any transaction
- `distribution_policies` seeded with the contract's three cases
- `DistributionEngine` per 8.5 — pure, deterministic, no DB writes in the calculation itself
- `settlements` with draft → confirmed → posted workflow and JSON snapshot freezing
- Loss handling and overcommitted-fee blocking with owner override
- `opex_charged_to_center_pool` setting per 8.6, defaulting to `true`
- Settlement review screen: full breakdown on one screen, single confirm
- Reversal flow: reversing entries, never edits

**Acceptance**
- [ ] **All eight golden tests in 8.8 pass with exact baisa values**
- [ ] Property-based test: 10,000 random revenue/cost/weight combinations always produce allocations summing exactly to `net_distributable`
- [ ] Every posted journal entry balances — asserted in a test that runs across all seeded and test-created entries
- [ ] Attempting to update or delete a posted journal entry throws
- [ ] Entry numbers are gapless under 100 concurrent creations
- [ ] A `coordinator` calling `confirmSettlement()` directly on the Livewire component receives 403 — test this by invoking the method, not by checking the UI
- [ ] Every `#[Locked]` property is verified locked by a tampering test
- [ ] Quick-add expense completes in four taps on a phone
- [ ] A settlement snapshot still renders correctly after its distribution policy is edited

**Do not build yet:** reports, tax screens.

---

### Phase 6 — Reporting and tax

**Goal:** every number the owners and their future accountant need.

**Build**
- `cohort_financial_summaries` materialized table with queued refresh on journal write
- All seven reports from 8.10
- Owner dashboard: cash position, MTD revenue, upcoming cohorts, unpaid receivables, VAT gauge, each partner's current-account balance
- VAT threshold monitor per 8.9, with the four alert states and nightly recomputation
- VAT treatment field on revenue lines
- Tax estimate screen per 8.9, with the non-removable disclaimer
- Annual partner income summary (PIT readiness)
- Tax threshold values as editable settings, not constants — VAT mandatory, VAT voluntary, CIT capital / gross income / employee limits, CIT rates, PIT threshold and effective date
- XLSX export for every report
- PDF export with correct Arabic shaping — **test with a lam-alef ligature and Arabic-Indic diacritics before choosing the library**
- `/docs/FINANCE.md`: a plain-Arabic explanation of the ledger for the owners, covering what each account means, how a settlement works, and what to hand an accountant

**Acceptance**
- [ ] Income statement ties exactly to the sum of journal lines — asserted in a test
- [ ] Cash flow closing balance equals the sum of 1010 + 1020 + 1030 balances
- [ ] Each partner statement's closing balance equals their current-account balance in the journal
- [ ] Dashboard loads in under 400ms with 5,000 journal entries seeded
- [ ] VAT monitor correctly computes a rolling 12-month window across a year boundary
- [ ] PDF exports render Arabic with correct shaping, ligatures, and RTL ordering
- [ ] XLSX exports open cleanly in Excel with RTL sheet direction
- [ ] Every threshold is editable in settings and no threshold is hardcoded anywhere

**Do not build yet:** English site.

---

### Phase 7 — Mobile polish and UX completion

**Goal:** the whole system is genuinely pleasant to use on a phone.

**Build**
- Full responsive audit of every admin screen at 375px, one-handed
- Bottom navigation, floating action button, sticky form action bars
- All tables converted to cards below 768px
- Skeleton loaders on every lazy component
- Empty states on every list
- Confirmation modals on every destructive action
- `wire:dirty` unsaved-changes guards
- Optimistic UI on toggles and status changes
- Command palette on desktop
- PWA: manifest, service worker for static asset caching, installable to home screen, Arabic app name and icons
- Full keyboard navigation and focus management on desktop

**Acceptance**
- [ ] Every admin screen is fully usable one-handed at 375px — verified on a real device, not an emulator
- [ ] Every submit button has a loading state — verified by a checklist walk of every form in the app
- [ ] Every destructive action has a confirmation naming what will be deleted
- [ ] No blank loading areas anywhere — skeletons everywhere
- [ ] PWA installs on iOS and Android with the correct Arabic name and icon
- [ ] Full keyboard navigation with visible focus rings and correct RTL tab order
- [ ] Axe accessibility scan passes with no critical or serious issues

---

### Phase 8 — Performance and hardening

**Goal:** production-ready.

**Build**
- Octane or FrankenPHP in production config, with a state-leakage audit
- Redis caching strategy with tagged invalidation
- Full index audit against the actual slow query log
- Lighthouse CI in the pipeline enforcing the budgets in 11.5
- Load test: 100 concurrent users on the public site, 20 on admin
- Security checklist walk-through of Section 10, item by item
- Backups configured, encrypted, off-server — **and a restore performed and documented**
- Error tracking (Sentry or equivalent) with Arabic-safe payloads
- Uptime monitoring
- Deployment script: zero-downtime, migrations, cache warming, health check
- `/docs/RUNBOOK.md`: how to deploy, roll back, restore a backup, rotate secrets

**Acceptance**
- [ ] Every performance budget in 11.5 met and enforced in CI
- [ ] Load test passes with p95 response under 500ms
- [ ] Every item in Section 10 verified and checked off in writing
- [ ] **A backup has been restored to a clean environment and the result documented**
- [ ] Zero-downtime deploy verified twice
- [ ] Rollback verified

---

### Phase 9 — English public site

**Goal:** English on the public site only, without touching the Arabic-first architecture.

**Build**
- `lang/en/*` for public-facing strings only
- `/en/*` route group with `dir="ltr"`
- `_en` content fields exposed in the admin course and instructor editors
- Language switcher on the public site only — **never in the admin panel**
- `hreflang` tags, per-locale sitemaps
- Latin typography pairing that sits correctly with the Arabic identity

**Acceptance**
- [ ] Arabic remains the default at `/` — no redirect based on browser language
- [ ] The admin panel has no language switcher and remains Arabic-only
- [ ] `dir` flips correctly and no layout breaks in either direction
- [ ] Missing English content falls back to Arabic gracefully, never to an empty page
- [ ] Both locales meet the Phase 3 performance budgets

---

## 14. Testing

**Framework:** Pest. **Minimum coverage:** 80% overall, **100% on `App\Finance`**.

| Layer | What |
|---|---|
| Unit | `Money`, `allocate()`, `DistributionEngine`, all status transitions |
| Feature | Every Livewire component, every route, every policy |
| Authorization | For each of the four roles, assert exactly which routes and component methods are reachable |
| Financial invariants | Journal balance, append-only enforcement, gapless numbering, allocation sums |
| Concurrency | Seat counter, entry numbering, settlement double-confirm |
| Property-based | Allocation across 10,000 random inputs |
| Browser (Dusk or Playwright) | The critical flows: register via link, record expense, confirm settlement |
| Performance | Lighthouse CI on public and admin |
| Accessibility | Axe on every public page |

**Required regression tests — write these first, in Phase 5, before the engine:**
1. The eight golden distribution tests (8.8)
2. Coordinator calling a finance Livewire method directly → 403
3. Tampering with a `#[Locked]` property → rejected
4. Editing a posted journal entry → throws
5. Concurrent registration against limited seats → no oversell

---

## 15. Deployment

- **Environments:** local (Docker), staging, production
- **Hosting:** any VPS with Docker, or Laravel Forge/Cloud. Prefer a region close to Oman for latency — UAE or Bahrain.
- **Zero-downtime deploys** with health checks and automatic rollback on failure
- **Migrations** run in the deploy, never manually
- **Cache warming** after deploy
- **Daily encrypted backups**, off-server, with a **monthly tested restore**
- **Error tracking and uptime monitoring** from day one of production

---

## Appendix A — Arabic terminology

Use these consistently. Do not invent alternatives.

| English | Arabic |
|---|---|
| Course | دورة |
| Workshop | ورشة |
| Cohort / delivery | مجموعة / دفعة |
| Session | جلسة |
| Instructor / trainer | مدرب |
| Partner | شريك |
| Enrollment | تسجيل |
| Participant | مشارك |
| Attendance | الحضور |
| Revenue | الإيرادات |
| Direct costs | التكاليف المباشرة |
| Operating expenses | المصروفات التشغيلية |
| Net profit | صافي الربح |
| Distribution | التوزيع |
| Settlement | التصفية |
| Partner current account | الحساب الجاري للشريك |
| Payout / drawing | مسحوبات |
| Receivables | الذمم المدينة |
| Payables | الذمم الدائنة |
| Income statement | قائمة الدخل |
| Cash flow | التدفق النقدي |
| Journal entry | قيد محاسبي |
| Chart of accounts | دليل الحسابات |
| VAT | ضريبة القيمة المضافة |
| Income tax | ضريبة الدخل |
| Invoice | فاتورة |
| Receipt | إيصال |
| Client | عميل |
| Save | حفظ |
| Cancel | إلغاء |
| Delete | حذف |
| Confirm | تأكيد |
| Edit | تعديل |
| Add | إضافة |
| Search | بحث |
| Export | تصدير |
| Loading | جارٍ التحميل |
| Saving | جارٍ الحفظ |

---

## Appendix B — Gaps in the partnership contract

These are business decisions the software has defaulted. **Both partners should agree explicitly and the decision recorded in `/docs/DECISIONS.md` with a date.** Every one of them is configurable in settings.

| # | Gap | Default implemented |
|---|---|---|
| 1 | Contract calls the center's 20%/30% "صافي الأرباح" but deducts only direct costs. Indirect costs are unaddressed. | Center operating expenses charged against the center pool before the 50/50 split. Setting: `opex_charged_to_center_pool = true`. Both figures shown side by side. |
| 2 | No loss rule. | If `net_distributable ≤ 0`: deliverer share = 0, loss to the center pool, split 50/50. Settlement blocked pending explicit owner confirmation. |
| 3 | Case 3 fixed fee "يُتفق عليه حسب تقدير الربح" — no protection if actual revenue misses the estimate. | Fee recorded before delivery. If it exceeds `net_distributable` at settlement, settlement is blocked and requires owner override with a written reason. |
| 4 | No revenue recognition timing. Ministries pay months late. | Accrual by default: revenue recognized on invoice, cash tracked separately. Both views available on the income statement. Settlement can be configured to trigger on delivery or on payment — default **on payment**, since distributing cash you have not received is how partnerships end badly. |
| 5 | No capital contribution or drawing tracking. | Accounts 3010/3011 (capital) and 3020/3021 (current accounts) exist from Phase 5. Every contribution and drawing is recorded. |
| 6 | No legal entity, therefore no defined invoicing vehicle. | `invoicing_entities` table. Every revenue transaction records which entity invoiced. Tax reports are grouped by entity. |
| 7 | No VAT treatment specified. | Every revenue line carries a VAT treatment field, default `standard`. Threshold monitor active from Phase 6. Unregistered until 38,500 is approached. |
| 8 | Co-delivery of one course by both partners is not addressed. | `cohort_deliverers` with weights. The deliverer share splits by weight; the center share still splits by ownership. |
| 9 | "أو نهاية كل شهر" — settlement cadence is ambiguous. | Both supported: per-cohort settlement and monthly settlement. Choose per cohort. |
| 10 | Contract is dated 2026 with blank day and month, and is unsigned in the supplied copy. | Not a software issue. Sign it. |

---

*End of specification.*
