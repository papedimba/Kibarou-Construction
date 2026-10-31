<?php /** About page from editable texts. Sections without text (history, team) are not shown. */ $t = $texts; ?>
<section class="about-intro">
  <div class="prose"><h2>Qui sommes-nous</h2><?= text_html($t->get('about_intro')) ?></div>
  <?= picture($t->photo('about'), 3, '', 'about-img') ?>
</section>
<?php if ($t->get('about_history') !== ''): ?><section class="section prose"><h2>Notre histoire</h2><?= text_html($t->get('about_history')) ?></section><?php endif; ?>
<section class="section mv">
  <div><h2>Notre mission</h2><?= text_html($t->get('about_mission')) ?></div>
  <div><h2>Notre vision</h2><?= text_html($t->get('about_vision')) ?></div>
</section>
<?php $values = $t->pairs('about_values'); if ($values): ?>
<section class="section"><h2>Nos valeurs</h2>
  <ul class="values"><?php foreach ($values as [$v, $tx]): ?><li><strong><?= e($v) ?></strong><span><?= e($tx) ?></span></li><?php endforeach; ?></ul>
</section>
<?php endif; ?>
<?php $commitments = $t->pairs('about_commitments'); if ($commitments): ?>
<section class="section band-light commitments"><h2>Nos engagements</h2>
  <ul><?php foreach ($commitments as [$c]): ?><li><?= \App\Blocks\Icons::svg('circle-check', 22) ?><span><?= e($c) ?></span></li><?php endforeach; ?></ul>
</section>
<?php endif; ?>
<?php if ($t->get('about_team') !== ''): ?><section class="section prose"><h2>Notre équipe</h2><?= text_html($t->get('about_team')) ?></section><?php endif; ?>
<section class="section identity"><h2>Notre identité</h2>
  <div class="identity-row">
    <?php if (!empty($settings['logo_url'])): ?><img src="<?= e($settings['logo_url']) ?>" alt="Logo <?= e($site['name']) ?>" class="identity-logo" width="300" height="200" loading="lazy"><?php endif; ?>
    <div>
      <?php if (!empty($settings['tagline'])): ?><p class="identity-tagline">« <?= e($settings['tagline']) ?> »</p><?php endif; ?>
      <ul class="swatches" aria-label="Couleurs de la marque"><li class="sw-blue">Bleu institutionnel</li><li class="sw-red">Rouge</li><li class="sw-grey">Gris</li></ul>
    </div>
  </div>
</section>
<section class="section"><a class="btn" href="/contact">Nous contacter</a> <a class="link-arrow" href="/terrains">Voir nos terrains</a></section>
