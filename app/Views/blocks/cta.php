<section class="blk-cta blk-cta-<?= e($p['tone']) ?>"><?php if ($p['title'] !== ''): ?><h2><?= e($p['title']) ?></h2><?php endif; ?><?php if ($p['text'] !== ''): ?><p><?= e($p['text']) ?></p><?php endif; ?>
<?php if ($p['button_label'] !== '' && $p['button_url'] !== ''): ?><p><a class="btn" href="<?= e($p['button_url']) ?>"><?= e($p['button_label']) ?></a></p><?php endif; ?></section>
