<?php $l = $r->landByReference($p['reference']); if ($l): $cover = $r->covers([$l])[(int) $l['id']] ?? null; ?><div class="blk-block"><?php require __DIR__ . '/_land_card.php'; ?></div>
<?php elseif ($r->editor): ?><div class="blk-notice">Terrain vedette : référence d'un terrain publié attendue.</div><?php endif; ?>
