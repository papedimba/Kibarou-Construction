<h1>Lotissements</h1>
<p><a class="btn" href="/admin/lotissements/new">Nouveau lotissement</a></p>
<table>
  <thead><tr><th>Nom</th><th>Parcelles</th><th>Disponibles</th><th>Publié</th><th></th></tr></thead>
  <tbody>
  <?php foreach ($items as $d): ?>
    <tr><td><?= e($d['name']) ?></td><td><?= (int) $d['plots'] ?></td><td><?= (int) $d['available'] ?></td><td><?= badge($d['is_published'] ? 'published' : 'draft') ?></td>
    <td><a href="/admin/lotissements/<?= (int) $d['id'] ?>/edit">Gérer</a></td></tr>
  <?php endforeach; ?>
  <?php if (!$items): ?><tr><td colspan="5">Aucun lotissement.</td></tr><?php endif; ?>
  </tbody>
</table>
