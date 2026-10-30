<section class="blk-hero blk-align-<?= e($p['align']) ?>">
  <?php if ($m = $r->media($p['image'])): ?><img class="blk-hero__bg" src="<?= e($m['url']) ?>" alt=""><?php endif; ?>
  <div class="blk-hero__body">
    <?php if ($p['title'] !== ''): ?><h1><?= e($p['title']) ?></h1><?php endif; ?>
    <?php if ($p['subtitle'] !== ''): ?><p><?= e($p['subtitle']) ?></p><?php endif; ?>
    <p class="blk-btnrow">
      <?php if ($p['primary_label'] !== '' && $p['primary_url'] !== ''): ?><a class="btn" href="<?= e($p['primary_url']) ?>"><?= e($p['primary_label']) ?></a><?php endif; ?>
      <?php if ($p['secondary_label'] !== '' && $p['secondary_url'] !== ''): ?><a class="btn blk-btn-outline" href="<?= e($p['secondary_url']) ?>"><?= e($p['secondary_label']) ?></a><?php endif; ?>
    </p>
  </div>
</section>
