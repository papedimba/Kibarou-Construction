<div class="ed" data-page-id="<?= (int) $row['id'] ?>">
  <header class="ed-bar">
    <strong class="ed-title"><?= e($row['title']) ?></strong>
    <span class="ed-status" id="status" role="status"><?= $hasDraft ? 'Brouillon non publié' : ($row['status'] === 'published' ? 'Publié' : 'Brouillon') ?></span>
    <div class="ed-devices" role="group" aria-label="Aperçu">
      <button type="button" data-device="desktop" aria-pressed="true">Ordinateur</button>
      <button type="button" data-device="tablet" aria-pressed="false">Tablette</button>
      <button type="button" data-device="mobile" aria-pressed="false">Mobile</button>
    </div>
    <a class="ed-link" id="btn-preview" href="/admin/pages/<?= (int) $row['id'] ?>/apercu" target="_blank" rel="noopener">Aperçu du brouillon</a>
    <button type="button" id="btn-save">Enregistrer</button>
    <button type="button" id="btn-publish" class="primary">Publier</button>
    <a class="ed-link" href="/admin/pages">Quitter</a>
  </header>

  <aside class="ed-left" aria-label="Bibliothèque de blocs">
    <h2>Blocs</h2>
    <div id="palette"></div>
    <?php if ($revisions): ?>
    <h2>Versions publiées</h2>
    <ul class="ed-revs">
      <?php foreach ($revisions as $v): ?>
      <li><?= e($v['created_at']) ?> — <?= e($v['author'] ?? '—') ?>
        <form method="post" action="/admin/pages/<?= (int) $row['id'] ?>/revisions/<?= (int) $v['id'] ?>/restore" class="inline"><?= csrf_field($csrf) ?><input type="hidden" name="return" value="editor"><button>Restaurer dans le brouillon</button></form></li>
      <?php endforeach; ?>
    </ul>
    <?php endif; ?>
  </aside>

  <section class="ed-center" aria-label="Zone de construction">
    <div class="ed-toolbar" id="toolbar" aria-label="Bloc sélectionné">
      <span id="sel-label">Aucun bloc sélectionné</span>
      <button type="button" id="b-up" aria-label="Monter" disabled>↑</button>
      <button type="button" id="b-down" aria-label="Descendre" disabled>↓</button>
      <button type="button" id="b-dup" disabled>Dupliquer</button>
      <button type="button" id="b-del" class="danger" disabled>Supprimer</button>
    </div>
    <div class="ed-stage" id="stage" data-device="desktop"><div class="ed-frame"><div class="blocks" id="canvas"></div></div></div>
  </section>

  <aside class="ed-right" aria-label="Paramètres du bloc">
    <h2>Paramètres</h2>
    <div id="settings"><p class="muted">Sélectionnez un bloc dans la zone de construction.</p></div>
  </aside>
</div>
<input type="hidden" id="csrf" value="<?= e($csrf) ?>">
<script type="application/json" id="editor-data"><?= $editorData ?></script>
<script src="/assets/js/editor.js" defer></script>
