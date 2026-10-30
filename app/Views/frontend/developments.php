<h1>Nos lotissements</h1>
<?php if (!$items): ?><p>Aucun lotissement publié pour le moment.</p><?php endif; ?>
<div class="grid">
<?php foreach ($items as $d): ?>
  <a class="card" href="/lotissements/<?= e($d['slug']) ?>"><strong><?= e($d['name']) ?></strong><span><?= (int) $d['available'] ?> parcelle(s) disponible(s) sur <?= (int) $d['plots'] ?></span></a>
<?php endforeach; ?>
</div>
