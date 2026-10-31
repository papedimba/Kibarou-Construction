<small><?= e($cats[$r['category']] ?? '') ?> · <?= e($statuses[$r['status']] ?? '') ?><?= $r['completed_on'] ? ' · ' . e(substr($r['completed_on'], 0, 4)) : '' ?></small>
<?php if ($r['location_text']): ?><p><?= e($r['location_text']) ?></p><?php endif; ?>
<?= text_html($r['description']) ?>
<div class="grid"><?php foreach ($images as $i): ?><img src="/uploads/<?= (int) $site['id'] ?>/<?= e($i['filename']) ?>" alt="<?= e($i['alt'] ?: $r['name']) ?>" loading="lazy" class="thumb"><?php endforeach; ?></div>
<p><a href="/realisations">← Toutes nos réalisations</a></p>
