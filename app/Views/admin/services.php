<h1>Services BTP</h1>
<p>Un service n'apparaît sur le site qu'une fois activé : laissez désactivé tant que la prestation n'est pas confirmée.</p>
<p><a class="btn" href="/admin/services/new">Ajouter un service</a></p>
<table>
  <thead><tr><th>Titre</th><th>Catégorie</th><th>Actif</th><th></th></tr></thead>
  <tbody>
  <?php foreach ($items as $s): ?>
    <tr><td><?= e($s['title']) ?></td><td><?= e($cats[$s['category']] ?? $s['category']) ?></td><td><?= badge($s['is_active'] ? 'active' : 'inactive', $s['is_active'] ? 'Actif' : 'Masqué') ?></td>
    <td><a href="/admin/services/<?= (int) $s['id'] ?>/edit">Modifier</a>
      <form method="post" action="/admin/services/<?= (int) $s['id'] ?>/delete" class="inline"><?= csrf_field($csrf) ?><button class="danger">Supprimer</button></form></td></tr>
  <?php endforeach; ?>
  <?php if (!$items): ?><tr><td colspan="4">Aucun service.</td></tr><?php endif; ?>
  </tbody>
</table>
