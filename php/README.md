# 9 Minnutes microsite + admin panel (PHP version)

For Realmark's own PHP hosting (cPanel or similar). No database server needed: leads go into a SQLite file, and content and settings go into JSON files, all inside a private folder the web can't reach.

**Requirements:** PHP 8.0 or newer with the `curl`, `pdo_sqlite`, `gd`, `fileinfo`, `mbstring` and `session` extensions (standard on cPanel). The server must be able to make outgoing HTTPS calls to `fvintegration.farvisioncloud.com`.

```
php/
  public_html/          → upload the CONTENTS of this folder to the domain's web root
    index.php           the microsite
    api/lead.php        the enquiry form endpoint (saves the lead, then sends it to Farvision)
    admin/              the admin panel at https://<domain>/admin/
    assets/             shipped images (logos, renders, floor plans, master plan)
    uploads/            images and PDFs uploaded from the admin panel (scripts disabled here)
    site.css, app.js, boot.php, .htaccess
  private/              → upload NEXT TO public_html (one level up), NOT inside it
    config.sample.php   copy to config.php and fill in
    lib/, templates/    site code
    data/               created automatically: site.sqlite (leads), content.json, settings.json, users.json
```

## 1. Upload (cPanel File Manager or FTP)

1. Upload everything inside `php/public_html/` into the domain's document root (for example `/home/realmark/public_html/` or the subdomain's folder).
2. Upload the `php/private/` folder one level ABOVE that document root, so it sits beside `public_html`, for example `/home/realmark/private/`.
   - If the host does not allow files outside the web root, put `private/` INSIDE public_html instead. Its `.htaccess` blocks all web access, and the site finds it automatically. Then check that `https://<domain>/private/config.php` shows an error (403 or 404), never the file.
3. Make sure PHP can write to `private/data/` and `public_html/uploads/` (permissions 755, or 775 if PHP runs as a different user).

## 2. Configure

Copy `private/config.sample.php` to `private/config.php` and set:

| Setting | Value |
|---|---|
| `farvision_client_id` | The ClientId from Farvision/Gamut. **Treat it as a password.** |
| `farvision_product` | `9 MINNUTES` (already set; product ID 6 in Farvision) |
| `farvision_campaign` | `9MIN Test` (already set; campaign ID 31 in Farvision) |
| `setup_token` | 20+ random characters. Needed once, to create the first admin account. |

PHP settings (cPanel → MultiPHP INI Editor): `upload_max_filesize = 100M` and `post_max_size = 110M`, so the teaser video (MP4, up to 80 MB) can be uploaded from the admin panel. PHP extensions needed: `pdo_sqlite`, `curl`, `gd`, `fileinfo`, `mbstring`.

Pages: `/` (microsite), `/privacy-policy.php`, `/disclaimer.php` (text for both is edited in admin → Content → Legal), `/admin/`.

## 3. Create the first admin

Open `https://<domain>/admin/`, enter the setup code from `config.php`, your email and a password (12+ characters, letters and a number). After that the setup page is gone, and further admins are added under **Account**. You can then blank `setup_token` in `config.php`.

## 4. Test before connecting the domain to ads

1. Submit the form on the site with a team member's mobile number.
2. In the admin panel → **Leads**, it should show **Sent**.
3. In Farvision, it should appear under product 9 MINNUTES and campaign 9MIN Test.
4. If it shows **Failed**, hover over the label to see Farvision's reply. The lead is still saved, so press **Retry** once the problem is fixed.
5. Turn on HTTPS redirection: uncomment the last three lines of `public_html/.htaccess` once the SSL certificate is active.

## What the admin panel does

| Page | What your team can do |
|---|---|
| Dashboard | Leads today / last 7 days / all time; leads not yet in Farvision; checklist of content still missing |
| Leads | Every enquiry with date, name, mobile, email, BHK, call time, ad source (UTM / gclid / fbclid) and Farvision status. Search, filter, **Retry** failed syncs, **Download CSV** |
| Content | Hero text, key facts, residence descriptions, carpet areas and prices, terrace text, amenity count, Metro note, FAQ (add, edit, delete), contact details, WhatsApp number, RERA number, disclaimer, banner text |
| Images & files | Gallery (upload, caption, reorder, remove), walkthrough video (YouTube link), master plan, brochure PDF, each floor plan's image and areas |
| Switches | Show prices; announcement banner; pause the enquiry form; mark a residence type sold out (also removes it from the form); show or hide the yellow reminders for missing content |
| Ad tracking | Google Tag Manager ID, Meta Pixel ID, GA4 ID. A successful enquiry fires `lead_submitted` (GTM dataLayer) and `Lead` (Pixel) |
| Account | Change password, add or remove admins |

Everything saves instantly and is live on the site on the next page load.

## Security notes

- Passwords are stored with PHP's `password_hash`. Sessions expire after 2 hours idle. Every form has CSRF protection. Login is locked for 15 minutes after 5 wrong attempts from one address.
- The admin panel sends `noindex` and strict security headers. It is safe to put it behind cPanel's "Directory Privacy" (an extra password) as well, if IT prefers.
- Uploads: images are checked by content (not file name) and re-saved as JPG; PDFs must start with `%PDF-`; random file names; scripts cannot run in `uploads/`.
- The enquiry endpoint only accepts same-site requests, validates every field again, has a hidden spam trap and allows 5 enquiries per visitor per 10 minutes.
- `config.php`, `data/` and uploads are excluded from Git. Back up `private/data/` (it holds all leads) and `public_html/uploads/` regularly: cPanel's backup tool covers both if `private/` is inside the home folder.
- The Farvision ClientId was shared over chat during setup, so ask Gamut to issue a new one before launch and put it in `config.php`.

## Local preview (developers)

```bash
cd php && cp private/config.sample.php private/config.php   # fill in values
php -S 127.0.0.1:8080 -t public_html                       # http://127.0.0.1:8080 and /admin/
```
(PHP's built-in server ignores `.htaccess`; Apache on the host applies it.)
