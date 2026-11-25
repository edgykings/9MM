// POST /api/lead  – receives the microsite form and forwards it to Farvision.
// Runs as a Vercel serverless function (Node 18+). Secrets come from environment variables.
const { sendToFarvision, dumpDataObjectId } = require("../lib/farvision");

const BHK_OPTIONS = ["2 BHK – Essential", "3 BHK – Smart", "3 BHK – Premium", "4 BHK – Signature", "Not sure yet"];
const SLOT_OPTIONS = ["Any time", "Morning (10 am – 1 pm)", "Afternoon (1 – 5 pm)", "Evening (5 – 8 pm)"];

function clean(v, max) {
  return String(v == null ? "" : v).replace(/[\u0000-\u001f<>]/g, "").trim().slice(0, max);
}

function validate(b) {
  const name = clean(b.name, 80);
  let phone = String(b.phone || "").replace(/\D/g, "");
  if (phone.length === 12 && phone.startsWith("91")) phone = phone.slice(2);
  if (phone.length === 11 && phone.startsWith("0")) phone = phone.slice(1);
  const email = clean(b.email, 120);
  if (name.length < 2) return { error: "name" };
  if (!/^[6-9]\d{9}$/.test(phone)) return { error: "phone" };
  if (email && !/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(email)) return { error: "email" };
  if (!BHK_OPTIONS.includes(b.bhk)) return { error: "bhk" };
  if (b.consent !== true) return { error: "consent" };
  const attribution = {};
  const a = b.attribution && typeof b.attribution === "object" ? b.attribution : {};
  for (const k of ["utm_source", "utm_medium", "utm_campaign", "utm_term", "utm_content", "gclid", "fbclid", "gbraid", "wbraid", "landing_page", "referrer"]) {
    if (a[k]) attribution[k] = clean(a[k], 300);
  }
  return {
    lead: {
      name, phone, email,
      bhk: b.bhk,
      slot: SLOT_OPTIONS.includes(b.slot) ? b.slot : "Any time",
      consent: true,
      eventId: clean(b.event_id, 64),
      attribution,
    },
  };
}

async function backup(lead, env) {
  // Optional safety net: copy every lead to a webhook (e.g. a Google Apps Script that appends to a Sheet and emails sales).
  if (!env.BACKUP_WEBHOOK_URL) return false;
  const r = await fetch(env.BACKUP_WEBHOOK_URL, {
    method: "POST",
    headers: { "Content-Type": "application/json" },
    body: JSON.stringify({ ...lead, receivedAt: new Date().toISOString() }),
  });
  return r.ok;
}

module.exports = async function handler(req, res) {
  if (req.method !== "POST") {
    res.setHeader("Allow", "POST");
    return res.status(405).json({ ok: false, error: "method" });
  }
  let body = req.body;
  if (typeof body === "string") { try { body = JSON.parse(body); } catch (e) { body = {}; } }
  body = body || {};

  // Honeypot: bots fill the hidden "company" field. Pretend success, send nothing.
  if (body.company) return res.status(200).json({ ok: true });

  const { lead, error } = validate(body);
  if (error) return res.status(400).json({ ok: false, error });

  lead.dumpId = dumpDataObjectId();
  const env = process.env;
  let crmOk = false, backupOk = false;

  try {
    await sendToFarvision(lead, env);
    crmOk = true;
  } catch (e) {
    console.error("[lead] Farvision error", lead.dumpId, e.message);
  }
  try {
    backupOk = await backup({ ...lead, crmOk }, env);
  } catch (e) {
    console.error("[lead] backup error", lead.dumpId, e.message);
  }

  console.log("[lead]", lead.dumpId, "crm:", crmOk, "backup:", backupOk);
  if (crmOk || backupOk) return res.status(200).json({ ok: true });
  return res.status(502).json({ ok: false, error: "crm" });
};

module.exports.validate = validate;
