/**
 * FoodFusion — site chrome: navigation, modal, carousel, cookie consent
 */
(function () {
  "use strict";

  var COOKIE_KEY = "foodfusion_cookie_consent";

  function onReady(fn) {
    if (document.readyState === "loading") {
      document.addEventListener("DOMContentLoaded", fn);
    } else {
      fn();
    }
  }

  function initNav() {
    var toggle = document.getElementById("nav-toggle");
    var nav = document.getElementById("site-nav");
    if (!toggle || !nav) {
      return;
    }

    toggle.addEventListener("click", function () {
      var open = nav.classList.toggle("is-open");
      toggle.setAttribute("aria-expanded", open ? "true" : "false");
      var header = document.querySelector(".site-header");
      if (header) {
        header.classList.remove("site-header--hidden");
      }
    });

    nav.querySelectorAll("a").forEach(function (link) {
      link.addEventListener("click", function () {
        nav.classList.remove("is-open");
        toggle.setAttribute("aria-expanded", "false");
      });
    });
  }

  function syncSiteHeaderOffset() {
    var header = document.querySelector(".site-header");
    if (!header) {
      return;
    }
    document.documentElement.style.setProperty("--site-header-offset", header.offsetHeight + "px");
  }

  function initHeaderScrollHide() {
    var header = document.querySelector(".site-header");
    if (!header) {
      return;
    }

    syncSiteHeaderOffset();
    window.addEventListener(
      "resize",
      function () {
        syncSiteHeaderOffset();
      },
      { passive: true }
    );
    window.addEventListener("load", syncSiteHeaderOffset);

    if (window.matchMedia("(prefers-reduced-motion: reduce)").matches) {
      return;
    }

    var lastY = window.scrollY || 0;
    var threshold = 6;
    var ticking = false;

    function navBlockingChrome() {
      var nav = document.getElementById("site-nav");
      var accountBtn = document.getElementById("site-account-btn");
      if (nav && nav.classList.contains("is-open")) {
        return true;
      }
      if (accountBtn && accountBtn.getAttribute("aria-expanded") === "true") {
        return true;
      }
      return false;
    }

    function update() {
      ticking = false;
      var y = window.scrollY || 0;
      if (navBlockingChrome()) {
        header.classList.remove("site-header--hidden");
        lastY = y;
        return;
      }

      if (y < 12) {
        header.classList.remove("site-header--hidden");
      } else if (y > lastY + threshold) {
        header.classList.add("site-header--hidden");
      } else if (y < lastY - threshold) {
        header.classList.remove("site-header--hidden");
      }
      lastY = y;
    }

    window.addEventListener(
      "scroll",
      function () {
        if (!ticking) {
          ticking = true;
          window.requestAnimationFrame(update);
        }
      },
      { passive: true }
    );
  }

  function initAccountMenu() {
    var btn = document.getElementById("site-account-btn");
    var menu = document.getElementById("site-account-menu");
    if (!btn || !menu) {
      return;
    }

    function setOpen(open) {
      btn.setAttribute("aria-expanded", open ? "true" : "false");
      if (open) {
        menu.removeAttribute("hidden");
      } else {
        menu.setAttribute("hidden", "");
      }
    }

    function isOpen() {
      return btn.getAttribute("aria-expanded") === "true";
    }

    btn.addEventListener("click", function (e) {
      e.stopPropagation();
      setOpen(!isOpen());
    });

    document.addEventListener("click", function (e) {
      if (!isOpen()) {
        return;
      }
      if (menu.contains(e.target) || btn.contains(e.target)) {
        return;
      }
      setOpen(false);
    });

    document.addEventListener("keydown", function (e) {
      if (e.key === "Escape" && isOpen()) {
        setOpen(false);
        btn.focus();
      }
    });
  }

  function trapFocus(dialog, previouslyFocused) {
    var focusables = dialog.querySelectorAll(
      'a[href], button:not([disabled]), textarea, input, select, [tabindex]:not([tabindex="-1"])'
    );
    var list = Array.prototype.slice.call(focusables).filter(function (el) {
      return el.offsetParent !== null || el === dialog.querySelector(".modal__close");
    });
    if (list.length === 0) {
      return function () {};
    }
    var first = list[0];
    var last = list[list.length - 1];

    function onKeydown(e) {
      if (e.key !== "Tab") {
        return;
      }
      if (e.shiftKey) {
        if (document.activeElement === first) {
          e.preventDefault();
          last.focus();
        }
      } else {
        if (document.activeElement === last) {
          e.preventDefault();
          first.focus();
        }
      }
    }

    dialog.addEventListener("keydown", onKeydown);
    first.focus();

    return function () {
      dialog.removeEventListener("keydown", onKeydown);
      if (previouslyFocused && previouslyFocused.focus) {
        previouslyFocused.focus();
      }
    };
  }

  function openModal(modal, releasePrevious) {
    if (releasePrevious) {
      releasePrevious();
    }
    modal.removeAttribute("hidden");
    modal.classList.add("modal--open");
    document.body.classList.add("modal-open");
    var prev = document.activeElement;
    return trapFocus(modal.querySelector(".modal__dialog") || modal, prev);
  }

  function closeModal(modal, releaseTrap) {
    if (typeof releaseTrap === "function") {
      releaseTrap();
    }
    modal.classList.remove("modal--open");
    modal.setAttribute("hidden", "");
    document.body.classList.remove("modal-open");
  }

  function initSignupModal() {
    var modal = document.getElementById("signup-modal");
    if (!modal) {
      return;
    }

    var releaseTrap = null;
    var backdrop = modal.querySelector(".modal__backdrop");

    function doClose() {
      closeModal(modal, releaseTrap);
      releaseTrap = null;
    }

    modal.querySelectorAll("[data-modal-close]").forEach(function (el) {
      el.addEventListener("click", doClose);
    });

    modal.addEventListener("click", function (e) {
      if (backdrop && e.target === backdrop) {
        doClose();
      }
    });

    document.addEventListener("keydown", function (e) {
      if (e.key === "Escape" && modal.classList.contains("modal--open")) {
        doClose();
      }
    });

    function doOpen() {
      releaseTrap = openModal(modal, releaseTrap);
    }

    var openBtn = document.getElementById("open-signup-modal");
    if (openBtn) {
      openBtn.addEventListener("click", doOpen);
    }

    document.querySelectorAll("[data-modal-open]").forEach(function (el) {
      if (el.id === "open-signup-modal") {
        return;
      }
      el.addEventListener("click", doOpen);
    });

    if (modal.getAttribute("data-open-on-load") === "true") {
      doOpen();
    } else if (window.location.hash === "#signup-modal") {
      doOpen();
    }
  }

  function initCarousel() {
    document.querySelectorAll("[data-carousel]").forEach(function (root) {
      var track = root.querySelector("[data-carousel-track]");
      var prev = root.querySelector("[data-carousel-prev]");
      var next = root.querySelector("[data-carousel-next]");
      if (!track || !prev || !next) {
        return;
      }

      function step() {
        var slide = track.querySelector(".carousel__slide");
        if (!slide) {
          return 280;
        }
        var gap = parseFloat(getComputedStyle(track).gap) || 16;
        return slide.getBoundingClientRect().width + gap;
      }

      prev.addEventListener("click", function () {
        track.scrollBy({ left: -step(), behavior: "smooth" });
      });
      next.addEventListener("click", function () {
        track.scrollBy({ left: step(), behavior: "smooth" });
      });

      var autoAttr = root.getAttribute("data-carousel-auto");
      if (autoAttr === null || autoAttr === "false") {
        return;
      }

      var reduceMotion = window.matchMedia("(prefers-reduced-motion: reduce)").matches;
      if (reduceMotion) {
        return;
      }

      var intervalMs = parseInt(root.getAttribute("data-carousel-interval") || "6000", 10);
      if (intervalMs < 2500) {
        intervalMs = 2500;
      }

      var autoTimer = null;

      function clearAutoTimer() {
        if (autoTimer !== null) {
          window.clearInterval(autoTimer);
          autoTimer = null;
        }
      }

      function advanceAuto() {
        var maxScroll = track.scrollWidth - track.clientWidth - 2;
        if (maxScroll <= 1) {
          return;
        }
        var delta = step();
        if (track.scrollLeft >= maxScroll - 4) {
          track.scrollTo({ left: 0, behavior: "smooth" });
        } else {
          track.scrollBy({ left: delta, behavior: "smooth" });
        }
      }

      function startAutoTimer() {
        clearAutoTimer();
        autoTimer = window.setInterval(advanceAuto, intervalMs);
      }

      root.addEventListener("mouseenter", clearAutoTimer);
      root.addEventListener("mouseleave", startAutoTimer);
      root.addEventListener("focusin", clearAutoTimer);
      root.addEventListener("focusout", function (e) {
        if (!root.contains(e.relatedTarget)) {
          startAutoTimer();
        }
      });

      startAutoTimer();
    });
  }

  function initHeroBackgroundCarousel() {
    var root = document.querySelector("[data-hero-carousel]");
    if (!root) {
      return;
    }

    var slides = Array.prototype.slice.call(root.querySelectorAll("[data-hero-slide]"));
    if (slides.length === 0) {
      return;
    }

    var eventCopy = document.getElementById("hero-event-copy");
    var eventTitle = document.getElementById("hero-event-title");
    var eventBlurb = document.getElementById("hero-event-blurb");
    var eventsSlot = document.getElementById("hero-events-slot");
    var heroSection = root.closest(".hero");
    var reduceMotion = window.matchMedia("(prefers-reduced-motion: reduce)").matches;

    if (slides.length < 2 || reduceMotion) {
      if (heroSection) {
        heroSection.classList.remove("hero--intro-wait");
        heroSection.classList.remove("hero--intro-exiting");
        heroSection.classList.remove("hero--mode-carousel");
      }
      if (slides[0]) {
        slides[0].classList.add("is-active");
        slides[0].setAttribute("aria-hidden", "false");
      }
      slides.forEach(function (el, i) {
        if (i !== 0) {
          el.setAttribute("aria-hidden", "true");
        }
      });
      if (eventCopy) {
        eventCopy.classList.add("is-visible");
      }
      if (eventsSlot) {
        eventsSlot.setAttribute("aria-hidden", "false");
      }
      return;
    }

    var INTRO_WAIT_MS = 5000;
    var WELCOME_FADE_MS = 720;
    var CAPTION_OUT_MS = 600;
    var IMAGE_FALLBACK_MS = 1700;
    var CAROUSEL_HOLD_MS = 5000;

    var idx = 0;
    var timers = [];
    var paused = false;
    var carouselStarted = false;

    function clearTimers() {
      timers.forEach(function (id) {
        window.clearTimeout(id);
      });
      timers = [];
    }

    function after(ms, fn) {
      var id = window.setTimeout(function () {
        timers = timers.filter(function (t) {
          return t !== id;
        });
        fn();
      }, ms);
      timers.push(id);
      return id;
    }

    function waitOpacityEnd(el, maxMs, done) {
      var finished = false;
      function cleanup() {
        el.removeEventListener("transitionend", onEnd);
      }
      function finish() {
        if (finished) {
          return;
        }
        finished = true;
        cleanup();
        done();
      }
      function onEnd(e) {
        if (e.target !== el || e.propertyName !== "opacity") {
          return;
        }
        finish();
      }
      el.addEventListener("transitionend", onEnd);
      window.setTimeout(finish, maxMs);
    }

    function beginCarouselCycle() {
      if (paused) {
        return;
      }

      var next = (idx + 1) % slides.length;
      var outgoing = slides[idx];
      var incoming = slides[next];
      var copyBox = eventCopy;

      if (!outgoing || !incoming) {
        return;
      }

      if (copyBox) {
        copyBox.classList.remove("is-visible");
      }

      after(CAPTION_OUT_MS, function () {
        if (paused) {
          return;
        }

        incoming.classList.add("is-active");
        incoming.setAttribute("aria-hidden", "false");
        outgoing.classList.remove("is-active");
        outgoing.setAttribute("aria-hidden", "true");

        waitOpacityEnd(incoming, IMAGE_FALLBACK_MS, function () {
          if (paused) {
            return;
          }

          var label = incoming.getAttribute("data-hero-caption") || "";
          var blurb = incoming.getAttribute("data-hero-blurb") || "";
          if (eventTitle) {
            eventTitle.textContent = label;
          }
          if (eventBlurb) {
            eventBlurb.textContent = blurb;
          }
          if (copyBox) {
            copyBox.classList.remove("is-visible");
            window.requestAnimationFrame(function () {
              window.requestAnimationFrame(function () {
                if (copyBox && !paused) {
                  copyBox.classList.add("is-visible");
                }
              });
            });
          }

          idx = next;
          after(CAROUSEL_HOLD_MS, function () {
            if (!paused) {
              beginCarouselCycle();
            }
          });
        });
      });
    }

    function finishIntroAndStartCarousel() {
      if (paused || !heroSection) {
        return;
      }

      heroSection.classList.remove("hero--intro-wait");
      heroSection.classList.remove("hero--intro-exiting");
      heroSection.classList.add("hero--mode-carousel");
      carouselStarted = true;

      var first = slides[0];
      if (first) {
        slides.forEach(function (el, i) {
          el.classList.toggle("is-active", i === 0);
          el.setAttribute("aria-hidden", i === 0 ? "false" : "true");
        });
        idx = 0;
      }

      if (first) {
        if (eventTitle) {
          eventTitle.textContent = first.getAttribute("data-hero-caption") || "";
        }
        if (eventBlurb) {
          eventBlurb.textContent = first.getAttribute("data-hero-blurb") || "";
        }
      }
      if (eventCopy) {
        eventCopy.classList.remove("is-visible");
        window.requestAnimationFrame(function () {
          window.requestAnimationFrame(function () {
            if (eventCopy && !paused) {
              eventCopy.classList.add("is-visible");
            }
          });
        });
      }

      if (eventsSlot) {
        eventsSlot.setAttribute("aria-hidden", "false");
      }

      after(CAROUSEL_HOLD_MS, function () {
        if (!paused) {
          beginCarouselCycle();
        }
      });
    }

    function runIntroSequence() {
      if (paused || !heroSection) {
        return;
      }

      after(INTRO_WAIT_MS, function () {
        if (paused || !heroSection) {
          return;
        }
        heroSection.classList.add("hero--intro-exiting");
        after(WELCOME_FADE_MS, function () {
          if (paused) {
            return;
          }
          finishIntroAndStartCarousel();
        });
      });
    }

    function pause() {
      paused = true;
      clearTimers();
    }

    function resume() {
      paused = false;
      if (carouselStarted) {
        slides.forEach(function (el, i) {
          if (el.classList.contains("is-active")) {
            idx = i;
          }
        });
        if (eventCopy) {
          var active = slides[idx];
          if (active) {
            if (eventTitle) {
              eventTitle.textContent = active.getAttribute("data-hero-caption") || "";
            }
            if (eventBlurb) {
              eventBlurb.textContent = active.getAttribute("data-hero-blurb") || "";
            }
          }
          eventCopy.classList.add("is-visible");
        }
        if (eventsSlot) {
          eventsSlot.setAttribute("aria-hidden", "false");
        }
        after(CAROUSEL_HOLD_MS, function () {
          if (!paused) {
            beginCarouselCycle();
          }
        });
      } else {
        runIntroSequence();
      }
    }

    if (heroSection) {
      heroSection.addEventListener("mouseenter", pause);
      heroSection.addEventListener("mouseleave", resume);
      heroSection.addEventListener("focusin", pause);
      heroSection.addEventListener("focusout", function (e) {
        if (!heroSection.contains(e.relatedTarget)) {
          resume();
        }
      });
    }

    runIntroSequence();
  }

  function syncConsentAttribute() {
    try {
      var raw = localStorage.getItem(COOKIE_KEY);
      if (raw === "accepted") {
        document.documentElement.setAttribute("data-ff-consent", "all");
      } else if (raw === "essential") {
        document.documentElement.setAttribute("data-ff-consent", "essential");
      } else {
        document.documentElement.removeAttribute("data-ff-consent");
      }
    } catch (e) {
      document.documentElement.removeAttribute("data-ff-consent");
    }
  }

  /**
   * Global contact sidebar (footer "Get in Touch"). Omitted on contact.php (no drawer markup).
   * Uses delegated click for the open button so a missed early init (stale cache, partial DOM)
   * cannot leave the button inert. Overlay/drawer are resolved inside setOpen on each toggle.
   */
  function initContactSidebar() {
    var OPEN_CLASS = "is-open";
    var BODY_OPEN_CLASS = "contact-drawer-active";
    var lastFocus = null;

    function getDrawerEls() {
      return {
        overlay: document.getElementById("contact-drawer-overlay"),
        drawer: document.getElementById("contact-sidebar"),
        closeBtn: document.getElementById("contact-drawer-close"),
      };
    }

    function isOpen() {
      return document.body.classList.contains(BODY_OPEN_CLASS);
    }

    function setOpen(open) {
      var els = getDrawerEls();
      var overlay = els.overlay;
      var drawer = els.drawer;
      var closeBtn = els.closeBtn;
      if (!overlay || !drawer) {
        return;
      }

      if (open) {
        lastFocus = document.activeElement;
        document.body.classList.add(BODY_OPEN_CLASS);
        overlay.classList.add(OPEN_CLASS);
        drawer.classList.add(OPEN_CLASS);
        overlay.setAttribute("aria-hidden", "false");
        drawer.setAttribute("aria-hidden", "false");
        var focusTarget = closeBtn || drawer;
        if (focusTarget && typeof focusTarget.focus === "function") {
          focusTarget.focus();
        }
      } else {
        document.body.classList.remove(BODY_OPEN_CLASS);
        overlay.classList.remove(OPEN_CLASS);
        drawer.classList.remove(OPEN_CLASS);
        overlay.setAttribute("aria-hidden", "true");
        drawer.setAttribute("aria-hidden", "true");
        if (lastFocus && typeof lastFocus.focus === "function") {
          lastFocus.focus();
        }
        lastFocus = null;
      }
    }

    function onOverlayClick(e) {
      var overlay = getDrawerEls().overlay;
      if (overlay && e.target === overlay) {
        setOpen(false);
      }
    }

    function onDocumentKeydown(e) {
      if (e.key === "Escape" && isOpen()) {
        e.preventDefault();
        setOpen(false);
      }
    }

    document.addEventListener("click", function (e) {
      var t = e.target;
      if (!t || typeof t.closest !== "function") {
        return;
      }
      if (t.closest("#footer-contact-open")) {
        e.preventDefault();
        setOpen(true);
      }
    });

    var els0 = getDrawerEls();
    if (!els0.overlay || !els0.drawer) {
      return;
    }

    if (els0.closeBtn) {
      els0.closeBtn.addEventListener("click", function () {
        setOpen(false);
      });
    }
    els0.overlay.addEventListener("click", onOverlayClick);
    document.addEventListener("keydown", onDocumentKeydown);
  }

  function initCookieBanner() {
    var banner = document.getElementById("cookie-banner");
    if (!banner) {
      return;
    }

    var inner = banner.querySelector(".cookie-banner__inner");
    var status = document.getElementById("cookie-banner-status");
    var accept = document.getElementById("cookie-accept");
    var decline = document.getElementById("cookie-decline");
    var prefsBtn = document.getElementById("cookie-preferences-btn");

    var releaseTrap = null;
    var focusBeforeBanner = null;

    syncConsentAttribute();

    function announce(msg) {
      if (status) {
        status.textContent = msg;
      }
    }

    function applyChoice(storageValue, consentAttr, eventChoice) {
      try {
        localStorage.setItem(COOKIE_KEY, storageValue);
      } catch (e) {}
      document.documentElement.setAttribute("data-ff-consent", consentAttr);
      document.dispatchEvent(
        new CustomEvent("foodfusion:cookie-consent", {
          detail: { choice: eventChoice, storageValue: storageValue },
        })
      );
    }

    function closeBanner(message) {
      if (typeof releaseTrap === "function") {
        releaseTrap();
        releaseTrap = null;
      }
      banner.setAttribute("hidden", "");
      if (message) {
        announce(message);
      }
    }

    function openBanner() {
      if (typeof releaseTrap === "function") {
        releaseTrap();
        releaseTrap = null;
      }
      focusBeforeBanner = document.activeElement;
      banner.removeAttribute("hidden");
      if (inner) {
        releaseTrap = trapFocus(inner, focusBeforeBanner);
      }
      if (accept && accept.focus) {
        window.requestAnimationFrame(function () {
          accept.focus();
        });
      }
    }

    function onAccept() {
      applyChoice("accepted", "all", "all");
      closeBanner("Cookie preferences saved: all optional choices accepted.");
    }

    function onEssential() {
      applyChoice("essential", "essential", "essential");
      closeBanner("Cookie preferences saved: essential only.");
    }

    function hasStoredChoice() {
      try {
        var v = localStorage.getItem(COOKIE_KEY);
        return v === "accepted" || v === "essential";
      } catch (e) {
        return false;
      }
    }

    if (!hasStoredChoice()) {
      openBanner();
    }

    if (accept) {
      accept.addEventListener("click", onAccept);
    }
    if (decline) {
      decline.addEventListener("click", onEssential);
    }

    if (prefsBtn) {
      prefsBtn.addEventListener("click", function () {
        try {
          localStorage.removeItem(COOKIE_KEY);
        } catch (e) {}
        document.documentElement.removeAttribute("data-ff-consent");
        openBanner();
      });
    }
  }

  /**
   * Minimal FAB + card: Hugging Face is called via api/chat.php (token stays server-side).
   */
  function initAssistantFab() {
    var root = document.getElementById("assistant-root");
    var fab = document.getElementById("assistant-fab");
    var panel = document.getElementById("assistant-panel");
    var closeBtn = document.getElementById("assistant-close");
    var form = document.getElementById("assistant-form");
    var input = document.getElementById("assistant-input");
    var messages = document.getElementById("assistant-messages");
    if (!root || !fab || !panel || !form || !input || !messages) {
      return;
    }

    var welcomePlaced = false;
    var context = {};
    try {
      var rawCtx = document.body.getAttribute("data-assistant-context");
      if (rawCtx) {
        context = JSON.parse(rawCtx);
      }
    } catch (e1) {
      context = {};
    }

    function openPanel() {
      root.classList.add("assistant--open");
      fab.setAttribute("aria-expanded", "true");
      panel.setAttribute("aria-hidden", "false");
      if (!welcomePlaced) {
        welcomePlaced = true;
        var intro = document.createElement("p");
        intro.className = "assistant__msg assistant__msg--system";
        if (context.page === "recipe" && context.recipeTitle) {
          intro.textContent =
            'You are viewing "' +
            context.recipeTitle +
            '". Want tips on this recipe or an ingredient swap? Ask below.';
        } else {
          intro.textContent =
            "Ask a quick cooking question — substitutions, timing, or technique.";
        }
        messages.appendChild(intro);
      }
      window.setTimeout(function () {
        input.focus();
      }, 220);
    }

    function closePanel() {
      root.classList.remove("assistant--open");
      fab.setAttribute("aria-expanded", "false");
      panel.setAttribute("aria-hidden", "true");
    }

    function isAssistantOpen() {
      return root.classList.contains("assistant--open");
    }

    fab.addEventListener("click", function () {
      if (isAssistantOpen()) {
        closePanel();
      } else {
        openPanel();
      }
    });

    if (closeBtn) {
      closeBtn.addEventListener("click", function () {
        closePanel();
        fab.focus();
      });
    }

    document.addEventListener("keydown", function (e) {
      if (e.key === "Escape" && isAssistantOpen()) {
        e.preventDefault();
        closePanel();
        fab.focus();
      }
    });

    function appendBubble(text, role) {
      var el = document.createElement("p");
      el.className = "assistant__msg assistant__msg--" + role;
      el.textContent = text;
      messages.appendChild(el);
      messages.scrollTop = messages.scrollHeight;
    }

    form.addEventListener("submit", function (e) {
      e.preventDefault();
      var text = input.value.trim();
      if (!text) {
        return;
      }
      appendBubble(text, "user");
      input.value = "";
      var sendBtn = document.getElementById("assistant-send");
      if (sendBtn) {
        sendBtn.disabled = true;
      }
      fetch("api/chat.php", {
        method: "POST",
        headers: { "Content-Type": "application/json" },
        body: JSON.stringify({ message: text, context: context }),
      })
        .then(function (r) {
          return r.json().then(function (j) {
            return { ok: r.ok, json: j };
          });
        })
        .then(function (res) {
          var j = res.json;
          if (j && j.ok && j.reply) {
            appendBubble(j.reply, "assistant");
          } else {
            var errEl = document.createElement("p");
            errEl.className = "assistant__msg assistant__msg--error";
            errEl.textContent = (j && j.error) || "Something went wrong. Try again.";
            messages.appendChild(errEl);
            messages.scrollTop = messages.scrollHeight;
          }
        })
        .catch(function () {
          appendBubble("Could not reach the assistant. Check your connection.", "error");
        })
        .finally(function () {
          if (sendBtn) {
            sendBtn.disabled = false;
          }
          input.focus();
        });
    });
  }

  function initCommunityRecipeImagePreview() {
    var input = document.getElementById("com_recipe_image");
    var preview = document.getElementById("com_image_preview");
    var img = preview && preview.querySelector(".recipe-image-preview__img");
    if (!input || !preview || !img) {
      return;
    }

    input.addEventListener("change", function () {
      var file = input.files && input.files[0];
      if (!file || !file.type || file.type.indexOf("image/") !== 0) {
        preview.setAttribute("hidden", "");
        img.removeAttribute("src");
        img.alt = "";
        return;
      }
      var url = URL.createObjectURL(file);
      img.onload = function () {
        URL.revokeObjectURL(url);
      };
      img.src = url;
      img.alt = "Selected recipe photo preview";
      preview.removeAttribute("hidden");
    });
  }

  function initPreferDownwardSelects() {
    var selects = document.querySelectorAll("select.input-box--select");
    if (!selects.length) {
      return;
    }

    function ensureRoomBelow(el) {
      var rect = el.getBoundingClientRect();
      var viewportH = window.innerHeight || document.documentElement.clientHeight;
      var desiredBelow = 300;
      var spaceBelow = viewportH - rect.bottom;
      if (spaceBelow >= desiredBelow) {
        return;
      }
      var delta = desiredBelow - spaceBelow;
      window.scrollBy({ top: delta, left: 0, behavior: "auto" });
    }

    selects.forEach(function (el) {
      el.addEventListener("mousedown", function () {
        ensureRoomBelow(el);
      });
      el.addEventListener(
        "touchstart",
        function () {
          ensureRoomBelow(el);
        },
        { passive: true }
      );
      el.addEventListener("focus", function () {
        ensureRoomBelow(el);
      });
    });
  }


  onReady(function () {
    initNav();
    initHeaderScrollHide();
    initAccountMenu();
    initHeroBackgroundCarousel();
    initSignupModal();
    initCarousel();
    initContactSidebar();
    initAssistantFab();
    initCommunityRecipeImagePreview();
    initPreferDownwardSelects();
    initCookieBanner();
  });
})();
