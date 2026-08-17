/**
 * Phase 4 visual acceptance: join page + enrollments + attendance at 375px.
 * Usage: node scripts/phase4-shots.mjs <outDir> <joinUrl> <cohortId>
 */
import puppeteer from 'puppeteer-core';
import { existsSync } from 'node:fs';

const [outDir = '.', joinUrl, cohortId] = process.argv.slice(2);
const base = 'http://localhost:8080';

const chromePaths = [
    'C:/Program Files/Google/Chrome/Application/chrome.exe',
    'C:/Program Files (x86)/Google/Chrome/Application/chrome.exe',
];
const executablePath = chromePaths.find((p) => existsSync(p));
const browser = await puppeteer.launch({ executablePath, headless: 'new' });

try {
    const page = await browser.newPage();
    await page.setViewport({ width: 375, height: 900, deviceScaleFactor: 1 });

    // صفحة التسجيل العامة
    await page.goto(joinUrl, { waitUntil: 'networkidle0' });
    console.log(`join@375: overflow=${await page.evaluate(() => document.scrollingElement.scrollWidth - document.documentElement.clientWidth)}px`);
    await page.screenshot({ path: `${outDir}/p4-join-mobile.png`, fullPage: true, captureBeyondViewport: false });

    // الإدارة — دخول المنسق
    await page.goto(`${base}/login`, { waitUntil: 'networkidle0' });
    await page.type('input[name=email]', 'coord-demo@omran.local');
    await page.type('input[name=password]', 'Phase2-Demo-Pass-123');
    await Promise.all([
        page.waitForNavigation({ waitUntil: 'networkidle0' }),
        page.click('button[type=submit]'),
    ]);

    for (const [path, name] of [
        [`/admin/cohorts/${cohortId}/enrollments`, 'enrollments'],
        [`/admin/cohorts/${cohortId}/attendance`, 'attendance'],
        [`/admin/cohorts/${cohortId}`, 'cohort-links'],
    ]) {
        await page.goto(`${base}${path}`, { waitUntil: 'networkidle0' });
        console.log(`${name}@375: ${page.url().endsWith(path) ? 'ok' : page.url()} overflow=${await page.evaluate(() => document.scrollingElement.scrollWidth - document.documentElement.clientWidth)}px`);
        await page.screenshot({ path: `${outDir}/p4-${name}-mobile.png`, fullPage: true, captureBeyondViewport: false });
    }
} finally {
    await browser.close();
}
