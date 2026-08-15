/**
 * Renders the brand OG image (1200×630) from an HTML template.
 * Usage: node scripts/og-image.mjs <template.html> <out.png>
 */
import puppeteer from 'puppeteer-core';
import { existsSync } from 'node:fs';
import { pathToFileURL } from 'node:url';

const [template, out = 'public/images/og-default.png'] = process.argv.slice(2);

const chromePaths = [
    'C:/Program Files/Google/Chrome/Application/chrome.exe',
    'C:/Program Files (x86)/Google/Chrome/Application/chrome.exe',
];
const executablePath = chromePaths.find((p) => existsSync(p));

const browser = await puppeteer.launch({ executablePath, headless: 'new' });
const page = await browser.newPage();
await page.setViewport({ width: 1200, height: 630, deviceScaleFactor: 1 });
await page.goto(pathToFileURL(template).href, { waitUntil: 'networkidle0' });
await page.evaluateHandle('document.fonts.ready');
await page.screenshot({ path: out });
await browser.close();
console.log(`OG image written to ${out}`);
