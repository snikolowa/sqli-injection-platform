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

    function ensureToastContainer() {
      let el = document.querySelector('[data-hint-toast-container]');
      if (el) return el;
      el = document.createElement('div');
      el.setAttribute('data-hint-toast-container', '1');
      el.className = 'toast-container position-fixed top-0 end-0 p-3';
      document.body.appendChild(el);
      return el;
    }

    function getHintTitle(btn) {
      const clone = btn.cloneNode(true);
      const cd = clone.querySelector('[data-hint-countdown]');
      if (cd) cd.remove();
      return clone.textContent.replace(/\s+/g, ' ').trim();
    }

    function showToast(message) {
      if (typeof bootstrap === 'undefined') return;
      const containerEl = ensureToastContainer();
      const toastEl = document.createElement('div');
      toastEl.className = 'toast align-items-center text-bg-dark border-0';
      toastEl.setAttribute('role', 'status');
      toastEl.setAttribute('aria-live', 'polite');
      toastEl.setAttribute('aria-atomic', 'true');
      toastEl.innerHTML = `
        <div class="d-flex">
          <div class="toast-body">${message}</div>
          <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast" aria-label="Close"></button>
        </div>
      `;
      containerEl.appendChild(toastEl);
      const toast = new bootstrap.Toast(toastEl, { delay: 3000 });
      toast.show();
      toastEl.addEventListener('hidden.bs.toast', () => toastEl.remove());
    }

    function tick() {
      const elapsed = Date.now() - startMs;

      hintButtons.forEach((btn) => {
        const unlockSeconds = Number(btn.getAttribute('data-hint-unlock')) || 0;
        const unlockMs = unlockSeconds * 1000;
        const cd = btn.querySelector('[data-hint-countdown]');

        if (elapsed >= unlockMs) {
          if (!btn.dataset.hintUnlocked) {
            btn.dataset.hintUnlocked = '1';
            const title = getHintTitle(btn);
            showToast(`Отключена подсказка: ${title}`);
          }
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
