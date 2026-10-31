<?php if (!$r['items']): ?><p>Aucun article publié pour le moment.</p><?php endif; ?>
<?php foreach ($r['items'] as $a): ?>
  <article class="card"><a href="/actualites/<?= e($a['slug']) ?>"><strong><?= e($a['title']) ?></strong></a>
  <small><?= e(substr((string) ($a['published_at'] ?? $a['created_at']), 0, 10)) ?></small></article>
<?php endforeach; ?>
<nav class="pager"><?php for ($i = 1; $i <= $r['pages']; $i++): ?><a href="?page=<?= $i ?>" <?= $i === $r['page'] ? 'aria-current="page"' : '' ?>><?= $i ?></a><?php endfor; ?></nav>
