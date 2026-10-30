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
<link rel="preload" href="/assets/fonts/literata-latin-wght-normal.woff2" as="font" type="font/woff2" crossorigin>
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
<header class="site-head">
  <a class="brand" href="/" aria-label="<?= e($site['name']) ?> — accueil">
    <?php if (!empty($settings['logo_url'])): ?><img src="<?= e($settings['logo_url']) ?>" alt="<?= e($site['name']) ?>" class="logo" width="96" height="64"><?php else: ?><span class="brand-name"><?= e($site['name']) ?></span><?php endif; ?>
  </a>
  <details class="menu" open>
    <summary>Menu</summary>
    <nav aria-label="Navigation principale">
      <?php foreach ($nav as $i): ?><a href="<?= e($i['url']) ?>"><?= e($i['label']) ?></a><?php endforeach; ?>
    </nav>
  </details>
  <div class="actions">
    <?php if ($user): ?><a href="/admin" class="small">Administration</a><?php endif; ?>
    <a class="btn" href="/terrains">Trouver un terrain</a>
    <?php if ($user): ?><form method="post" action="/admin/logout" class="inline"><?= csrf_field($csrf) ?><button class="link">Déconnexion</button></form><?php endif; ?>
  </div>
</header>
<main id="contenu" class="<?= !empty($fullBleed) ? 'full' : 'site-main' ?>">
  <?php if (!empty($flash)): ?><p class="flash" role="status"><?= e($flash) ?></p><?php endif; ?>
  <?= $content ?>
</main>
<footer class="site-foot">
  <div class="foot-grid">
    <div class="foot-brand">
      <?php if (!empty($settings['logo_url'])): ?><img src="<?= e($settings['logo_url']) ?>" alt="<?= e($site['name']) ?>" width="126" height="84" loading="lazy"><?php else: ?><strong><?= e($site['name']) ?></strong><?php endif; ?>
      <?php if (!empty($settings['tagline'])): ?><p><?= e($settings['tagline']) ?></p><?php endif; ?>
    </div>
    <nav class="foot-menu" aria-label="Pied de page">
      <?php foreach (($footerMenu ?: $nav) as $i): ?><a href="<?= e($i['url']) ?>"><?= e($i['label']) ?></a><?php endforeach; ?>
    </nav>
  </div>
  <div class="foot-contact">
    <?php if (!empty($settings['phone'])): ?><p>Téléphone<br><a href="tel:<?= e(preg_replace('/[^0-9+]/', '', $settings['phone'])) ?>"><?= e($settings['phone']) ?></a></p><?php endif; ?>
    <?php if (!empty($settings['email'])): ?><p>Email<br><a href="mailto:<?= e($settings['email']) ?>"><?= e($settings['email']) ?></a></p><?php endif; ?>
    <?php if ($wa !== ''): ?><p>WhatsApp<br><a href="https://wa.me/<?= e($wa) ?>" rel="noopener">Nous écrire</a></p><?php endif; ?>
    <?php if (!empty($settings['address'])): ?><p>Adresse<br><?= e($settings['address']) ?></p><?php endif; ?>
  </div>
  <p class="foot-legal">© <?= date('Y') ?> <?= e($site['name']) ?> · Côte d'Ivoire</p>
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
    ['Contenu', [['Pages', '/admin/pages', 'file-text', 'content.manage'], ['Articles', '/admin/articles', 'clipboard-list', 'content.manage'], ['Médias', '/admin/media', 'images', 'content.manage'], ['Menus', '/admin/menus', 'menu', 'content.manage'], ['Formulaires', '/admin/formulaires', 'message-circle', 'content.manage']]],
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
        <span class="who"><?= e($user['name']) ?></span>
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
