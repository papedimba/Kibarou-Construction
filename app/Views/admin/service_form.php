<h1><?= $id ? 'Modifier le service' : 'Nouveau service' ?></h1>
<?php foreach ($errors as $m): ?><p class="error" role="alert"><?= e($m) ?></p><?php endforeach; ?>
<form method="post" action="<?= $id ? '/admin/services/' . (int) $id : '/admin/services' ?>" enctype="multipart/form-data" class="card">
  <?= csrf_field($csrf) ?>
  <label>Titre <input name="title" required value="<?= e($s['title'] ?? '') ?>"></label>
  <label>Catégorie <select name="category"><?php foreach ($cats as $k => $n): ?><option value="<?= e($k) ?>" <?= ($s['category'] ?? '') === $k ? 'selected' : '' ?>><?= e($n) ?></option><?php endforeach; ?></select></label>
  <label>Description <textarea name="description" rows="6"><?= e($s['description'] ?? '') ?></textarea></label>
  <label>Image (facultative) <input type="file" name="file" accept="image/jpeg,image/png,image/webp,image/gif"></label>
  <label class="check"><input type="checkbox" name="is_active" value="1" <?= !empty($s['is_active']) ? 'checked' : '' ?>> Actif (visible sur le site)</label>
  <button class="btn">Enregistrer</button>
</form>
