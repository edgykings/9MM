// Offline test: runs the lead function against a fake Farvision server. Never calls the real CRM.
//   npm test
const assert = require("assert");
const handler = require("../api/lead");
const { buildPayload, dumpDataObjectId } = require("../lib/farvision");

function fakeRes() {
  return {
    code: 0, body: null, headers: {},
    status(c) { this.code = c; return this; },
    json(b) { this.body = b; return this; },
    setHeader(k, v) { this.headers[k] = v; },
  };
}

(async () => {
  process.env.FARVISION_CLIENT_ID = "test-client-id";
  let sent = null;
  global.fetch = async (url, opts) => {
    sent = { url, opts, body: JSON.parse(opts.body) };
    return { ok: true, status: 200, text: async () => '{"status":true,"message":"Sync request accepted."}' };
  };

  const good = {
    name: "Ananya Sen Gupta", phone: "+91 98300 12345", email: "ananya@example.com",
    bhk: "3 BHK – Premium", slot: "Evening (5 – 8 pm)", consent: true, company: "",
    attribution: { utm_source: "facebook", utm_campaign: "9m_prelaunch", fbclid: "abc", landing_page: "/?utm_source=facebook" },
  };

  let res = fakeRes();
  await handler({ method: "POST", body: good }, res);
  assert.strictEqual(res.code, 200); assert.strictEqual(res.body.ok, true);
  assert.strictEqual(sent.opts.headers.ClientId, "test-client-id");
  const b = sent.body;
  assert.strictEqual(b.firstName, "Ananya"); assert.strictEqual(b.lastName, "Sen Gupta");
  assert.strictEqual(b.mobilePhone, "+919830012345");
  assert.strictEqual(b.product, "9 MINNUTES"); assert.strictEqual(b.campaign, "9MIN Test");
  assert.strictEqual(b.originFrom, "Auto Lead from Digital");
  assert.strictEqual(b.isUpdatefromUIDate, false); assert.strictEqual(b.isImported, true);
  assert.match(b.DumpdataObjectId, /^\d{17}$/);
  assert.match(b.comments, /3 BHK – Premium/); assert.match(b.comments, /utm_source=facebook/);
  console.log("Payload sent to Farvision:\n", JSON.stringify(b, null, 2));

  // Bad phone → 400, nothing sent
  sent = null; res = fakeRes();
  await handler({ method: "POST", body: { ...good, phone: "12345" } }, res);
  assert.strictEqual(res.code, 400); assert.strictEqual(sent, null);

  // Honeypot → silent 200, nothing sent
  res = fakeRes();
  await handler({ method: "POST", body: { ...good, company: "spam inc" } }, res);
  assert.strictEqual(res.code, 200); assert.strictEqual(sent, null);

  // Farvision says no, no backup → 502
  global.fetch = async () => ({ ok: true, status: 200, text: async () => '{"status":false,"message":"Invalid product"}' });
  res = fakeRes();
  await handler({ method: "POST", body: good }, res);
  assert.strictEqual(res.code, 502);

  // Single-word name
  assert.strictEqual(buildPayload({ ...good, name: "Rahul", phone: "9830012345" }).lastName, "");
  // Ids are unique
  assert.notStrictEqual(dumpDataObjectId(), dumpDataObjectId() + "x");

  console.log("\nAll lead tests passed.");
})().catch((e) => { console.error(e); process.exit(1); });
