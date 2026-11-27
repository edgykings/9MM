// Shared checks for mobile numbers and email addresses (used by every form).
window.NMV = (function () {
  "use strict";
  function cleanPhone(v) {
    var d = String(v || "").replace(/[^\d]/g, "");
    if (d.length === 12 && d.indexOf("91") === 0) d = d.slice(2);
    if (d.length === 11 && d.charAt(0) === "0") d = d.slice(1);
    return d;
  }
  function phoneProblem(v) {
    var d = cleanPhone(v);
    if (!d) return "Please enter your mobile number.";
    if (d.length !== 10) return "Mobile number must be 10 digits.";
    if (!/^[6-9]/.test(d)) return "Indian mobile numbers start with 6, 7, 8 or 9.";
    if (/^(\d)\1{9}$/.test(d) || d === "9876543210" || d === "6789012345" || d === "1234567890") return "Please enter your real mobile number.";
    return "";
  }
  function emailProblem(v) {
    v = String(v || "").trim();
    if (!v) return "";
    if (!/^[^\s@]+@[^\s@.]+(\.[^\s@.]+)+$/.test(v) || /\.\./.test(v)) return "Please enter a valid email address, like name@gmail.com.";
    return "";
  }
  function mark(input, msg) {
    var slot = input.parentNode.querySelector(".field-msg");
    if (slot) slot.textContent = msg;
    input.classList.toggle("invalid", !!msg);
    input.setAttribute("aria-invalid", msg ? "true" : "false");
  }
  // Live checks: digits only in phone fields; messages after leaving a field, cleared as soon as it's fixed.
  function wire(form) {
    if (!form) return;
    var ph = form.querySelector('input[name="phone"]'), em = form.querySelector('input[name="email"]');
    if (ph) {
      ph.addEventListener("input", function () {
        var v = ph.value.replace(/[^\d+ ]/g, ""); if (v !== ph.value) ph.value = v;
        if (ph.classList.contains("invalid")) mark(ph, phoneProblem(ph.value));
      });
      ph.addEventListener("blur", function () { if (ph.value) mark(ph, phoneProblem(ph.value)); });
    }
    if (em) {
      em.addEventListener("input", function () { if (em.classList.contains("invalid")) mark(em, emailProblem(em.value)); });
      em.addEventListener("blur", function () { mark(em, emailProblem(em.value)); });
    }
  }
  return { cleanPhone: cleanPhone, phoneProblem: phoneProblem, emailProblem: emailProblem, mark: mark, wire: wire };
})();

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
  var cleanPhone = NMV.cleanPhone;
  NMV.wire(form);
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
    var pp = NMV.phoneProblem(f.get("phone")), ep = NMV.emailProblem(f.get("email"));
    NMV.mark(form.querySelector('input[name="phone"]'), pp);
    NMV.mark(form.querySelector('input[name="email"]'), ep);
    if (pp) return showError(pp);
    if (ep) return showError(ep);
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

    // Preview copy (no server behind it): go to the thank-you page without sending anything.
    if (window.LEAD_PREVIEW) {
      try { sessionStorage.setItem("9m_lead", JSON.stringify({ name: name, preview: true })); } catch (e) {}
      window.location.href = "thank-you.html";
      return;
    }

    fetch("/api/lead.php", {
      method: "POST",
      headers: { "Content-Type": "application/json" },
      body: JSON.stringify(payload)
    })
      .then(function (r) { return r.json().then(function (j) { return { ok: r.ok, body: j }; }); })
      .then(function (res) {
        if (!res.ok || !res.body.ok) throw new Error(res.body && res.body.error || "failed");
        // Conversion events fire on the thank-you page (one page view per lead).
        try { sessionStorage.setItem("9m_lead", JSON.stringify({ name: name, event_id: payload.event_id, bhk: payload.bhk })); } catch (e) {}
        window.location.href = "/thank-you.php";
      })
      .catch(function () {
        showError("Sorry, that didn't go through. Please try again, or call us on +91 98367 80001.");
        button.disabled = false;
        button.textContent = "Register interest";
      });
  });
})();

// Thank-you page: greet by name and send the conversion once per lead.
(function () {
  "use strict";
  if (!document.getElementById("ty")) return;
  var lead = null;
  try { lead = JSON.parse(sessionStorage.getItem("9m_lead") || "null"); sessionStorage.removeItem("9m_lead"); } catch (e) {}
  if (!lead) return; // opened directly or refreshed: no conversion
  if (lead.name) document.getElementById("ty-name").textContent = ", " + String(lead.name).split(" ")[0];
  if (lead.preview) { document.getElementById("ty-preview").hidden = false; return; }
  window.dataLayer = window.dataLayer || [];
  window.dataLayer.push({ event: "lead_submitted", lead_event_id: lead.event_id, bhk: lead.bhk });
  if (typeof window.fbq === "function") window.fbq("track", "Lead", {}, { eventID: lead.event_id });
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
  // Plays for everyone (the client wants the film seen); the Pause button stays available.
  var p = v.play && v.play(); if (p && p.catch) p.catch(function () { b.textContent = "Play"; b.setAttribute("aria-pressed", "true"); });
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


// Brochure: a click on any "Download brochure" opens a small form; after a valid
// mobile number the lead is saved (and sent to Farvision) and the PDF downloads.
(function () {
  "use strict";
  var modal = document.getElementById("brochure-modal");
  if (!modal) return;
  var file = modal.getAttribute("data-file");
  var form = document.getElementById("brochure-form");
  var err = modal.querySelector(".bm-error");
  var btn = form.querySelector('button[type="submit"]');
  var lastFocus = null;
  NMV.wire(form);

  function unlocked() { try { return sessionStorage.getItem("9m_brochure") === "1"; } catch (e) { return false; } }
  function download() {
    var a = document.createElement("a");
    a.href = file; a.setAttribute("download", ""); a.rel = "noopener";
    document.body.appendChild(a); a.click(); a.parentNode.removeChild(a);
  }
  function open() {
    lastFocus = document.activeElement;
    modal.hidden = false; document.documentElement.classList.add("bm-open");
    var first = form.querySelector('input[name="phone"]'); if (first && !document.getElementById("bm-step1").hidden) first.focus();
  }
  function close() {
    modal.hidden = true; document.documentElement.classList.remove("bm-open");
    if (lastFocus && lastFocus.focus) lastFocus.focus();
  }
  document.addEventListener("click", function (e) {
    var t = e.target.closest && e.target.closest(".js-brochure");
    if (!t) return;
    e.preventDefault();
    if (unlocked()) { download(); return; } // already shared a number in this visit
    open();
  });
  modal.addEventListener("click", function (e) { if (e.target === modal || (e.target.closest && e.target.closest(".bm-close"))) close(); });
  document.addEventListener("keydown", function (e) { if (!modal.hidden && (e.key === "Escape" || e.key === "Esc")) close(); });

  form.addEventListener("submit", function (e) {
    e.preventDefault();
    err.textContent = "";
    var f = new FormData(form);
    var pp = NMV.phoneProblem(f.get("phone"));
    NMV.mark(form.querySelector('input[name="phone"]'), pp);
    if (pp) { form.querySelector('input[name="phone"]').focus(); return; } // message shows under the field
    if (!f.get("consent")) { err.textContent = "Please tick the consent box to continue."; return; }
    var name = String(f.get("name") || "").trim();
    var eid = (window.crypto && crypto.randomUUID) ? crypto.randomUUID() : String(Date.now()) + Math.random().toString(16).slice(2);
    var attr = {}; try { attr = JSON.parse(sessionStorage.getItem("9m_attr") || "{}"); } catch (x) {}
    function done() {
      try { sessionStorage.setItem("9m_brochure", "1"); } catch (x) {}
      document.getElementById("bm-step1").hidden = true;
      document.getElementById("bm-step2").hidden = false;
      download();
    }
    btn.disabled = true; btn.textContent = "Preparing…";
    if (window.LEAD_PREVIEW) { done(); return; } // preview copy: nothing is sent
    fetch("/api/lead.php", {
      method: "POST", headers: { "Content-Type": "application/json" },
      body: JSON.stringify({ source: "brochure", name: name, phone: NMV.cleanPhone(f.get("phone")), consent: true, company: f.get("company") || "", event_id: eid, attribution: attr })
    })
      .then(function (r) { return r.json().then(function (j) { return { ok: r.ok, body: j }; }); })
      .then(function (res) {
        if (!res.ok || !res.body.ok) throw new Error((res.body && res.body.error) || "failed");
        window.dataLayer = window.dataLayer || [];
        window.dataLayer.push({ event: "brochure_download", lead_event_id: eid });
        if (typeof window.fbq === "function") window.fbq("track", "Lead", { content_name: "Brochure" }, { eventID: eid });
        done();
      })
      .catch(function (x) {
        err.textContent = x && x.message === "phone" ? "Please enter a valid 10-digit mobile number." : "Sorry, that didn't go through. Please try again.";
        btn.disabled = false; btn.textContent = "Download brochure";
      });
  });
})();

// Carousels (Gallery, Brand Legacy): scroll by themselves, pause while the visitor
// is touching, hovering or reading elsewhere; arrows move one card at a time.
(function () {
  "use strict";
  var list = document.querySelectorAll(".carousel");
  Array.prototype.forEach.call(list, function (car) {
    var track = car.querySelector(".car-track");
    if (!track || track.children.length < 2) return;
    var delay = parseInt(car.getAttribute("data-auto"), 10) || 3500;
    var paused = false, visible = true, timer = null;
    function step() {
      var card = track.children[0];
      var gap = parseFloat(getComputedStyle(track).columnGap || getComputedStyle(track).gap) || 0;
      return card.getBoundingClientRect().width + gap;
    }
    function go(dir) {
      var max = track.scrollWidth - track.clientWidth - 2;
      if (max <= 0) return;
      var x = track.scrollLeft + dir * step();
      if (dir > 0 && track.scrollLeft >= max) x = 0;            // end reached: back to the start
      else if (dir < 0 && track.scrollLeft <= 2) x = max;       // start: jump to the end
      if (track.scrollTo) track.scrollTo({ left: x, behavior: "smooth" }); else track.scrollLeft = x;
    }
    function tick() { if (!paused && visible && !document.hidden) go(1); }
    function start() { if (!timer) timer = setInterval(tick, delay); }
    function hold() { paused = true; }
    function release() { paused = false; }
    car.addEventListener("mouseenter", hold); car.addEventListener("mouseleave", release);
    car.addEventListener("focusin", hold); car.addEventListener("focusout", release);
    track.addEventListener("touchstart", hold, { passive: true });
    track.addEventListener("touchend", function () { setTimeout(release, 4000); }, { passive: true });
    var prev = car.querySelector(".car-btn.prev"), next = car.querySelector(".car-btn.next");
    if (prev) prev.addEventListener("click", function () { go(-1); });
    if (next) next.addEventListener("click", function () { go(1); });
    if ("IntersectionObserver" in window) {
      new IntersectionObserver(function (es) { visible = es[0].isIntersecting; }, { threshold: 0.3 }).observe(car);
    }
    start();
  });
})();
