# 9 Minnutes microsite – handover for IT

Pre-launch microsite for **9 Minnutes** (Realmark Realty, Joka). The page is static, and a single serverless function sends every enquiry straight into **Farvision** through its LeadSync API.

```
public/            The website (index.html, app.js, assets/)
api/lead.js        POST /api/lead: validates the form and sends it to Farvision
lib/farvision.js   Farvision LeadSync client (field mapping lives here)
tools/             Local preview server, offline tests, design-to-HTML build script
vercel.json        Hosting config (static files from public/, security headers)
.env.example       The settings the hosting needs
```

No build step and no npm packages: Node 18+ only.

---

## 1. Farvision setup (done 26 Nov 2026)

Already set up in Realmark's Farvision:

| In Farvision | Name | Notes |
|---|---|---|
| Product | `9 MINNUTES` | Product ID 6, Residential |
| Campaign | `9MIN Test` | Campaign ID 31: Digital Media / Own Websites / Website, product 9 MINNUTES, Main Campaign, Active |

The code sends exactly these names. If either is renamed in Farvision, set `FARVISION_PRODUCT` / `FARVISION_CAMPAIGN` (step 3) to match.

## 2. What gets sent to Farvision

`POST https://fvintegration.farvisioncloud.com/LeadSync/api/SyncLeadsV2/RawLeads` with header `ClientId`.

| Farvision field | Value |
|---|---|
| firstName / lastName | Name from the form, split at the first space |
| email | From the form (optional, may be empty) |
| mobilePhone | `+91` + 10-digit mobile |
| comments | BHK wanted, best time to call, UTM / gclid / fbclid, landing page, consent |
| originFrom | `Auto Lead from Digital` (static) |
| product | `9 MINNUTES` |
| campaign | `9MIN Test` |
| isUpdatefromUIDate | `false` (static) |
| isImported | `true` (static) |
| DumpdataObjectId | Unique per lead: IST timestamp `ddMMyyyyHHmmss` + 3 random digits |

A lead counts as delivered only when Farvision replies `{"status":true, ...}`.

## 3. Deploy on Vercel (about 30 minutes)

1. Put this folder in a GitHub repository (private).
2. vercel.com → **Add New → Project** → import the repo. Framework preset: **Other**. Leave build settings empty.
3. **Settings → Environment Variables** (Production and Preview):

   | Name | Value |
   |---|---|
   | `FARVISION_CLIENT_ID` | The ClientId from Farvision. **Secret**: never put it in the code or in chat |
   | `FARVISION_PRODUCT` | `9 MINNUTES` (only if different from the default) |
   | `FARVISION_CAMPAIGN` | `9MIN Test` (only if different from the default) |
   | `BACKUP_WEBHOOK_URL` | Optional: a webhook that gets a copy of every lead (see section 6) |

4. Deploy. Vercel gives a test link like `9minnutes.vercel.app`.
5. **Test one real lead** on the test link (use a team member's number), confirm it appears in Farvision under the 9 Minnutes product and campaign, then mark it as a test in Farvision.
6. **Settings → Domains** → add `www.9minnutes.com` (and the bare domain, redirecting to www). Add the DNS records Vercel shows at the domain registrar. HTTPS is automatic.

Logs: Vercel → Project → **Logs**. Each lead prints one line, `[lead] <DumpdataObjectId> crm: true|false backup: true|false`. Failures print Farvision's reply.

## 4. Ad tracking (before running ads)

`index.html` has two `TRACKING:` comments. Paste the **Google Tag Manager** snippets there, then add the Meta Pixel, Google Ads and GA4 tags inside GTM.

On a successful submit the page already:
- pushes `{event: "lead_submitted", lead_event_id, bhk}` to `dataLayer`. Use it as the GTM trigger for the Google Ads and Meta conversions.
- calls `fbq('track','Lead', {}, {eventID})` if the Meta Pixel is loaded. The eventID is ready for Conversions API de-duplication later.

UTM parameters, `gclid` and `fbclid` are captured on landing and stored in each lead's **comments** in Farvision, so every lead can be traced to its ad.

A cookie-consent banner and a privacy policy page are needed before tracking goes live (content from Realmark legal).

## 5. Must be filled before launch (content, not code)

Search `public/index.html` for the yellow placeholder tags:
- `[ADD RERA REGISTRATION NUMBER]`: **no ads before the project is RERA-registered**, and every ad must show the number
- `[ADD LEGAL-APPROVED DISCLAIMER]`
- `[45 or 48 — CONFIRM]` amenity count
- `[CONFIRM: plans label the third room "Study"]` (Smart 3 BHK)
- `[CONFIRM claim]` "three sides open"
- `[CONFIRM wording with legal]` projected Metro times
- `[REPLACE: …]` terrace image

Edit the text directly in `public/index.html` and redeploy (pushing to GitHub redeploys automatically).

## 6. Optional: backup copy of every lead

If `BACKUP_WEBHOOK_URL` is set, every lead is also POSTed there as JSON (with `crmOk` true/false). A Google Apps Script web app that appends to a Google Sheet and emails sales works well. If Farvision is down, the visitor still gets the thank-you message and the lead is kept in the backup.

Without a backup, a Farvision failure shows the visitor an error with the phone number to call.

## 7. Local preview and tests

```bash
npm test                      # offline tests with a fake Farvision, never calls the real CRM
FARVISION_CLIENT_ID=xxx node tools/dev-server.js    # http://localhost:3000 (sends REAL leads)
```

## 8. Security notes

- The ClientId acts as the password for creating leads in Farvision. Keep it only in Vercel's environment variables. It was shared over chat during setup, so ask Farvision/Gamut whether it can be rotated before going live.
- The form has a hidden honeypot field to drop simple spam bots. For heavy ad traffic, add an OTP check on the mobile number or a CAPTCHA (e.g. Cloudflare Turnstile).
- All inputs are validated again on the server (name, 10-digit Indian mobile, email format, BHK from a fixed list, consent).
