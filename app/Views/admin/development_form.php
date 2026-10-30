<h1><?= $id ? 'Lotissement' : 'Nouveau lotissement' ?></h1>
<?php foreach ($errors as $m): ?><p class="error" role="alert"><?= e($m) ?></p><?php endforeach; ?>
<form method="post" action="<?= $id ? '/admin/lotissements/' . (int) $id : '/admin/lotissements' ?>" class="card">
  <?= csrf_field($csrf) ?>
  <label>Nom <input name="name" required value="<?= e($dev['name'] ?? '') ?>"></label>
  <label>Localisation <select name="location_id"><option value="">—</option>
    <?php foreach ($locs as $l): ?><option value="<?= (int) $l['id'] ?>" <?= (string) ($dev['location_id'] ?? '') === (string) $l['id'] ? 'selected' : '' ?>><?= e($l['label']) ?></option><?php endforeach; ?></select></label>
  <label>Description <textarea name="description" rows="4"><?= e($dev['description'] ?? '') ?></textarea></label>
  <label class="check"><input type="checkbox" name="is_published" value="1" <?= !empty($dev['is_published']) ? 'checked' : '' ?>> Publié sur le site</label>
  <button class="btn">Enregistrer</button>
</form>
<?php if ($id): ?>
<h2>Parcelles</h2>
<table>
  <thead><tr><th>N°</th><th>Réf.</th><th>Superficie</th><th>Prix</th><th>Statut</th><th>Situation</th><th></th></tr></thead>
  <tbody>
  <?php foreach ($plots as $p): ?>
    <tr><td><?= e($p['number']) ?></td><td><?= e($p['reference']) ?></td><td><?= (int) $p['area_m2'] ?> m²</td><td><?= e(fcfa($p['price_xof'])) ?></td><td><?= badge($p['status']) ?></td><td><?= e($p['location_note'] ?? '') ?></td>
    <td>
      <form method="post" action="/admin/lotissements/<?= (int) $id ?>/parcelles/<?= (int) $p['id'] ?>" class="inline"><?= csrf_field($csrf) ?>
        <select name="status" aria-label="Statut"><?php foreach ($statuses as $s): ?><option value="<?= e($s) ?>" <?= $p['status'] === $s ? 'selected' : '' ?>><?= e($s) ?></option><?php endforeach; ?></select><button>OK</button></form>
      <form method="post" action="/admin/lotissements/<?= (int) $id ?>/parcelles/<?= (int) $p['id'] ?>/delete" class="inline"><?= csrf_field($csrf) ?><button class="danger">Supprimer</button></form>
    </td></tr>
  <?php endforeach; ?>
  <?php if (!$plots): ?><tr><td colspan="7">Aucune parcelle.</td></tr><?php endif; ?>
  </tbody>
</table>
<form method="post" action="/admin/lotissements/<?= (int) $id ?>/parcelles" class="card">
  <?= csrf_field($csrf) ?>
  <label>Numéro <input name="number" required maxlength="20"></label>
  <label>Référence <input name="reference" required maxlength="40"></label>
  <label>Superficie (m²) <input name="area_m2" inputmode="numeric" required></label>
  <label>Prix (FCFA) <input name="price_xof" inputmode="numeric" required></label>
  <label>Statut <select name="status"><?php foreach ($statuses as $s): ?><option value="<?= e($s) ?>"><?= e(status_label($s)) ?></option><?php endforeach; ?></select></label>
  <label>Situation dans le lotissement (facultatif) <input name="location_note" maxlength="190"></label>
  <button class="btn">Ajouter la parcelle</button>
</form>
<form method="post" action="/admin/lotissements/<?= (int) $id ?>/delete"><?= csrf_field($csrf) ?><button class="danger">Supprimer le lotissement et ses parcelles</button></form>
<?php endif; ?>
