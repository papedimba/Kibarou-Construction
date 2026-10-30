<h1>Médias</h1>
<form method="post" action="/admin/media" enctype="multipart/form-data" class="card">
  <?= csrf_field($csrf) ?>
  <label>Image (JPEG, PNG, WebP, GIF — 5 Mo max) <input type="file" name="file" accept="image/jpeg,image/png,image/webp,image/gif" required></label>
  <label>Texte alternatif (ALT) <input name="alt" maxlength="190"></label>
  <button class="btn">Téléverser</button>
</form>
<div class="grid">
<?php foreach ($items as $m): ?>
  <div class="card">
    <img src="/uploads/<?= (int) $site['id'] ?>/<?= e($m['filename']) ?>" alt="<?= e($m['alt'] ?? '') ?>" loading="lazy" class="thumb">
    <small>/uploads/<?= (int) $site['id'] ?>/<?= e($m['filename']) ?></small>
    <form method="post" action="/admin/media/<?= (int) $m['id'] ?>/delete" class="inline"><?= csrf_field($csrf) ?><button class="danger">Supprimer</button></form>
  </div>
<?php endforeach; ?>
<?php if (!$items): ?><p>Aucune image.</p><?php endif; ?>
</div>
