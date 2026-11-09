import { chromium } from 'playwright-core';
import { mkdir } from 'node:fs/promises';
import { join } from 'node:path';

const base = process.env.SMOKE_BASE ?? 'http://127.0.0.1:8100';
const chrome = 'C:/Program Files/Google/Chrome/Application/chrome.exe';
const outputDir = join(process.cwd(), 'storage', 'app', 'ui-smoke');
await mkdir(outputDir, { recursive: true });

const browser = await chromium.launch({ executablePath: chrome, headless: true });
const page = await browser.newPage({ viewport: { width: 1440, height: 1000 } });
const errors = [];

page.on('console', (message) => {
    if (message.type() === 'error') errors.push(`console: ${message.text()}`);
});
page.on('pageerror', (error) => errors.push(`pageerror: ${error.message}`));

await page.goto(`${base}/login`, { waitUntil: 'networkidle' });
console.log('login title:', await page.locator('h1').first().innerText());
console.log('register link:', await page.getByRole('link', { name: 'Daftar' }).count());
await page.screenshot({ path: join(outputDir, 'auth-login.png'), fullPage: true });

await page.getByRole('button', { name: 'Hubungi administrator' }).click();
console.log('help dialog open:', await page.locator('dialog.help-dialog[open]').count());
await page.getByRole('button', { name: 'Mengerti' }).click();

await page.getByRole('button', { name: /Masuk dengan Google/ }).click();
await page.waitForTimeout(200);
console.log('google toast:', await page.locator('.toast.show').innerText());

await page.getByRole('button', { name: 'Pilih tema' }).click();
await page.getByRole('menuitem', { name: /Gelap/ }).click();
await page.waitForTimeout(300);
console.log('dark mode applied:', await page.evaluate(() => document.documentElement.classList.contains('dark')));

await page.goto(`${base}/register`, { waitUntil: 'networkidle' });
console.log('register title:', await page.locator('h1').first().innerText());
await page.screenshot({ path: join(outputDir, 'auth-register.png'), fullPage: true });

const mobile = await browser.newPage({ viewport: { width: 390, height: 844 } });
await mobile.goto(`${base}/login`, { waitUntil: 'networkidle' });
await mobile.screenshot({ path: join(outputDir, 'auth-login-mobile.png'), fullPage: true });
await mobile.close();

await page.goto(`${base}/login`, { waitUntil: 'networkidle' });
await page.fill('input[type="email"]', 'admin@mom.test');
await page.fill('input[type="password"]', 'password');
await Promise.all([page.waitForURL(/dashboard|notulen/), page.click('button[type="submit"]')]);
await page.goto(`${base}/notulen/1`, { waitUntil: 'networkidle' });
console.log('share button:', await page.getByRole('button', { name: 'Bagikan' }).count());
console.log('export button:', await page.getByRole('button', { name: 'Ekspor' }).count());
await page.getByRole('button', { name: 'Bagikan' }).click();
console.log('share dialog:', await page.getByRole('dialog').count());
await page.screenshot({ path: join(outputDir, 'meeting-share-dialog.png'), fullPage: true });
await page.keyboard.press('Escape');

await page.getByRole('button', { name: 'Pilih tema' }).click();
await page.getByRole('menuitem', { name: /Terang/ }).click();
await page.waitForTimeout(300);
await page.screenshot({ path: join(outputDir, 'meeting-light.png'), fullPage: true });

await browser.close();

if (errors.length > 0) {
    console.error('UI ERRORS:');
    for (const error of errors) console.error(`- ${error}`);
    process.exit(1);
}

console.log('Auth/theme/share smoke passed.');
