/**
 * Phase 5 visual acceptance: finance hub, quick-add sheet, settlement flow.
 * Usage: node scripts/phase5-shots.mjs <outDir>
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

    // المركز المالي
    await page.goto(`${base}/admin/finance`, { waitUntil: 'networkidle0' });
    console.log(`finance-hub: ${page.url().endsWith('/admin/finance') ? 'ok' : page.url()} overflow=${await page.evaluate(() => document.scrollingElement.scrollWidth - document.documentElement.clientWidth)}px`);
    await page.screenshot({ path: `${outDir}/p5-finance-hub-mobile.png`, fullPage: true, captureBeyondViewport: false });

    // الإضافة السريعة — الزر العائم
    await page.click('[aria-label="مصروف سريع"]');
    await new Promise((resolve) => setTimeout(resolve, 700));
    await page.screenshot({ path: `${outDir}/p5-quick-add-mobile.png`, fullPage: false });

    // التصفيات
    await page.goto(`${base}/admin/finance/settlements`, { waitUntil: 'networkidle0' });
    await page.screenshot({ path: `${outDir}/p5-settlements-mobile.png`, fullPage: true, captureBeyondViewport: false });

    // احسب التصفية للدفعة الجاهزة ← شاشة المراجعة
    const buttons = await page.$$('button');
    for (const button of buttons) {
        const text = await button.evaluate((el) => el.textContent.trim());
        if (text.includes('احسب التصفية')) {
            await Promise.all([
                page.waitForNavigation({ waitUntil: 'networkidle0' }).catch(() => {}),
                button.click(),
            ]);
            break;
        }
    }
    await new Promise((resolve) => setTimeout(resolve, 800));
    console.log(`settlement-show: ${page.url()}`);
    await page.screenshot({ path: `${outDir}/p5-settlement-show-mobile.png`, fullPage: true, captureBeyondViewport: false });
} finally {
    await browser.close();
}
