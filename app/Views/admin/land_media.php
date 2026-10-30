<h1>Photos et documents — <?= e($land['reference']) ?></h1>
<p><a href="/admin/terrains/<?= (int) $land['id'] ?>/edit">← Modifier le terrain</a> ·
  <a href="/admin/terrains/<?= (int) $land['id'] ?>/fiche.pdf">Fiche PDF</a> ·
  <a href="/admin/terrains/<?= (int) $land['id'] ?>/qr.svg">QR code (SVG, impression)</a> ·
  <a href="/admin/terrains/<?= (int) $land['id'] ?>/qr.png">QR code (PNG)</a></p>
<?php if (!$land['is_published']): ?><p class="flash">Ce terrain n'est pas publié : le QR code mènera à une page introuvable tant qu'il ne l'est pas.</p><?php endif; ?>

<h2>Photos</h2>
<div class="grid">
<?php foreach ($images as $i): ?>
  <div class="card">
    <img src="/uploads/<?= (int) $site['id'] ?>/<?= e($i['filename']) ?>" alt="<?= e($i['alt'] ?? '') ?>" loading="lazy" class="thumb">
    <form method="post" action="/admin/terrains/<?= (int) $land['id'] ?>/images/<?= (int) $i['id'] ?>/delete" class="inline"><?= csrf_field($csrf) ?><button class="danger">Retirer</button></form>
  </div>
<?php endforeach; ?>
<?php if (!$images): ?><p>Aucune photo.</p><?php endif; ?>
</div>
<form method="post" action="/admin/terrains/<?= (int) $land['id'] ?>/images" enctype="multipart/form-data" class="card">
  <?= csrf_field($csrf) ?>
  <label>Ajouter une photo <input type="file" name="file" accept="image/jpeg,image/png,image/webp,image/gif" required></label>
  <label>Texte alternatif <input name="alt" maxlength="190"></label>
  <button class="btn">Téléverser la photo</button>
</form>

<h2>Documents fonciers</h2>
<p>Stockés hors du répertoire public. Un document n'est présenté comme « vérifié » qu'après votre validation explicite.</p>
<table>
  <thead><tr><th>Type</th><th>Date</th><th>Fichier</th><th>Visibilité</th><th>Vérification</th><th>Actions</th></tr></thead>
  <tbody>
  <?php foreach ($documents as $d): ?>
    <tr>
      <td><?= e($docTypes[$d['type']] ?? $d['type']) ?></td><td><?= e($d['doc_date'] ?? '—') ?></td>
      <td><a href="/admin/terrains/<?= (int) $land['id'] ?>/documents/<?= (int) $d['id'] ?>"><?= e($d['original_name']) ?></a></td>
      <td><?= badge($d['visibility'] === 'public' ? 'active' : 'inactive', $d['visibility'] === 'public' ? 'Public' : 'Privé') ?></td>
      <td><?= badge($d['verification']) ?></td>
      <td>
        <form method="post" action="/admin/terrains/<?= (int) $land['id'] ?>/documents/<?= (int) $d['id'] ?>/verify" class="inline"><?= csrf_field($csrf) ?>
          <input type="hidden" name="verification" value="<?= $d['verification'] === 'verified' ? 'unverified' : 'verified' ?>">
          <button><?= $d['verification'] === 'verified' ? 'Marquer non vérifié' : 'Marquer vérifié' ?></button></form>
        <form method="post" action="/admin/terrains/<?= (int) $land['id'] ?>/documents/<?= (int) $d['id'] ?>/delete" class="inline"><?= csrf_field($csrf) ?><button class="danger">Supprimer</button></form>
      </td>
    </tr>
  <?php endforeach; ?>
  <?php if (!$documents): ?><tr><td colspan="6">Aucun document.</td></tr><?php endif; ?>
  </tbody>
</table>
<form method="post" action="/admin/terrains/<?= (int) $land['id'] ?>/documents" enctype="multipart/form-data" class="card">
  <?= csrf_field($csrf) ?>
  <label>Fichier (PDF, JPEG, PNG — 10 Mo max) <input type="file" name="file" accept="application/pdf,image/jpeg,image/png" required></label>
  <label>Type <select name="type"><?php foreach ($docTypes as $k => $n): ?><option value="<?= e($k) ?>"><?= e($n) ?></option><?php endforeach; ?></select></label>
  <label>Date du document <input type="date" name="doc_date"></label>
  <label>Visibilité <select name="visibility"><option value="private">Privé (équipe uniquement)</option><option value="public">Public (téléchargeable sur la fiche)</option></select></label>
  <button class="btn">Ajouter le document</button>
</form>
