<div class="catalog">
  <aside class="filter-panel">
    <form method="get" class="filter-form">
      <h2>Affiner la recherche</h2>
      <label>Mot-clé ou référence<input name="q" value="<?= e($f['q'] ?? '') ?>"></label>
      <label>Catégorie<select name="category"><option value="">Toutes</option><?php foreach ($cats as $c): ?><option value="<?= e($c['slug']) ?>" <?= ($f['category'] ?? '') === $c['slug'] ? 'selected' : '' ?>><?= e($c['name']) ?></option><?php endforeach; ?></select></label>
      <label>Localisation<select name="location"><option value="">Toutes</option><?php foreach ($locs as $l): ?><option value="<?= (int) $l['id'] ?>" <?= (string) ($f['location'] ?? '') === (string) $l['id'] ? 'selected' : '' ?>><?= e($l['label']) ?></option><?php endforeach; ?></select></label>
      <div class="pair"><label>Budget min (FCFA)<input name="min" inputmode="numeric" value="<?= e($f['min'] ?? '') ?>"></label><label>Budget max (FCFA)<input name="max" inputmode="numeric" value="<?= e($f['max'] ?? '') ?>"></label></div>
      <div class="pair"><label>Surface min (m²)<input name="area_min" inputmode="numeric" value="<?= e($f['area_min'] ?? '') ?>"></label><label>Surface max (m²)<input name="area_max" inputmode="numeric" value="<?= e($f['area_max'] ?? '') ?>"></label></div>
      <label>Disponibilité<select name="status"><option value="">Terrains disponibles</option><?php foreach ($statuses as $s): ?><option value="<?= e($s) ?>" <?= ($f['status'] ?? '') === $s ? 'selected' : '' ?>><?= e(status_label($s)) ?></option><?php endforeach; ?></select></label>
      <label>Trier par<select name="sort"><?php foreach (['recent' => 'Plus récents', 'price_asc' => 'Prix croissant', 'price_desc' => 'Prix décroissant', 'area_desc' => 'Superficie'] as $k => $label): ?><option value="<?= $k ?>" <?= ($f['sort'] ?? 'recent') === $k ? 'selected' : '' ?>><?= $label ?></option><?php endforeach; ?></select></label>
      <button class="btn">Rechercher</button>
      <a class="reset" href="/terrains">Réinitialiser</a>
    </form>
  </aside>
  <div class="results">
    <p class="results-count"><strong><?= (int) $r['total'] ?></strong> terrain(s)</p>
    <?php if (!$r['items']): ?><div class="empty"><p>Aucun terrain ne correspond à ces critères.</p><p><a href="/terrains">Voir tous les terrains</a> ou <a href="/contact?type=info">décrivez-nous votre recherche</a>.</p></div><?php endif; ?>
    <div class="cards">
      <?php foreach ($r['items'] as $l): require __DIR__ . '/_land_card.php'; endforeach; ?>
    </div>
    <?php if ($r['pages'] > 1): ?>
    <nav class="pager" aria-label="Pages">
      <?php for ($i = 1; $i <= $r['pages']; $i++): ?><a href="?<?= e(http_build_query(array_merge($f, ['page' => $i]))) ?>" <?= $i === $r['page'] ? 'aria-current="page"' : '' ?>><?= $i ?></a><?php endfor; ?>
    </nav>
    <?php endif; ?>
  </div>
</div>
