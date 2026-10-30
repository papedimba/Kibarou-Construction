<a class="card" href="/terrains/<?= e($l['slug']) ?>">
  <?php if (!empty($cover)): ?><img src="/uploads/<?= (int) $r->siteId() ?>/<?= e($cover) ?>" alt="<?= e($l['title']) ?>" loading="lazy" class="thumb"><?php endif; ?>
  <small><?= e($l['reference']) ?> · <?= e($l['category_name'] ?? '') ?></small><strong><?= e($l['title']) ?></strong><span><?= (int) $l['area_m2'] ?> m² — <?= e(fcfa($l['price_xof'])) ?></span>
</a>
