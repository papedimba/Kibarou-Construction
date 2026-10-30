<h1><?= e($title) ?></h1>
<p><a class="btn" href="/admin/<?= e($kind) ?>/new">Ajouter</a></p>
<table>
  <thead><tr><th>Titre</th><th>Adresse</th><th>Statut</th><th>Actions</th></tr></thead>
  <tbody>
  <?php foreach ($r['items'] as $i): ?>
    <tr><td><?= e($i['title']) ?></td><td>/<?= $kind === 'articles' ? 'actualites/' : '' ?><?= e($i['slug']) ?></td><td><?= $i['status'] === 'published' ? 'Publié' : 'Brouillon' ?></td>
    <td><a href="/admin/<?= e($kind) ?>/<?= (int) $i['id'] ?>/edit">Modifier</a><?php if ($kind === 'pages'): ?> · <a href="/admin/pages/<?= (int) $i['id'] ?>/editeur">Éditeur visuel</a><?php endif; ?>
      <form method="post" action="/admin/<?= e($kind) ?>/<?= (int) $i['id'] ?>/delete" class="inline"><?= csrf_field($csrf) ?><button class="danger">Supprimer</button></form></td></tr>
  <?php endforeach; ?>
  <?php if (!$r['items']): ?><tr><td colspan="4">Aucun contenu.</td></tr><?php endif; ?>
  </tbody>
</table>
