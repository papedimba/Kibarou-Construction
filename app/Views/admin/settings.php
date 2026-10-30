<h1>Réglages</h1>
<?php foreach ($errors as $m): ?><p class="error" role="alert"><?= e($m) ?></p><?php endforeach; ?>
<form method="post" action="/admin/settings" class="card">
  <?= csrf_field($csrf) ?>
  <label>Slogan <input name="tagline" value="<?= e($values['tagline'] ?? '') ?>"></label>
  <label>Téléphone <input name="phone" value="<?= e($values['phone'] ?? '') ?>"></label>
  <label>Email public <input type="email" name="email" value="<?= e($values['email'] ?? '') ?>"></label>
  <label>WhatsApp (ex. +225…, vide = bouton masqué) <input name="whatsapp" value="<?= e($values['whatsapp'] ?? '') ?>"></label>
  <label>Adresse <input name="address" value="<?= e($values['address'] ?? '') ?>"></label>
  <label>Logo (chemin, ex. /uploads/1/…png) <input name="logo_url" value="<?= e($values['logo_url'] ?? '') ?>"></label>
  <label>Description SEO par défaut <textarea name="meta_description" rows="3"><?= e($values['meta_description'] ?? '') ?></textarea></label>
  <label>Thème <select name="theme"><?php foreach (\App\Repositories\SettingsRepository::THEMES as $k => $n): ?><option value="<?= e($k) ?>" <?= ($values['theme'] ?? 'corporate-premium') === $k ? 'selected' : '' ?>><?= e($n) ?></option><?php endforeach; ?></select></label>
  <button class="btn">Enregistrer</button>
</form>
