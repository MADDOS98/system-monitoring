/**
 * Adaptive polling helper.
 *
 * createPoller({ url, getUrl?, intervalMs, onData, onError? })
 *   - url:        endpoint static (string) sau...
 *   - getUrl:     functie care intoarce URL-ul curent (apelata la fiecare tick)
 *                 — folosita cand query string-ul depinde de starea componentei
 *   - intervalMs: interval initial (ms)
 *   - onData:     callback (payload) la fiecare raspuns 2xx
 *   - onError:    callback (err) optional
 *
 * Comportament:
 *   - start():        apel imediat o data, apoi setInterval
 *   - stop():         clearInterval + abort request in zbor (AbortController)
 *   - setInterval(ms): reconfigurare la cald
 *   - pauza automata pe document.visibilityState === 'hidden'
 *   - anti-overlap (sare peste tick daca request anterior inca in zbor)
 *   - backoff exponential pe erori (1s -> 2s -> 4s -> max 30s, reset la succes)
 */

export function createPoller({ url, getUrl, intervalMs, onData, onError }) {
    let timer       = null;
    let abortCtrl   = null;
    let inFlight    = false;
    let stopped     = true;
    let currentMs   = Math.max(250, intervalMs || 1000);
    let backoffMs   = 0;

    function resolveUrl() {
        return typeof getUrl === 'function' ? getUrl() : url;
    }

    async function tick() {
        if (stopped) return;
        if (document.visibilityState === 'hidden') return;
        if (inFlight) return;

        const target = resolveUrl();
        if (!target) return;

        inFlight = true;
        abortCtrl = new AbortController();
        try {
            const res = await fetch(target, {
                signal: abortCtrl.signal,
                credentials: 'same-origin',
                headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
            });
            if (!res.ok) throw new Error(`HTTP ${res.status}`);
            const data = await res.json();
            backoffMs = 0;
            onData?.(data);
        } catch (e) {
            if (e.name === 'AbortError') return;
            backoffMs = backoffMs === 0 ? 1000 : Math.min(backoffMs * 2, 30000);
            onError?.(e);
            schedule(backoffMs); // re-schedule cu backoff in loc de interval normal
            inFlight = false;
            return;
        } finally {
            inFlight = false;
            abortCtrl = null;
        }
    }

    function schedule(ms) {
        if (timer) clearInterval(timer);
        timer = setInterval(tick, ms);
    }

    function start() {
        if (!stopped) return;
        stopped = false;
        tick();
        schedule(currentMs);
    }

    function stop() {
        stopped = true;
        if (timer) { clearInterval(timer); timer = null; }
        if (abortCtrl) { abortCtrl.abort(); abortCtrl = null; }
        inFlight = false;
    }

    function setIntervalMs(ms) {
        const next = Math.max(250, ms || 1000);
        if (next === currentMs) return;
        currentMs = next;
        if (!stopped && timer) {
            schedule(currentMs);
        }
    }

    // Page Visibility — pauza/restart automat
    document.addEventListener('visibilitychange', () => {
        if (stopped) return;
        if (document.visibilityState === 'visible') {
            tick();
            schedule(currentMs);
        } else {
            if (timer) { clearInterval(timer); timer = null; }
            if (abortCtrl) { abortCtrl.abort(); abortCtrl = null; }
            inFlight = false;
        }
    });

    return { start, stop, setInterval: setIntervalMs, tick };
}

// Expune global pentru blade @script
window.createPoller = createPoller;
