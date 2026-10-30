<h1>Catégories de terrains</h1>
<table>
  <thead><tr><th>Nom</th><th>Identifiant</th><th class="num">Terrains</th><th></th></tr></thead>
  <tbody>
  <?php foreach ($rows as $c): ?>
    <tr><td><?= e($c['name']) ?></td><td><code><?= e($c['slug']) ?></code></td><td class="num"><?= (int) $c['uses'] ?></td>
    <td><form method="post" action="/admin/categories/<?= (int) $c['id'] ?>/delete" class="inline"><?= csrf_field($csrf) ?><button class="danger" <?= (int) $c['uses'] > 0 ? 'disabled title="Utilisée par des terrains"' : '' ?>>Supprimer</button></form></td></tr>
  <?php endforeach; ?>
  </tbody>
</table>
<form method="post" action="/admin/categories" class="card"><?= csrf_field($csrf) ?>
  <label>Nouvelle catégorie <input name="name" required maxlength="100"></label><button class="btn">Ajouter</button></form>
