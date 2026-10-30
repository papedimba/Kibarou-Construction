<h1>Visites</h1>
<table>
  <thead><tr><th>Date</th><th>Prospect</th><th>Terrain</th><th>Statut</th><th>Note</th></tr></thead>
  <tbody>
  <?php foreach ($items as $a): ?>
    <tr><td><?= e($a['scheduled_at']) ?></td><td><a href="/admin/prospects/<?= (int) $a['lead_id'] ?>"><?= e($a['reference']) ?></a> <?= e($a['first_name'] . ' ' . $a['last_name']) ?><br><small><?= e($a['phone']) ?></small></td>
    <td><?= e($a['land_title'] ?? '—') ?></td>
    <td><form method="post" action="/admin/visites/<?= (int) $a['id'] ?>" class="inline"><?= csrf_field($csrf) ?>
      <select name="status" aria-label="Statut"><?php foreach ($statuses as $k => $n): ?><option value="<?= e($k) ?>" <?= $a['status'] === $k ? 'selected' : '' ?>><?= e($n) ?></option><?php endforeach; ?></select><button>OK</button></form></td>
    <td><?= e($a['note'] ?? '') ?></td></tr>
  <?php endforeach; ?>
  <?php if (!$items): ?><tr><td colspan="5">Aucune visite.</td></tr><?php endif; ?>
  </tbody>
</table>
