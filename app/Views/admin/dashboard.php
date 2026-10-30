<h1>Tableau de bord</h1>
<ul class="stats">
  <li><strong><?= (int) $total ?></strong> terrains au total</li>
  <li><strong><?= (int) ($counts['available'] ?? 0) ?></strong> disponibles</li>
  <li><strong><?= (int) (($counts['reserving'] ?? 0) + ($counts['reserved'] ?? 0)) ?></strong> réservés / en cours</li>
  <li><strong><?= (int) ($counts['sold'] ?? 0) ?></strong> vendus</li>
  <li><strong><?= (int) $crm['total'] ?></strong> demandes commerciales</li>
  <li><strong><?= (int) $crm['pending'] ?></strong> demandes en attente</li>
  <li><strong><?= (int) $crm['quotes'] ?></strong> demandes de devis</li>
  <li><strong><?= (int) $projects ?></strong> réalisations publiées</li>
</ul>
<p><a class="btn" href="/admin/terrains">Gérer les terrains</a></p>
