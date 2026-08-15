/**
 * Phase 1 visual acceptance: login page, admin shell (coordinator),
 * forced 2FA setup (owner). Usage: node scripts/phase1-shots.mjs <outDir>
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

// Fresh cookie jar per scenario — otherwise the first login bleeds into all
async function freshPage(width, height) {
    const context = await browser.createBrowserContext();
    const page = await context.newPage();
    await page.setViewport({ width, height, deviceScaleFactor: 1 });
    return page;
}

async function login(page, email, password) {
    await page.goto(`${base}/login`, { waitUntil: 'networkidle0' });
    await page.type('input[name=email]', email);
    await page.type('input[name=password]', password);
    await Promise.all([
        page.waitForNavigation({ waitUntil: 'networkidle0' }),
        page.click('button[type=submit]'),
    ]);
}

try {
    // 1. Login page, mobile + desktop
    for (const [w, h, label] of [[375, 900, 'mobile'], [1440, 900, 'desktop']]) {
        const page = await freshPage(w, h);
        await page.goto(`${base}/login`, { waitUntil: 'networkidle0' });
        await page.screenshot({ path: `${outDir}/p1-login-${label}.png`, fullPage: true, captureBeyondViewport: false });
        await page.browserContext().close();
    }

    // 2. Coordinator (no 2FA requirement) → dashboard shell
    for (const [w, h, label] of [[375, 900, 'mobile'], [1440, 900, 'desktop']]) {
        const page = await freshPage(w, h);
        await login(page, 'coord-demo@omran.local', 'Phase1-Demo-Pass-123');
        console.log(`coordinator@${label}: landed on ${page.url()}`);
        await page.screenshot({ path: `${outDir}/p1-dashboard-${label}.png`, fullPage: true, captureBeyondViewport: false });
        await page.browserContext().close();
    }

    // 3. Owner without 2FA → must be forced to the 2FA setup page
    {
        const page = await freshPage(1440, 900);
        await login(page, 'owner-demo@omran.local', 'Phase1-Demo-Pass-123');
        console.log(`owner-no-2fa: landed on ${page.url()}`);
        await page.screenshot({ path: `${outDir}/p1-owner-forced-2fa.png`, fullPage: true, captureBeyondViewport: false });
        await page.browserContext().close();
    }

    // 4. Coordinator security page with session list
    {
        const page = await freshPage(375, 900);
        await login(page, 'coord-demo@omran.local', 'Phase1-Demo-Pass-123');
        await page.goto(`${base}/admin/security`, { waitUntil: 'networkidle0' });
        console.log(`security: landed on ${page.url()}`);
        await page.screenshot({ path: `${outDir}/p1-security-mobile.png`, fullPage: true, captureBeyondViewport: false });
        await page.browserContext().close();
    }
} finally {
    await browser.close();
}
