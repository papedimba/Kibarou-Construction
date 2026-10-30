<small><?= e($land['reference']) ?> · <?= e($land['category_name'] ?? '') ?></small>
<h1><?= e($land['title']) ?></h1>
<?php if ($images): ?>
<div class="grid">
<?php foreach ($images as $i): ?><img src="/uploads/<?= (int) $site['id'] ?>/<?= e($i['filename']) ?>" alt="<?= e($i['alt'] ?: $land['title']) ?>" loading="lazy" class="thumb"><?php endforeach; ?>
</div>
<?php endif; ?>
<p><strong><?= (int) $land['area_m2'] ?> m²</strong> — <strong><?= e(fcfa($land['price_xof'])) ?></strong>
  <?php if ((int) $land['area_m2'] > 0): ?><small>(≈ <?= e(fcfa((int) round($land['price_xof'] / $land['area_m2']))) ?> / m²)</small><?php endif; ?></p>
<p>Disponibilité : <strong><?= e(['available' => 'Disponible', 'reserving' => 'En cours de réservation', 'reserved' => 'Réservé', 'sold' => 'Vendu', 'unavailable' => 'Indisponible'][$land['status']] ?? $land['status']) ?></strong></p>
<?php if ($location): ?><p>Localisation : <?= e($location) ?></p><?php endif; ?>
<?php if ($mapUrl): ?><p>Coordonnées GPS : <?= e((string) $land['latitude']) ?>, <?= e((string) $land['longitude']) ?> — <a href="<?= e($mapUrl) ?>" rel="noopener">Voir sur la carte</a></p><?php endif; ?>
<?= text_html($land['description']) ?>
<?php if (!empty($land['features'])): ?><h2>Caractéristiques</h2><?= text_html($land['features']) ?><?php endif; ?>
<?php if (!empty($land['land_info'])): ?><h2>Informations foncières</h2><?= text_html($land['land_info']) ?><?php endif; ?>
<?php if ($documents): ?>
<h2>Documents</h2>
<ul>
<?php foreach ($documents as $d): ?>
  <li><a href="/terrains/<?= e($land['slug']) ?>/documents/<?= (int) $d['id'] ?>"><?= e($docTypes[$d['type']] ?? $d['type']) ?></a>
    — <?= $d['verification'] === 'verified' ? 'document vérifié' : 'en attente de vérification' ?></li>
<?php endforeach; ?>
</ul>
<?php endif; ?>
<p><a class="btn" href="/terrains/<?= e($land['slug']) ?>/fiche.pdf">Télécharger la fiche PDF</a></p>
<?php if ($similar): ?>
<h2>Terrains similaires</h2>
<div class="grid">
<?php foreach ($similar as $l): ?>
  <a class="card" href="/terrains/<?= e($l['slug']) ?>">
    <?php if (!empty($covers[(int) $l['id']])): ?><img src="/uploads/<?= (int) $site['id'] ?>/<?= e($covers[(int) $l['id']]) ?>" alt="<?= e($l['title']) ?>" loading="lazy" class="thumb"><?php endif; ?>
    <strong><?= e($l['title']) ?></strong><span><?= (int) $l['area_m2'] ?> m² — <?= e(fcfa($l['price_xof'])) ?></span></a>
<?php endforeach; ?>
</div>
<?php endif; ?>
<p><a href="/terrains">← Retour au catalogue</a></p>
