/**
 * FoodFusion — shared UI (auth pages)
 * Password visibility toggles + login lock countdown
 */
(function () {
  "use strict";

  function initPasswordToggles() {
    document.querySelectorAll(".password-toggle").forEach(function (btn) {
      var id = btn.getAttribute("aria-controls");
      var wrap = btn.closest(".password-field-wrap");
      var input =
        (id && document.getElementById(id)) ||
        (wrap && wrap.querySelector(".input-box"));

      if (!input) {
        return;
      }

      btn.addEventListener("click", function () {
        var show = input.type === "password";
        input.type = show ? "text" : "password";
        btn.setAttribute("aria-pressed", show ? "true" : "false");
        btn.textContent = show ? "Hide" : "Show";
      });
    });
  }

  function initLockCountdown() {
    var el = document.getElementById("lock-countdown");
    if (!el || !el.dataset.seconds) {
      return;
    }

    var remaining = parseInt(el.dataset.seconds, 10);
    if (isNaN(remaining) || remaining <= 0) {
      return;
    }

    function formatTime(totalSec) {
      var m = Math.floor(totalSec / 60);
      var s = totalSec % 60;
      return m + ":" + (s < 10 ? "0" : "") + s;
    }

    function tick() {
      if (remaining <= 0) {
        el.textContent = "You can try again now";
        window.setTimeout(function () {
          window.location.reload();
        }, 400);
        return;
      }
      el.textContent = "Try again in " + formatTime(remaining);
      remaining -= 1;
    }

    tick();
    window.setInterval(tick, 1000);
  }

  if (document.readyState === "loading") {
    document.addEventListener("DOMContentLoaded", function () {
      initPasswordToggles();
      initLockCountdown();
    });
  } else {
    initPasswordToggles();
    initLockCountdown();
  }
})();
