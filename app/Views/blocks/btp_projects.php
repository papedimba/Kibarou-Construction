<?php $items = $r->publishedProjects((int) $p['count']); if ($items || $r->editor): ?>
<section class="blk-block"><?php if ($p['title'] !== ''): ?><h2><?= e($p['title']) ?></h2><?php endif; ?>
<div class="grid"><?php foreach ($items as $pr): ?><a class="card" href="/realisations/<?= e($pr['slug']) ?>"><?php if ($pr['cover']): ?><img src="/uploads/<?= (int) $r->siteId() ?>/<?= e($pr['cover']) ?>" alt="<?= e($pr['name']) ?>" loading="lazy" class="thumb"><?php endif; ?><strong><?= e($pr['name']) ?></strong><small><?= e($pr['location_text'] ?? '') ?></small></a><?php endforeach; ?></div>
<?php if (!$items): ?><div class="blk-notice">Aucune réalisation publiée.</div><?php endif; ?></section>
<?php endif; ?>
