// E2E (Playwright + node:test). Run: php -S 127.0.0.1:8080 -t public & node --test tests/E2E
// Uses dev data only; all records created here are prefixed E2E- (fictitious).
const { test, before, after } = require('node:test');
const assert = require('node:assert/strict');
const { chromium } = require('playwright');

const PORT = 8080, ref = `E2E-${Date.now()}`;
const url = (host, path = '/') => `http://${host}:${PORT}${path}`;
let browser;
before(async () => { browser = await chromium.launch(); });
after(async () => { await browser.close(); });

async function login(page, host, area, email, pass) {
  await page.goto(url(host, `/${area}/login`));
  await page.fill('input[name=email]', email);
  await page.fill('input[name=password]', pass);
  await page.click('button.btn');
}

test('platform: super admin creates a second site, which resolves by domain', async () => {
  const page = await browser.newPage();
  await login(page, 'platform.localhost', 'platform', 'root@kibarou.localhost', 'dev-root-passphrase-1');
  await page.waitForURL('**/platform/sites');
  await page.goto(url('platform.localhost', '/platform/sites/new'));
  const slug = `e2e${Date.now() % 100000}`;
  await page.fill('input[name=name]', `E2E ${slug}`);
  await page.fill('input[name=slug]', slug);
  await page.fill('input[name=host]', `${slug}.localhost`);
  await page.fill('input[name=admin_name]', 'E2E Admin');
  await page.fill('input[name=admin_email]', `${slug}@example.test`);
  await page.fill('input[name=password]', 'e2e-passphrase-123');
  await page.click('button.btn');
  await page.waitForURL('**/platform/sites');
  assert.match(await page.textContent('main'), new RegExp(`${slug}.localhost`));
  const res = await page.goto(url(`${slug}.localhost`));
  assert.equal(res.status(), 200);
  // that tenant's admin cannot be entered with Kibarou's credentials
  await login(page, `${slug}.localhost`, 'admin', 'admin@kibarou.localhost', 'dev-kibarou-passphrase-1');
  assert.match(await page.textContent('main'), /Identifiants incorrects/);
});

test('tenant admin: create, publish, filter, sell land; sold land leaves public catalogue', async () => {
  const page = await browser.newPage();
  await login(page, 'kibarou.localhost', 'admin', 'admin@kibarou.localhost', 'dev-kibarou-passphrase-1');
  await page.waitForURL('**/admin');
  await page.goto(url('kibarou.localhost', '/admin/terrains/new'));
  await page.fill('input[name=reference]', ref);
  await page.fill('input[name=title]', `Lot ${ref}`);
  await page.fill('input[name=area_m2]', '450');
  await page.fill('input[name=price_xof]', '25000000');
  await page.check('input[name=is_published]');
  await page.click('button.btn');
  await page.waitForURL('**/admin/terrains');

  await page.goto(url('kibarou.localhost', `/terrains?q=${ref}`));
  assert.match(await page.textContent('main'), /25[\s ]000[\s ]000 FCFA/);

  await page.goto(url('kibarou.localhost', '/admin/terrains'));
  const row = page.locator('tr', { hasText: ref });
  await row.locator('select[name=status]').selectOption('sold');
  await row.locator('form[action$="/status"] button').click();
  await page.waitForURL('**/admin/terrains');

  await page.goto(url('kibarou.localhost', `/terrains?q=${ref}`));
  assert.doesNotMatch(await page.textContent('main'), new RegExp(`Lot ${ref}`));
  await page.goto(url('kibarou.localhost', `/terrains?q=${ref}&status=sold`));
  assert.match(await page.textContent('main'), new RegExp(`Lot ${ref}`));
});

test('responsive: catalogue has no horizontal overflow at 5 viewports (screenshots saved)', async () => {
  for (const [w, h] of [[375, 812], [390, 844], [768, 1024], [1366, 768], [1920, 1080]]) {
    const page = await browser.newPage({ viewport: { width: w, height: h } });
    await page.goto(url('kibarou.localhost', '/terrains'));
    await page.screenshot({ path: `test-results/terrains-${w}x${h}.png` });
    const overflow = await page.evaluate(() => document.documentElement.scrollWidth - window.innerWidth);
    assert.ok(overflow <= 0, `overflow ${overflow}px at ${w}x${h}`);
    await page.close();
  }
});
