<h1>Sauvegardes</h1>
<p>Une sauvegarde contient la base de données de <strong>tous</strong> les sites, les images téléversées et les documents fonciers privés. Conservez-la hors du serveur. La restauration se fait en ligne de commande (voir DEPLOYMENT_CPANEL.md).</p>
<?php if ($zip): ?>
<form method="post" action="/platform/backups" class="inline"><?= csrf_field($csrf) ?><button class="btn">Créer une sauvegarde maintenant</button></form>
<?php else: ?><p class="error">L'extension PHP « zip » n'est pas activée : les sauvegardes sont indisponibles.</p><?php endif; ?>
<table>
  <thead><tr><th>Fichier</th><th>Date</th><th class="num">Taille</th><th></th></tr></thead>
  <tbody>
  <?php foreach ($items as $b): ?>
    <tr><td><?= e($b['name']) ?></td><td><?= e($b['date']) ?></td><td class="num"><?= e(number_format($b['size'] / 1048576, 2, ',', ' ')) ?> Mo</td><td><a href="/platform/backups/<?= e($b['name']) ?>">Télécharger</a></td></tr>
  <?php endforeach; ?>
  <?php if (!$items): ?><tr><td colspan="4">Aucune sauvegarde.</td></tr><?php endif; ?>
  </tbody>
</table>
