// Farvision LeadSync (SyncLeadsV2/RawLeads) client.
// Spec supplied by Realmark (Nov 2026):
//   POST https://fvintegration.farvisioncloud.com/LeadSync/api/SyncLeadsV2/RawLeads
//   Headers: Content-Type: application/json, ClientId: <secret>
//   Success response: {"status":true,"message":"Sync request accepted."}
// Dynamic fields: firstName, lastName, email, mobilePhone, comments, product, campaign, DumpdataObjectId.
// Static fields (do not change): originFrom, isUpdatefromUIDate, isImported.

const DEFAULT_URL = "https://fvintegration.farvisioncloud.com/LeadSync/api/SyncLeadsV2/RawLeads";

function splitName(full) {
  const parts = String(full || "").trim().split(/\s+/);
  const firstName = parts.shift() || "";
  const lastName = parts.join(" ");
  return { firstName, lastName };
}

// Unique id per lead: IST date-time stamp (ddMMyyyyHHmmss) + 3 random digits.
function dumpDataObjectId(now = new Date()) {
  const ist = new Date(now.getTime() + 5.5 * 60 * 60 * 1000);
  const p = (n, w = 2) => String(n).padStart(w, "0");
  const stamp =
    p(ist.getUTCDate()) + p(ist.getUTCMonth() + 1) + ist.getUTCFullYear() +
    p(ist.getUTCHours()) + p(ist.getUTCMinutes()) + p(ist.getUTCSeconds());
  return stamp + p(Math.floor(Math.random() * 1000), 3);
}

function buildComments(lead) {
  const a = lead.attribution || {};
  const bits = [
    `Looking for: ${lead.bhk}`,
    `Best time to call: ${lead.slot || "Any time"}`,
    "Source: 9 Minnutes microsite",
  ];
  const utm = ["utm_source", "utm_medium", "utm_campaign", "utm_term", "utm_content"]
    .filter((k) => a[k]).map((k) => `${k}=${a[k]}`);
  if (utm.length) bits.push("UTM: " + utm.join(", "));
  if (a.gclid) bits.push("gclid=" + a.gclid);
  if (a.fbclid) bits.push("fbclid=" + a.fbclid);
  if (a.landing_page) bits.push("Landing: " + a.landing_page);
  bits.push("Consent to contact: yes");
  return bits.join(" | ").slice(0, 1000);
}

function buildPayload(lead, env = process.env) {
  const { firstName, lastName } = splitName(lead.name);
  return {
    firstName,
    lastName,
    email: lead.email || "",
    mobilePhone: "+91" + lead.phone,
    comments: buildComments(lead),
    originFrom: "Auto Lead from Digital",              // static
    product: env.FARVISION_PRODUCT || "REALMARK 9 MINNUTES",
    campaign: env.FARVISION_CAMPAIGN || "9 Minnutes Microsite",
    isUpdatefromUIDate: false,                         // static
    isImported: true,                                  // static
    DumpdataObjectId: lead.dumpId || dumpDataObjectId(),
  };
}

async function sendToFarvision(lead, env = process.env, fetchImpl = fetch) {
  if (!env.FARVISION_CLIENT_ID) throw new Error("FARVISION_CLIENT_ID is not set");
  const body = buildPayload(lead, env);
  const ctrl = new AbortController();
  const timer = setTimeout(() => ctrl.abort(), 10000);
  try {
    const res = await fetchImpl(env.FARVISION_API_URL || DEFAULT_URL, {
      method: "POST",
      headers: { "Content-Type": "application/json", ClientId: env.FARVISION_CLIENT_ID },
      body: JSON.stringify(body),
      signal: ctrl.signal,
    });
    const text = await res.text();
    let json = null;
    try { json = JSON.parse(text); } catch (e) { /* non-JSON reply */ }
    if (!res.ok || !json || json.status !== true) {
      throw new Error(`Farvision rejected the lead (HTTP ${res.status}): ${text.slice(0, 300)}`);
    }
    return { dumpId: body.DumpdataObjectId, message: json.message };
  } finally {
    clearTimeout(timer);
  }
}

module.exports = { buildPayload, sendToFarvision, dumpDataObjectId, splitName };
