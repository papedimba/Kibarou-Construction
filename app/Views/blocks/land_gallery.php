<?php $ph = $r->landPhotos($p['reference']); if ($ph): ?><div class="blk-gallery blk-grid-3"><?php foreach ($ph as $i): ?><img src="<?= e($i['url']) ?>" alt="<?= e($i['alt']) ?>" loading="lazy"><?php endforeach; ?></div>
<?php elseif ($r->editor): ?><div class="blk-notice">Galerie immobilière : référence d'un terrain publié avec photos attendue.</div><?php endif; ?>
