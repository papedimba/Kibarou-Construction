<?php
/** @var string $content */
$site = $site ?? null; $user = $user ?? null; $csrf = $csrf ?? ''; $settings = $settings ?? [];
$headerMenu = $headerMenu ?? []; $footerMenu = $footerMenu ?? []; $adminNav = $adminNav ?? false;
$public = $site && !$adminNav && empty($authScreen);
$theme = preg_replace('/[^a-z-]/', '', $settings['theme'] ?? 'corporate-premium');
$pageTitle = ($title ?? '') !== '' ? $title . ' — ' . ($site['name'] ?? 'Kibarou CMS') : ($site['name'] ?? 'Kibarou CMS');
$desc = $metaDescription ?? ($settings['meta_description'] ?? '');
$wa = preg_replace('/\D/', '', $settings['whatsapp'] ?? '');
$nav = $headerMenu ?: ($navDefault ?? []);
?>
<!doctype html>
<html lang="fr">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title><?= e($pageTitle) ?></title>
<?php if ($desc !== ''): ?><meta name="description" content="<?= e($desc) ?>"><?php endif; ?>
<?php if ($public): ?>
<link rel="canonical" href="<?= e($canonical ?? '') ?>">
<meta property="og:type" content="website">
<meta property="og:title" content="<?= e($pageTitle) ?>">
<meta property="og:url" content="<?= e($canonical ?? '') ?>">
<?php if ($desc !== ''): ?><meta property="og:description" content="<?= e($desc) ?>"><?php endif; ?>
<?php if (!empty($jsonld)): ?><script type="application/ld+json"><?= json_encode($jsonld, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_HEX_TAG | JSON_HEX_AMP) ?></script><?php endif; ?>
<link rel="preload" href="/assets/fonts/ibm-plex-sans-latin-wght-normal.woff2" as="font" type="font/woff2" crossorigin>
<?php endif; ?>
<?php if (!$public || !empty($preview)): ?><meta name="robots" content="noindex"><?php endif; ?>
<link rel="stylesheet" href="<?= $public ? '/assets/css/site.css' : '/assets/css/admin.css' ?>">
<link rel="stylesheet" href="/assets/css/blocks.css">
<?php if ($public): ?><link rel="stylesheet" href="/assets/css/themes/<?= e($theme) ?>.css"><?php endif; ?>
<?php foreach (($extraCss ?? []) as $css): ?><link rel="stylesheet" href="<?= e($css) ?>"><?php endforeach; ?>
<?php if ($public): ?><script src="/assets/js/site.js" defer></script><?php endif; ?>
</head>
<body class="<?= e($bodyClass ?? '') ?>">
<?php if ($public): ?>
<a class="skip" href="#contenu">Aller au contenu</a>
<?php
$tpl = $view ?? '';
$path = $currentPath ?? '/';
$isHome = $tpl === 'frontend/home';
$banner = !$isHome && !str_starts_with($tpl, 'errors/') && empty($blocksHtml);
$parents = ['terrains' => ['Nos terrains', '/terrains'], 'services' => ['Nos services BTP', '/services'], 'realisations' => ['Nos réalisations', '/realisations'], 'lotissements' => ['Nos lotissements', '/lotissements'], 'actualites' => ['Actualités', '/actualites']];
$seg = explode('/', trim($path, '/'));
$parent = count($seg) > 1 && isset($parents[$seg[0]]) ? $parents[$seg[0]] : null;
$phone = $settings['phone'] ?? ''; $mail = $settings['email'] ?? '';
$isActive = fn(string $u) => $u === '/' ? $path === '/' : ($path === $u || str_starts_with($path, $u . '/'));
?>
<div class="topbar<?= $phone || $mail || $wa !== '' || $user ? '' : ' no-contact' ?>">
  <div class="wrap">
    <span><?= e($settings['tagline'] ?? '') ?: 'Construction · Travaux publics · Terrains' ?> — Côte d'Ivoire</span>
    <span class="topbar-links">
      <?php if ($phone): ?><a href="tel:<?= e(preg_replace('/[^0-9+]/', '', $phone)) ?>"><?= \App\Blocks\Icons::svg('phone', 15) ?><?= e($phone) ?></a><?php endif; ?>
      <?php if ($mail): ?><a href="mailto:<?= e($mail) ?>"><?= \App\Blocks\Icons::svg('mail', 15) ?><?= e($mail) ?></a><?php endif; ?>
      <?php if ($wa !== ''): ?><a href="https://wa.me/<?= e($wa) ?>" rel="noopener"><?= \App\Blocks\Icons::svg('message-circle', 15) ?>WhatsApp</a><?php endif; ?>
      <?php if ($user): ?><a href="/admin">Administration</a><?php endif; ?>
    </span>
  </div>
</div>
<header class="site-head">
  <div class="wrap head-row">
    <a class="brand" href="/" aria-label="<?= e($site['name']) ?> — accueil">
      <?php if (!empty($settings['logo_url'])): ?><img src="<?= e($settings['logo_url']) ?>" alt="<?= e($site['name']) ?>" class="logo" width="141" height="94"><?php else: ?><span class="brand-name"><?= e($site['name']) ?></span><?php endif; ?>
    </a>
    <details class="menu" open>
      <summary>Menu</summary>
      <nav aria-label="Navigation principale">
        <?php foreach ($nav as $i): ?><a href="<?= e($i['url']) ?>" <?= $isActive($i['url']) ? 'aria-current="page"' : '' ?>><?= e($i['label']) ?></a><?php endforeach; ?>
      </nav>
    </details>
    <div class="actions">
      <a class="btn" href="/terrains">Trouver un terrain</a>
      <?php if ($user): ?><form method="post" action="/admin/logout" class="inline"><?= csrf_field($csrf) ?><button class="link">Déconnexion</button></form><?php endif; ?>
    </div>
  </div>
</header>
<?php if ($banner): $bp = $texts ? $texts->photo('banner') : null; ?>
<section class="banner">
  <?= picture($bp, 3, '', 'banner-img') ?>
  <div class="wrap banner-body">
    <nav class="crumbs" aria-label="Fil d'Ariane"><a href="/">Accueil</a><?php if ($parent): ?> <span aria-hidden="true">›</span> <a href="<?= e($parent[1]) ?>"><?= e($parent[0]) ?></a><?php endif; ?></nav>
    <h1><?= e($heading ?? $title ?? $site['name']) ?></h1>
  </div>
</section>
<?php endif; ?>
<main id="contenu" class="<?= $isHome || !empty($blocksHtml) ? 'full' : 'site-main wrap' ?>">
  <?php if (!empty($flash)): ?><p class="flash" role="status"><?= e($flash) ?></p><?php endif; ?>
  <?= $content ?>
</main>
<footer class="site-foot">
  <div class="wrap foot-cols">
    <div class="foot-brand">
      <?php if (!empty($settings['logo_url'])): ?><img src="<?= e($settings['logo_url']) ?>" alt="<?= e($site['name']) ?>" width="141" height="94" loading="lazy"><?php else: ?><strong class="foot-name"><?= e($site['name']) ?></strong><?php endif; ?>
      <p><?= e($settings['tagline'] ?? '') ?></p>
      <p class="foot-small">Construction, travaux publics et commercialisation de terrains en Côte d'Ivoire.</p>
    </div>
    <nav class="foot-col" aria-label="Plan du site"><h2>Navigation</h2>
      <?php foreach (($footerMenu ?: $nav) as $i): ?><a href="<?= e($i['url']) ?>"><?= e($i['label']) ?></a><?php endforeach; ?>
    </nav>
    <nav class="foot-col" aria-label="Nos activités"><h2>Nos activités</h2>
      <a href="/terrains">Terrains à vendre</a><a href="/lotissements">Lotissements</a><a href="/services">Construction et BTP</a><a href="/contact?type=info">Demande d'informations</a><a href="/contact">Demander un devis</a>
    </nav>
    <div class="foot-col"><h2>Contact</h2>
      <?php if (!empty($settings['address'])): ?><p><?= e($settings['address']) ?></p><?php endif; ?>
      <?php if ($phone): ?><a href="tel:<?= e(preg_replace('/[^0-9+]/', '', $phone)) ?>"><?= e($phone) ?></a><?php endif; ?>
      <?php if ($mail): ?><a href="mailto:<?= e($mail) ?>"><?= e($mail) ?></a><?php endif; ?>
      <?php if ($wa !== ''): ?><a href="https://wa.me/<?= e($wa) ?>" rel="noopener">WhatsApp</a><?php endif; ?>
      <a class="btn" href="/contact">Nous écrire</a>
    </div>
  </div>
  <div class="wrap foot-legal">
    <span>© <?= date('Y') ?> <?= e($site['name']) ?> · Côte d'Ivoire</span>
    <?php $credits = \App\Content\SiteTexts::illustrationCredits(); if ($credits): ?>
      <details class="credits"><summary>Crédits photos</summary><p>Photos d'illustration, sans lien avec les réalisations de l'entreprise :</p><ul><?php foreach (array_unique($credits) as $c): ?><li><?= e($c) ?></li><?php endforeach; ?></ul></details>
    <?php endif; ?>
  </div>
</footer>
<?php elseif (!empty($authScreen)): ?>
<div class="auth"><main><?php if (!empty($flash)): ?><p class="flash" role="status"><?= e($flash) ?></p><?php endif; ?><?= $content ?></main></div>
<?php else: ?>
<?php
$perms = $perms ?? []; $path = $currentPath ?? '';
$can = fn(string $p) => in_array('*', $perms, true) || in_array($p, $perms, true);
$groups = $site ? [
    ['', [['Tableau de bord', '/admin', 'layout-dashboard', null]]],
    ['Gestion foncière', [['Terrains', '/admin/terrains', 'map-pin', 'land.view'], ['Lotissements', '/admin/lotissements', 'layers', 'land.manage'], ['Catégories', '/admin/categories', 'list', 'land.manage'], ['Localisations', '/admin/localisations', 'map', 'land.manage']]],
    ['BTP', [['Services', '/admin/services', 'hammer', 'btp.manage'], ['Réalisations', '/admin/realisations', 'hard-hat', 'btp.manage']]],
    ['CRM', [['Prospects', '/admin/prospects', 'users', 'lead.view'], ['Visites', '/admin/visites', 'calendar', 'lead.view']]],
    ['Contenu', [['Textes et photos', '/admin/textes', 'camera', 'content.manage'], ['Pages', '/admin/pages', 'file-text', 'content.manage'], ['Articles', '/admin/articles', 'clipboard-list', 'content.manage'], ['Médias', '/admin/media', 'images', 'content.manage'], ['Menus', '/admin/menus', 'menu', 'content.manage'], ['Formulaires', '/admin/formulaires', 'message-circle', 'content.manage']]],
    ['Configuration', [['Réglages', '/admin/settings', 'settings', 'site.settings'], ['Utilisateurs', '/admin/utilisateurs', 'users', 'users.manage'], ['Journal', '/admin/journal', 'clipboard-list', 'site.settings']]],
] : [['', [['Sites', '/platform/sites', 'layout-dashboard', null], ['Sauvegardes', '/platform/backups', 'briefcase', null]]]];
$current = fn(string $u) => $u === '/admin' ? $path === '/admin' : ($path === $u || str_starts_with($path, $u . '/'));
?>
<a class="skip" href="#contenu">Aller au contenu</a>
<div class="shell">
  <aside class="side" id="side" aria-label="Navigation de l'administration">
    <a class="side-brand" href="<?= $site ? '/admin' : '/platform/sites' ?>"><?= e($site['name'] ?? 'Kibarou CMS') ?><small><?= $site ? 'Administration' : 'Plateforme' ?></small></a>
    <nav>
      <?php foreach ($groups as [$label, $items]): $items = array_filter($items, fn($i) => $i[3] === null || $can($i[3])); if (!$items) continue; ?>
        <?php if ($label !== ''): ?><div class="grp"><?= e($label) ?></div><?php endif; ?>
        <?php foreach ($items as [$text, $url, $icon]): ?>
          <a href="<?= e($url) ?>" <?= $current($url) ? 'aria-current="page"' : '' ?>><?= \App\Blocks\Icons::svg($icon, 18) ?><?= e($text) ?></a>
        <?php endforeach; ?>
      <?php endforeach; ?>
    </nav>
  </aside>
  <div class="col">
    <header class="top">
      <button type="button" class="menu-btn" id="menu-btn" aria-controls="side" aria-expanded="false"><?= \App\Blocks\Icons::svg('menu', 18) ?>Menu</button>
      <span class="spacer"></span>
      <?php if ($site): ?><a href="/"><?= \App\Blocks\Icons::svg('external-link', 16) ?>Voir le site</a><?php endif; ?>
      <?php if ($user): ?>
        <a class="who" href="<?= $site ? '/admin/compte' : '/platform/compte' ?>"><?= e($user['name']) ?></a>
        <form method="post" action="<?= $site ? '/admin/logout' : '/platform/logout' ?>" class="inline"><?= csrf_field($csrf) ?><button class="link"><?= \App\Blocks\Icons::svg('log-out', 16) ?> Déconnexion</button></form>
      <?php endif; ?>
    </header>
    <main id="contenu">
      <?php if (!empty($flash)): ?><p class="flash" role="status"><?= e($flash) ?></p><?php endif; ?>
      <?= $content ?>
    </main>
  </div>
</div>
<script src="/assets/js/admin.js" defer></script>
<?php endif; ?>
</body>
</html>
