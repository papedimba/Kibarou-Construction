<h1><?= $id ? 'Modifier le formulaire' : 'Nouveau formulaire' ?></h1>
<?php foreach ($errors as $m): ?><p class="error" role="alert"><?= e($m) ?></p><?php endforeach; ?>
<form method="post" action="<?= $id ? '/admin/formulaires/' . (int) $id : '/admin/formulaires' ?>" class="card">
  <?= csrf_field($csrf) ?>
  <label>Nom <input name="name" required value="<?= e($f['name'] ?? '') ?>"></label>
  <label>Adresse (vide = automatique) <input name="slug" value="<?= e($f['slug'] ?? '') ?>"></label>
  <label>Champs — une ligne par champ : <code>Libellé | type | obligatoire (1/0) | options</code>
    <textarea name="fields_text" rows="8" required><?= e($f['fields_text'] ?? '') ?></textarea></label>
  <p class="muted">Types : <?= e(implode(', ', array_keys($types))) ?>. Pour une liste, séparez les options par des virgules.</p>
  <label>Email de notification (facultatif) <input type="email" name="notify_email" value="<?= e($f['notify_email'] ?? '') ?>"></label>
  <label>Message de confirmation <input name="success_message" maxlength="300" value="<?= e($f['success_message'] ?? '') ?>"></label>
  <label class="check"><input type="checkbox" name="is_active" value="1" <?= !empty($f['is_active']) ? 'checked' : '' ?>> Actif (accessible publiquement)</label>
  <button class="btn">Enregistrer</button>
</form>
