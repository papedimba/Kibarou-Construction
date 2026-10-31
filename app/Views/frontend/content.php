<?php if (!empty($blocksHtml)): ?>
<?= $blocksHtml /* trusted: produced by BlockRenderer from sanitized blocks, every value escaped there */ ?>
<?php else: ?>
<article class="prose">
  <?= text_html($item['body']) ?>
</article>
<?php endif; ?>
