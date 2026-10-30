<section class="blk-block"><?php if ($p['title'] !== ''): ?><h2><?= e($p['title']) ?></h2><?php endif; ?>
<div class="blk-cards"><?php foreach ($p['items'] as $i): ?><div class="blk-card"><?= $i['icon'] !== '' ? '<span class="blk-card__icon">' . $r->icon($i['icon'], 32) . '</span>' : '' ?><h3><?= e($i['title']) ?></h3><?= text_html($i['text']) ?></div><?php endforeach; ?></div></section>
