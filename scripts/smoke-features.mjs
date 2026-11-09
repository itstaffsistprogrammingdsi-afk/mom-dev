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
    if (message.type() === 'error') {
        errors.push(`console: ${message.text()}`);
    }
});
page.on('pageerror', (error) => errors.push(`pageerror: ${error.message}`));

await page.goto(`${base}/login`, { waitUntil: 'networkidle' });
await page.fill('input[type="email"]', 'admin@mom.test');
await page.fill('input[type="password"]', 'password');
await Promise.all([page.waitForURL(/dashboard|notulen/), page.click('button[type="submit"]')]);
await page.waitForLoadState('networkidle');

// Template editor page
await page.goto(`${base}/admin/template`, { waitUntil: 'networkidle' });
await page.goto(`${base}/admin/template/5/sunting`, { waitUntil: 'networkidle' });
await page.waitForSelector('.ProseMirror');
const templateText = await page.locator('.ProseMirror').innerText();
console.log('template editor has structure:', templateText.includes('Ringkasan Proyek'));
await page.screenshot({ path: join(outputDir, 'template-edit.png'), fullPage: true });

// Create page: template prefill and participant auto-select
await page.goto(`${base}/notulen/buat`, { waitUntil: 'networkidle' });
await page.selectOption('#division_id', { label: 'Teknologi Informasi' });
await page.waitForTimeout(300);
const checkedParticipants = await page.locator('input[type="checkbox"]:checked').count();
console.log('participants auto-selected:', checkedParticipants);
await page.selectOption('#template', { label: 'Proposal Proyek' });
await page.waitForTimeout(800);
const createText = await page.locator('.ProseMirror').innerText();
console.log('create page prefilled proposal:', createText.includes('Ringkasan Proyek'));
await page.screenshot({ path: join(outputDir, 'create-proposal.png'), fullPage: true });

// Mention autocomplete in edit page
await page.goto(`${base}/notulen/1/sunting`, { waitUntil: 'networkidle' });
await page.waitForSelector('.ProseMirror');
await page.waitForTimeout(2500);
await page.locator('.ProseMirror').click();
await page.keyboard.press('Control+End');
await page.keyboard.type(' @Bud');
await page.waitForSelector('.mention-suggestion', { timeout: 5000 });
console.log('mention popup visible for @:', await page.locator('.mention-suggestion').isVisible());
await page.keyboard.press('Enter');
await page.waitForTimeout(500);
const personMention = await page.locator('.ProseMirror .mention').last().innerText();
console.log('person mention inserted:', personMention.includes('Budi Manajer'));

await page.keyboard.type(' #Jar');
await page.waitForSelector('.mention-suggestion', { timeout: 5000 });
await page.keyboard.press('Enter');
await page.waitForTimeout(500);
const topicMention = await page.locator('.ProseMirror .mention').last().innerText();
console.log('topic mention inserted:', topicMention.includes('Jaringan'));
await page.screenshot({ path: join(outputDir, 'mention-autocomplete.png'), fullPage: true });

// Voice button presence
const voiceButton = page.locator('button[title*="dikte"], button[title*="Dikte"]');
console.log('voice button present:', (await voiceButton.count()) > 0);

// Role page
await page.goto(`${base}/admin/peran`, { waitUntil: 'networkidle' });
const roleText = await page.locator('body').innerText();
console.log('role matrix present:', roleText.includes('Matriks hak akses') && roleText.includes('Notulis') && roleText.includes('Pembaca'));

await browser.close();

const ok =
    templateText.includes('Ringkasan Proyek') &&
    checkedParticipants > 0 &&
    createText.includes('Ringkasan Proyek') &&
    personMention.includes('Budi Manajer') &&
    topicMention.includes('Jaringan') &&
    roleText.includes('Matriks hak akses');

if (errors.length > 0) {
    console.error('UI ERRORS:');
    for (const error of errors) console.error(`- ${error}`);
    process.exit(1);
}

if (!ok) {
    console.error('Feature smoke failed.');
    process.exit(1);
}

console.log('Feature smoke passed.');
