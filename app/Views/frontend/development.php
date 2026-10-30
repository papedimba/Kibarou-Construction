<h1><?= e($d['name']) ?></h1>
<?php if ($location): ?><p><?= e($location) ?></p><?php endif; ?>
<?= text_html($d['description']) ?>
<table>
  <thead><tr><th>Parcelle</th><th>Réf.</th><th>Superficie</th><th>Prix</th><th>Statut</th></tr></thead>
  <tbody>
  <?php foreach ($plots as $p): ?>
    <tr><td><?= e($p['number']) ?></td><td><?= e($p['reference']) ?></td><td><?= (int) $p['area_m2'] ?> m²</td><td><?= e(fcfa($p['price_xof'])) ?></td>
    <td><?= e(['available' => 'Disponible', 'reserving' => 'En cours de réservation', 'reserved' => 'Réservé', 'sold' => 'Vendu', 'unavailable' => 'Indisponible'][$p['status']] ?? $p['status']) ?></td></tr>
  <?php endforeach; ?>
  <?php if (!$plots): ?><tr><td colspan="5">Aucune parcelle.</td></tr><?php endif; ?>
  </tbody>
</table>
<p><a href="/lotissements">← Tous les lotissements</a></p>
