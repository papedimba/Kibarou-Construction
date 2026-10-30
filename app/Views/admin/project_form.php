<h1><?= $id ? 'Modifier la réalisation' : 'Nouvelle réalisation' ?></h1>
<?php foreach ($errors as $m): ?><p class="error" role="alert"><?= e($m) ?></p><?php endforeach; ?>
<form method="post" action="<?= $id ? '/admin/realisations/' . (int) $id : '/admin/realisations' ?>" class="card">
  <?= csrf_field($csrf) ?>
  <label>Nom <input name="name" required value="<?= e($r['name'] ?? '') ?>"></label>
  <label>Catégorie <select name="category"><?php foreach ($cats as $k => $n): ?><option value="<?= e($k) ?>" <?= ($r['category'] ?? '') === $k ? 'selected' : '' ?>><?= e($n) ?></option><?php endforeach; ?></select></label>
  <label>Localisation <input name="location_text" value="<?= e($r['location_text'] ?? '') ?>"></label>
  <label>Date de livraison <input type="date" name="completed_on" value="<?= e($r['completed_on'] ?? '') ?>"></label>
  <label>Statut <select name="status"><?php foreach ($statuses as $k => $n): ?><option value="<?= e($k) ?>" <?= ($r['status'] ?? '') === $k ? 'selected' : '' ?>><?= e($n) ?></option><?php endforeach; ?></select></label>
  <label>Description <textarea name="description" rows="6"><?= e($r['description'] ?? '') ?></textarea></label>
  <label class="check"><input type="checkbox" name="is_published" value="1" <?= !empty($r['is_published']) ? 'checked' : '' ?>> Publiée sur le site</label>
  <button class="btn">Enregistrer</button>
</form>
<?php if ($id): ?>
<h2>Galerie</h2>
<div class="grid">
<?php foreach ($images as $i): ?>
  <div class="card"><img src="/uploads/<?= (int) $site['id'] ?>/<?= e($i['filename']) ?>" alt="<?= e($i['alt'] ?? '') ?>" loading="lazy" class="thumb">
    <form method="post" action="/admin/realisations/<?= (int) $id ?>/images/<?= (int) $i['id'] ?>/delete" class="inline"><?= csrf_field($csrf) ?><button class="danger">Retirer</button></form></div>
<?php endforeach; ?>
</div>
<form method="post" action="/admin/realisations/<?= (int) $id ?>/images" enctype="multipart/form-data" class="card">
  <?= csrf_field($csrf) ?>
  <label>Ajouter une photo <input type="file" name="file" accept="image/jpeg,image/png,image/webp,image/gif" required></label>
  <label>Texte alternatif <input name="alt" maxlength="190"></label>
  <button class="btn">Téléverser la photo</button>
</form>
<?php endif; ?>
