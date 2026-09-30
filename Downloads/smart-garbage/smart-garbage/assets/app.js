(function () {
  const box = document.getElementById('bins');
  if (!box) return;
  const kp = document.getElementById('kpis');
  const role = box.dataset.role, token = box.dataset.csrf;
  const esc = s => String(s).replace(/[&<>"']/g, c => ({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;'}[c]));
  const cls = p => p >= 80 ? 'd' : p >= 60 ? 'w' : 'g';
  const txt = p => p >= 80 ? 'Collect now' : p >= 60 ? 'Filling up' : 'OK';

  function card(b) {
    const p = +b.fill_percent, c = cls(p);
    let extra = b.owner ? `<p>${esc(b.owner)}</p>` : (role === 'admin' ? '<p>Shared bin</p>' : '');
    if (role === 'collector') {
      extra += `<form method="post"><input type="hidden" name="csrf" value="${esc(token)}">` +
               `<input type="hidden" name="bin_id" value="${+b.id}"><button class="btn s p">Mark collected</button></form>`;
    }
    return `<article class="bin"><div class="can" aria-hidden="true"><i class="${c}" style="height:${p}%"></i></div>` +
           `<div><h3>${esc(b.bin_code)}</h3><p>${esc(b.location)}</p>${extra}` +
           `<div class="pct">${p}%</div><span class="tag ${c}">${txt(p)}</span>` +
           `<p class="note">Updated ${esc(b.updated_at)}</p></div></article>`;
  }

  async function load() {
    try {
      const r = await fetch('api/bins.php', {credentials: 'same-origin'});
      if (r.status === 401) { location.href = 'login.php'; return; }
      if (!r.ok) throw new Error();
      const bins = (await r.json()).bins;
      box.innerHTML = bins.map(card).join('') || '<p class="empty">No bins to show yet.</p>';
      if (kp) {
        const full = bins.filter(b => b.fill_percent >= 80).length;
        const avg = bins.length ? Math.round(bins.reduce((a, b) => a + (+b.fill_percent), 0) / bins.length) : 0;
        kp.innerHTML = [[bins.length, 'Bins monitored'], [full, 'Bins needing collection'], [avg + '%', 'Average fill level']]
          .map(k => `<div class="kpi"><b>${k[0]}</b><span>${k[1]}</span></div>`).join('');
      }
    } catch (e) {
      box.innerHTML = '<p class="empty">Could not load bins. Check that Apache and MySQL are running in XAMPP.</p>';
    }
  }
  load();
  setInterval(load, 10000);
})();
