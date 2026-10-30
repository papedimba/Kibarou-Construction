<h1>Prospects</h1>
<form method="get" class="filters">
  <input name="q" placeholder="Nom, téléphone, email, référence" value="<?= e($f['q'] ?? '') ?>" aria-label="Recherche">
  <select name="status" aria-label="Statut"><option value="">Tous statuts</option><?php foreach ($statuses as $k => $n): ?><option value="<?= e($k) ?>" <?= ($f['status'] ?? '') === $k ? 'selected' : '' ?>><?= e($n) ?></option><?php endforeach; ?></select>
  <select name="type" aria-label="Type"><option value="">Tous types</option><?php foreach ($types as $k => $n): ?><option value="<?= e($k) ?>" <?= ($f['type'] ?? '') === $k ? 'selected' : '' ?>><?= e($n) ?></option><?php endforeach; ?></select>
  <select name="assigned" aria-label="Responsable"><option value="">Tous responsables</option><option value="none" <?= ($f['assigned'] ?? '') === 'none' ? 'selected' : '' ?>>Non affectés</option>
    <?php foreach ($users as $u): ?><option value="<?= (int) $u['id'] ?>" <?= (string) ($f['assigned'] ?? '') === (string) $u['id'] ? 'selected' : '' ?>><?= e($u['name']) ?></option><?php endforeach; ?></select>
  <button>Filtrer</button>
  <a class="btn" href="/admin/prospects/export.csv?<?= e(http_build_query(array_filter($f, fn($v, $k) => $k !== 'page' && $v !== '', ARRAY_FILTER_USE_BOTH))) ?>">Exporter CSV</a>
</form>
<table>
  <thead><tr><th>Réf.</th><th>Date</th><th>Contact</th><th>Type</th><th>Objet</th><th>Statut</th><th>Responsable</th></tr></thead>
  <tbody>
  <?php foreach ($r['items'] as $l): ?>
    <tr>
      <td><a href="/admin/prospects/<?= (int) $l['id'] ?>"><?= e($l['reference']) ?></a></td><td><?= e(substr($l['created_at'], 0, 10)) ?></td>
      <td><?= e($l['first_name'] . ' ' . $l['last_name']) ?><br><small><?= e($l['phone']) ?></small></td>
      <td><?= e($types[$l['type']] ?? $l['type']) ?></td><td><?= e($l['land_ref'] ? $l['land_ref'] . ' — ' . $l['land_title'] : ($l['service_title'] ?? '—')) ?></td>
      <td><?= badge($l['status'], $statuses[$l['status']] ?? null) ?></td><td><?= e($l['assignee'] ?? '—') ?></td>
    </tr>
  <?php endforeach; ?>
  <?php if (!$r['items']): ?><tr><td colspan="7">Aucun prospect.</td></tr><?php endif; ?>
  </tbody>
</table>
<p><?= (int) $r['total'] ?> résultat(s)</p>
<nav class="pager"><?php for ($i = 1; $i <= $r['pages']; $i++): ?><a href="?<?= e(http_build_query(array_merge($f, ['page' => $i]))) ?>" <?= $i === $r['page'] ? 'aria-current="page"' : '' ?>><?= $i ?></a><?php endfor; ?></nav>
