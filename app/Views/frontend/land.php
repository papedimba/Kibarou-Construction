<?php
$statusText = status_label($land['status']);
$wa = preg_replace('/\D/', '', $settings['whatsapp'] ?? '');
?>
<div class="land-top">
  <div class="gallery">
    <?php if ($images): ?>
      <img class="gallery-main" src="/uploads/<?= (int) $site['id'] ?>/<?= e($images[0]['filename']) ?>" alt="<?= e($images[0]['alt'] ?: $land['title']) ?>" fetchpriority="high">
      <?php if (count($images) > 1): ?><div class="gallery-thumbs"><?php foreach (array_slice($images, 1) as $i): ?><img src="/uploads/<?= (int) $site['id'] ?>/<?= e($i['filename']) ?>" alt="<?= e($i['alt'] ?: $land['title']) ?>" loading="lazy" class="thumb"><?php endforeach; ?></div><?php endif; ?>
    <?php else: ?>
      <?= picture(null, 1, '', 'gallery-main') ?>
    <?php endif; ?>
  </div>
  <aside class="facts">
    <p class="facts-ref"><?= e($land['reference']) ?><?= !empty($land['category_name']) ? ' · ' . e($land['category_name']) : '' ?></p>
    <p class="facts-price"><?= e(fcfa($land['price_xof'])) ?></p>
    <?php if ((int) $land['area_m2'] > 0): ?><p class="facts-sub">≈ <?= e(fcfa((int) round($land['price_xof'] / $land['area_m2']))) ?> / m²</p><?php endif; ?>
    <dl>
      <div><dt>Superficie</dt><dd><?= number_format((int) $land['area_m2'], 0, ',', ' ') ?> m²</dd></div>
      <div><dt>Disponibilité</dt><dd><?= badge($land['status']) ?></dd></div>
      <?php if ($location): ?><div><dt>Localisation</dt><dd><?= e($location) ?></dd></div><?php endif; ?>
      <div><dt>Référence</dt><dd><?= e($land['reference']) ?></dd></div>
    </dl>
    <div class="facts-actions">
      <a class="btn" href="/contact?terrain=<?= e($land['slug']) ?>&amp;type=info">Demander des informations</a>
      <a class="btn btn-outline" href="/contact?terrain=<?= e($land['slug']) ?>&amp;type=visit">Programmer une visite</a>
      <a class="btn btn-outline" href="/contact?terrain=<?= e($land['slug']) ?>&amp;type=callback">Être contacté</a>
      <?php if ($wa !== ''): ?><a class="btn btn-wa" rel="noopener" href="https://wa.me/<?= e($wa) ?>?text=<?= e(rawurlencode("Bonjour, je souhaite des informations sur le terrain {$land['reference']} : " . ($canonical ?? ''))) ?>">Contacter sur WhatsApp</a><?php endif; ?>
    </div>
    <a class="link-arrow" href="/terrains/<?= e($land['slug']) ?>/fiche.pdf">Télécharger la fiche PDF</a>
    <p class="facts-note">Une demande ne constitue pas une réservation.</p>
  </aside>
</div>

<div class="land-body">
  <?php if (!empty($land['description'])): ?><section><h2>Description</h2><?= text_html($land['description']) ?></section><?php endif; ?>
  <?php if (!empty($land['features'])): ?><section><h2>Caractéristiques</h2><?= text_html($land['features']) ?></section><?php endif; ?>
  <?php if (!empty($land['land_info'])): ?><section><h2>Informations foncières</h2><?= text_html($land['land_info']) ?></section><?php endif; ?>
  <?php if ($mapUrl): ?><section><h2>Localisation</h2><p>Coordonnées GPS : <?= e((string) $land['latitude']) ?>, <?= e((string) $land['longitude']) ?> — <a href="<?= e($mapUrl) ?>" rel="noopener">Voir sur la carte</a></p></section><?php endif; ?>
  <?php if ($documents): ?>
  <section><h2>Documents</h2>
    <ul class="docs">
      <?php foreach ($documents as $d): ?><li><?= \App\Blocks\Icons::svg('file-text', 20) ?><a href="/terrains/<?= e($land['slug']) ?>/documents/<?= (int) $d['id'] ?>"><?= e($docTypes[$d['type']] ?? $d['type']) ?></a> — <?= $d['verification'] === 'verified' ? 'document vérifié' : 'en attente de vérification' ?></li><?php endforeach; ?>
    </ul>
  </section>
  <?php endif; ?>
</div>

<?php if ($similar): ?>
<section class="section">
  <h2>Terrains similaires</h2>
  <div class="cards">
    <?php foreach ($similar as $l): $l['category_name'] = $l['category_name'] ?? null; require __DIR__ . '/_land_card.php'; endforeach; ?>
  </div>
</section>
<?php endif; ?>
