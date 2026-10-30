// E2E (Playwright + node:test). Run: php -S 127.0.0.1:8080 -t public bin/dev-router.php & node --test tests/E2E
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

test('cms: create page, upload real image, switch theme, sitemap lists page', async () => {
  const page = await browser.newPage();
  const slug = `e2e-page-${Date.now()}`;
  await login(page, 'kibarou.localhost', 'admin', 'admin@kibarou.localhost', 'dev-kibarou-passphrase-1');
  await page.waitForURL('**/admin');

  await page.goto(url('kibarou.localhost', '/admin/pages/new'));
  await page.fill('input[name=title]', `E2E ${slug}`);
  await page.fill('input[name=slug]', slug);
  await page.fill('textarea[name=body]', 'Premier paragraphe.\n\n<b>pas de HTML</b>');
  await page.fill('textarea[name=meta_description]', 'Description E2E');
  await page.selectOption('select[name=status]', 'published');
  await page.click('button.btn');
  await page.waitForURL('**/admin/pages');
  await page.goto(url('kibarou.localhost', `/${slug}`));
  assert.match(await page.content(), /&lt;b&gt;pas de HTML&lt;\/b&gt;/);
  assert.match(await page.content(), /<meta name="description" content="Description E2E">/);

  // 1x1 transparent PNG, sent through a genuine multipart browser upload
  const png = Buffer.from('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAYAAAAfFcSJAAAADUlEQVR42mNkYPhfDwAChwGA60e6kgAAAABJRU5ErkJggg==', 'base64');
  await page.goto(url('kibarou.localhost', '/admin/media'));
  await page.setInputFiles('input[type=file]', { name: 'e2e.png', mimeType: 'image/png', buffer: png });
  await page.fill('input[name=alt]', 'E2E image');
  await page.click('button.btn');
  await page.waitForURL('**/admin/media');
  const src = await page.getAttribute('img.thumb >> nth=0', 'src');
  assert.match(src, /^\/uploads\/\d+\/[0-9a-f]{24}\.png$/);
  // browser navigation (Chromium resolves *.localhost; Node's request context does not)
  assert.equal((await page.goto(url('kibarou.localhost', src))).status(), 200);

  await page.goto(url('kibarou.localhost', '/admin/settings'));
  await page.selectOption('select[name=theme]', 'elegance-prestige');
  await page.click('button.btn');
  await page.goto(url('kibarou.localhost', '/'));
  assert.match(await page.content(), /themes\/elegance-prestige\.css/);
  await page.goto(url('kibarou.localhost', '/admin/settings'));
  await page.selectOption('select[name=theme]', 'corporate-premium');
  await page.click('button.btn');

  const sm = await page.goto(url('kibarou.localhost', '/sitemap.xml'));
  assert.match(await sm.text(), new RegExp(`/${slug}`));
  await page.screenshot({ path: 'test-results/cms-media.png' });
});

test('land module: location, photo, public+verified document, PDF sheet, QR, inactive service hidden', async () => {
  const page = await browser.newPage();
  const tag = `E2E-L4-${Date.now()}`;
  await login(page, 'kibarou.localhost', 'admin', 'admin@kibarou.localhost', 'dev-kibarou-passphrase-1');
  await page.waitForURL('**/admin');

  await page.goto(url('kibarou.localhost', '/admin/localisations'));
  await page.selectOption('select[name=type]', 'ville');
  await page.fill('input[name=name]', `Ville ${tag}`);
  await page.click('button.btn');
  await page.goto(url('kibarou.localhost', '/admin/terrains/new'));
  await page.fill('input[name=reference]', tag);
  await page.fill('input[name=title]', `Lot ${tag}`);
  await page.fill('input[name=area_m2]', '600');
  await page.fill('input[name=price_xof]', '18000000');
  await page.selectOption('select[name=location_id]', { label: `Ville ${tag}` });
  await page.fill('input[name=latitude]', '5.345317');
  await page.fill('input[name=longitude]', '-4.024429');
  await page.check('input[name=is_published]');
  await page.click('button.btn');
  await page.waitForURL('**/admin/terrains');

  await page.locator('tr', { hasText: tag }).getByText('Photos/docs').click();
  const png = Buffer.from('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAYAAAAfFcSJAAAADUlEQVR42mNkYPhfDwAChwGA60e6kgAAAABJRU5ErkJggg==', 'base64');
  await page.setInputFiles('form[action$="/images"] input[type=file]', { name: 'p.png', mimeType: 'image/png', buffer: png });
  await page.click('form[action$="/images"] button.btn');
  await page.waitForSelector('img.thumb');

  const pdf = Buffer.from('%PDF-1.4\n1 0 obj<<>>endobj\ntrailer<<>>\n%%EOF');
  await page.setInputFiles('form[action$="/documents"] input[type=file]', { name: 'acd.pdf', mimeType: 'application/pdf', buffer: pdf });
  await page.selectOption('form[action$="/documents"] select[name=type]', 'acd');
  await page.selectOption('form[action$="/documents"] select[name=visibility]', 'public');
  await page.click('form[action$="/documents"] button.btn');
  assert.match(await page.textContent('main'), /Non vérifié/);

  const landUrl = page.url().replace('/medias', '');
  // generated assets are fetched in-browser (Chromium resolves *.localhost, Node does not)
  const status = async (path) => (await page.evaluate(async (u) => { const r = await fetch(u); return [r.status, r.headers.get('content-type'), (await r.text()).slice(0, 4)]; }, path));
  const base = new URL(landUrl).pathname;
  assert.deepEqual((await status(`${base}/fiche.pdf`)).slice(0, 3), [200, 'application/pdf', '%PDF']);
  assert.equal((await status(`${base}/qr.svg`))[1], 'image/svg+xml');
  assert.equal((await status(`${base}/qr.png`))[1], 'image/png');

  await page.click('form[action$="/verify"] button');
  assert.match(await page.textContent('main'), /Vérifié/);

  await page.goto(url('kibarou.localhost', `/terrains?q=${tag}`));
  assert.ok(await page.locator('.card img.thumb').count() >= 1, 'cover photo in catalogue');
  await page.click(`a.card:has-text("${tag}")`);
  const body = await page.textContent('main');
  assert.match(body, /Ville E2E-L4/);
  assert.match(body, /document vérifié/);
  assert.match(body, /Voir sur la carte/);
  assert.match(body, /18[\s ]000[\s ]000 FCFA/);
  await page.screenshot({ path: 'test-results/land-detail.png', fullPage: true });

  // services: a new one is hidden until activated
  await page.goto(url('kibarou.localhost', '/admin/services/new'));
  await page.fill('input[name=title]', `Service ${tag}`);
  await page.selectOption('select[name=category]', 'terrassement');
  await page.click('button.btn');
  await page.goto(url('kibarou.localhost', '/services'));
  assert.doesNotMatch(await page.textContent('main'), new RegExp(tag));
});

test('brand: official logo is shown on the Kibarou site home', async () => {
  const page = await browser.newPage({ viewport: { width: 1366, height: 768 } });
  await page.goto(url('kibarou.localhost', '/'));
  const logo = page.locator('header img.logo');
  assert.equal(await logo.count(), 1);
  assert.equal(await logo.evaluate((i) => i.complete && i.naturalWidth), 1280);
  await page.screenshot({ path: 'test-results/home-logo.png' });
});

test('crm: visitor request from a land page -> admin follow-up, CSV export, custom form', async () => {
  const tag = `E2E-L5-${Date.now()}`;
  const admin = await browser.newPage();
  await login(admin, 'kibarou.localhost', 'admin', 'admin@kibarou.localhost', 'dev-kibarou-passphrase-1');
  await admin.waitForURL('**/admin');
  await admin.goto(url('kibarou.localhost', '/admin/terrains/new'));
  await admin.fill('input[name=reference]', tag);
  await admin.fill('input[name=title]', `Lot ${tag}`);
  await admin.fill('input[name=area_m2]', '500');
  await admin.fill('input[name=price_xof]', '12000000');
  await admin.check('input[name=is_published]');
  await admin.click('button.btn');
  await admin.waitForURL('**/admin/terrains');

  // anonymous visitor (separate context: no admin cookies)
  const ctx = await browser.newContext();
  const visitor = await ctx.newPage();
  await visitor.goto(url('kibarou.localhost', `/terrains?q=${tag}`));
  await visitor.click(`a.card:has-text("${tag}")`);
  await visitor.click('text=Programmer une visite');
  assert.match(await visitor.textContent('main'), /ne constitue pas une réservation/);
  await visitor.fill('input[name=first_name]', 'Awa');
  await visitor.fill('input[name=last_name]', `Visiteur ${tag}`);
  await visitor.fill('input[name=phone]', '+225 07 00 00 00 00');
  await visitor.fill('textarea[name=message]', '=1+1 test formule');
  await visitor.check('input[name=consent]');
  await visitor.waitForTimeout(3200); // antispam: a human takes more than 3 s
  await visitor.click('button.btn');
  await visitor.waitForURL('**/demandes/merci');
  assert.match(await visitor.textContent('main'), /LD-\d{6}/);
  await visitor.screenshot({ path: 'test-results/crm-thanks.png' });
  await ctx.close();

  await admin.goto(url('kibarou.localhost', `/admin/prospects?q=${tag}`));
  assert.match(await admin.textContent('main'), /Programmer|Demande de visite/);
  await admin.click(`a[href^="/admin/prospects/"]:has-text("LD-")`);
  await admin.selectOption('form[action$="/status"] select', 'contacted');
  await admin.click('form[action$="/status"] button');
  await admin.fill('textarea[name=body]', 'Rappeler demain matin');
  await admin.click('form[action$="/notes"] button.btn');
  await admin.selectOption('form[action$="/assign"] select', { index: 1 });
  await admin.click('form[action$="/assign"] button');
  await admin.fill('input[name=scheduled_at]', '2030-05-06T10:30');
  await admin.click('form[action$="/visites"] button');
  const detail = await admin.textContent('main');
  assert.match(detail, /Visite programmée/);
  assert.match(detail, /Rappeler demain matin/);
  assert.match(detail, /Contact établi → Visite programmée/);
  await admin.screenshot({ path: 'test-results/crm-lead.png', fullPage: true });

  const csv = await admin.evaluate(async (u) => { const r = await fetch(u); return [r.headers.get('content-type'), await r.text()]; }, `/admin/prospects/export.csv?q=${tag}`);
  assert.match(csv[0], /text\/csv/);
  assert.match(csv[1], /'=1\+1 test formule/);

  // form builder -> public form -> responses
  const slug = `e2e-form-${Date.now() % 100000}`;
  await admin.goto(url('kibarou.localhost', '/admin/formulaires/new'));
  await admin.fill('input[name=name]', `Form ${slug}`);
  await admin.fill('input[name=slug]', slug);
  await admin.click('button.btn');
  await admin.waitForURL('**/admin/formulaires');
  const ctx2 = await browser.newContext();
  const v2 = await ctx2.newPage();
  await v2.goto(url('kibarou.localhost', `/f/${slug}`));
  await v2.fill('input[name=f0]', 'Partenaire E2E');
  await v2.fill('input[name=f1]', 'p@example.test');
  await v2.fill('textarea[name=f2]', 'Bonjour');
  await v2.waitForTimeout(3200);
  await v2.click('button.btn');
  await v2.waitForSelector('.flash');
  await ctx2.close();
  await admin.goto(url('kibarou.localhost', '/admin/formulaires'));
  await admin.click(`tr:has-text("${slug}") a[href$="/reponses"]`);
  assert.match(await admin.textContent('main'), /Partenaire E2E/);

  // cleanup: antispam caps requests per IP per hour, so leave nothing behind
  await admin.goto(url('kibarou.localhost', `/admin/prospects?q=${tag}`));
  await admin.click(`a[href^="/admin/prospects/"]:has-text("LD-")`);
  await admin.click('form[action$="/delete"] button');
  await admin.goto(url('kibarou.localhost', '/admin/formulaires'));
  await admin.click(`tr:has-text("${slug}") form[action$="/delete"] button`);
});
