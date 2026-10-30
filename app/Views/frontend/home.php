<section class="hero">
  <h1>Construisons vos projets. Trouvons ensemble votre terrain.</h1>
  <p>De l'acquisition foncière à la réalisation de vos projets immobiliers, <?= e($site['name']) ?> vous accompagne en Côte d'Ivoire.</p>
  <a class="btn" href="/terrains">Découvrir nos terrains</a>
</section>
<h2>Terrains récents</h2>
<?php if (!$featured): ?><p>Aucun terrain publié pour le moment.</p><?php endif; ?>
<div class="grid">
<?php foreach ($featured as $l): ?>
  <a class="card" href="/terrains/<?= e($l['slug']) ?>"><strong><?= e($l['title']) ?></strong><span><?= (int) $l['area_m2'] ?> m² — <?= e(fcfa($l['price_xof'])) ?></span></a>
<?php endforeach; ?>
</div>
