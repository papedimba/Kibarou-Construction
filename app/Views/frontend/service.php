<small><?= e($cats[$s['category']] ?? '') ?></small>
<h1><?= e($s['title']) ?></h1>
<?php if ($s['media_id'] && isset($images[(int) $s['media_id']])): $m = $images[(int) $s['media_id']]; ?><img src="/uploads/<?= (int) $site['id'] ?>/<?= e($m['filename']) ?>" alt="<?= e($m['alt'] ?: $s['title']) ?>" class="thumb"><?php endif; ?>
<?= text_html($s['description']) ?>
<p><a href="/services">← Tous nos services</a></p>
