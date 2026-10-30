<?php if ($p['label'] !== '' && $p['url'] !== ''): ?><p class="blk-btnrow blk-align-<?= e($p['align']) ?>"><a class="btn blk-btn-<?= e($p['style']) ?>" href="<?= e($p['url']) ?>"><?= e($p['label']) ?></a></p>
<?php elseif ($r->editor): ?><div class="blk-notice">Bouton : renseignez un libellé et un lien.</div><?php endif; ?>
