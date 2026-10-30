<?php /** Request form used by /contact and by the home page. Needs: $types, $old, $errors, $spam, $csrf; optional $land, $service. */
$land = $land ?? null; $service = $service ?? null; ?>
<?php if (!empty($errors['_form'])): ?><p class="error" role="alert"><?= e($errors['_form']) ?></p><?php endif; ?>
<form method="post" action="/contact" class="card">
  <?= csrf_field($csrf) ?><?= $spam ?>
  <?php if ($land): ?><input type="hidden" name="terrain" value="<?= e($land['slug']) ?>"><?php endif; ?>
  <?php if ($service): ?><input type="hidden" name="service" value="<?= e($service['slug']) ?>"><?php endif; ?>
  <label>Votre demande
    <select name="type" required>
      <?php foreach ($types as $k => $n): ?><option value="<?= e($k) ?>" <?= ($old['type'] ?? '') === $k ? 'selected' : '' ?>><?= e($n) ?></option><?php endforeach; ?>
    </select></label>
  <?php if (!empty($errors['type'])): ?><span class="error"><?= e($errors['type']) ?></span><?php endif; ?>
  <label>Prénom <input name="first_name" required maxlength="100" autocomplete="given-name" value="<?= e($old['first_name'] ?? '') ?>"></label>
  <?php if (!empty($errors['first_name'])): ?><span class="error"><?= e($errors['first_name']) ?></span><?php endif; ?>
  <label>Nom <input name="last_name" required maxlength="100" autocomplete="family-name" value="<?= e($old['last_name'] ?? '') ?>"></label>
  <?php if (!empty($errors['last_name'])): ?><span class="error"><?= e($errors['last_name']) ?></span><?php endif; ?>
  <label>Téléphone <input name="phone" type="tel" required autocomplete="tel" placeholder="+225 …" value="<?= e($old['phone'] ?? '') ?>"></label>
  <?php if (!empty($errors['phone'])): ?><span class="error"><?= e($errors['phone']) ?></span><?php endif; ?>
  <label>Email (facultatif) <input name="email" type="email" autocomplete="email" value="<?= e($old['email'] ?? '') ?>"></label>
  <?php if (!empty($errors['email'])): ?><span class="error"><?= e($errors['email']) ?></span><?php endif; ?>
  <?php if ($land): ?><label>Date de visite souhaitée (facultatif) <input name="preferred_date" type="date" value="<?= e($old['preferred_date'] ?? '') ?>"></label><?php endif; ?>
  <label>Message <textarea name="message" rows="5" maxlength="2000"><?= e($old['message'] ?? '') ?></textarea></label>
  <label class="check"><input type="checkbox" name="consent" value="1" required <?= !empty($old['consent']) ? 'checked' : '' ?>> J'accepte que mes informations soient utilisées pour traiter ma demande.</label>
  <?php if (!empty($errors['consent'])): ?><span class="error"><?= e($errors['consent']) ?></span><?php endif; ?>
  <button class="btn">Envoyer ma demande</button>
</form>
