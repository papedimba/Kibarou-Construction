<?php if (!$items): ?><p>Nos réalisations seront présentées ici.</p><?php endif; ?>
<div class="grid">
<?php foreach ($items as $r): ?>
  <a class="card" href="/realisations/<?= e($r['slug']) ?>">
    <?php if (!empty($covers[(int) $r['id']])): ?><img src="/uploads/<?= (int) $site['id'] ?>/<?= e($covers[(int) $r['id']]) ?>" alt="<?= e($r['name']) ?>" loading="lazy" class="thumb"><?php endif; ?>
    <strong><?= e($r['name']) ?></strong><small><?= e($r['location_text'] ?? '') ?></small>
  </a>
<?php endforeach; ?>
</div>
