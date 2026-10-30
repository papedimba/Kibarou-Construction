<?php $lv = in_array($p['level'], ['2', '3', '4'], true) ? $p['level'] : '2'; ?>
<h<?= $lv ?> class="blk-heading blk-align-<?= e($p['align']) ?>"><?= e($p['text']) ?></h<?= $lv ?>>
