<?php /** Land card (catalogue, home, similar lands). Needs $l, $covers, $site. */ $cover = $covers[(int) $l['id']] ?? null; ?>
<a class="card lcard" href="/terrains/<?= e($l['slug']) ?>">
  <span class="lcard-media"><?= picture($cover ? ['url' => '/uploads/' . (int) $site['id'] . '/' . $cover] : null, 1, $l['title'], 'thumb') ?><?= badge($l['status']) ?></span>
  <span class="lcard-body">
    <small><?= e($l['reference']) ?><?= !empty($l['category_name']) ? ' · ' . e($l['category_name']) : '' ?></small>
    <strong><?= e($l['title']) ?></strong>
    <span class="lcard-meta"><?= \App\Blocks\Icons::svg('ruler', 16) ?><?= number_format((int) $l['area_m2'], 0, ',', ' ') ?> m²<?php if (!empty($l['location_name'])): ?> <?= \App\Blocks\Icons::svg('map-pin', 16) ?><?= e($l['location_name']) ?><?php endif; ?></span>
    <span class="lcard-price"><?= e(fcfa($l['price_xof'])) ?></span>
  </span>
</a>
