// 9 Minnutes microsite: lead form + campaign capture.
(function () {
  "use strict";

  // 1. Remember where the visitor came from (first touch in this browser tab).
  var KEYS = ["utm_source", "utm_medium", "utm_campaign", "utm_term", "utm_content", "gclid", "fbclid", "gbraid", "wbraid"];
  var params = new URLSearchParams(window.location.search);
  var attribution = {};
  try { attribution = JSON.parse(sessionStorage.getItem("9m_attr") || "{}"); } catch (e) {}
  var changed = false;
  KEYS.forEach(function (k) {
    var v = params.get(k);
    if (v && !attribution[k]) { attribution[k] = v.slice(0, 200); changed = true; }
  });
  if (!attribution.landing_page) { attribution.landing_page = window.location.pathname + window.location.search; changed = true; }
  if (!attribution.referrer && document.referrer) { attribution.referrer = document.referrer.slice(0, 300); changed = true; }
  if (changed) { try { sessionStorage.setItem("9m_attr", JSON.stringify(attribution)); } catch (e) {} }

  // 2. Lead form.
  var form = document.getElementById("lead-form");
  if (!form) return;
  var errorBox = document.getElementById("form-error");
  var button = form.querySelector('button[type="submit"]');

  function showError(msg) { errorBox.textContent = msg; }
  function cleanPhone(v) {
    var d = (v || "").replace(/[^\d]/g, "");
    if (d.length === 12 && d.indexOf("91") === 0) d = d.slice(2);
    if (d.length === 11 && d.charAt(0) === "0") d = d.slice(1);
    return d;
  }
  function eventId() {
    return (window.crypto && crypto.randomUUID) ? crypto.randomUUID() : String(Date.now()) + Math.random().toString(16).slice(2);
  }

  form.addEventListener("input", function () { showError(""); });

  form.addEventListener("submit", function (e) {
    e.preventDefault();
    var f = new FormData(form);
    var name = (f.get("name") || "").trim();
    var phone = cleanPhone(f.get("phone"));
    if (name.length < 2) return showError("Please enter your name.");
    if (!/^[6-9]\d{9}$/.test(phone)) return showError("Please enter a valid 10-digit mobile number.");
    if (!f.get("bhk")) return showError("Please choose the home you're looking for.");
    if (!f.get("consent")) return showError("Please tick the consent box so our team can contact you.");

    var payload = {
      name: name,
      phone: phone,
      email: (f.get("email") || "").trim(),
      bhk: f.get("bhk"),
      slot: f.get("slot"),
      consent: true,
      company: f.get("company") || "", // honeypot: must stay empty
      event_id: eventId(),
      attribution: attribution
    };

    button.disabled = true;
    button.textContent = "Sending…";

    fetch("/api/lead.php", {
      method: "POST",
      headers: { "Content-Type": "application/json" },
      body: JSON.stringify(payload)
    })
      .then(function (r) { return r.json().then(function (j) { return { ok: r.ok, body: j }; }); })
      .then(function (res) {
        if (!res.ok || !res.body.ok) throw new Error(res.body && res.body.error || "failed");
        // Conversion signals for ads (only fire if GTM / Pixel are installed).
        window.dataLayer = window.dataLayer || [];
        window.dataLayer.push({ event: "lead_submitted", lead_event_id: payload.event_id, bhk: payload.bhk });
        if (typeof window.fbq === "function") window.fbq("track", "Lead", {}, { eventID: payload.event_id });

        form.hidden = true;
        document.getElementById("lead-name").textContent = ", " + name.split(" ")[0];
        document.getElementById("lead-thanks").hidden = false;
      })
      .catch(function () {
        showError("Sorry, that didn't go through. Please try again, or call us on +91 98367 80001.");
        button.disabled = false;
        button.textContent = "Register interest";
      });
  });
})();

// Residences: clicking a BHK card opens its floor plans right below that row.
(function () {
  "use strict";
  var grid = document.getElementById("homes");
  var panel = document.getElementById("plans-panel");
  if (!grid || !panel) return;
  var cards = Array.prototype.slice.call(grid.querySelectorAll("button.home"));
  var plansGrid = panel.querySelector(".plans");
  var title = panel.querySelector(".pp-title");
  var openTier = null;

  function lastCardInRow(card) {
    var top = card.offsetTop, last = card;
    cards.forEach(function (c) { if (c.offsetTop === top && c.offsetLeft > last.offsetLeft) last = c; });
    return last;
  }
  function place(card) { lastCardInRow(card).after(panel); }
  function close() {
    openTier = null; panel.hidden = true;
    cards.forEach(function (c) { c.setAttribute("aria-expanded", "false"); });
  }
  function open(card) {
    var tier = card.dataset.tier, tpl = document.getElementById("plans-" + tier);
    if (!tpl) return;
    openTier = tier;
    cards.forEach(function (c) { c.setAttribute("aria-expanded", String(c === card)); });
    plansGrid.innerHTML = "";
    plansGrid.appendChild(tpl.content.cloneNode(true));
    title.textContent = tpl.dataset.title || "Floor plans";
    panel.hidden = false;
    place(card);
    var r = panel.getBoundingClientRect();
    if (r.top < 0 || r.top > window.innerHeight - 120) panel.scrollIntoView({ behavior: "smooth", block: "start" });
  }
  cards.forEach(function (card) {
    card.addEventListener("click", function () { openTier === card.dataset.tier ? close() : open(card); });
  });
  panel.querySelector(".pp-close").addEventListener("click", function () {
    var card = cards.filter(function (c) { return c.dataset.tier === openTier; })[0];
    close(); if (card) card.focus();
  });
  window.addEventListener("resize", function () {
    if (!openTier) return;
    var card = cards.filter(function (c) { return c.dataset.tier === openTier; })[0];
    grid.appendChild(panel); place(card);
  });
})();

// Hero teaser video: pause/play button (and respect reduced motion).
(function () {
  var v = document.querySelector(".hero-video"), b = document.querySelector(".video-toggle");
  if (!v || !b) return;
  if (window.matchMedia && window.matchMedia("(prefers-reduced-motion: reduce)").matches) { v.pause(); b.hidden = true; return; }
  b.addEventListener("click", function () {
    if (v.paused) { v.play(); b.textContent = "Pause"; b.setAttribute("aria-pressed", "false"); b.setAttribute("aria-label", "Pause background video"); }
    else { v.pause(); b.textContent = "Play"; b.setAttribute("aria-pressed", "true"); b.setAttribute("aria-label", "Play background video"); }
  });
})();

// Review videos load only when clicked (keeps the page fast).
document.addEventListener("click", function (e) {
  var b = e.target.closest && e.target.closest("button.yt");
  if (!b || !/^[A-Za-z0-9_-]{11}$/.test(b.dataset.yt || "")) return;
  var f = document.createElement("iframe");
  f.src = "https://www.youtube-nocookie.com/embed/" + b.dataset.yt + "?autoplay=1&rel=0";
  f.title = b.getAttribute("aria-label") || "Video review";
  f.allow = "autoplay; encrypted-media; picture-in-picture";
  f.allowFullscreen = true;
  var wrap = document.createElement("div");
  wrap.className = "yt";
  wrap.appendChild(f);
  b.replaceWith(wrap);
});

// Image lightbox
(function () {
  "use strict";
  var box = document.getElementById("lightbox");
  if (!box || typeof box.showModal !== "function") return; // old browsers just follow the link
  var img = box.querySelector("img"), cap = box.querySelector(".lb-cap");
  document.addEventListener("click", function (e) {
    var a = e.target.closest && e.target.closest("a.zoom");
    if (!a) return;
    e.preventDefault();
    img.src = a.getAttribute("href");
    img.alt = a.dataset.caption || "";
    cap.textContent = a.dataset.caption || "";
    box.showModal();
  });
  box.querySelector(".lb-close").addEventListener("click", function () { box.close(); });
  box.addEventListener("click", function (e) { if (e.target === box) box.close(); });
})();
