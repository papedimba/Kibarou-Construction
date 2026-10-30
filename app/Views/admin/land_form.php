<h1><?= $id ? 'Modifier le terrain' : 'Nouveau terrain' ?></h1>
<?php foreach ($errors as $msg): ?><p class="error" role="alert"><?= e($msg) ?></p><?php endforeach; ?>
<form method="post" action="<?= $id ? '/admin/terrains/' . (int) $id : '/admin/terrains' ?>" class="card">
  <?= csrf_field($csrf) ?>
  <label>Référence <input name="reference" required value="<?= e($land['reference'] ?? '') ?>"></label>
  <label>Désignation <input name="title" required value="<?= e($land['title'] ?? '') ?>"></label>
  <label>Catégorie <select name="category_id"><option value="">—</option>
    <?php foreach ($cats as $c): ?><option value="<?= (int) $c['id'] ?>" <?= (string) ($land['category_id'] ?? '') === (string) $c['id'] ? 'selected' : '' ?>><?= e($c['name']) ?></option><?php endforeach; ?>
  </select></label>
  <label>Superficie (m²) <input name="area_m2" inputmode="numeric" required value="<?= e((string) ($land['area_m2'] ?? '')) ?>"></label>
  <label>Prix (FCFA) <input name="price_xof" inputmode="numeric" required value="<?= e((string) ($land['price_xof'] ?? '')) ?>"></label>
  <label>Statut <select name="status"><?php foreach ($statuses as $s): ?><option value="<?= e($s) ?>" <?= ($land['status'] ?? 'available') === $s ? 'selected' : '' ?>><?= e($s) ?></option><?php endforeach; ?></select></label>
  <label>Description <textarea name="description" rows="5"><?= e($land['description'] ?? '') ?></textarea></label>
  <label class="check"><input type="checkbox" name="is_published" value="1" <?= !empty($land['is_published']) ? 'checked' : '' ?>> Publié sur le site</label>
  <button class="btn">Enregistrer</button>
</form>
