/* Public site behaviour: pinned-scene switching, entrance reveals, live land count.
   Everything is an enhancement: without this file the page is complete and readable. */
(() => {
  const reduce = matchMedia('(prefers-reduced-motion: reduce)').matches;

  // Phone: the menu starts collapsed (it stays open, fully usable, without script).
  const menu = document.querySelector('.site-head .menu');
  if (menu && matchMedia('(max-width: 900px)').matches) menu.removeAttribute('open');

  // Scene follows the section being read.
  const stage = document.querySelector('.stage');
  if (stage && 'IntersectionObserver' in window) {
    const so = new IntersectionObserver((entries) => {
      entries.forEach((e) => { if (e.isIntersecting) stage.dataset.scene = e.target.dataset.scene; });
    }, { rootMargin: '-45% 0px -45% 0px' });
    document.querySelectorAll('.reading [data-scene]').forEach((s) => so.observe(s));
  }

  // Staggered entrance of each section's parts, once.
  const items = document.querySelectorAll('.reveal');
  document.querySelectorAll('.reveal-group').forEach((g) => g.querySelectorAll('.reveal').forEach((r, i) => r.style.setProperty('--i', Math.min(i, 6))));
  if (reduce || !('IntersectionObserver' in window)) {
    items.forEach((i) => i.classList.add('in'));
  } else {
    const ro = new IntersectionObserver((entries) => entries.forEach((e) => { if (e.isIntersecting) { e.target.classList.add('in'); ro.unobserve(e.target); } }), { rootMargin: '0px 0px -8% 0px' });
    items.forEach((i) => ro.observe(i));
  }

  // Live count: "how many lands fit this budget" from the real catalogue.
  const live = document.querySelector('[data-live]');
  if (live) {
    const max = live.querySelector('[name=max]');
    const cat = live.querySelector('[name=category]');
    const out = live.querySelector('[data-live-out]');
    let t, n = 0;
    const fmt = (v) => new Intl.NumberFormat('fr-FR').format(v).replace(/ /g, ' ');
    const run = async () => {
      const digits = max.value.replace(/\D/g, '');
      const mine = ++n;
      const q = new URLSearchParams({ category: cat.value });
      if (digits) q.set('max', digits);
      try {
        const r = await fetch('/api/terrains/compte?' + q, { headers: { Accept: 'application/json' } });
        const { count } = await r.json();
        if (mine !== n) return;
        const where = digits ? ` jusqu'à ${fmt(digits)} FCFA` : '';
        out.textContent = count === 0 ? `Aucun terrain disponible${where} pour le moment.` : `${count} terrain${count > 1 ? 's' : ''} disponible${count > 1 ? 's' : ''}${where}.`;
      } catch { out.textContent = ''; }
    };
    const later = () => { clearTimeout(t); t = setTimeout(run, 250); };
    max.addEventListener('input', () => { const d = max.value.replace(/\D/g, ''); max.value = d ? fmt(d) : ''; later(); });
    cat.addEventListener('change', later);
    run();
  }
})();
