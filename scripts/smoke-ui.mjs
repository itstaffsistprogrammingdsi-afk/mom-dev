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
page.on('pageerror', (error) => {
    errors.push(`pageerror: ${error.message}`);
});

await page.goto(`${base}/login`, { waitUntil: 'networkidle' });
await page.fill('input[type="email"]', 'admin@mom.test');
await page.fill('input[type="password"]', 'password');
await Promise.all([
    page.waitForURL(/dashboard|notulen/, { timeout: 15000 }),
    page.click('button[type="submit"]'),
]);
await page.waitForLoadState('networkidle');

const pages = [
    ['dashboard', '/dashboard'],
    ['meetings', '/notulen'],
    ['meeting-show', '/notulen/1'],
    ['meeting-edit', '/notulen/1/sunting'],
    ['related', '/terkait-saya'],
    ['tasks', '/tugas'],
    ['tasks-board', '/tugas?view=board'],
    ['topics', '/topik'],
    ['notifications', '/notifikasi'],
    ['admin-templates', '/admin/template'],
    ['admin-template-edit', '/admin/template/5/sunting'],
    ['admin-roles', '/admin/peran'],
    ['admin-users', '/admin/pengguna'],
    ['admin-topics', '/admin/topik'],
    ['admin-divisions', '/admin/divisi'],
    ['admin-audit', '/admin/audit'],
    ['division', '/divisi/teknologi-informasi'],
    ['person', '/orang/1'],
];

for (const [name, path] of pages) {
    const response = await page.goto(`${base}${path}`, { waitUntil: 'networkidle' });
    await page.waitForTimeout(500);
    await page.screenshot({ path: join(outputDir, `${name}.png`), fullPage: true });
    console.log(`${path} => ${response?.status()}`);
}

await browser.close();

if (errors.length > 0) {
    console.error('UI ERRORS:');
    for (const error of errors) {
        console.error(`- ${error}`);
    }
    process.exit(1);
}

console.log('UI smoke passed without console errors.');
