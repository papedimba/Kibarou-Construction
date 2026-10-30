<h1>Mon compte</h1>
<p class="muted">Connecté en tant que <strong><?= e($user['name']) ?></strong> (<?= e($user['email']) ?>).</p>
<h2>Changer mon mot de passe</h2>
<?php if ($error): ?><p class="error" role="alert"><?= e($error) ?></p><?php endif; ?>
<form method="post" action="<?= e($currentPath) ?>" class="card">
  <?= csrf_field($csrf) ?>
  <label>Mot de passe actuel <input type="password" name="current" required autocomplete="current-password"></label>
  <label>Nouveau mot de passe (12 caractères minimum) <input type="password" name="new" required minlength="12" autocomplete="new-password"></label>
  <label>Confirmer le nouveau mot de passe <input type="password" name="confirm" required minlength="12" autocomplete="new-password"></label>
  <button class="btn">Enregistrer</button>
</form>
