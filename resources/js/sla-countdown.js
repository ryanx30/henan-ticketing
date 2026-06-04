/**
 * SLA countdown helper for ticket cards and queue rows.
 * Calculates remaining time and breach display on the client using API-provided deadlines.
 */

(function () {
  function pad2(n) {
    return String(n).padStart(2, "0");
  }

  // format :"-hh:mm:ss"
  function formatDashHHMMSS(ms) {
    const abs = Math.abs(ms);
    const totalSeconds = Math.floor(abs / 1000);

    const hours = Math.floor(totalSeconds / 3600);
    const minutes = Math.floor((totalSeconds % 3600) / 60);
    const seconds = totalSeconds % 60;

    return `-${pad2(hours)}:${pad2(minutes)}:${pad2(seconds)}`;
  }

  function tick() {
    const now = Date.now();

    document.querySelectorAll(".sla-countdown").forEach((el) => {
      const raw = el.dataset.deadline;
      const deadline = new Date(raw).getTime();

      if (Number.isNaN(deadline)) {
        el.textContent = "--:--:--";
        return;
      }

      const remaining = deadline - now;
      el.textContent = formatDashHHMMSS(remaining);

      if (remaining < 0) {
        el.classList.add("text-red-600");
        el.classList.remove("text-slate-800");
      } else {
        el.classList.add("text-slate-800");
        el.classList.remove("text-red-600");
      }
    });
  }

  function boot() {
    if (!document.querySelector(".sla-countdown")) return;
    tick();
    setInterval(tick, 1000);
  }

  // DOM ready
  if (document.readyState === "loading") {
    document.addEventListener("DOMContentLoaded", boot);
  } else {
    boot();
  }
})();