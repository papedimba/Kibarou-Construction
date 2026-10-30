<h1>Réponses — <?= e($f['name']) ?></h1>
<p><a href="/admin/formulaires">← Formulaires</a></p>
<?php foreach ($rows as $r): ?>
  <div class="card"><small><?= e($r['at']) ?></small>
  <?php foreach ($r['data'] as $label => $value): ?><p><strong><?= e((string) $label) ?></strong> : <?= e((string) $value) ?></p><?php endforeach; ?></div>
<?php endforeach; ?>
<?php if (!$rows): ?><p>Aucune réponse.</p><?php endif; ?>
