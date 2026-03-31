(function () {
    let liveInterval = null;

    function setText(sel, val) {
        const el = document.querySelector(sel);
        if (el) el.textContent = val;
    }

    function fmtMs(ms) {
        const sec = Math.max(0, Math.floor(ms / 1000));
        return String(Math.floor(sec / 3600)).padStart(2, '0') + ':' +
               String(Math.floor((sec % 3600) / 60)).padStart(2, '0') + ':' +
               String(sec % 60).padStart(2, '0');
    }

    function getSelectedDate() {
        const g = document.querySelector('.planner-grid');
        if (g && g.dataset.selectedDate) return g.dataset.selectedDate;
        const i = document.getElementById('dateSelector');
        return i ? i.value : '';
    }

    function getSelectedTaskId() {
        const f = document.getElementById('slaTaskFilter');
        return f ? f.value : '';
    }

    function clearLive() {
        if (liveInterval) { clearInterval(liveInterval); liveInterval = null; }
    }

    // ── Spec-exact working time from card DOM ─────────────────────────────────
    function workingTimeMs(card) {
        const status    = card.dataset.status || '';
        const accumMs   = parseInt(card.dataset.pausedAccumMs) || 0;
        const startTsMs = parseInt(card.dataset.startTsMs)     || 0;

        if ((status === 'in_progress' || status === 'overdue') && startTsMs > 0) {
            return accumMs + Math.max(0, Date.now() - startTsMs);
        }
        return accumMs;
    }

    // ── Single-task live panel ────────────────────────────────────────────────
    function syncFromCard(taskId) {
        const card = document.querySelector('.task-card[data-task-id="' + taskId + '"]');
        if (!card) return;

        const status      = card.dataset.status || '';
        const slaDurMs    = (parseInt(card.dataset.slaDuration) || 900) * 1000;
        const wMs         = workingTimeMs(card);
        const remainMs    = slaDurMs - wMs;
        const isOverdue   = wMs >= slaDurMs && (status === 'in_progress' || status === 'overdue');

        const titleEl = card.querySelector('.task-card__title');
        setText('.sla-selected-task-name', titleEl ? titleEl.textContent.trim() : 'Task #' + taskId);
        setText('.sla-total-time',     fmtMs(slaDurMs));
        setText('.sla-used-time',      fmtMs(wMs));
        setText('.sla-remaining-time', isOverdue ? '00:00:00' : fmtMs(Math.max(0, remainMs)));
        setText('.sla-pause-time',     '00:00:00');   // break time not tracked separately in spec

        setText('.sla-stat-total',     '1');
        setText('.sla-stat-completed', status === 'completed' ? '1' : '0');
        setText('.sla-stat-active',    (status === 'in_progress' || status === 'overdue') ? '1' : '0');
        setText('.sla-stat-postponed', status === 'postponed' ? '1' : '0');
        setText('.sla-completion-rate', (parseInt(card.dataset.completedPercentage) || 0) + '%');
    }

    function startLiveSync(taskId) {
        clearLive();
        syncFromCard(taskId);
        liveInterval = setInterval(() => syncFromCard(taskId), 1000);
    }

    // ── Aggregate via API ─────────────────────────────────────────────────────
    async function loadAggregate(date) {
        const r = await fetch('/ergon/api/sla_dashboard.php?date=' + encodeURIComponent(date), {
            credentials: 'same-origin',
            headers: { 'X-Requested-With': 'XMLHttpRequest' }
        });
        const text = await r.text();
        if (!r.ok) throw new Error('HTTP ' + r.status + ' — ' + text.slice(0, 120));
        let payload;
        try { payload = JSON.parse(text); }
        catch (_) {
            console.error('sla_dashboard.php raw response:', text.slice(0, 500));
            throw new Error('Non-JSON response from sla_dashboard.php');
        }
        if (!payload.success) throw new Error(payload.message || 'Failed');

        const d = payload.sla_data;
        setText('.sla-selected-task-name', 'All Tasks');
        setText('.sla-total-time',     d.total_sla_time       || '00:00:00');
        setText('.sla-used-time',      d.total_time_used      || '00:00:00');
        setText('.sla-remaining-time', d.total_remaining_time || '00:00:00');
        setText('.sla-pause-time',     d.total_pause_time     || '00:00:00');
        setText('.sla-stat-total',     String(d.total_tasks       || 0));
        setText('.sla-stat-completed', String(d.completed_tasks   || 0));
        setText('.sla-stat-active',    String(d.in_progress_tasks || 0));
        setText('.sla-stat-postponed', String(d.postponed_tasks   || 0));
        const rate = d.total_tasks > 0
            ? ((d.completed_tasks / d.total_tasks) * 100).toFixed(1) + '%' : '0%';
        setText('.sla-completion-rate', rate);
    }

    // ── Entry ─────────────────────────────────────────────────────────────────
    async function loadSlaDashboard() {
        const date = getSelectedDate();
        if (!date) return;
        clearLive();

        const taskId = getSelectedTaskId();
        if (taskId) {
            startLiveSync(taskId);
        } else {
            ['.sla-total-time', '.sla-used-time', '.sla-remaining-time', '.sla-pause-time']
                .forEach(s => setText(s, 'Loading...'));
            try {
                await loadAggregate(date);
            } catch (e) {
                console.error('SLA aggregate failed:', e);
                ['.sla-total-time', '.sla-used-time', '.sla-remaining-time', '.sla-pause-time']
                    .forEach(s => setText(s, '--:--:--'));
            }
        }
    }

    window.forceSLARefresh = function() { loadSlaDashboard(); };

    document.addEventListener('DOMContentLoaded', function() {
        const filter = document.getElementById('slaTaskFilter');
        if (filter) filter.addEventListener('change', loadSlaDashboard);
        loadSlaDashboard();
    });
})();
