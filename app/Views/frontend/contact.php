<h1><?= $land ? 'À propos du terrain ' . e($land['reference']) : ($service ? 'Devis : ' . e($service['title']) : 'Nous contacter') ?></h1>
<?php if ($land): ?><p><a href="/terrains/<?= e($land['slug']) ?>"><?= e($land['title']) ?></a> — <?= (int) $land['area_m2'] ?> m² — <?= e(fcfa($land['price_xof'])) ?></p><?php endif; ?>
<p class="muted">Une demande ne constitue pas une réservation : elle nous permet de vous recontacter.</p>
<?php require __DIR__ . '/_lead_form.php'; ?>
