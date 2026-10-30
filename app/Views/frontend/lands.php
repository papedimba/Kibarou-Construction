<h1>Nos terrains</h1>
<form method="get" class="filters">
  <input name="q" placeholder="Référence ou désignation" value="<?= e($f['q'] ?? '') ?>" aria-label="Recherche">
  <select name="category" aria-label="Catégorie"><option value="">Toutes catégories</option>
    <?php foreach ($cats as $c): ?><option value="<?= e($c['slug']) ?>" <?= ($f['category'] ?? '') === $c['slug'] ? 'selected' : '' ?>><?= e($c['name']) ?></option><?php endforeach; ?>
  </select>
  <input name="min" inputmode="numeric" placeholder="Budget min (FCFA)" value="<?= e($f['min'] ?? '') ?>" aria-label="Budget minimum">
  <input name="max" inputmode="numeric" placeholder="Budget max (FCFA)" value="<?= e($f['max'] ?? '') ?>" aria-label="Budget maximum">
  <input name="area_min" inputmode="numeric" placeholder="Surface min (m²)" value="<?= e($f['area_min'] ?? '') ?>" aria-label="Superficie minimale">
  <select name="status" aria-label="Statut"><option value="">Disponibles</option>
    <?php foreach ($statuses as $s): ?><option value="<?= e($s) ?>" <?= ($f['status'] ?? '') === $s ? 'selected' : '' ?>><?= e($s) ?></option><?php endforeach; ?>
  </select>
  <select name="sort" aria-label="Tri">
    <?php foreach (['recent' => 'Plus récents', 'price_asc' => 'Prix croissant', 'price_desc' => 'Prix décroissant', 'area_desc' => 'Superficie'] as $k => $label): ?>
      <option value="<?= $k ?>" <?= ($f['sort'] ?? 'recent') === $k ? 'selected' : '' ?>><?= $label ?></option>
    <?php endforeach; ?>
  </select>
  <button>Rechercher</button>
</form>
<p><?= (int) $r['total'] ?> terrain(s)</p>
<div class="grid">
<?php foreach ($r['items'] as $l): ?>
  <a class="card" href="/terrains/<?= e($l['slug']) ?>">
    <small><?= e($l['reference']) ?> · <?= e($l['category_name'] ?? '') ?></small>
    <strong><?= e($l['title']) ?></strong>
    <span><?= (int) $l['area_m2'] ?> m² — <?= e(fcfa($l['price_xof'])) ?></span>
    <em><?= e($l['status']) ?></em>
  </a>
<?php endforeach; ?>
</div>
<nav class="pager">
<?php for ($i = 1; $i <= $r['pages']; $i++): ?>
  <a href="?<?= e(http_build_query(array_merge($f, ['page' => $i]))) ?>" <?= $i === $r['page'] ? 'aria-current="page"' : '' ?>><?= $i ?></a>
<?php endfor; ?>
</nav>
