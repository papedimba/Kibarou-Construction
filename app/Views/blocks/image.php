<?php $m = $r->media($p['image']); if ($m): $img = '<img src="' . e($m['url']) . '" alt="' . e($p['alt'] ?: $m['alt']) . '" loading="lazy">'; ?>
<figure class="blk-image"><?= $p['link'] !== '' ? '<a href="' . e($p['link']) . '">' . $img . '</a>' : $img ?><?php if ($p['caption'] !== ''): ?><figcaption><?= e($p['caption']) ?></figcaption><?php endif; ?></figure>
<?php elseif ($r->editor): ?><div class="blk-notice">Image : choisissez un média dans les paramètres.</div><?php endif; ?>
