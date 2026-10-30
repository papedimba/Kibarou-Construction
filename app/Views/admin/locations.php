<h1>Localisations</h1>
<p>Hiérarchie géographique libre : district, région, département, ville, commune, quartier.</p>
<ul>
<?php foreach ($tree as $n): ?>
  <li class="depth-<?= min((int) $n['depth'], 5) ?>"><?= e($types[$n['type']] ?? $n['type']) ?> — <?= e($n['name']) ?>
    <form method="post" action="/admin/localisations/<?= (int) $n['id'] ?>/delete" class="inline"><?= csrf_field($csrf) ?><button class="danger">Supprimer</button></form></li>
<?php endforeach; ?>
<?php if (!$tree): ?><li>Aucune localisation. Ajoutez-en une ci-dessous.</li><?php endif; ?>
</ul>
<form method="post" action="/admin/localisations" class="card">
  <?= csrf_field($csrf) ?>
  <label>Type <select name="type"><?php foreach ($types as $k => $n): ?><option value="<?= e($k) ?>"><?= e($n) ?></option><?php endforeach; ?></select></label>
  <label>Nom <input name="name" required maxlength="150"></label>
  <label>Rattachée à <select name="parent_id"><option value="">— aucune (niveau supérieur)</option>
    <?php foreach ($tree as $n): ?><option value="<?= (int) $n['id'] ?>"><?= e($n['label']) ?></option><?php endforeach; ?></select></label>
  <button class="btn">Ajouter</button>
</form>
