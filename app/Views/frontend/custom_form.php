<?php if (!empty($errors['_form'])): ?><p class="error" role="alert"><?= e($errors['_form']) ?></p><?php endif; ?>
<form method="post" action="/f/<?= e($f['slug']) ?>" class="card">
  <?= csrf_field($csrf) ?><?= $spam ?>
  <?php foreach ($fields as $i => $fl): $name = "f$i"; $val = (string) ($old[$name] ?? ''); $req = $fl['required'] ? 'required' : ''; ?>
    <?php if ($fl['type'] === 'checkbox'): ?>
      <label class="check"><input type="checkbox" name="<?= $name ?>" value="1" <?= $val === '1' ? 'checked' : '' ?> <?= $req ?>> <?= e($fl['label']) ?></label>
    <?php else: ?>
      <label><?= e($fl['label']) ?><?= $fl['required'] ? '' : ' (facultatif)' ?>
        <?php if ($fl['type'] === 'textarea'): ?><textarea name="<?= $name ?>" rows="5" <?= $req ?>><?= e($val) ?></textarea>
        <?php elseif ($fl['type'] === 'select'): ?><select name="<?= $name ?>" <?= $req ?>><option value="">—</option><?php foreach ($fl['options'] as $o): ?><option <?= $val === $o ? 'selected' : '' ?>><?= e($o) ?></option><?php endforeach; ?></select>
        <?php else: ?><input name="<?= $name ?>" type="<?= $fl['type'] === 'email' ? 'email' : ($fl['type'] === 'phone' ? 'tel' : 'text') ?>" value="<?= e($val) ?>" <?= $req ?>><?php endif; ?>
      </label>
    <?php endif; ?>
    <?php if (!empty($errors[$i])): ?><span class="error"><?= e($errors[$i]) ?></span><?php endif; ?>
  <?php endforeach; ?>
  <button class="btn">Envoyer</button>
</form>
