<h1>Textes et photos du site</h1>
<p class="muted">Les textes <strong>provisoires</strong> ont été rédigés en attendant les vôtres : remplacez-les. Videz un champ pour revenir au texte d'origine. Une section dont le texte est vide n'est pas affichée sur le site (rien n'est inventé pour la remplir).</p>
<?php if ($error): ?><p class="error" role="alert"><?= e($error) ?></p><?php endif; ?>
<form method="post" action="/admin/textes">
  <?= csrf_field($csrf) ?>
  <?php foreach ($groups as $group => $items): ?>
  <section class="panel">
    <h2><?= e($group) ?></h2>
    <?php foreach ($items as $t): ?>
      <label><span><?= e($t['label']) ?> <?= match ($t['origin']) {
          'provisoire' => '<span class="badge b-warn">Provisoire, à remplacer</span>',
          'cahier' => '<span class="badge b-info">Cahier des charges</span>',
          'vide' => '<span class="badge b-idle">Vide, section masquée</span>',
          default => '<span class="badge b-ok">Personnalisé</span>',
      } ?></span>
        <textarea name="txt[<?= e($t['k']) ?>]" rows="<?= $t['max'] > 400 ? 6 : 2 ?>" maxlength="<?= (int) $t['max'] ?>"><?= e($t['value']) ?></textarea></label>
    <?php endforeach; ?>
  </section>
  <?php endforeach; ?>
  <section class="panel">
    <h2>Photos</h2>
    <p class="muted">Par défaut, des photos d'illustration libres sont utilisées (créditées en bas de page) : elles ne montrent pas vos réalisations. Choisissez vos propres photos dans la médiathèque dès que possible.</p>
    <?php foreach ($photos as $ph): ?>
      <label><?= e($ph['label']) ?> <small class="muted">(<?= e($ph['where']) ?>)</small>
        <select name="img[<?= e($ph['k']) ?>]"><option value="">— photo d'illustration par défaut</option>
          <?php foreach ($library as $m): ?><option value="<?= (int) $m['id'] ?>" <?= $ph['current'] === (int) $m['id'] ? 'selected' : '' ?>><?= e($m['alt'] ?: $m['original_name']) ?></option><?php endforeach; ?>
        </select></label>
      <?php if ($ph['preview']): ?><img src="<?= e($ph['preview']['url']) ?>" alt="" class="thumb thumb-sm" loading="lazy"><?php endif; ?>
    <?php endforeach; ?>
  </section>
  <button class="btn">Enregistrer</button>
</form>
