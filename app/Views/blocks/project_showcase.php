<?php $pr = $r->project($p['project']); if ($pr): $ph = $r->projectPhotos($pr); ?>
<section class="blk-block blk-showcase"><?php if ($ph): ?><img src="<?= e($ph[0]['url']) ?>" alt="<?= e($ph[0]['alt']) ?>" loading="lazy"><?php endif; ?>
<div><h2><?= e($pr['name']) ?></h2><?php if ($pr['location_text']): ?><p><?= e($pr['location_text']) ?></p><?php endif; ?><?= text_html($pr['description']) ?><p><a href="/realisations/<?= e($pr['slug']) ?>">Voir la réalisation →</a></p></div></section>
<?php elseif ($r->editor): ?><div class="blk-notice">Projet : identifiant d'une réalisation publiée attendu.</div><?php endif; ?>
