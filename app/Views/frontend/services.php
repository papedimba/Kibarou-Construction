<?php if (!$items): ?><p>Nos prestations seront bientôt présentées ici.</p><?php endif; ?>
<div class="grid">
<?php foreach ($items as $s): ?>
  <a class="card" href="/services/<?= e($s['slug']) ?>">
    <?php if ($s['media_id'] && isset($images[(int) $s['media_id']])): $m = $images[(int) $s['media_id']]; ?><img src="/uploads/<?= (int) $site['id'] ?>/<?= e($m['filename']) ?>" alt="<?= e($m['alt'] ?: $s['title']) ?>" loading="lazy" class="thumb"><?php endif; ?>
    <small><?= e($cats[$s['category']] ?? '') ?></small><strong><?= e($s['title']) ?></strong>
  </a>
<?php endforeach; ?>
</div>
