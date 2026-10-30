<?php $src = $r->mapSrc($p['lat'], $p['lng']); if ($src): ?>
<section class="blk-block"><?php if ($p['title'] !== ''): ?><h2><?= e($p['title']) ?></h2><?php endif; ?>
<iframe class="blk-map" src="<?= e($src) ?>" title="Carte" loading="lazy"></iframe></section>
<?php elseif ($r->editor): ?><div class="blk-notice">Carte : renseignez latitude et longitude.</div><?php endif; ?>
