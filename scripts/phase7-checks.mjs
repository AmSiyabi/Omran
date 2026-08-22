/**
 * Phase 7 acceptance harness:
 *  1. 375px walk of every admin screen — horizontal overflow + screenshot
 *  2. axe-core scan per screen (fails on critical/serious)
 *  3. PWA: manifest reachable + service worker registered
 *  4. Command palette: Ctrl+K opens, search returns, arrows + Enter navigate
 *  5. Modal focus trap: Tab stays inside an open dialog
 * Usage: node scripts/phase7-checks.mjs <outDir>
 */
import puppeteer from 'puppeteer-core';
import { existsSync, readFileSync } from 'node:fs';
import { createRequire } from 'node:module';

const require = createRequire(import.meta.url);
const axeSource = readFileSync(require.resolve('axe-core/axe.min.js'), 'utf8');

const outDir = process.argv[2] ?? '.';
const base = 'http://localhost:8080';

const chromePaths = [
    'C:/Program Files/Google/Chrome/Application/chrome.exe',
    'C:/Program Files (x86)/Google/Chrome/Application/chrome.exe',
];
const executablePath = chromePaths.find((p) => existsSync(p));
const browser = await puppeteer.launch({ executablePath, headless: 'new' });

const screens = [
    ['/admin', 'dashboard'],
    ['/admin/courses', 'courses'],
    ['/admin/courses/create', 'course-form'],
    ['/admin/categories', 'categories'],
    ['/admin/instructors', 'instructors'],
    ['/admin/clients', 'clients'],
    ['/admin/cohorts', 'cohorts'],
    ['/admin/finance', 'finance'],
    ['/admin/finance/settlements', 'settlements'],
    ['/admin/reports?report=cohorts', 'report-cohorts'],
    ['/admin/reports?report=aging', 'report-aging'],
    ['/admin/reports?report=annual', 'report-annual'],
    ['/admin/reports/tax', 'tax'],
    ['/admin/settings', 'settings'],
    ['/admin/security', 'security'],
    ['/admin/more', 'more'],
];

let failures = 0;

try {
    const page = await browser.newPage();
    await page.setViewport({ width: 375, height: 900, deviceScaleFactor: 1 });

    await page.goto(`${base}/login`, { waitUntil: 'networkidle0' });
    await page.type('input[name=email]', 'owner-demo@omran.local');
    await page.type('input[name=password]', 'Phase5-Demo-Pass-123');
    await Promise.all([
        page.waitForNavigation({ waitUntil: 'networkidle0' }),
        page.click('button[type=submit]'),
    ]);

    // ── 1+2: 375px walk + axe per screen ──
    for (const [path, name] of screens) {
        await page.goto(`${base}${path}`, { waitUntil: 'networkidle0' });
        await new Promise((r) => setTimeout(r, 500)); // lazy components settle

        const overflow = await page.evaluate(
            () => document.scrollingElement.scrollWidth - document.documentElement.clientWidth,
        );

        await page.evaluate(axeSource);
        const axe = await page.evaluate(() =>
            window.axe.run(document, { resultTypes: ['violations'] }),
        );
        const serious = axe.violations.filter((v) => ['critical', 'serious'].includes(v.impact));

        const ok = overflow === 0 && serious.length === 0;
        if (!ok) failures++;

        console.log(
            `${ok ? 'PASS' : 'FAIL'} ${name}: overflow=${overflow}px axe-serious=${serious.length}` +
                (serious.length ? ' → ' + serious.map((v) => `${v.id}(${v.impact})×${v.nodes.length}`).join(', ') : ''),
        );

        await page.screenshot({ path: `${outDir}/p7-${name}-375.png`, fullPage: true, captureBeyondViewport: false });
    }

    // ── 3: PWA signals ──
    await page.goto(`${base}/admin`, { waitUntil: 'networkidle0' });
    await new Promise((r) => setTimeout(r, 1200));
    const pwa = await page.evaluate(async () => {
        const link = document.querySelector('link[rel=manifest]');
        const manifest = link ? await (await fetch(link.href)).json() : null;
        const registration = await navigator.serviceWorker?.getRegistration();

        return {
            name: manifest?.name ?? null,
            dir: manifest?.dir ?? null,
            icons: manifest?.icons?.length ?? 0,
            swActive: !!(registration?.active || registration?.installing || registration?.waiting),
        };
    });
    const pwaOk = pwa.name === 'مركز عمران للتدريب والاستشارات' && pwa.dir === 'rtl' && pwa.icons === 4 && pwa.swActive;
    if (!pwaOk) failures++;
    console.log(`${pwaOk ? 'PASS' : 'FAIL'} pwa: name="${pwa.name}" dir=${pwa.dir} icons=${pwa.icons} sw=${pwa.swActive}`);

    // ── 4: command palette on desktop ──
    await page.setViewport({ width: 1280, height: 800, deviceScaleFactor: 1 });
    await page.goto(`${base}/admin`, { waitUntil: 'networkidle0' });
    await page.keyboard.down('Control');
    await page.keyboard.press('k');
    await page.keyboard.up('Control');
    await page.waitForSelector('[data-palette-input]', { visible: true });

    const paletteOpen = await page.evaluate(() => !!document.querySelector('[data-palette-item]'));
    await page.type('[data-palette-input]', 'التقارير', { delay: 30 });
    await page
        .waitForFunction(() => document.querySelectorAll('[data-palette-item]').length <= 3, { timeout: 5000 })
        .catch(() => {});
    const searchCount = await page.evaluate(() => document.querySelectorAll('[data-palette-item]').length);

    await page.keyboard.press('ArrowDown');
    await page.keyboard.press('ArrowUp');
    await Promise.all([
        page.waitForNavigation({ waitUntil: 'networkidle0' }).catch(() => {}),
        page.keyboard.press('Enter'),
    ]);
    await new Promise((r) => setTimeout(r, 600));
    const landedOnReports = page.url().includes('/admin/reports');
    const paletteOk = paletteOpen && searchCount >= 1 && landedOnReports;
    if (!paletteOk) failures++;
    console.log(`${paletteOk ? 'PASS' : 'FAIL'} palette: open=${paletteOpen} results=${searchCount} navigated=${landedOnReports} url=${page.url()}`);
    await page.screenshot({ path: `${outDir}/p7-palette-desktop.png` });

    // ── 5: focus trap in a Livewire modal (session revoke on /admin/security) ──
    await page.goto(`${base}/admin/security`, { waitUntil: 'networkidle0' });
    const hasRevoke = await page.evaluate(() => {
        const btn = [...document.querySelectorAll('button')].find((b) => b.textContent.includes('إنهاء الجلسة'));
        if (btn) btn.click();
        return !!btn;
    });

    if (hasRevoke) {
        await new Promise((r) => setTimeout(r, 700));
        const trapResult = await page.evaluate(() => !!document.querySelector('[role=dialog]'));
        let insideCount = 0;
        for (let i = 0; i < 6; i++) {
            await page.keyboard.press('Tab');
            const inside = await page.evaluate(
                () => !!document.querySelector('[role=dialog]')?.contains(document.activeElement),
            );
            if (inside) insideCount++;
        }
        const trapOk = trapResult && insideCount === 6;
        if (!trapOk) failures++;
        console.log(`${trapOk ? 'PASS' : 'FAIL'} focus-trap: dialog=${trapResult} tabs-inside=${insideCount}/6`);
        await page.screenshot({ path: `${outDir}/p7-revoke-modal.png` });
    } else {
        console.log('SKIP focus-trap: only current session exists (no revoke button)');
    }
} finally {
    await browser.close();
}

console.log(failures === 0 ? 'ALL CHECKS PASSED' : `${failures} CHECK(S) FAILED`);
process.exit(failures === 0 ? 0 : 1);
