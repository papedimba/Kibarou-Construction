<?php
/** @var string $content */
$site = $site ?? null; $user = $user ?? null; $csrf = $csrf ?? ''; $settings = $settings ?? [];
$headerMenu = $headerMenu ?? []; $footerMenu = $footerMenu ?? []; $adminNav = $adminNav ?? false;
$theme = $settings['theme'] ?? 'corporate-premium';
$pageTitle = ($title ?? '') !== '' ? $title . ' — ' . ($site['name'] ?? 'Kibarou CMS') : ($site['name'] ?? 'Kibarou CMS');
$desc = $metaDescription ?? ($settings['meta_description'] ?? '');
$wa = preg_replace('/\D/', '', $settings['whatsapp'] ?? '');
?>
<!doctype html>
<html lang="fr">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title><?= e($pageTitle) ?></title>
<?php if ($desc !== ''): ?><meta name="description" content="<?= e($desc) ?>"><?php endif; ?>
<?php if ($site && !$adminNav): ?>
<link rel="canonical" href="<?= e($canonical ?? '') ?>">
<meta property="og:type" content="website">
<meta property="og:title" content="<?= e($pageTitle) ?>">
<meta property="og:url" content="<?= e($canonical ?? '') ?>">
<?php if ($desc !== ''): ?><meta property="og:description" content="<?= e($desc) ?>"><?php endif; ?>
<?php endif; ?>
<?php if ($adminNav || !$site): ?><meta name="robots" content="noindex"><?php endif; ?>
<link rel="stylesheet" href="/assets/css/app.css">
<?php if ($site): ?><link rel="stylesheet" href="/assets/css/themes/<?= e(preg_replace('/[^a-z-]/', '', $theme)) ?>.css"><?php endif; ?>
</head>
<body>
<header class="bar">
  <a class="brand" href="<?= $site ? '/' : '/platform/sites' ?>">
    <?php if (!empty($settings['logo_url']) && !$adminNav): ?><img src="<?= e($settings['logo_url']) ?>" alt="<?= e($site['name']) ?>" class="logo"><?php else: ?><?= e($site['name'] ?? 'Kibarou CMS — Plateforme') ?><?php endif; ?>
  </a>
  <nav aria-label="Navigation principale">
    <?php if ($site && !$adminNav): ?>
      <?php foreach ($headerMenu as $i): ?><a href="<?= e($i['url']) ?>"><?= e($i['label']) ?></a><?php endforeach; ?>
      <?php if (!$headerMenu): ?><a href="/terrains">Nos terrains</a><a href="/services">Services</a><a href="/realisations">Réalisations</a><a href="/actualites">Actualités</a><a href="/contact">Contact</a><?php endif; ?>
      <?php if ($user): ?><a href="/admin">Administration</a><?php endif; ?>
    <?php elseif ($site && $adminNav): ?>
      <a href="/admin">Accueil</a><a href="/admin/terrains">Terrains</a><a href="/admin/lotissements">Lotissements</a><a href="/admin/localisations">Localisations</a>
      <a href="/admin/prospects">Prospects</a><a href="/admin/visites">Visites</a><a href="/admin/formulaires">Formulaires</a><a href="/admin/services">Services</a><a href="/admin/realisations">Réalisations</a><a href="/admin/pages">Pages</a><a href="/admin/articles">Articles</a>
      <a href="/admin/media">Médias</a><a href="/admin/menus">Menus</a><a href="/admin/settings">Réglages</a><a href="/">Voir le site</a>
    <?php elseif ($user): ?>
      <a href="/platform/sites">Sites</a>
    <?php endif; ?>
    <?php if ($user): ?>
      <form method="post" action="<?= $site ? '/admin/logout' : '/platform/logout' ?>" class="inline"><?= csrf_field($csrf) ?><button class="link">Déconnexion</button></form>
    <?php endif; ?>
  </nav>
</header>
<main>
  <?php if (!empty($flash)): ?><p class="flash" role="status"><?= e($flash) ?></p><?php endif; ?>
  <?= $content ?>
</main>
<?php if ($site && !$adminNav): ?>
<footer class="foot">
  <p><strong><?= e($site['name']) ?></strong><?php if (!empty($settings['tagline'])): ?> — <?= e($settings['tagline']) ?><?php endif; ?></p>
  <p>
    <?php if (!empty($settings['phone'])): ?>Tél. <?= e($settings['phone']) ?> · <?php endif; ?>
    <?php if (!empty($settings['email'])): ?><a href="mailto:<?= e($settings['email']) ?>"><?= e($settings['email']) ?></a> · <?php endif; ?>
    <?php if ($wa !== ''): ?><a href="https://wa.me/<?= e($wa) ?>" rel="noopener">WhatsApp</a><?php endif; ?>
  </p>
  <?php if (!empty($settings['address'])): ?><p><?= e($settings['address']) ?></p><?php endif; ?>
  <nav aria-label="Pied de page"><?php foreach ($footerMenu as $i): ?><a href="<?= e($i['url']) ?>"><?= e($i['label']) ?></a> <?php endforeach; ?></nav>
</footer>
<?php endif; ?>
</body>
</html>
