/**
 * FoodFusion — Fusion Assistant UI
 * Placeholder: outgoing text is logged to the console. Wire your HF / api/chat.php
 * handler here later (e.g. fetch + append assistant reply).
 */
(function () {
  "use strict";

  var SEL_ROOT = "[data-fusion-chat]";

  function onReady(fn) {
    if (document.readyState === "loading") {
      document.addEventListener("DOMContentLoaded", fn);
    } else {
      fn();
    }
  }

  /**
   * Placeholder pipeline: log user text. Replace body with API call + UI update.
   * @param {string} text
   */
  function fusionChatHandleOutgoing(text) {
    console.log("[Fusion Assistant] outgoing message:", text);
  }

  function initFusionChat(root) {
    if (!root) {
      return;
    }

    var launcher = root.querySelector("#fusion-chat-launcher");
    var windowEl = root.querySelector("#fusion-chat-window");
    var closeBtn = root.querySelector("#fusion-chat-close");
    var form = root.querySelector("#fusion-chat-form");
    var input = root.querySelector("#fusion-chat-input");
    var messages = root.querySelector("#fusion-chat-messages");

    if (!launcher || !windowEl || !form || !input || !messages) {
      return;
    }

    function isOpen() {
      return root.getAttribute("data-open") === "true";
    }

    function setOpen(open) {
      root.setAttribute("data-open", open ? "true" : "false");
      launcher.setAttribute("aria-expanded", open ? "true" : "false");
      launcher.setAttribute("aria-label", open ? "Close Fusion Assistant" : "Open Fusion Assistant");
      windowEl.setAttribute("aria-hidden", open ? "false" : "true");
      if (open) {
        window.setTimeout(function () {
          input.focus();
        }, 200);
      }
    }

    launcher.addEventListener("click", function () {
      setOpen(!isOpen());
    });

    if (closeBtn) {
      closeBtn.addEventListener("click", function () {
        setOpen(false);
        launcher.focus();
      });
    }

    document.addEventListener("keydown", function (e) {
      if (e.key === "Escape" && isOpen()) {
        e.preventDefault();
        setOpen(false);
        launcher.focus();
      }
    });

    function appendUserBubble(text) {
      var p = document.createElement("p");
      p.className = "fusion-chat__msg fusion-chat__msg--user";
      p.textContent = text;
      messages.appendChild(p);
      messages.scrollTop = messages.scrollHeight;
    }

    form.addEventListener("submit", function (e) {
      e.preventDefault();
      var text = input.value.trim();
      if (!text) {
        return;
      }
      fusionChatHandleOutgoing(text);
      appendUserBubble(text);
      input.value = "";
      input.focus();
    });
  }

  onReady(function () {
    var root = document.querySelector(SEL_ROOT);
    initFusionChat(root);
  });
})();
