<?php
/** Corporate home: big photo hero + land search, the two trades, live figures from the site's own data, process, CTA, contact. Nothing invented: empty data hides its section. */
$t = $texts;
$name = $site['name'];
$domains = ['Construction et bâtiment', 'Travaux publics et génie civil', 'Gros œuvre et second œuvre', 'Rénovation et réhabilitation', 'Terrassement et aménagement'];
$money = fn($v) => fcfa($v);
?>
<section class="hero">
  <?= picture($t->photo('hero'), 3, '', 'hero-img', true) ?>
  <div class="hero-scrim"></div>
  <div class="wrap hero-body">
    <h1><?= e($t->get('home_hero_title')) ?></h1>
    <p class="hero-lead"><?= e($t->get('home_hero_subtitle')) ?></p>
    <div class="hero-actions"><a class="btn btn-lg" href="/terrains">Découvrir nos terrains</a><a class="btn btn-lg btn-ghost" href="/services">Nos services de construction</a></div>
  </div>
</section>

<section class="search-strip" aria-label="Recherche rapide de terrains">
  <div class="wrap">
    <form method="get" action="/terrains" class="search-box" data-live>
      <label>Catégorie<select name="category"><option value="">Toutes</option><?php foreach ($cats as $c): ?><option value="<?= e($c['slug']) ?>"><?= e($c['name']) ?></option><?php endforeach; ?></select></label>
      <label>Budget maximum (FCFA)<input name="max" inputmode="numeric" placeholder="Ex. 25 000 000" autocomplete="off"></label>
      <label>Superficie minimale (m²)<input name="area_min" inputmode="numeric" placeholder="Ex. 500" autocomplete="off"></label>
      <button class="btn">Rechercher</button>
      <output aria-live="polite" data-live-out></output>
    </form>
  </div>
</section>

<section class="section intro wrap">
  <div class="intro-text reveal-group">
    <h2 class="reveal"><?= e($t->get('home_intro_title')) ?></h2>
    <p class="reveal lead"><?= e($t->get('home_intro_text')) ?></p>
    <p class="reveal"><a class="link-arrow" href="/a-propos">Découvrir l'entreprise</a></p>
  </div>
  <?php if ($stats): ?>
  <ul class="figures reveal" aria-label="En chiffres">
    <?php foreach ($stats as [$n, $label, $url]): ?><li><a href="<?= e($url) ?>"><strong><?= (int) $n ?></strong><span><?= e($label) ?></span></a></li><?php endforeach; ?>
  </ul>
  <?php endif; ?>
</section>

<section class="section trades" aria-label="Nos métiers">
  <div class="wrap trades-grid">
    <a class="trade reveal" href="/terrains">
      <?= picture($t->photo('land'), 1, '', 'trade-img') ?>
      <span class="trade-body"><span class="trade-kicker">Foncier</span><strong>Terrains et lotissements</strong><span><?= e($t->get('home_land_text')) ?></span><span class="link-arrow">Voir les terrains</span></span>
    </a>
    <a class="trade reveal" href="/services">
      <?= picture($t->photo('btp'), 2, '', 'trade-img') ?>
      <span class="trade-body"><span class="trade-kicker">BTP</span><strong>Construction et travaux</strong><span><?= e($t->get('home_btp_text')) ?></span><span class="link-arrow">Voir nos services</span></span>
    </a>
  </div>
</section>

<?php if ($featured): ?>
<section class="section wrap" id="terrains">
  <div class="section-head"><h2>Terrains à la une</h2><a class="link-arrow" href="/terrains">Tout le catalogue</a></div>
  <div class="cards">
    <?php foreach ($featured as $l): require __DIR__ . '/_land_card.php'; endforeach; ?>
  </div>
</section>
<?php endif; ?>

<section class="section band-light" id="services">
  <div class="wrap">
    <div class="section-head"><h2>Nos services BTP</h2><a class="link-arrow" href="/services">Tous nos services</a></div>
    <ul class="service-list">
      <?php if ($services): foreach ($services as $s): ?>
        <li class="reveal"><a href="/services/<?= e($s['slug']) ?>"><?= \App\Blocks\Icons::svg('hammer', 28) ?><strong><?= e($s['title']) ?></strong><span><?= e(mb_substr((string) $s['description'], 0, 120)) ?></span></a></li>
      <?php endforeach; else: foreach ($domains as $i => $d): ?>
        <li class="reveal"><a href="/services"><?= \App\Blocks\Icons::svg(['building-2', 'truck', 'hard-hat', 'paintbrush', 'shovel'][$i], 28) ?><strong><?= e($d) ?></strong></a></li>
      <?php endforeach; endif; ?>
    </ul>
    <p><a class="btn" href="/contact">Demander un devis</a></p>
  </div>
</section>

<?php if ($projects): ?>
<section class="section wrap" id="realisations">
  <div class="section-head"><h2>Nos réalisations</h2><a class="link-arrow" href="/realisations">Toutes nos réalisations</a></div>
  <div class="cards">
    <?php foreach ($projects as $p): ?>
    <a class="card pcard" href="/realisations/<?= e($p['slug']) ?>">
      <span class="pcard-media"><?= picture(!empty($projectCovers[(int) $p['id']]) ? ['url' => '/uploads/' . (int) $site['id'] . '/' . $projectCovers[(int) $p['id']]] : null, 3, $p['name']) ?></span>
      <span class="pcard-body"><strong><?= e($p['name']) ?></strong><small><?= e($p['location_text'] ?? '') ?></small></span>
    </a>
    <?php endforeach; ?>
  </div>
</section>
<?php endif; ?>

<?php $steps = $t->pairs('home_steps'); if ($steps): ?>
<section class="section wrap" id="demarche">
  <h2>Notre démarche</h2>
  <ol class="steps">
    <?php foreach ($steps as [$st, $tx]): ?><li class="reveal"><strong><?= e($st) ?></strong><span><?= e($tx) ?></span></li><?php endforeach; ?>
  </ol>
</section>
<?php endif; ?>

<?php if ($posts): ?>
<section class="section band-light" id="actualites">
  <div class="wrap">
    <div class="section-head"><h2>Actualités</h2><a class="link-arrow" href="/actualites">Toutes les actualités</a></div>
    <div class="cards">
      <?php foreach ($posts as $a): ?><a class="card news" href="/actualites/<?= e($a['slug']) ?>"><small><?= e(substr((string) ($a['published_at'] ?? $a['created_at']), 0, 10)) ?></small><strong><?= e($a['title']) ?></strong><span class="link-arrow">Lire</span></a><?php endforeach; ?>
    </div>
  </div>
</section>
<?php endif; ?>

<section class="cta-band">
  <?= picture($t->photo('cta'), 2, '', 'cta-img') ?>
  <div class="hero-scrim"></div>
  <div class="wrap cta-body">
    <h2><?= e($t->get('home_cta_title')) ?></h2>
    <p><?= e($t->get('home_cta_text')) ?></p>
    <div class="hero-actions"><a class="btn btn-lg" href="/contact">Nous contacter</a><?php $wa = preg_replace('/\D/', '', $settings['whatsapp'] ?? ''); if ($wa !== ''): ?><a class="btn btn-lg btn-ghost" href="https://wa.me/<?= e($wa) ?>" rel="noopener">WhatsApp</a><?php endif; ?></div>
  </div>
</section>

<section class="section wrap contact-split" id="contact">
  <div>
    <h2>Parlons de votre projet</h2>
    <p>Terrain, construction, rénovation : décrivez-nous votre besoin, nous vous recontactons. Une demande ne constitue pas une réservation.</p>
    <ul class="contact-list">
      <?php if (!empty($settings['address'])): ?><li><?= \App\Blocks\Icons::svg('map-pin', 20) ?><span><?= e($settings['address']) ?></span></li><?php endif; ?>
      <?php if (!empty($settings['phone'])): ?><li><?= \App\Blocks\Icons::svg('phone', 20) ?><a href="tel:<?= e(preg_replace('/[^0-9+]/', '', $settings['phone'])) ?>"><?= e($settings['phone']) ?></a></li><?php endif; ?>
      <?php if (!empty($settings['email'])): ?><li><?= \App\Blocks\Icons::svg('mail', 20) ?><a href="mailto:<?= e($settings['email']) ?>"><?= e($settings['email']) ?></a></li><?php endif; ?>
    </ul>
  </div>
  <div><?php $errors = []; $old = []; require __DIR__ . '/_lead_form.php'; ?></div>
</section>
