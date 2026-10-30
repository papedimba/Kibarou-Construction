<h1>Sites</h1>
<p><a class="btn" href="/platform/sites/new">Créer un site</a></p>
<table>
  <thead><tr><th>#</th><th>Nom</th><th>Slug</th><th>Domaines</th><th>Statut</th><th>Actions</th></tr></thead>
  <tbody>
  <?php foreach ($sites as $s): ?>
    <tr>
      <td><?= (int) $s['id'] ?></td><td><?= e($s['name']) ?></td><td><?= e($s['slug']) ?></td><td><?= e($s['hosts']) ?></td><td><?= e($s['status']) ?></td>
      <td>
        <form method="post" action="/platform/sites/<?= (int) $s['id'] ?>/status" class="inline"><?= csrf_field($csrf) ?>
          <input type="hidden" name="status" value="<?= $s['status'] === 'suspended' ? 'active' : 'suspended' ?>">
          <button><?= $s['status'] === 'suspended' ? 'Réactiver' : 'Suspendre' ?></button>
        </form>
        <form method="post" action="/platform/sites/<?= (int) $s['id'] ?>/domains" class="inline"><?= csrf_field($csrf) ?>
          <input name="host" placeholder="domaine.com" required aria-label="Nouveau domaine"><button>Ajouter domaine</button>
        </form>
      </td>
    </tr>
  <?php endforeach; ?>
  </tbody>
</table>
