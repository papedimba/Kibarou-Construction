<h1>Terrains</h1>
<p><a class="btn" href="/admin/terrains/new">Ajouter un terrain</a></p>
<form method="get" class="filters">
  <input name="q" placeholder="Référence ou désignation" value="<?= e($f['q'] ?? '') ?>" aria-label="Recherche">
  <select name="status" aria-label="Statut"><option value="">Tous statuts</option>
    <?php foreach ($statuses as $s): ?><option value="<?= e($s) ?>" <?= ($f['status'] ?? '') === $s ? 'selected' : '' ?>><?= e($s) ?></option><?php endforeach; ?>
  </select>
  <button>Filtrer</button>
</form>
<table>
  <thead><tr><th>Réf.</th><th>Désignation</th><th>Superficie</th><th>Prix</th><th>Statut</th><th>Publié</th><th>Actions</th></tr></thead>
  <tbody>
  <?php foreach ($r['items'] as $l): ?>
    <tr>
      <td><?= e($l['reference']) ?></td><td><?= e($l['title']) ?></td><td><?= (int) $l['area_m2'] ?> m²</td><td><?= e(fcfa($l['price_xof'])) ?></td>
      <td><?= e($l['status']) ?></td><td><?= $l['is_published'] ? 'Oui' : 'Non' ?></td>
      <td>
        <a href="/admin/terrains/<?= (int) $l['id'] ?>/edit">Modifier</a> · <a href="/admin/terrains/<?= (int) $l['id'] ?>/medias">Photos/docs</a>
        <form method="post" action="/admin/terrains/<?= (int) $l['id'] ?>/status" class="inline"><?= csrf_field($csrf) ?>
          <select name="status" aria-label="Changer le statut"><?php foreach ($statuses as $s): ?><option value="<?= e($s) ?>" <?= $l['status'] === $s ? 'selected' : '' ?>><?= e($s) ?></option><?php endforeach; ?></select>
          <button>OK</button>
        </form>
        <form method="post" action="/admin/terrains/<?= (int) $l['id'] ?>/delete" class="inline"><?= csrf_field($csrf) ?><button class="danger">Supprimer</button></form>
      </td>
    </tr>
  <?php endforeach; ?>
  <?php if (!$r['items']): ?><tr><td colspan="7">Aucun terrain.</td></tr><?php endif; ?>
  </tbody>
</table>
<p><?= (int) $r['total'] ?> résultat(s) — page <?= (int) $r['page'] ?>/<?= (int) $r['pages'] ?></p>
