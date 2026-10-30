<div class="blk-gallery blk-grid-<?= (int) $p['cols'] ?>">
<?php foreach ($p['images'] as $i): if ($m = $r->media($i['image'])): ?><img src="<?= e($m['url']) ?>" alt="<?= e($i['alt'] ?: $m['alt']) ?>" loading="lazy"><?php endif; endforeach; ?>
<?php if (!$p['images'] && $r->editor): ?><div class="blk-notice">Galerie vide : ajoutez des images.</div><?php endif; ?>
</div>
