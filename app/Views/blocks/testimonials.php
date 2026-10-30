<?php $ok = array_filter($p['items'], fn($i) => $i['verified']); if ($ok || $r->editor): ?>
<section class="blk-block"><?php if ($p['title'] !== ''): ?><h2><?= e($p['title']) ?></h2><?php endif; ?>
<div class="blk-cards"><?php foreach ($ok as $i): ?><blockquote class="blk-card"><p><?= e($i['quote']) ?></p><footer><?= e($i['author']) ?><?= $i['role'] !== '' ? ', ' . e($i['role']) : '' ?></footer></blockquote><?php endforeach; ?></div>
<?php if ($r->editor && count($ok) < count($p['items'])): ?><div class="blk-notice"><?= count($p['items']) - count($ok) ?> témoignage(s) non vérifié(s) : masqué(s) sur le site.</div><?php endif; ?></section>
<?php endif; ?>
