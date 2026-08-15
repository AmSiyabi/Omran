/**
 * Phase 3 visual acceptance: public pages at 5 widths + reduced-motion check.
 * Usage: node scripts/phase3-shots.mjs <outDir>
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

let failed = false;

try {
    const page = await browser.newPage();

    // الصفحة الرئيسية على العروض الخمسة المطلوبة
    for (const width of [375, 768, 1024, 1440, 2560]) {
        await page.setViewport({ width, height: 950, deviceScaleFactor: 1 });
        await page.goto(`${base}/`, { waitUntil: 'networkidle0' });
        const overflow = await page.evaluate(() =>
            document.scrollingElement.scrollWidth - document.documentElement.clientWidth);
        if (overflow > 0) failed = true;
        console.log(`home@${width}: overflow=${overflow}px`);
        await page.screenshot({ path: `${outDir}/p3-home-${width}.png`, fullPage: width <= 1440, captureBeyondViewport: false });
    }

    // بقية الصفحات على الجوال
    for (const [path, name] of [
        ['/courses', 'courses'],
        ['/about', 'about'],
        ['/work', 'work'],
        ['/instructors', 'instructors'],
        ['/contact', 'contact'],
    ]) {
        await page.setViewport({ width: 375, height: 950, deviceScaleFactor: 1 });
        await page.goto(`${base}${path}`, { waitUntil: 'networkidle0' });
        const overflow = await page.evaluate(() =>
            document.scrollingElement.scrollWidth - document.documentElement.clientWidth);
        if (overflow > 0) failed = true;
        console.log(`${name}@375: overflow=${overflow}px`);
        await page.screenshot({ path: `${outDir}/p3-${name}-375.png`, fullPage: true, captureBeyondViewport: false });
    }

    // فحص تقليل الحركة: كل عناصر الكشف مرئية فوراً والرسوم متوقفة فعلياً
    await page.emulateMediaFeatures([{ name: 'prefers-reduced-motion', value: 'reduce' }]);
    await page.goto(`${base}/`, { waitUntil: 'networkidle0' });
    const reduced = await page.evaluate(() => {
        const hero = document.querySelector('.hero-seq');
        const heroDuration = getComputedStyle(hero).animationDuration;
        const revealed = [...document.querySelectorAll('[data-reveal]')]
            .every((el) => el.classList.contains('is-revealed'));
        const heroOpacity = getComputedStyle(hero).opacity;
        return { heroDuration, revealed, heroOpacity };
    });
    console.log(`reduced-motion: hero animation-duration=${reduced.heroDuration}, hero opacity=${reduced.heroOpacity}, all reveals visible=${reduced.revealed}`);
    if (!reduced.revealed || reduced.heroOpacity !== '1') failed = true;
} finally {
    await browser.close();
}

process.exit(failed ? 1 : 0);
