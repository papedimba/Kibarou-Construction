<section class="blk-block"><?php if ($p['title'] !== ''): ?><h2><?= e($p['title']) ?></h2><?php endif; ?>
<?php foreach ($p['items'] as $i): ?><details class="blk-faq"><summary><?= e($i['q']) ?></summary><?= text_html($i['a']) ?></details><?php endforeach; ?></section>
