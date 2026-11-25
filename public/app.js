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

    fetch("/api/lead", {
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
