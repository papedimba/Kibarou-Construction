<?php $v = $r->video($p['url']); if ($v): ?>
<figure class="blk-video"><?php if ($v['kind'] === 'iframe'): ?><iframe src="<?= e($v['src']) ?>" title="<?= e($p['caption'] ?: 'Vidéo') ?>" loading="lazy" allowfullscreen referrerpolicy="strict-origin-when-cross-origin"></iframe>
<?php else: ?><video src="<?= e($v['src']) ?>" controls preload="none"></video><?php endif; ?>
<?php if ($p['caption'] !== ''): ?><figcaption><?= e($p['caption']) ?></figcaption><?php endif; ?></figure>
<?php elseif ($r->editor): ?><div class="blk-notice">Vidéo : lien YouTube, Vimeo ou .mp4 (https) attendu.</div><?php endif; ?>
