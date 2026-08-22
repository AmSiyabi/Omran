/**
 * Axe failure detail: prints failing nodes (selector, colors, snippet).
 * Usage: node scripts/axe-detail.mjs /admin /admin/courses ...
 */
import puppeteer from 'puppeteer-core';
import { existsSync, readFileSync } from 'node:fs';
import { createRequire } from 'node:module';

const require = createRequire(import.meta.url);
const axeSource = readFileSync(require.resolve('axe-core/axe.min.js'), 'utf8');
const base = 'http://localhost:8080';

const chromePaths = [
    'C:/Program Files/Google/Chrome/Application/chrome.exe',
    'C:/Program Files (x86)/Google/Chrome/Application/chrome.exe',
];
const browser = await puppeteer.launch({ executablePath: chromePaths.find((p) => existsSync(p)), headless: 'new' });

try {
    const page = await browser.newPage();
    await page.setViewport({ width: 375, height: 900 });

    await page.goto(`${base}/login`, { waitUntil: 'networkidle0' });
    await page.type('input[name=email]', 'owner-demo@omran.local');
    await page.type('input[name=password]', 'Phase5-Demo-Pass-123');
    await Promise.all([page.waitForNavigation({ waitUntil: 'networkidle0' }), page.click('button[type=submit]')]);

    for (const path of process.argv.slice(2)) {
        await page.goto(`${base}${path}`, { waitUntil: 'networkidle0' });
        await new Promise((r) => setTimeout(r, 500));
        await page.evaluate(axeSource);
        const axe = await page.evaluate(() => window.axe.run(document, { resultTypes: ['violations'] }));

        console.log(`\n===== ${path}`);
        for (const violation of axe.violations.filter((v) => ['critical', 'serious'].includes(v.impact))) {
            console.log(`  ${violation.id} (${violation.impact})`);
            for (const node of violation.nodes.slice(0, 5)) {
                console.log(`    target: ${node.target.join(' ')}`);
                console.log(`    html: ${node.html.slice(0, 160)}`);
                console.log(`    msg: ${(node.any[0]?.message ?? '').slice(0, 160)}`);
            }
        }
    }
} finally {
    await browser.close();
}
