<h1><?= $area === 'platform' ? 'Administration centrale' : 'Administration du site' ?></h1>
<?php if ($error): ?><p class="error" role="alert"><?= e($error) ?></p><?php endif; ?>
<form method="post" action="/<?= e($area) ?>/login" class="card">
  <?= csrf_field($csrf) ?>
  <label>Email <input type="email" name="email" required autocomplete="username"></label>
  <label>Mot de passe <input type="password" name="password" required autocomplete="current-password"></label>
  <button class="btn">Se connecter</button>
</form>
