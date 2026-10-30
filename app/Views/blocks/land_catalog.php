<?php $d = $r->catalog(['category' => $p['category'], 'sort' => $p['sort']], (int) $p['count']); ?>
<section class="blk-block"><?php if ($p['title'] !== ''): ?><h2><?= e($p['title']) ?></h2><?php endif; ?>
<div class="grid"><?php foreach ($d['items'] as $l): $cover = $d['covers'][(int) $l['id']] ?? null; require __DIR__ . '/_land_card.php'; endforeach; ?></div>
<?php if (!$d['items'] && $r->editor): ?><div class="blk-notice">Aucun terrain publié ne correspond.</div><?php endif; ?>
<p><a href="/terrains">Voir tous les terrains →</a></p></section>
