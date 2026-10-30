<h1>Journal d'activité</h1>
<p class="muted">Les 200 dernières actions de modification faites par les utilisateurs de ce site (qui, quoi, depuis quelle adresse IP). Les contenus saisis ne sont pas enregistrés ici.</p>
<table>
  <thead><tr><th>Date</th><th>Utilisateur</th><th>Action</th><th>Adresse IP</th></tr></thead>
  <tbody>
  <?php foreach ($rows as $r): ?>
    <tr><td class="num"><?= e($r['created_at']) ?></td><td><?= e($r['name'] ?? '—') ?></td><td><code><?= e($r['action']) ?></code></td><td><?= e($r['ip']) ?></td></tr>
  <?php endforeach; ?>
  <?php if (!$rows): ?><tr><td colspan="4">Aucune activité enregistrée.</td></tr><?php endif; ?>
  </tbody>
</table>
