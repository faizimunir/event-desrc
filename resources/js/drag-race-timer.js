import Echo from 'laravel-echo';
import Pusher from 'pusher-js';

window.Pusher = Pusher;

function csrf() {
    return document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') ?? '';
}

async function fetchJson(url, options = {}) {
    const headers = {
        Accept: 'application/json',
        'Content-Type': 'application/json',
        'X-CSRF-TOKEN': csrf(),
        'X-Requested-With': 'XMLHttpRequest',
        ...(typeof window !== 'undefined' && window.__dragRaceSocketId
            ? { 'X-Socket-Id': window.__dragRaceSocketId }
            : {}),
        ...options.headers,
    };
    const res = await fetch(url, { credentials: 'same-origin', ...options, headers });
    const data = await res.json().catch(() => ({}));
    if (!res.ok) {
        const msg = data.message ?? res.statusText;
        throw new Error(typeof msg === 'string' ? msg : 'Request failed');
    }
    return data;
}

function normalizeState(raw) {
    return {
        phase: raw.phase ?? 'idle',
        startTimeMs: raw.start_time_ms ?? null,
        finishAMs: raw.finish_a_ms ?? null,
        finishBMs: raw.finish_b_ms ?? null,
        history: Array.isArray(raw.history) ? raw.history : undefined,
    };
}

function formatElapsed(ms) {
    if (ms == null || Number.isNaN(ms) || ms < 0) {
        return '—.———';
    }
    const total = Math.floor(ms);
    const s = Math.floor(total / 1000);
    const m = Math.floor(s / 60);
    const sec = s % 60;
    const frac = total % 1000;
    return `${String(m).padStart(2, '0')}:${String(sec).padStart(2, '0')}.${String(frac).padStart(3, '0')}`;
}

function buildEcho(root) {
    const key = root.dataset.pusherKey?.trim();
    if (!key) {
        return null;
    }
    const cluster = root.dataset.pusherCluster || 'mt1';
    const scheme = root.dataset.pusherScheme || 'https';
    const forceTLS = scheme === 'https';

    const opts = {
        broadcaster: 'pusher',
        key,
        cluster,
        forceTLS,
        encrypted: forceTLS,
        disableStats: true,
        authEndpoint: '/broadcasting/auth',
        auth: {
            headers: {
                'X-CSRF-TOKEN': csrf(),
                Accept: 'application/json',
            },
        },
    };

    const host = root.dataset.pusherHost?.trim();
    if (host) {
        const port = Number(root.dataset.pusherPort) || 6001;
        opts.wsHost = host;
        opts.wsPort = port;
        opts.wssPort = port;
        opts.enabledTransports = ['ws', 'wss'];
    }

    return new Echo(opts);
}

class DragRaceTimerApp {
    constructor(root) {
        this.root = root;
        this.skewMs = 0;
        this.perfRace0 = null;
        this.startTimeMs = null;
        this.countdownTotalMs = 0;
        this.phase = 'idle';
        this.finishAMs = null;
        this.finishBMs = null;
        this.rafId = null;
        this.countdownRafId = null;
        this.scheduledStartTimer = null;
        this.history = [];
        this.echo = null;
        this.channel = null;
        /** @type {string|null} */
        this.appliedRaceStartKey = null;

        this.boundTick = this.tick.bind(this);
        this.boundCountdownTick = this.countdownTick.bind(this);

        this.el = {
            main: root.querySelector('#main-timer'),
            laneATime: root.querySelector('#lane-a-time'),
            laneBTime: root.querySelector('#lane-b-time'),
            laneACard: root.querySelector('#lane-a-card'),
            laneBCard: root.querySelector('#lane-b-card'),
            laneABadge: root.querySelector('#lane-a-badge'),
            laneBBadge: root.querySelector('#lane-b-badge'),
            btnStart: root.querySelector('#btn-start'),
            btnStopA: root.querySelector('#btn-stop-a'),
            btnStopB: root.querySelector('#btn-stop-b'),
            btnReset: root.querySelector('#btn-reset'),
            btnClearHistory: root.querySelector('#btn-clear-history'),
            chkCountdown: root.querySelector('#chk-countdown'),
            history: root.querySelector('#history-list'),
            winner: root.querySelector('#winner-banner'),
            pill: root.querySelector('#connection-pill'),
            hint: root.querySelector('#broadcast-hint'),
            countdownOverlay: root.querySelector('#countdown-overlay'),
            countdownNumber: root.querySelector('#countdown-number'),
            goOverlay: root.querySelector('#go-overlay'),
            goText: root.querySelector('#go-text'),
        };
    }

    estServerNow() {
        return Date.now() + this.skewMs;
    }

    async syncSkew() {
        const t0 = Date.now();
        const data = await fetchJson(this.root.dataset.timeUrl);
        const t1 = Date.now();
        const localMid = (t0 + t1) / 2;
        this.skewMs = data.serverTime - localMid;
    }

    clearScheduled() {
        if (this.scheduledStartTimer) {
            clearTimeout(this.scheduledStartTimer);
            this.scheduledStartTimer = null;
        }
        if (this.countdownRafId) {
            cancelAnimationFrame(this.countdownRafId);
            this.countdownRafId = null;
        }
        if (this.rafId) {
            cancelAnimationFrame(this.rafId);
            this.rafId = null;
        }
    }

    hideOverlays() {
        this.el.countdownOverlay.classList.add('hidden');
        this.el.goOverlay.classList.add('hidden');
        this.el.countdownNumber.textContent = '';
        this.el.goText.textContent = '';
        this.el.goText.classList.add('opacity-0');
    }

    playBeep() {
        try {
            const ctx = new AudioContext();
            const o = ctx.createOscillator();
            const g = ctx.createGain();
            o.connect(g);
            g.connect(ctx.destination);
            o.frequency.value = 880;
            o.type = 'sine';
            g.gain.setValueAtTime(0.15, ctx.currentTime);
            g.gain.exponentialRampToValueAtTime(0.001, ctx.currentTime + 0.12);
            o.start(ctx.currentTime);
            o.stop(ctx.currentTime + 0.15);
        } catch {
            /* ignore */
        }
    }

    flashGo() {
        this.el.goOverlay.classList.remove('hidden');
        this.el.goText.textContent = 'GO';
        requestAnimationFrame(() => {
            this.el.goText.classList.remove('opacity-0');
        });
        setTimeout(() => {
            this.el.goText.classList.add('opacity-0');
            setTimeout(() => this.el.goOverlay.classList.add('hidden'), 400);
        }, 650);
    }

    /**
     * @param {{ silent?: boolean }} opts
     */
    beginRaceClock(opts = {}) {
        const silent = opts.silent === true;
        this.hideOverlays();
        this.perfRace0 = performance.now();
        this.phase = 'running';
        if (!silent) {
            this.flashGo();
            this.playBeep();
        }
        this.updateButtons();
        this.rafId = requestAnimationFrame(this.boundTick);
    }

    countdownTick() {
        if (!this.startTimeMs || this.countdownTotalMs <= 0) {
            this.hideOverlays();
            return;
        }
        const remain = this.startTimeMs - this.estServerNow();
        if (remain <= 0) {
            this.countdownRafId = null;
            return;
        }
        const n = Math.min(3, Math.max(1, Math.ceil(remain / 1000)));
        this.el.countdownOverlay.classList.remove('hidden');
        this.el.countdownNumber.textContent = String(n);
        this.countdownRafId = requestAnimationFrame(this.boundCountdownTick);
    }

    scheduleRaceStarted(startTime, countdownTotalMs) {
        this.clearScheduled();
        this.startTimeMs = startTime;
        this.countdownTotalMs = countdownTotalMs;

        const delay = startTime - this.estServerNow();
        if (countdownTotalMs > 0) {
            this.el.countdownOverlay.classList.remove('hidden');
            this.countdownRafId = requestAnimationFrame(this.boundCountdownTick);
        }

        this.scheduledStartTimer = setTimeout(() => {
            this.scheduledStartTimer = null;
            this.beginRaceClock({ silent: false });
        }, Math.max(0, delay));
    }

    onRaceStarted(e) {
        const startTime = e.startTime;
        const countdownTotalMs = e.countdownTotalMs ?? 0;
        const key = String(startTime);
        if (this.appliedRaceStartKey === key) {
            return;
        }
        this.appliedRaceStartKey = key;
        this.finishAMs = null;
        this.finishBMs = null;
        this.phase = 'running';
        this.scheduleRaceStarted(startTime, countdownTotalMs);
    }

    onLaneFinished(e) {
        const lane = e.lane;
        const finishTime = e.finishTime;
        if (lane === 'a' && this.finishAMs != null) {
            return;
        }
        if (lane === 'b' && this.finishBMs != null) {
            return;
        }
        if (lane === 'a') {
            this.finishAMs = finishTime;
        } else if (lane === 'b') {
            this.finishBMs = finishTime;
        }
        if (this.startTimeMs != null) {
            if (lane === 'a') {
                this.el.laneATime.textContent = formatElapsed(finishTime - this.startTimeMs);
            } else {
                this.el.laneBTime.textContent = formatElapsed(finishTime - this.startTimeMs);
            }
        }
        if (this.finishAMs != null && this.finishBMs != null) {
            this.phase = 'finished';
            this.clearScheduled();
            this.perfRace0 = null;
            this.updateWinnerUi();
        }
        this.updateButtons();
        this.renderFrame();
    }

    onRaceReset() {
        this.clearScheduled();
        this.appliedRaceStartKey = null;
        this.applyState(normalizeState({ phase: 'idle', start_time_ms: null, finish_a_ms: null, finish_b_ms: null }));
        this.hideOverlays();
        this.el.winner.classList.add('hidden');
        this.clearLaneHighlights();
    }

    renderHistory() {
        this.el.history.innerHTML = '';
        for (const row of this.history) {
            if (
                row.start_time_ms == null ||
                row.finish_a_ms == null ||
                row.finish_b_ms == null
            ) {
                continue;
            }
            const a = row.finish_a_ms - row.start_time_ms;
            const b = row.finish_b_ms - row.start_time_ms;
            let winner = 'tie';
            if (a < b) {
                winner = 'A';
            } else if (b < a) {
                winner = 'B';
            }
            const li = document.createElement('li');
            const w = winner === 'tie' ? 'Tie' : `Lane ${winner}`;
            li.textContent = `A ${formatElapsed(a)}  vs  B ${formatElapsed(b)}  →  ${w}`;
            this.el.history.appendChild(li);
        }
    }

    clearLaneHighlights() {
        for (const el of [this.el.laneACard, this.el.laneBCard]) {
            el.classList.remove('ring-2', 'ring-emerald-500', 'ring-red-500', 'bg-emerald-950/30', 'bg-red-950/30');
        }
        this.el.laneABadge.classList.add('hidden');
        this.el.laneBBadge.classList.add('hidden');
    }

    updateWinnerUi() {
        if (this.startTimeMs == null || this.finishAMs == null || this.finishBMs == null) {
            return;
        }
        const a = this.finishAMs - this.startTimeMs;
        const b = this.finishBMs - this.startTimeMs;
        this.clearLaneHighlights();
        if (a === b) {
            this.el.winner.textContent = 'Tie';
            this.el.winner.classList.remove('hidden');
            return;
        }
        const aWin = a < b;
        this.el.winner.textContent = aWin ? 'Lane A wins' : 'Lane B wins';
        this.el.winner.classList.remove('hidden');
        this.el.laneACard.classList.add('ring-2', aWin ? 'ring-emerald-500' : 'ring-red-500', aWin ? 'bg-emerald-950/30' : 'bg-red-950/30');
        this.el.laneBCard.classList.add('ring-2', !aWin ? 'ring-emerald-500' : 'ring-red-500', !aWin ? 'bg-emerald-950/30' : 'bg-red-950/30');
        this.el.laneABadge.textContent = aWin ? 'WIN' : 'LOSS';
        this.el.laneBBadge.textContent = !aWin ? 'WIN' : 'LOSS';
        this.el.laneABadge.classList.remove('hidden');
        this.el.laneBBadge.classList.remove('hidden');
        this.el.laneABadge.classList.toggle('bg-emerald-600', aWin);
        this.el.laneABadge.classList.toggle('bg-red-600', !aWin);
        this.el.laneBBadge.classList.toggle('bg-emerald-600', !aWin);
        this.el.laneBBadge.classList.toggle('bg-red-600', aWin);
    }

    mainDisplayMs() {
        if (this.phase === 'idle' || this.startTimeMs == null) {
            return 0;
        }
        if (this.finishAMs != null && this.finishBMs != null) {
            return Math.max(this.finishAMs - this.startTimeMs, this.finishBMs - this.startTimeMs);
        }
        if (this.perfRace0 != null) {
            return performance.now() - this.perfRace0;
        }
        const est = this.estServerNow() - this.startTimeMs;
        return Math.max(0, est);
    }

    renderFrame() {
        this.el.main.textContent = formatElapsed(this.mainDisplayMs());
    }

    tick() {
        this.renderFrame();
        if (this.phase === 'running' && (this.finishAMs == null || this.finishBMs == null)) {
            this.rafId = requestAnimationFrame(this.boundTick);
        }
    }

    applyState(s) {
        this.phase = s.phase;
        this.startTimeMs = s.startTimeMs;
        this.finishAMs = s.finishAMs;
        this.finishBMs = s.finishBMs;

        if (s.finishAMs != null && s.startTimeMs != null) {
            this.el.laneATime.textContent = formatElapsed(s.finishAMs - s.startTimeMs);
        } else {
            this.el.laneATime.textContent = '—.———';
        }
        if (s.finishBMs != null && s.startTimeMs != null) {
            this.el.laneBTime.textContent = formatElapsed(s.finishBMs - s.startTimeMs);
        } else {
            this.el.laneBTime.textContent = '—.———';
        }

        if (s.phase === 'running' && s.startTimeMs != null) {
            const remain = s.startTimeMs - this.estServerNow();
            const countdownMs = remain > 2600 ? 3000 : 0;
            const key = String(s.startTimeMs);
            if (this.appliedRaceStartKey !== key) {
                this.appliedRaceStartKey = key;
                if (remain > 80) {
                    this.scheduleRaceStarted(s.startTimeMs, countdownMs);
                } else {
                    this.clearScheduled();
                    const lateBy = Math.max(0, -remain);
                    this.beginRaceClock({ silent: lateBy > 500 });
                    this.perfRace0 = performance.now() - lateBy;
                }
            }
        } else {
            if (s.phase === 'idle') {
                this.appliedRaceStartKey = null;
            }
            this.clearScheduled();
            this.perfRace0 = null;
        }

        if (s.phase === 'finished') {
            this.updateWinnerUi();
        } else {
            this.el.winner.classList.add('hidden');
            this.clearLaneHighlights();
        }

        if (Array.isArray(s.history)) {
            this.history = s.history;
            this.renderHistory();
        }

        this.updateButtons();
        this.renderFrame();
    }

    updateButtons() {
        const idle = this.phase === 'idle';
        const running = this.phase === 'running';
        const finished = this.phase === 'finished';

        this.el.btnStart.disabled = !idle;
        this.el.btnStopA.disabled = !running || this.finishAMs != null;
        this.el.btnStopB.disabled = !running || this.finishBMs != null;
        this.el.chkCountdown.disabled = !idle;
    }

    async refreshState() {
        const raw = await fetchJson(this.root.dataset.stateUrl);
        this.applyState(normalizeState(raw));
    }

    async postStart() {
        const countdown = this.el.chkCountdown.checked;
        const raw = await fetchJson(this.root.dataset.startUrl, {
            method: 'POST',
            body: JSON.stringify({ countdown }),
        });
        this.applyState(normalizeState(raw));
    }

    async postStopA() {
        const raw = await fetchJson(this.root.dataset.stopAUrl, { method: 'POST', body: '{}' });
        this.applyState(normalizeState(raw));
    }

    async postStopB() {
        const raw = await fetchJson(this.root.dataset.stopBUrl, { method: 'POST', body: '{}' });
        this.applyState(normalizeState(raw));
    }

    async postReset() {
        const raw = await fetchJson(this.root.dataset.resetUrl, { method: 'POST', body: '{}' });
        this.applyState(normalizeState(raw));
        this.hideOverlays();
    }

    async postClearHistory() {
        const raw = await fetchJson(this.root.dataset.clearHistoryUrl, { method: 'POST', body: '{}' });
        this.applyState(normalizeState(raw));
    }

    bindUi() {
        this.el.btnStart.addEventListener('click', () => this.postStart().catch((e) => alert(e.message)));
        this.el.btnStopA.addEventListener('click', () => this.postStopA().catch((e) => alert(e.message)));
        this.el.btnStopB.addEventListener('click', () => this.postStopB().catch((e) => alert(e.message)));
        this.el.btnReset.addEventListener('click', () => this.postReset().catch((e) => alert(e.message)));
        this.el.btnClearHistory?.addEventListener('click', () => {
            if (!window.confirm('Hapus seluruh race log di semua perangkat?')) {
                return;
            }
            this.postClearHistory().catch((e) => alert(e.message));
        });

        window.addEventListener('keydown', (ev) => {
            if (ev.target.matches('input, textarea, select, button')) {
                return;
            }
            const k = ev.key.toLowerCase();
            if (ev.code === 'Space') {
                ev.preventDefault();
                if (!this.el.btnStart.disabled) {
                    this.postStart().catch(() => {});
                }
            } else if (k === 'a' && !this.el.btnStopA.disabled) {
                ev.preventDefault();
                this.postStopA().catch(() => {});
            } else if (k === 'l' && !this.el.btnStopB.disabled) {
                ev.preventDefault();
                this.postStopB().catch(() => {});
            } else if (k === 'r') {
                ev.preventDefault();
                this.postReset().catch(() => {});
            }
        });
    }

    setConnection(text, ok) {
        this.el.pill.textContent = text;
        this.el.pill.classList.toggle('border-emerald-600', ok);
        this.el.pill.classList.toggle('text-emerald-400', ok);
        this.el.pill.classList.toggle('border-red-600', !ok && text !== 'Connecting…');
        this.el.pill.classList.toggle('text-red-400', !ok && text !== 'Connecting…');
    }

    initEcho() {
        const driver = this.root.dataset.broadcastDriver;
        if (driver === 'log' || driver === 'null') {
            this.el.hint.textContent =
                'Broadcast driver is log/null — configure Pusher or Reverb in .env for multi-device sync.';
            this.el.hint.classList.remove('hidden');
            this.setConnection('No WebSocket', false);
            return;
        }

        this.echo = buildEcho(this.root);
        if (!this.echo) {
            this.el.hint.textContent = 'Set PUSHER_APP_KEY (and optional PUSHER_HOST for Soketi) for WebSocket sync.';
            this.el.hint.classList.remove('hidden');
            this.setConnection('No Pusher key', false);
            return;
        }

        this.channel = this.echo.private('drag-race-timer');
        this.channel.listen('.RaceStarted', (e) => this.onRaceStarted(e));
        this.channel.listen('.LaneFinished', (e) => this.onLaneFinished(e));
        this.channel.listen('.RaceReset', () => this.onRaceReset());
        this.channel.listen('.RaceHistoryUpdated', (e) => {
            if (Array.isArray(e.history)) {
                this.history = e.history;
                this.renderHistory();
            }
        });

        this.echo.connector.pusher.connection.bind('connected', () => {
            window.__dragRaceSocketId = this.echo.socketId();
            this.setConnection('Live', true);
            this.refreshState().catch(() => {});
        });
        this.echo.connector.pusher.connection.bind('disconnected', () => {
            this.setConnection('Disconnected', false);
        });
        this.echo.connector.pusher.connection.bind('unavailable', () => {
            this.setConnection('Unavailable', false);
        });
        this.echo.connector.pusher.connection.bind('failed', () => {
            this.setConnection('Failed', false);
        });
    }

    async boot() {
        this.bindUi();
        await this.syncSkew().catch(() => {
            this.skewMs = 0;
        });

        let initialHist = [];
        try {
            initialHist = JSON.parse(this.root.dataset.initialHistory || '[]');
        } catch {
            initialHist = [];
        }
        this.history = Array.isArray(initialHist) ? initialHist : [];
        this.renderHistory();

        const initial = JSON.parse(this.root.dataset.initialState || '{}');
        this.applyState(normalizeState(initial));

        this.initEcho();
        await this.refreshState().catch(() => {});
    }
}

document.addEventListener('DOMContentLoaded', () => {
    const root = document.getElementById('drag-race-root');
    if (root) {
        const app = new DragRaceTimerApp(root);
        app.boot();
    }
});
