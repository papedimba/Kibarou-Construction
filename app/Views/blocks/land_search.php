<section class="blk-block"><?php if ($p['title'] !== ''): ?><h2><?= e($p['title']) ?></h2><?php endif; ?>
<form method="get" action="/terrains" class="filters">
  <select name="category" aria-label="Catégorie"><option value="">Toutes catégories</option><?php foreach ($r->categories() as $c): ?><option value="<?= e($c['slug']) ?>"><?= e($c['name']) ?></option><?php endforeach; ?></select>
  <input name="min" inputmode="numeric" placeholder="Budget min (FCFA)" aria-label="Budget minimum"><input name="max" inputmode="numeric" placeholder="Budget max (FCFA)" aria-label="Budget maximum">
  <button>Rechercher</button>
</form></section>
