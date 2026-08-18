/**
 * Phase 6 visual acceptance: dashboard with real numbers, reports hub,
 * tax estimate screen, settings. Logs horizontal overflow per page.
 * Usage: node scripts/phase6-shots.mjs <outDir>
 */
import puppeteer from 'puppeteer-core';
import { existsSync } from 'node:fs';

const outDir = process.argv[2] ?? '.';
const base = 'http://localhost:8080';

const chromePaths = [
    'C:/Program Files/Google/Chrome/Application/chrome.exe',
    'C:/Program Files (x86)/Google/Chrome/Application/chrome.exe',
];
const executablePath = chromePaths.find((p) => existsSync(p));
const browser = await puppeteer.launch({ executablePath, headless: 'new' });

const overflow = (page) =>
    page.evaluate(() => document.scrollingElement.scrollWidth - document.documentElement.clientWidth);

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

    const shots = [
        ['/admin', 'p6-dashboard-mobile'],
        ['/admin/reports?report=income', 'p6-report-income-mobile'],
        ['/admin/reports?report=cohorts', 'p6-report-cohorts-mobile'],
        ['/admin/reports?report=vat', 'p6-report-vat-mobile'],
        ['/admin/reports/tax', 'p6-tax-screen-mobile'],
        ['/admin/settings', 'p6-settings-mobile'],
    ];

    for (const [path, name] of shots) {
        await page.goto(`${base}${path}`, { waitUntil: 'networkidle0' });
        await new Promise((resolve) => setTimeout(resolve, 400));
        console.log(`${name}: url=${page.url()} overflow=${await overflow(page)}px`);
        await page.screenshot({ path: `${outDir}/${name}.png`, fullPage: true, captureBeyondViewport: false });
    }
} finally {
    await browser.close();
}
