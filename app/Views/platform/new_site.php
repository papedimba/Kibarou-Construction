<h1>Nouveau site</h1>
<?php if ($error): ?><p class="error" role="alert"><?= e($error) ?></p><?php endif; ?>
<form method="post" action="/platform/sites" class="card">
  <?= csrf_field($csrf) ?>
  <label>Nom du site <input name="name" required value="<?= e($old['name'] ?? '') ?>"></label>
  <label>Identifiant (slug) <input name="slug" required pattern="[a-z0-9-]+" value="<?= e($old['slug'] ?? '') ?>"></label>
  <label>Domaine principal <input name="host" required value="<?= e($old['host'] ?? '') ?>"></label>
  <label>Nom de l'administrateur <input name="admin_name" required value="<?= e($old['admin_name'] ?? '') ?>"></label>
  <label>Email de l'administrateur <input type="email" name="admin_email" required value="<?= e($old['admin_email'] ?? '') ?>"></label>
  <label>Mot de passe (12 caractères min.) <input type="password" name="password" required minlength="12" autocomplete="new-password"></label>
  <button class="btn">Créer</button>
</form>
