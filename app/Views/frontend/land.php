<small><?= e($land['reference']) ?> · <?= e($land['category_name'] ?? '') ?></small>
<h1><?= e($land['title']) ?></h1>
<p><strong><?= (int) $land['area_m2'] ?> m²</strong> — <strong><?= e(fcfa($land['price_xof'])) ?></strong> — <?= e($land['status']) ?></p>
<p><?= nl2br(e($land['description'] ?? '')) ?></p>
<p><a href="/terrains">← Retour au catalogue</a></p>
