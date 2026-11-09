import { chromium } from 'playwright-core';
import { mkdir } from 'node:fs/promises';
import { join } from 'node:path';

const base = process.env.SMOKE_BASE ?? 'http://127.0.0.1:8100';
const chrome = 'C:/Program Files/Google/Chrome/Application/chrome.exe';
const outputDir = join(process.cwd(), 'storage', 'app', 'ui-smoke');
await mkdir(outputDir, { recursive: true });

const browser = await chromium.launch({ executablePath: chrome, headless: true });
const context = await browser.newContext({ viewport: { width: 1440, height: 1000 } });
const errors = [];

const loginPage = await context.newPage();
await loginPage.goto(`${base}/login`, { waitUntil: 'networkidle' });
await loginPage.fill('input[type="email"]', 'admin@mom.test');
await loginPage.fill('input[type="password"]', 'password');
await Promise.all([
    loginPage.waitForURL(/dashboard|notulen/, { timeout: 15000 }),
    loginPage.click('button[type="submit"]'),
]);
await loginPage.close();

const pageOne = await context.newPage();
const pageTwo = await context.newPage();

for (const page of [pageOne, pageTwo]) {
    page.on('console', (message) => {
        if (message.type() === 'error') {
            errors.push(`console: ${message.text()}`);
        }
    });
    page.on('pageerror', (error) => errors.push(`pageerror: ${error.message}`));
    await page.goto(`${base}/notulen/1/sunting`, { waitUntil: 'networkidle' });
    await page.waitForSelector('.ProseMirror');
}

await pageOne.waitForTimeout(3000);

const unique = `Uji kolaborasi ${Date.now()}`;
await pageOne.locator('.ProseMirror').click();
await pageOne.keyboard.press('Control+End');
await pageOne.keyboard.type(` ${unique}`);
await pageOne.waitForTimeout(2000);

const textOne = await pageOne.locator('.ProseMirror').innerText();
const textTwo = await pageTwo.locator('.ProseMirror').innerText();

await pageOne.screenshot({ path: join(outputDir, 'collab-page-one.png'), fullPage: true });
await pageTwo.screenshot({ path: join(outputDir, 'collab-page-two.png'), fullPage: true });

console.log('page one contains typed text:', textOne.includes(unique));
console.log('page two received text:', textTwo.includes(unique));

await browser.close();

if (!textOne.includes(unique) || !textTwo.includes(unique)) {
    console.error('Collaboration sync failed.');
    process.exit(1);
}

if (errors.length > 0) {
    console.error('UI ERRORS:');
    for (const error of errors) {
        console.error(`- ${error}`);
    }
    process.exit(1);
}

console.log('Collaboration smoke passed.');
