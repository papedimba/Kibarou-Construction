<h1>Formulaires</h1>
<p><a class="btn" href="/admin/formulaires/new">Nouveau formulaire</a></p>
<table>
  <thead><tr><th>Nom</th><th>Adresse publique</th><th>Actif</th><th>Réponses</th><th></th></tr></thead>
  <tbody>
  <?php foreach ($items as $f): ?>
    <tr><td><?= e($f['name']) ?></td><td>/f/<?= e($f['slug']) ?></td><td><?= $f['is_active'] ? 'Oui' : 'Non' ?></td><td><a href="/admin/formulaires/<?= (int) $f['id'] ?>/reponses"><?= (int) $f['submissions'] ?></a></td>
    <td><a href="/admin/formulaires/<?= (int) $f['id'] ?>/edit">Modifier</a>
      <form method="post" action="/admin/formulaires/<?= (int) $f['id'] ?>/delete" class="inline"><?= csrf_field($csrf) ?><button class="danger">Supprimer</button></form></td></tr>
  <?php endforeach; ?>
  <?php if (!$items): ?><tr><td colspan="5">Aucun formulaire.</td></tr><?php endif; ?>
  </tbody>
</table>
