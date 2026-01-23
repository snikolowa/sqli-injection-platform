(function () {
  function pad(n) { return String(n).padStart(2, '0'); }

  function fmt(ms) {
    const total = Math.max(0, Math.floor(ms / 1000));
    const m = Math.floor(total / 60);
    const s = total % 60;
    return `${pad(m)}:${pad(s)}`;
  }

  // Auto-expand accordion item without Bootstrap JS API calls
  function expandAccordionItem(btn) {
    const target = btn.getAttribute('data-bs-target') || btn.getAttribute('data-target');
    if (!target || target[0] !== '#') return;

    const collapseEl = document.querySelector(target);
    if (!collapseEl) return;

    btn.classList.remove('collapsed');
    btn.setAttribute('aria-expanded', 'true');
    collapseEl.classList.add('show');
  }

  function initContainer(container) {
    const hintButtons = Array.from(container.querySelectorAll('button[data-hint-unlock]'));
    if (!hintButtons.length) return;

    // ✅ restart timer every page open/refresh
    const startMs = Date.now();

    // solved flag -> unlock + expand all hints as "Разбор"
    const status = document.querySelector('#exercise-status[data-solved]');
    const solved = status && status.getAttribute('data-solved') === '1';

    if (solved) {
      hintButtons.forEach((btn) => {
        btn.disabled = false;
        const cd = btn.querySelector('[data-hint-countdown]');
        if (cd) cd.textContent = 'Разбор';
        expandAccordionItem(btn);
      });
      return;
    }

    function tick() {
      const elapsed = Date.now() - startMs;

      hintButtons.forEach((btn) => {
        const unlockSeconds = Number(btn.getAttribute('data-hint-unlock')) || 0;
        const unlockMs = unlockSeconds * 1000;
        const cd = btn.querySelector('[data-hint-countdown]');

        if (elapsed >= unlockMs) {
          btn.disabled = false;
          if (cd) cd.textContent = 'Налична';
        } else {
          btn.disabled = true;
          if (cd) cd.textContent = `Налична след ${fmt(unlockMs - elapsed)}`;
        }
      });
    }

    tick();
    setInterval(tick, 250);

    // Prevent opening disabled accordion buttons
    container.addEventListener('click', (e) => {
      const btn = e.target.closest('button[data-hint-unlock]');
      if (btn && btn.disabled) {
        e.preventDefault();
        e.stopPropagation();
      }
    }, true);
  }

  document.addEventListener('DOMContentLoaded', () => {
    document.querySelectorAll('[data-hints]').forEach(initContainer);
  });
})();
