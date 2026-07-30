
  function toggleNotifPanel(e){
    e?.stopPropagation();
    const panel = document.getElementById('notif-panel');
    if(!panel) return;
    const willOpen = !panel.classList.contains('open');
    panel.classList.toggle('open');
    if(willOpen){ loadNotifications(panel); }
  }

  async function loadNotifications(panel){
    if(!panel) return;
    // No "Loading…" placeholder — the panel keeps its current content until the
    // fresh list is ready, so opening it never flashes a loading state.
    try{
      const headers = { 'X-Requested-With': 'XMLHttpRequest' };
      // Fetch recent requisitions
      const [reqRes, delRes] = await Promise.all([
        fetch(procurementUrl('requisitions'), { headers }),
        fetch(procurementUrl('deliveries'), { headers })
      ]);
      const reqJson = await safeJson(reqRes);
      const delJson = await safeJson(delRes);
      const reqs = Array.isArray(reqJson)
        ? reqJson
        : (reqJson && Array.isArray(reqJson.data) ? reqJson.data : []);
      const dels = Array.isArray(delJson)
        ? delJson
        : (delJson && Array.isArray(delJson.data) ? delJson.data : []);

      // Build items
      const items = [];
      // Prioritize pending requisitions
      (reqs || []).slice(0,5).forEach(r => {
        items.push({ type: 'req', title: r.rq || r.ref || r.id || 'Requisition', text: `${r.item || ''} · Qty ${r.qty || r.quantity || ''}`, meta: r.requester || r.dept || '' });
      });
      // Add deliveries with relevant statuses
      (dels || []).slice(0,5).forEach(d => {
        items.push({ type: 'del', title: d.dr || d.id || d.ref || 'Delivery', text: `${d.items || d.items || ''} · Qty ${d.qty || ''}`, meta: d.status || '' });
      });

      if(items.length === 0){
        panel.innerHTML = `<div class="notif-item ok"><span class="notif-icon">✓</span><div class="notif-content"><strong>No alerts</strong>You have no new notifications right now.<small>System · live</small></div></div>`;
        updateNavCounts(0,0);
        return;
      }

      panel.innerHTML = '';
      let reqCount = 0, delCount = 0;
      items.forEach(it => {
        const div = document.createElement('div');
        div.className = 'notif-item ' + (it.type === 'req' ? 'warn' : 'ok');
        div.innerHTML = `<span class="notif-icon">${it.type==='req' ? 'R' : 'D'}</span><div class="notif-content"><strong>${escapeHtml(it.title)}</strong><div>${escapeHtml(it.text)}</div><small>${escapeHtml(it.meta)}</small></div>`;
        panel.appendChild(div);
        if(it.type === 'req') reqCount++; else if(it.type === 'del') delCount++;
      });
      updateNavCounts(reqCount, delCount);
    }catch(err){
      panel.innerHTML = `<div style="padding:12px;color:#c34">Unable to load notifications</div>`;
      console.error('loadNotifications', err);
    }
  }

  async function safeJson(res){
    try{ return await res.json(); }catch(e){ return null; }
  }

  // Single escaper for the whole module. This used to be a second, weaker
  // implementation that left `'` unescaped while app-modals.js defined
  // htmlEscape() alongside it — two escapers with different character sets.
  function escapeHtml(s){ return htmlEscape(s); }

  function updateNavCounts(reqCount, delCount){
    const reqBadge = document.querySelector("a[href*='requisitions'] .nav-badge");
    const delBadge = document.querySelector("a[href*='deliveries'] .nav-badge");
    if(reqBadge){ reqBadge.textContent = reqCount; reqBadge.classList.toggle('red', reqCount>0); }
    if(delBadge){ delBadge.textContent = delCount; delBadge.classList.toggle('red', delCount>0); }
  }

  document.addEventListener('click', (e)=>{
    const panel = document.getElementById('notif-panel');
    if(panel && !panel.contains(e.target) && !e.target.closest('.notif-badge')){
      panel.classList.remove('open');
    }
  });

  /* ---------- Live stats: poll dashboard cards + sidebar badges (no refresh) ---------- */
  function setLiveStat(id, val){
    const el = document.getElementById(id);
    if(!el || val == null) return;
    if(String(el.textContent).trim() !== String(val)){
      el.textContent = val;
      el.classList.remove('bump'); void el.offsetWidth; el.classList.add('bump');
    }
  }
  // Sub-labels are plain text, no bump animation — they change alongside the
  // number above them and two animations at once reads as a glitch.
  function setLiveText(id, val){
    const el = document.getElementById(id);
    if(!el || val == null) return;
    if(el.textContent.trim() !== String(val)) el.textContent = val;
  }
  function setLiveBadge(selector, val){
    const el = document.querySelector(selector);
    if(!el || val == null) return;
    if(el.textContent.trim() !== String(val)){
      el.textContent = val;
      el.classList.toggle('red', Number(val) > 0);
      el.classList.remove('badge-pulse'); void el.offsetWidth; el.classList.add('badge-pulse');
    }
  }
  // Guarded so a slow response can't queue up behind itself. Without this the
  // 15s timer kept firing regardless of whether the previous request had come
  // back, so a slow connection produced a growing pile of in-flight requests —
  // each one scanning two external databases.
  let liveStatsInFlight = false;
  async function pollLiveStats(){
    if(liveStatsInFlight) return;
    liveStatsInFlight = true;
    try{
      const res = await fetch(procurementUrl('live-stats'), { headers: { 'X-Requested-With': 'XMLHttpRequest', 'Accept': 'application/json' } });
      if(!res.ok) return;
      const j = await res.json();
      if(!j) return;
      if(j.cards){
        setLiveStat('dash-stat-po',  j.cards.activePos);
        setLiveStat('dash-stat-sup', j.cards.suppliers);
        setLiveStat('dash-stat-req', j.cards.requisitions);
        setLiveStat('dash-stat-inv', j.cards.deliveries);
      }
      if(j.subs){
        setLiveText('dash-sub-po',  j.subs.activePos);
        setLiveText('dash-sub-sup', j.subs.suppliers);
        setLiveText('dash-sub-req', j.subs.requisitions);
        setLiveText('dash-sub-inv', j.subs.deliveries);
      }
      if(j.badges){
        setLiveBadge("a[href*='purchase-orders'] .nav-badge", j.badges.purchaseOrders);
        setLiveBadge("a[href*='requisitions'] .nav-badge", j.badges.requisitions);
        setLiveBadge("a[href*='deliveries'] .nav-badge", j.badges.deliveries);
      }
    }catch(err){ /* keep last known values */ }
    finally{ liveStatsInFlight = false; }
  }
  window.pollLiveStats = pollLiveStats;

  // Poll only while the tab is actually visible, and stop on unload. The old
  // setInterval was never cleared and kept hitting the server from background
  // tabs for as long as the browser stayed open.
  const LIVE_STATS_INTERVAL = 30000;
  let liveStatsTimer = null;
  function startLiveStats(){
    if(liveStatsTimer !== null) return;
    liveStatsTimer = setInterval(pollLiveStats, LIVE_STATS_INTERVAL);
  }
  function stopLiveStats(){
    if(liveStatsTimer === null) return;
    clearInterval(liveStatsTimer);
    liveStatsTimer = null;
  }
  document.addEventListener('DOMContentLoaded', ()=>{
    pollLiveStats();
    startLiveStats();
  });
  document.addEventListener('visibilitychange', ()=>{
    if(document.hidden){ stopLiveStats(); }
    else { pollLiveStats(); startLiveStats(); }
  });
  window.addEventListener('pagehide', stopLiveStats);

  /* ---------- Theme (light / dark) ---------- */
  function applyStoredTheme(){
    const saved = localStorage.getItem('procurement-theme');
    if(saved) document.documentElement.setAttribute('data-theme', saved);
  }
  function toggleTheme(){
    const current = document.documentElement.getAttribute('data-theme') === 'dark' ? 'dark' : 'light';
    const next = current === 'dark' ? 'light' : 'dark';
    document.documentElement.setAttribute('data-theme', next);
    localStorage.setItem('procurement-theme', next);
  }
  applyStoredTheme();

  /* ---------- Profile dropdown ---------- */
  function toggleProfileMenu(e){
    if(e) e.stopPropagation();
    document.getElementById('profile-dropdown')?.classList.toggle('open');
  }
  document.addEventListener('click', (e) => {
    const menu = document.querySelector('.profile-menu');
    if(menu && !menu.contains(e.target)){
      document.getElementById('profile-dropdown')?.classList.remove('open');
    }
  });

  // NOTE: animateDashboard() lives in app-dashboard.js. A second definition
  // used to sit here; because both files load into the same global scope, the
  // later <script> tag silently decided which one ran, so reordering the tags
  // in app.blade.php would have changed dashboard behaviour for no visible
  // reason. The bar/donut/top-supplier work this copy did is covered there.

  /* ---------- Toasts ---------- */
  function showToast(message, kind){
    const stack = document.getElementById('toast-stack');
    if(!stack) return;
    const toast = document.createElement('div');
    toast.className = 'toast';
    // Built as nodes, not innerHTML: toast text routinely carries server
    // messages and user-entered values (supplier names, remarks, validation
    // errors), which the old template literal injected as raw markup.
    const dot = document.createElement('span');
    dot.className = 'toast-dot ' + (kind || '');
    const label = document.createElement('span');
    label.textContent = String(message ?? '');
    toast.append(dot, label);
    stack.appendChild(toast);
    setTimeout(()=>{
      toast.classList.add('leaving');
      setTimeout(()=> toast.remove(), 260);
    }, 2600);
  }

  /* NOTE: bumpStat / refreshTabCounts / checkEmpty / handleDecision were
     removed along with the approvals queue. They drove #approval-tabs,
     .queue-row, #queue-empty and #stat-approved / #stat-rejected /
     #stat-pending, none of which exist in any Procurement view. */
