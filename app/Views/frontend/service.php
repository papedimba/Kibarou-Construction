<small><?= e($cats[$s['category']] ?? '') ?></small>
<?php if ($s['media_id'] && isset($images[(int) $s['media_id']])): $m = $images[(int) $s['media_id']]; ?><img src="/uploads/<?= (int) $site['id'] ?>/<?= e($m['filename']) ?>" alt="<?= e($m['alt'] ?: $s['title']) ?>" class="thumb"><?php endif; ?>
<?= text_html($s['description']) ?>
<p><a class="btn" href="/contact?service=<?= e($s['slug']) ?>&amp;type=quote">Demander un devis</a></p>
<p><a href="/services">← Tous nos services</a></p>
