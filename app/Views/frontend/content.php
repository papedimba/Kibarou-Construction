<?php if (!empty($blocksHtml)): ?>
<?= $blocksHtml /* trusted: produced by BlockRenderer from sanitized blocks, every value escaped there */ ?>
<?php else: ?>
<article>
  <h1><?= e($item['title']) ?></h1>
  <?= text_html($item['body']) ?>
</article>
<?php endif; ?>
