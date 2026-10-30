<h1>Utilisateurs</h1>
<p class="muted">Les rôles déterminent ce que chaque personne peut faire sur ce site. Retirer un accès ne supprime pas le compte.</p>
<table>
  <thead><tr><th>Nom</th><th>Email</th><th>Rôle</th><th>Actions</th></tr></thead>
  <tbody>
  <?php foreach ($members as $m): ?>
    <tr>
      <td><?= e($m['name']) ?><?= (int) $m['user_id'] === $me ? ' (vous)' : '' ?></td><td><?= e($m['email']) ?></td>
      <td><form method="post" action="/admin/utilisateurs/<?= (int) $m['id'] ?>/role" class="inline"><?= csrf_field($csrf) ?>
        <select name="role" aria-label="Rôle de <?= e($m['name']) ?>"><?php foreach ($roles as $r): ?><option value="<?= e($r['slug']) ?>" <?= $m['role_slug'] === $r['slug'] ? 'selected' : '' ?>><?= e($r['name']) ?></option><?php endforeach; ?></select><button>OK</button></form></td>
      <td>
        <?php if ((int) $m['sites'] <= 1): ?>
        <form method="post" action="/admin/utilisateurs/<?= (int) $m['id'] ?>/mot-de-passe" class="inline"><?= csrf_field($csrf) ?>
          <input type="password" name="password" placeholder="Nouveau mot de passe" minlength="12" autocomplete="new-password" aria-label="Nouveau mot de passe de <?= e($m['name']) ?>"><button>Réinitialiser</button></form>
        <?php else: ?><small class="muted">Compte partagé avec d'autres sites</small><?php endif; ?>
        <form method="post" action="/admin/utilisateurs/<?= (int) $m['id'] ?>/retirer" class="inline"><?= csrf_field($csrf) ?><button class="danger">Retirer l'accès</button></form>
      </td>
    </tr>
  <?php endforeach; ?>
  </tbody>
</table>
<h2>Ajouter un utilisateur</h2>
<form method="post" action="/admin/utilisateurs" class="card">
  <?= csrf_field($csrf) ?>
  <label>Email <input type="email" name="email" required autocomplete="off"></label>
  <label>Nom (nouveau compte) <input name="name" autocomplete="off"></label>
  <label>Mot de passe initial, 12 caractères minimum (nouveau compte) <input type="password" name="password" minlength="12" autocomplete="new-password"></label>
  <label>Rôle <select name="role"><?php foreach ($roles as $r): ?><option value="<?= e($r['slug']) ?>"><?= e($r['name']) ?></option><?php endforeach; ?></select></label>
  <p class="muted">Si l'email correspond à un compte existant, il est simplement rattaché à ce site : son mot de passe n'est pas modifié.</p>
  <button class="btn">Ajouter</button>
</form>
