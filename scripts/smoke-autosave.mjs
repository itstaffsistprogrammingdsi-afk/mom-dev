import { chromium } from 'playwright-core';

const base = process.env.SMOKE_BASE ?? 'http://127.0.0.1:8100';
const chrome = 'C:/Program Files/Google/Chrome/Application/chrome.exe';

const browser = await chromium.launch({ executablePath: chrome, headless: true });
const page = await browser.newPage({ viewport: { width: 1440, height: 1000 } });

await page.goto(`${base}/login`, { waitUntil: 'networkidle' });
await page.fill('input[type="email"]', 'admin@mom.test');
await page.fill('input[type="password"]', 'password');
await Promise.all([page.waitForURL(/dashboard|notulen/), page.click('button[type="submit"]')]);

await page.goto(`${base}/notulen/1/sunting`, { waitUntil: 'networkidle' });
await page.waitForSelector('.ProseMirror');
await page.waitForTimeout(1500);

const unique = `Autosave ${Date.now()}`;
await page.locator('.ProseMirror').click();
await page.keyboard.press('Control+End');
await page.keyboard.type(` ${unique}`);

const response = await page.waitForResponse(
    (res) => res.url().includes('/notulen/1/autosave') && res.request().method() === 'PATCH',
    { timeout: 10000 },
);

console.log('autosave status:', response.status());
await page.waitForTimeout(500);
console.log('autosave label:', await page.locator('text=Tersimpan').first().innerText());

await browser.close();

if (response.status() !== 200) {
    process.exit(1);
}
