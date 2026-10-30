<h1>Réalisations</h1>
<p><a class="btn" href="/admin/realisations/new">Ajouter une réalisation</a></p>
<table>
  <thead><tr><th>Nom</th><th>Statut</th><th>Publié</th><th></th></tr></thead>
  <tbody>
  <?php foreach ($items as $r): ?>
    <tr><td><?= e($r['name']) ?></td><td><?= e($statuses[$r['status']] ?? $r['status']) ?></td><td><?= $r['is_published'] ? 'Oui' : 'Non' ?></td>
    <td><a href="/admin/realisations/<?= (int) $r['id'] ?>/edit">Modifier</a>
      <form method="post" action="/admin/realisations/<?= (int) $r['id'] ?>/delete" class="inline"><?= csrf_field($csrf) ?><button class="danger">Supprimer</button></form></td></tr>
  <?php endforeach; ?>
  <?php if (!$items): ?><tr><td colspan="4">Aucune réalisation.</td></tr><?php endif; ?>
  </tbody>
</table>
