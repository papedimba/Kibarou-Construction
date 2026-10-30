<h1><?= e($lead['reference']) ?> — <?= e($lead['first_name'] . ' ' . $lead['last_name']) ?></h1>
<p><a href="/admin/prospects">← Prospects</a></p>
<div class="card">
  <p><strong><?= e($types[$lead['type']] ?? $lead['type']) ?></strong> · reçue le <?= e($lead['created_at']) ?></p>
  <p>Téléphone : <a href="tel:<?= e(preg_replace('/[^0-9+]/', '', $lead['phone'])) ?>"><?= e($lead['phone']) ?></a><?php if ($lead['email']): ?> · Email : <a href="mailto:<?= e($lead['email']) ?>"><?= e($lead['email']) ?></a><?php endif; ?></p>
  <?php if ($lead['land_ref']): ?><p>Terrain : <a href="/admin/terrains/<?= (int) $lead['land_id'] ?>/edit"><?= e($lead['land_ref'] . ' — ' . $lead['land_title']) ?></a></p><?php endif; ?>
  <?php if ($lead['service_title']): ?><p>Service : <?= e($lead['service_title']) ?></p><?php endif; ?>
  <?php if ($lead['preferred_date']): ?><p>Date de visite souhaitée : <?= e($lead['preferred_date']) ?></p><?php endif; ?>
  <?= text_html($lead['message']) ?>
</div>
<h2>Suivi</h2>
<form method="post" action="/admin/prospects/<?= (int) $lead['id'] ?>/status" class="filters"><?= csrf_field($csrf) ?>
  <select name="status" aria-label="Statut"><?php foreach ($statuses as $k => $n): ?><option value="<?= e($k) ?>" <?= $lead['status'] === $k ? 'selected' : '' ?>><?= e($n) ?></option><?php endforeach; ?></select><button>Changer le statut</button></form>
<form method="post" action="/admin/prospects/<?= (int) $lead['id'] ?>/assign" class="filters"><?= csrf_field($csrf) ?>
  <select name="assigned_to" aria-label="Responsable"><option value="">— non affecté</option><?php foreach ($users as $u): ?><option value="<?= (int) $u['id'] ?>" <?= (int) $lead['assigned_to'] === (int) $u['id'] ? 'selected' : '' ?>><?= e($u['name']) ?></option><?php endforeach; ?></select><button>Affecter</button></form>
<h2>Visite</h2>
<ul><?php foreach ($appointments as $a): ?><li><?= e($a['scheduled_at']) ?> — <?= e(\App\Repositories\AppointmentRepository::STATUSES[$a['status']] ?? $a['status']) ?><?= $a['note'] ? ' — ' . e($a['note']) : '' ?></li><?php endforeach; ?></ul>
<form method="post" action="/admin/prospects/<?= (int) $lead['id'] ?>/visites" class="filters"><?= csrf_field($csrf) ?>
  <input type="datetime-local" name="scheduled_at" required aria-label="Date et heure"><input name="note" placeholder="Note (facultatif)" maxlength="300" aria-label="Note"><button>Programmer</button></form>
<h2>Notes internes</h2>
<form method="post" action="/admin/prospects/<?= (int) $lead['id'] ?>/notes" class="card"><?= csrf_field($csrf) ?>
  <label>Nouvelle note <textarea name="body" rows="3" maxlength="2000" required></textarea></label><button class="btn">Ajouter</button></form>
<?php foreach ($notes as $n): ?><div class="card"><small><?= e($n['created_at']) ?> — <?= e($n['author'] ?? '—') ?></small><?= text_html($n['body']) ?></div><?php endforeach; ?>
<h2>Historique</h2>
<ul><?php foreach ($history as $h): ?><li><?= e($h['created_at']) ?> — <?= e($h['author'] ?? 'Système / visiteur') ?> : <?= e(['created' => 'Création', 'status' => 'Statut', 'assign' => 'Affectation', 'note' => 'Note', 'visit' => 'Visite'][$h['action']] ?? $h['action']) ?><?= $h['detail'] ? ' — ' . e($h['detail']) : '' ?></li><?php endforeach; ?></ul>
<form method="post" action="/admin/prospects/<?= (int) $lead['id'] ?>/delete"><?= csrf_field($csrf) ?><button class="danger">Supprimer ce prospect</button></form>
