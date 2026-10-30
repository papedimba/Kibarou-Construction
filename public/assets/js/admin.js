/* Admin: phone menu toggle (progressive enhancement; the menu is always visible without script). */
(() => {
  const btn = document.getElementById('menu-btn');
  const side = document.getElementById('side');
  if (!btn || !side) return;
  const set = (open) => { side.classList.toggle('open', open); btn.setAttribute('aria-expanded', String(open)); };
  btn.addEventListener('click', () => set(!side.classList.contains('open')));
  document.addEventListener('click', (e) => { if (side.classList.contains('open') && !side.contains(e.target) && !btn.contains(e.target)) set(false); });
  document.addEventListener('keydown', (e) => { if (e.key === 'Escape') set(false); });
})();
