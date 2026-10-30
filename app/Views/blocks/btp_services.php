<?php $items = $r->activeServices((int) $p['count']); if ($items || $r->editor): ?>
<section class="blk-block"><?php if ($p['title'] !== ''): ?><h2><?= e($p['title']) ?></h2><?php endif; ?>
<div class="blk-cards"><?php foreach ($items as $s): ?><a class="blk-card" href="/services/<?= e($s['slug']) ?>"><h3><?= e($s['title']) ?></h3><?= text_html(mb_substr((string) $s['description'], 0, 160)) ?></a><?php endforeach; ?></div>
<?php if (!$items): ?><div class="blk-notice">Aucun service actif : activez-en dans l'administration.</div><?php endif; ?></section>
<?php endif; ?>
