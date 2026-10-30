<h1>Tableau de bord</h1>
<div class="panels">
  <section class="panel" aria-labelledby="p-land">
    <h2 id="p-land">Gestion foncière</h2>
    <ul class="figs">
      <li><a href="/admin/terrains"><span>Terrains au total</span><b><?= (int) $total ?></b></a></li>
      <li><a href="/admin/terrains?status=available"><span>Disponibles</span><b><?= (int) ($counts['available'] ?? 0) ?></b></a></li>
      <li><a href="/admin/terrains?status=reserved"><span>Réservés ou en cours de réservation</span><b><?= (int) (($counts['reserving'] ?? 0) + ($counts['reserved'] ?? 0)) ?></b></a></li>
      <li><a href="/admin/terrains?status=sold"><span>Vendus</span><b><?= (int) ($counts['sold'] ?? 0) ?></b></a></li>
    </ul>
    <a class="btn" href="/admin/terrains/new">Ajouter un terrain</a>
  </section>
  <section class="panel" aria-labelledby="p-crm">
    <h2 id="p-crm">Demandes commerciales</h2>
    <ul class="figs">
      <li><a href="/admin/prospects"><span>Demandes commerciales</span><b><?= (int) $crm['total'] ?></b></a></li>
      <li class="<?= $crm['pending'] > 0 ? 'alert' : '' ?>"><a href="/admin/prospects?status=new"><span>Demandes en attente</span><b><?= (int) $crm['pending'] ?></b></a></li>
      <li><a href="/admin/prospects?type=quote"><span>Demandes de devis</span><b><?= (int) $crm['quotes'] ?></b></a></li>
    </ul>
    <a class="btn" href="/admin/prospects">Traiter les demandes</a>
  </section>
  <section class="panel" aria-labelledby="p-btp">
    <h2 id="p-btp">BTP</h2>
    <ul class="figs">
      <li><a href="/admin/realisations"><span>Réalisations publiées</span><b><?= (int) $projects ?></b></a></li>
    </ul>
    <a class="btn" href="/admin/realisations/new">Ajouter une réalisation</a>
  </section>
</div>
