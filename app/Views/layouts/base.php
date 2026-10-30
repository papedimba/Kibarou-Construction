<?php /** @var string $content */ $site = $site ?? null; $user = $user ?? null; $csrf = $csrf ?? ''; ?>
<!doctype html>
<html lang="fr">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title><?= e(($title ?? '') . ($title ?? '' ? ' — ' : '') . ($site['name'] ?? 'Kibarou CMS')) ?></title>
<link rel="stylesheet" href="/assets/css/app.css">
</head>
<body>
<header class="bar">
  <a class="brand" href="<?= $site ? '/' : '/platform/sites' ?>"><?= e($site['name'] ?? 'Kibarou CMS — Plateforme') ?></a>
  <nav>
    <?php if ($site): ?>
      <a href="/terrains">Nos terrains</a>
      <?php if ($user): ?><a href="/admin">Administration</a><?php endif; ?>
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
</body>
</html>
