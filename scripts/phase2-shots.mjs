/**
 * Phase 2 visual acceptance: catalog CRUD at 375px + desktop.
 * Usage: node scripts/phase2-shots.mjs <outDir>
 */
import puppeteer from 'puppeteer-core';
import { existsSync } from 'node:fs';

const outDir = process.argv[2] ?? '.';
const base = 'http://localhost:8080';

const chromePaths = [
    'C:\\Program Files\\Google\\Chrome\\Application\\chrome.exe',
    'C:\\Program Files (x86)\\Google\\Chrome\\Application\\chrome.exe',
];
const executablePath = chromePaths.find((p) => existsSync(p));
const browser = await puppeteer.launch({ executablePath, headless: 'new' });

const shots = [
    ['/admin/courses', 375, 'p2-courses-mobile'],
    ['/admin/courses', 1440, 'p2-courses-desktop'],
    ['/admin/courses/create', 375, 'p2-course-form-mobile'],
    ['/admin/cohorts', 375, 'p2-cohorts-mobile'],
    ['/admin/cohorts/1', 375, 'p2-cohort-show-mobile'],
    ['/admin/cohorts/1', 1440, 'p2-cohort-show-desktop'],
    ['/admin/categories', 375, 'p2-categories-mobile'],
];

try {
    const context = await browser.createBrowserContext();
    const page = await context.newPage();
    await page.setViewport({ width: 375, height: 900, deviceScaleFactor: 1 });

    // login once as coordinator
    await page.goto(`${base}/login`, { waitUntil: 'networkidle0' });
    await page.type('input[name=email]', 'coord-demo@omran.local');
    await page.type('input[name=password]', 'Phase2-Demo-Pass-123');
    await Promise.all([
        page.waitForNavigation({ waitUntil: 'networkidle0' }),
        page.click('button[type=submit]'),
    ]);

    for (const [path, width, name] of shots) {
        await page.setViewport({ width, height: 900, deviceScaleFactor: 1 });
        await page.goto(`${base}${path}`, { waitUntil: 'networkidle0' });
        const overflow = await page.evaluate(() =>
            document.scrollingElement.scrollWidth - document.documentElement.clientWidth);
        console.log(`${name}: ${page.url().replace(base, '')} overflow=${overflow}px`);
        await page.screenshot({ path: `${outDir}/${name}.png`, fullPage: true, captureBeyondViewport: false });
    }
} finally {
    await browser.close();
}
