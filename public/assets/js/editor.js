/* Visual block editor. Vanilla JS, no dependencies. The server is the authority: every save/render goes through
   BlockSanitizer, so nothing here is trusted. User text is only ever put in the DOM through textContent/value. */
(() => {
  const D = JSON.parse(document.getElementById('editor-data').textContent);
  const R = D.registry;
  const csrf = document.getElementById('csrf').value;
  const $ = (id) => document.getElementById(id);
  let blocks = D.blocks;
  let selected = null;
  let dirty = false;
  let renderTimer = null;
  let renderSeq = 0;

  const el = (tag, attrs = {}, ...kids) => {
    const n = document.createElement(tag);
    for (const [k, v] of Object.entries(attrs)) {
      if (k === 'class') n.className = v;
      else if (k.startsWith('on')) n.addEventListener(k.slice(2), v);
      else if (v !== false && v !== null && v !== undefined) n.setAttribute(k, v === true ? '' : v);
    }
    kids.flat().forEach((c) => n.append(c));
    return n;
  };

  const uid = () => Array.from(crypto.getRandomValues(new Uint8Array(4)), (b) => b.toString(16).padStart(2, '0')).join('');

  function fieldDefault(f) {
    switch (f.type) {
      case 'number': return f.default ?? f.min;
      case 'select': return f.default ?? Object.keys(f.options)[0];
      case 'image': return 0;
      case 'bool': return false;
      case 'list': return [];
      default: return '';
    }
  }
  const defaults = (fields) => Object.fromEntries(Object.entries(fields).map(([k, f]) => [k, fieldDefault(f)]));
  const newBlock = (type) => ({ id: uid(), type, props: defaults(R[type].fields), children: [] });

  function locate(id, list = blocks, parent = null) {
    for (let i = 0; i < list.length; i++) {
      if (list[i].id === id) return { list, i, b: list[i], parent };
      const r = locate(id, list[i].children, list[i]);
      if (r) return r;
    }
    return null;
  }
  const clone = (b) => ({ ...JSON.parse(JSON.stringify(b)), id: uid(), children: b.children.map(clone) });
  const contains = (b, id) => b.id === id || b.children.some((c) => contains(c, id));

  // — status / dirty
  const status = (msg, isError = false) => { const s = $('status'); s.textContent = msg; s.classList.toggle('error', isError); };
  function changed() {
    dirty = true;
    status('Modifications non enregistrées');
    clearTimeout(renderTimer);
    renderTimer = setTimeout(renderCanvas, 200);
  }
  window.addEventListener('beforeunload', (e) => { if (dirty) { e.preventDefault(); e.returnValue = ''; } });

  async function post(url, body) {
    const r = await fetch(url, { method: 'POST', headers: { 'Content-Type': 'application/json', Accept: 'application/json' }, body: JSON.stringify({ _csrf: csrf, ...body }) });
    let data = {};
    try { data = await r.json(); } catch { /* non-JSON error page */ }
    if (!r.ok || !data.ok) throw new Error(data.error || `Erreur ${r.status}`);
    return data;
  }

  // — canvas
  async function renderCanvas() {
    const seq = ++renderSeq;
    try {
      const { html } = await post(D.urls.render, { blocks });
      if (seq !== renderSeq) return; // a newer render is in flight
      const canvas = $('canvas');
      canvas.innerHTML = html || '<p class="ed-empty">Page vide : ajoutez un bloc depuis la bibliothèque.</p>'; // html comes from the server renderer (escaped, sanitized)
      canvas.querySelectorAll('.blk').forEach((n) => n.setAttribute('draggable', 'true'));
      markSelected();
    } catch (e) {
      status(e.message, true);
    }
  }
  function markSelected() {
    document.querySelectorAll('#canvas .blk').forEach((n) => n.classList.toggle('is-selected', n.dataset.blockId === selected));
  }

  // — selection, toolbar
  function select(id) {
    selected = id && locate(id) ? id : null;
    markSelected();
    const loc = selected && locate(selected);
    $('sel-label').textContent = loc ? R[loc.b.type].label : 'Aucun bloc sélectionné';
    ['b-up', 'b-down', 'b-dup', 'b-del'].forEach((b) => { $(b).disabled = !loc; });
    renderSettings();
  }
  $('canvas').addEventListener('click', (e) => {
    e.preventDefault(); // no navigation / form submission inside the editor canvas
    const b = e.target.closest('.blk');
    select(b ? b.dataset.blockId : null);
  });

  function add(type, target) {
    const nb = newBlock(type);
    const loc = (target && locate(target)) || (selected && locate(selected));
    if (loc && R[loc.b.type].container && !target) loc.b.children.push(nb);
    else if (loc) loc.list.splice(loc.i + 1, 0, nb);
    else blocks.push(nb);
    changed();
    select(nb.id);
  }
  function move(dir) {
    const loc = selected && locate(selected);
    if (!loc) return;
    const j = loc.i + dir;
    if (j < 0 || j >= loc.list.length) return;
    [loc.list[loc.i], loc.list[j]] = [loc.list[j], loc.list[loc.i]];
    changed();
  }
  $('b-up').addEventListener('click', () => move(-1));
  $('b-down').addEventListener('click', () => move(1));
  $('b-dup').addEventListener('click', () => {
    const loc = selected && locate(selected);
    if (!loc) return;
    const c = clone(loc.b);
    loc.list.splice(loc.i + 1, 0, c);
    changed();
    select(c.id);
  });
  $('b-del').addEventListener('click', () => {
    const loc = selected && locate(selected);
    if (!loc || !confirm('Supprimer ce bloc ?')) return;
    loc.list.splice(loc.i, 1);
    changed();
    select(null);
  });

  // — palette
  (function buildPalette() {
    const pal = $('palette');
    for (const [cat, label] of Object.entries(D.categories)) {
      pal.append(el('div', { class: 'pal-cat' }, label));
      for (const [type, def] of Object.entries(R)) {
        if (def.category !== cat) continue;
        const b = el('button', { type: 'button', class: 'pal-item', 'data-type': type, draggable: 'true', title: `Ajouter : ${def.label}` }, def.label);
        b.addEventListener('click', () => add(type));
        b.addEventListener('dragstart', (e) => e.dataTransfer.setData('text/plain', `new:${type}`));
        pal.append(b);
      }
    }
  })();

  // — drag & drop (palette -> canvas, and reordering inside the canvas)
  const stage = $('canvas');
  const clearDrop = () => stage.querySelectorAll('.drop-before,.drop-after,.drop-into').forEach((n) => n.classList.remove('drop-before', 'drop-after', 'drop-into'));
  stage.addEventListener('dragstart', (e) => {
    const b = e.target.closest('.blk');
    if (b) { e.dataTransfer.setData('text/plain', `move:${b.dataset.blockId}`); e.dataTransfer.effectAllowed = 'move'; }
    e.stopPropagation();
  });
  stage.addEventListener('dragover', (e) => {
    e.preventDefault();
    clearDrop();
    const t = e.target.closest('.blk-empty, .blk');
    if (!t) return;
    if (t.classList.contains('blk-empty')) { t.classList.add('drop-into'); return; }
    const r = t.getBoundingClientRect();
    t.classList.add(e.clientY < r.top + r.height / 2 ? 'drop-before' : 'drop-after');
  });
  stage.addEventListener('dragleave', (e) => { if (!stage.contains(e.relatedTarget)) clearDrop(); });
  stage.addEventListener('drop', (e) => {
    e.preventDefault();
    const t = e.target.closest('.blk-empty, .blk');
    const before = t && t.classList.contains('drop-before');
    const into = t && t.classList.contains('blk-empty') ? t.dataset.dropInto : null;
    clearDrop();
    const [kind, val] = (e.dataTransfer.getData('text/plain') || '').split(':');
    const targetId = into || (t ? t.dataset.blockId : null);
    let moving;
    if (kind === 'new' && R[val]) {
      moving = newBlock(val);
    } else if (kind === 'move') {
      const src = locate(val);
      if (!src || (targetId && contains(src.b, targetId))) return; // cannot drop a block into itself
      src.list.splice(src.i, 1);
      moving = src.b;
    } else return;
    const dest = targetId && locate(targetId);
    if (into && dest) dest.b.children.push(moving);
    else if (dest) dest.list.splice(dest.i + (before ? 0 : 1), 0, moving);
    else blocks.push(moving);
    changed();
    select(moving.id);
  });

  // — settings form (generated from the registry)
  function control(f, value, onChange, rebuild) {
    const label = (txt, input) => el('label', { class: 'fld' }, txt, input);
    switch (f.type) {
      case 'textarea': {
        const i = el('textarea', { rows: 4, maxlength: f.max || false });
        i.value = value ?? '';
        i.addEventListener('input', () => onChange(i.value));
        return label(f.label, i);
      }
      case 'select': {
        const i = el('select', {}, Object.entries(f.options).map(([k, v]) => el('option', { value: k }, v)));
        i.value = value;
        i.addEventListener('change', () => onChange(i.value));
        return label(f.label, i);
      }
      case 'icon': {
        const i = el('select', {}, [el('option', { value: '' }, '— aucune'), ...D.icons.map((n) => el('option', { value: n }, n))]);
        i.value = value || '';
        i.addEventListener('change', () => onChange(i.value));
        return label(f.label, i);
      }
      case 'image': {
        const i = el('select', {}, [el('option', { value: 0 }, '— aucune'), ...D.media.map((m) => el('option', { value: m.id }, m.label))]);
        i.value = value || 0;
        const prev = el('img', { class: 'img-prev', alt: '' });
        const sync = () => { const m = D.media.find((x) => x.id === Number(i.value)); prev.hidden = !m; if (m) prev.src = m.url; };
        i.addEventListener('change', () => { onChange(Number(i.value)); sync(); });
        sync();
        return el('div', { class: 'fld' }, f.label, i, prev, D.media.length ? '' : el('small', {}, 'Aucun média : ajoutez-en dans Médias.'));
      }
      case 'bool': {
        const i = el('input', { type: 'checkbox' });
        i.checked = !!value;
        i.addEventListener('change', () => onChange(i.checked));
        return el('label', { class: 'fld check' }, i, f.label);
      }
      case 'number': {
        const i = el('input', { type: 'number', min: f.min, max: f.max });
        i.value = value;
        i.addEventListener('input', () => onChange(Number(i.value)));
        return label(f.label, i);
      }
      case 'list': return list(f, value, onChange, rebuild);
      default: { // text, url, decimal
        const i = el('input', { type: 'text', maxlength: f.max || false, inputmode: f.type === 'decimal' ? 'decimal' : false });
        i.value = value ?? '';
        i.addEventListener('input', () => onChange(i.value));
        return label(f.label + (f.type === 'url' ? ' (/chemin ou https://…)' : ''), i);
      }
    }
  }
  function list(f, items, onChange, rebuild) {
    const box = el('div', { class: 'rep' }, el('strong', {}, f.label));
    items.forEach((item, idx) => {
      const row = el('div', { class: 'rep-item' });
      for (const [k, sub] of Object.entries(f.item)) {
        row.append(control(sub, item[k], (v) => { item[k] = v; changed(); }, rebuild));
      }
      const act = (txt, fn, aria) => el('button', { type: 'button', 'aria-label': aria, onclick: () => { fn(); changed(); rebuild(); } }, txt);
      row.append(el('div', { class: 'rep-actions' },
        act('↑', () => { if (idx > 0) [items[idx - 1], items[idx]] = [items[idx], items[idx - 1]]; }, 'Monter l\'élément'),
        act('↓', () => { if (idx < items.length - 1) [items[idx + 1], items[idx]] = [items[idx], items[idx + 1]]; }, 'Descendre l\'élément'),
        act('Retirer', () => items.splice(idx, 1), 'Retirer l\'élément')));
      box.append(row);
    });
    const addBtn = el('button', { type: 'button', class: 'rep-add', disabled: items.length >= f.max, onclick: () => { items.push(defaults(f.item)); changed(); rebuild(); } }, '+ Ajouter');
    box.append(addBtn);
    return box;
  }
  function renderSettings() {
    const box = $('settings');
    box.replaceChildren();
    const loc = selected && locate(selected);
    if (!loc) { box.append(el('p', { class: 'muted' }, 'Sélectionnez un bloc dans la zone de construction.')); return; }
    const def = R[loc.b.type];
    box.append(el('h3', {}, def.label));
    if (!Object.keys(def.fields).length) box.append(el('p', { class: 'muted' }, 'Ce bloc n\'a pas de paramètres.'));
    for (const [k, f] of Object.entries(def.fields)) {
      box.append(control(f, loc.b.props[k], (v) => { loc.b.props[k] = v; changed(); }, renderSettings));
    }
  }

  // — save / publish
  async function persist(publish) {
    try {
      status(publish ? 'Publication…' : 'Enregistrement…');
      const data = await post(publish ? D.urls.publish : D.urls.save, { blocks });
      blocks = data.blocks; // adopt the sanitized version (invalid values dropped by the server)
      dirty = false;
      status((publish ? 'Publié à ' : 'Enregistré à ') + new Date().toLocaleTimeString('fr-FR', { hour: '2-digit', minute: '2-digit' }));
      select(selected);
      renderCanvas();
    } catch (e) {
      status(e.message, true);
    }
  }
  $('btn-save').addEventListener('click', () => persist(false));
  $('btn-publish').addEventListener('click', () => { if (confirm('Publier cette version sur le site ?')) persist(true); });

  // — device preview
  document.querySelectorAll('.ed-devices button').forEach((b) => b.addEventListener('click', () => {
    $('stage').dataset.device = b.dataset.device;
    document.querySelectorAll('.ed-devices button').forEach((x) => x.setAttribute('aria-pressed', String(x === b)));
  }));
  // The preview link shows the saved draft: save first so "what you see" is what opens.
  $('btn-preview').addEventListener('click', async (e) => {
    if (!dirty) return;
    e.preventDefault();
    await persist(false);
    window.open(D.urls.preview, '_blank', 'noopener');
  });

  renderCanvas();
  select(null);
})();
