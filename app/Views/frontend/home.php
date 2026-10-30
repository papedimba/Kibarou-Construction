<?php
/** Home: split stage. Content is the site's own data; sections with nothing to show are left out (nothing is invented). */
$name = $site['name'];
$domains = ['Construction et bâtiment', 'Travaux publics et génie civil', 'Gros œuvre et second œuvre', 'Rénovation et réhabilitation', 'Terrassement et aménagement'];
?>
<div class="split">
  <?php require __DIR__ . '/_stage.php'; ?>
  <div class="reading">
    <section data-scene="1" class="reveal-group">
      <h1 class="reveal">Construisons vos projets. Trouvons ensemble votre terrain.</h1>
      <p class="lead reveal">De l'acquisition foncière à la réalisation de vos projets immobiliers, <?= e($name) ?> vous accompagne en Côte d'Ivoire.</p>
      <div class="cta-row reveal"><a class="btn" href="/terrains">Découvrir nos terrains</a><a class="btn btn-quiet" href="/services">Nos services de construction</a></div>
    </section>

    <section data-scene="1" class="reveal-group" id="presentation">
      <h2 class="reveal">Du terrain au bâtiment</h2>
      <p class="reveal"><?= e($name) ?> intervient en Côte d'Ivoire dans la construction et le bâtiment, les travaux publics, la rénovation, le terrassement et le génie civil, et commercialise des terrains de différentes catégories.</p>
      <p class="reveal"><a href="/contact">Parler de votre projet</a></p>
    </section>

    <section data-scene="1" class="reveal-group" id="terrains">
      <h2 class="reveal">Des terrains à vendre</h2>
      <div class="live reveal" data-live>
        <form method="get" action="/terrains">
          <input name="max" inputmode="numeric" placeholder="Votre budget maximum en FCFA" autocomplete="off" aria-label="Budget maximum en FCFA">
          <select name="category" aria-label="Catégorie"><option value="">Toutes catégories</option><?php foreach ($cats as $c): ?><option value="<?= e($c['slug']) ?>"><?= e($c['name']) ?></option><?php endforeach; ?></select>
          <button class="btn">Voir les terrains</button>
        </form>
        <output aria-live="polite" data-live-out></output>
      </div>
      <?php if ($featured): ?>
      <div class="rows reveal">
        <?php foreach ($featured as $l): ?>
        <a class="row" href="/terrains/<?= e($l['slug']) ?>"><span><?= e($l['title']) ?><small><?= e($l['reference']) ?> · <?= (int) $l['area_m2'] ?> m²</small></span><span><?= e(fcfa($l['price_xof'])) ?></span></a>
        <?php endforeach; ?>
      </div>
      <p class="reveal"><a href="/terrains">Tout le catalogue →</a></p>
      <?php else: ?>
      <p class="reveal muted">Les terrains disponibles seront présentés ici dès leur publication.</p>
      <?php endif; ?>
    </section>

    <section data-scene="2" class="reveal-group" id="services">
      <h2 class="reveal">Construire avec nous</h2>
      <ul class="menu-list">
        <?php if ($services): foreach ($services as $s): ?>
          <li class="reveal"><strong><a href="/services/<?= e($s['slug']) ?>"><?= e($s['title']) ?></a></strong><?= e(mb_substr((string) $s['description'], 0, 140)) ?></li>
        <?php endforeach; else: foreach ($domains as $d): ?>
          <li class="reveal"><strong><?= e($d) ?></strong></li>
        <?php endforeach; endif; ?>
      </ul>
      <p class="reveal"><a href="/services">Nos services BTP →</a> · <a href="/contact">Demander un devis</a></p>
    </section>

    <?php if ($projects): ?>
    <section data-scene="2" class="reveal-group" id="realisations">
      <h2 class="reveal">Nos réalisations</h2>
      <div class="rows reveal">
        <?php foreach ($projects as $p): ?><a class="row" href="/realisations/<?= e($p['slug']) ?>"><span><?= e($p['name']) ?><small><?= e($p['location_text'] ?? '') ?></small></span><span><?= $p['completed_on'] ? e(substr($p['completed_on'], 0, 4)) : '' ?></span></a><?php endforeach; ?>
      </div>
      <p class="reveal"><a href="/realisations">Toutes nos réalisations →</a></p>
    </section>
    <?php endif; ?>

    <section data-scene="3" class="reveal-group" id="pourquoi">
      <h2 class="reveal">Pourquoi nous choisir</h2>
      <div class="three">
        <p class="reveal"><strong>Un seul interlocuteur</strong>Vous achetez votre terrain et vous faites construire avec la même entreprise.</p>
        <p class="reveal"><strong>Des informations claires</strong>Chaque fiche indique la superficie, le prix en FCFA, la localisation et la disponibilité. Un document n'est présenté comme vérifié qu'après contrôle.</p>
        <p class="reveal"><strong>Une demande suivie</strong>Chaque demande reçoit une référence et reste suivie par notre équipe jusqu'à votre réponse.</p>
      </div>
    </section>

    <section data-scene="3" class="reveal-group" id="contact">
      <h2 class="reveal">Parlons de votre projet</h2>
      <p class="reveal">Terrain, construction, rénovation : décrivez-nous votre besoin, nous vous recontactons. Une demande ne constitue pas une réservation.</p>
      <div class="contact-links reveal">
        <?php if (!empty($settings['phone'])): ?><a href="tel:<?= e(preg_replace('/[^0-9+]/', '', $settings['phone'])) ?>"><?= e($settings['phone']) ?></a><?php endif; ?>
        <?php if (!empty($settings['email'])): ?><a href="mailto:<?= e($settings['email']) ?>"><?= e($settings['email']) ?></a><?php endif; ?>
      </div>
      <div class="reveal"><?php $errors = []; $old = []; require __DIR__ . '/_lead_form.php'; ?></div>
    </section>
  </div>
</div>
