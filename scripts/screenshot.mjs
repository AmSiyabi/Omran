/**
 * Viewport-accurate full-page screenshots + horizontal-overflow check.
 * Usage: node scripts/screenshot.mjs <url> <outDir> [widths...]
 * Exits 1 if any viewport has horizontal overflow (scrollWidth > innerWidth).
 */
import puppeteer from 'puppeteer-core';

const [url = 'http://localhost:8080/', outDir = '.', ...widthArgs] = process.argv.slice(2);
const widths = widthArgs.length ? widthArgs.map(Number) : [375, 768, 1440];

const chromePaths = [
    'C:\\Program Files\\Google\\Chrome\\Application\\chrome.exe',
    'C:\\Program Files (x86)\\Google\\Chrome\\Application\\chrome.exe',
    '/usr/bin/google-chrome',
    '/usr/bin/chromium',
];

const { existsSync } = await import('node:fs');
const executablePath = chromePaths.find((p) => existsSync(p));
if (!executablePath) {
    console.error('Chrome not found');
    process.exit(1);
}

const browser = await puppeteer.launch({ executablePath, headless: 'new' });
let failed = false;

try {
    for (const width of widths) {
        const page = await browser.newPage();
        await page.setViewport({ width, height: 900, deviceScaleFactor: 1 });
        await page.goto(url, { waitUntil: 'networkidle0', timeout: 60000 });

        const metrics = await page.evaluate(() => ({
            innerWidth: window.innerWidth,
            scrollWidth: document.scrollingElement.scrollWidth,
            dir: document.documentElement.dir,
            lang: document.documentElement.lang,
        }));

        const overflow = metrics.scrollWidth > metrics.innerWidth;
        if (overflow) failed = true;

        console.log(
            `${width}px → innerWidth=${metrics.innerWidth} scrollWidth=${metrics.scrollWidth} ` +
            `dir=${metrics.dir} lang=${metrics.lang} ${overflow ? '❌ HORIZONTAL OVERFLOW' : '✓ no overflow'}`
        );

        await page.screenshot({ path: `${outDir}/viewport-${width}.png`, fullPage: true });
        await page.close();
    }
} finally {
    await browser.close();
}

process.exit(failed ? 1 : 0);
