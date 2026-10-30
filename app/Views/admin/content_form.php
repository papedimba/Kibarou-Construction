<h1><?= $id ? 'Modifier' : 'Nouveau' ?> — <?= e($title) ?></h1>
<?php foreach ($errors as $m): ?><p class="error" role="alert"><?= e($m) ?></p><?php endforeach; ?>
<form method="post" action="/admin/<?= e($kind) ?><?= $id ? '/' . (int) $id : '' ?>" class="card">
  <?= csrf_field($csrf) ?>
  <label>Titre <input name="title" required value="<?= e($row['title'] ?? '') ?>"></label>
  <label>Adresse (vide = automatique) <input name="slug" value="<?= e($row['slug'] ?? '') ?>"></label>
  <label>Contenu <textarea name="body" rows="12"><?= e($row['body'] ?? '') ?></textarea></label>
  <label>Titre SEO <input name="meta_title" value="<?= e($row['meta_title'] ?? '') ?>"></label>
  <label>Description SEO <textarea name="meta_description" rows="3"><?= e($row['meta_description'] ?? '') ?></textarea></label>
  <label>Statut <select name="status"><option value="draft" <?= ($row['status'] ?? '') !== 'published' ? 'selected' : '' ?>>Brouillon</option><option value="published" <?= ($row['status'] ?? '') === 'published' ? 'selected' : '' ?>>Publié</option></select></label>
  <button class="btn">Enregistrer</button>
</form>
<?php if ($revisions): ?>
<h2>Versions précédentes</h2>
<ul>
<?php foreach ($revisions as $v): ?>
  <li><?= e($v['created_at']) ?> — <?= e($v['title']) ?> (<?= e($v['author'] ?? '—') ?>)
    <form method="post" action="/admin/pages/<?= (int) $id ?>/revisions/<?= (int) $v['id'] ?>/restore" class="inline"><?= csrf_field($csrf) ?><button>Restaurer</button></form></li>
<?php endforeach; ?>
</ul>
<?php endif; ?>
