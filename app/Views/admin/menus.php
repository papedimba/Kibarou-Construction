<h1>Menus</h1>
<?php foreach ($lists as $loc => $l): ?>
  <h2><?= e($l['label']) ?></h2>
  <ul>
  <?php foreach ($l['items'] as $i): ?>
    <li><?= e($i['label']) ?> → <?= e($i['url']) ?>
      <form method="post" action="/admin/menus/items/<?= (int) $i['id'] ?>/delete" class="inline"><?= csrf_field($csrf) ?><button class="danger">Retirer</button></form></li>
  <?php endforeach; ?>
  <?php if (!$l['items']): ?><li>Aucun lien.</li><?php endif; ?>
  </ul>
  <form method="post" action="/admin/menus/items" class="filters"><?= csrf_field($csrf) ?>
    <input type="hidden" name="location" value="<?= e($loc) ?>">
    <input name="label" placeholder="Libellé" required aria-label="Libellé"><input name="url" placeholder="/terrains" required aria-label="Adresse"><button>Ajouter</button>
  </form>
<?php endforeach; ?>
<h2>Redirections 301</h2>
<ul>
<?php foreach ($redirects as $r): ?>
  <li><?= e($r['from_path']) ?> → <?= e($r['to_url']) ?>
    <form method="post" action="/admin/menus/redirects/<?= (int) $r['id'] ?>/delete" class="inline"><?= csrf_field($csrf) ?><button class="danger">Retirer</button></form></li>
<?php endforeach; ?>
<?php if (!$redirects): ?><li>Aucune redirection.</li><?php endif; ?>
</ul>
<form method="post" action="/admin/menus/redirects" class="filters"><?= csrf_field($csrf) ?>
  <input name="from_path" placeholder="/ancienne-page" required aria-label="Ancien chemin"><input name="to_url" placeholder="/nouvelle-page" required aria-label="Nouvelle adresse"><button>Ajouter</button>
</form>
