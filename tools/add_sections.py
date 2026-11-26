"""One-off: insert the Nov 2026 content sections into public/index.html.
Floor plan data comes from tools/floorplans.json (read from the 20_11_2026 floor plan PDF).
"""
import json, html, pathlib, re

ROOT = pathlib.Path(__file__).resolve().parent.parent
idx = ROOT / "public/index.html"
page = idx.read_text(encoding="utf-8")
plans = json.loads((ROOT / "tools/floorplans.json").read_text())

TAG = 'style="background:#FFF3CD;color:#6A4700;padding:2px 6px"'
EYEBROW = "margin:0 0 16px;font-family:'Geologica',sans-serif;font-size:13px;letter-spacing:.14em;text-transform:uppercase;color:#6B5E52"
H2 = "margin:0 0 16px;font-size:clamp(36px,4.4vw,56px);font-weight:300;line-height:1.08;color:#1C3A50;letter-spacing:-.02em"
WRAP = "max-width:1280px;margin:0 auto;padding:110px 24px"
e = html.escape

# ---------- FLOOR PLANS ----------
tiers = [("ESSENTIAL", "Essential · 2 BHK"), ("SMART", "Smart · 3 BHK"), ("PREMIUM", "Premium · 3 BHK"), ("SIGNATURE", "Signature · 4 BHK")]
tabs = "".join(
    f'<button type="button" class="plan-tab" data-tier="{t}" aria-pressed="{"true" if i == 0 else "false"}">{e(label)}</button>'
    for i, (t, label) in enumerate(tiers))
cards = []
for p in plans:
    num = f"{p['page']:02d}"
    parts = [x.strip() for x in p["cfg"].split("|")]
    cfg = " · ".join([parts[0], parts[1].replace(" T", " Toilets")] + [x.title() for x in parts[2:]])
    terrace = f'<div style="font-size:14px;color:#58595B">Open terrace {e(p["terrace"])} sq. ft.</div>' if p.get("terrace") else ""
    alt = f"Floor plan, flat {p['flat']}, {p['cfg']}, {p['floors']}"
    cards.append(f'''<figure class="plan-card" data-tier="{p['tier']}" style="margin:0;background:#FFFFFF;border:1px solid #E4DCD8;display:flex;flex-direction:column">
<a href="assets/plans/plan-{num}.jpg" class="zoom" data-caption="{e(alt)}" aria-label="Enlarge {e(alt)}"><img src="assets/plans/plan-{num}-thumb.jpg" alt="{e(alt)}" loading="lazy" width="720" height="498" style="width:100%;height:auto;display:block;background:#F4F1EF"></a>
<figcaption style="padding:16px 18px 20px;display:flex;flex-direction:column;gap:4px">
<div style="font-family:'Geologica',sans-serif;font-size:12px;letter-spacing:.14em;text-transform:uppercase;color:#8A6A1F">{e(p['tier'].title())} · Flat {e(p['flat'])}</div>
<div style="font-size:18px;color:#1C3A50">{e(cfg)}</div>
<div style="font-size:14px;color:#58595B">{e(p['floors'])}</div>
<div style="font-size:15px;margin-top:6px">Carpet area <strong style="font-weight:500;color:#1C3A50">{e(p['carpet'])} sq. ft.</strong></div>
{terrace}
</figcaption></figure>''')
floorplans = f'''
<!-- FLOOR PLANS -->
<section id="plans" style="background:#EBE3E0">
<div style="{WRAP}">
<p style="{EYEBROW}">Floor plans</p>
<h2 style="{H2}">Find the plan that fits.</h2>
<p style="margin:0 0 32px;max-width:680px;font-size:18px;font-weight:300;line-height:1.6">27 layouts across four residence types. Tap a plan to see it in full.</p>
<div role="group" aria-label="Residence type" style="display:flex;flex-wrap:wrap;gap:10px;margin-bottom:32px">{tabs}</div>
<div id="plan-grid" style="display:grid;grid-template-columns:repeat(auto-fill,minmax(280px,1fr));gap:20px">
{"".join(cards)}
</div>
<p style="margin:24px 0 0;font-size:13px;color:#58595B">Plans are indicative and not to scale. Carpet area as per RERA. <span {TAG}>[CONFIRM: Smart B and G plans label the third room "Study"]</span></p>
</div>
</section>
'''

# ---------- SPECIFICATIONS ----------
specs = [
    ("Walls", ["Common areas: paint", "Inside the home: POP finish", "Exterior: paint"]),
    ("Flooring", ["Vitrified tiles in living room and bedrooms", "Marble, tiles or stone in all common areas"]),
    ("Kitchen", ["Granite counter with stainless steel sink", "2 ft ceramic dado tiles"]),
    ("Toilets", ["Ceramic tiles up to dado", "Anti-skid floor tiles", "Concealed plumbing, CP fittings of a reputed brand"]),
    ("Doors", ["Main door: flush door, laminate on one side, with fittings", "Other doors: door shutters with fittings"]),
    ("Windows", ["Aluminium powder-coated or uPVC sliding windows"]),
    ("Electrical", ["Concealed copper wiring", "TV and intercom points in the living room", "AC points in all bedrooms and the living-dining area"]),
    ("Power backup", ["Common-area lighting", "1 kVA for 2 BHK, 1.5 kVA for 3 BHK, 2 kVA for 4 BHK"]),
    ("Lift", ["Elevator of a leading brand"]),
]
spec_cards = "".join(
    f'<div style="border-top:2px solid #B48B2A;padding-top:14px"><h3 style="margin:0 0 10px;font-size:15px;font-weight:600;letter-spacing:.1em;text-transform:uppercase;color:#1C3A50">{e(t)}</h3>'
    f'<ul style="margin:0;padding-left:18px;font-size:15px;font-weight:300;line-height:1.7">{"".join(f"<li>{e(x)}</li>" for x in items)}</ul></div>'
    for t, items in specs)
specifications = f'''
<!-- SPECIFICATIONS -->
<section id="specifications" style="background:#FFFFFF">
<div style="{WRAP}">
<p style="{EYEBROW}">Specifications</p>
<h2 style="{H2}">Built with care, inside and out.</h2>
<div style="margin-top:40px;display:grid;grid-template-columns:repeat(auto-fit,minmax(240px,1fr));gap:36px 40px">{spec_cards}</div>
<p style="margin:28px 0 0;font-size:13px;color:#58595B">Specifications are indicative and subject to change.</p>
</div>
</section>
'''

# ---------- GALLERY + VIDEO ----------
gal = [("tower-metro", "9 Minnutes beside the Joka Metro line"), ("aerial", "Aerial view among greenery"), ("facade", "Tower façade"),
       ("living", "Living room"), ("dusk", "Evening view"), ("terrace", "Terrace apartment"), ("pool", "Swimming pool"),
       ("fitness", "Fitness studio"), ("lawn", "Festival lawn"), ("banquet", "Banquet")]
tiles = "".join(
    f'<a href="assets/gallery/{f}.jpg" class="zoom" data-caption="{e(c)} (artist\'s impression)" style="display:block;position:relative;overflow:hidden;aspect-ratio:4/3;background:#D9D0CB">'
    f'<img src="assets/gallery/{f}-thumb.jpg" alt="{e(c)}, artist\'s impression" loading="lazy" style="width:100%;height:100%;object-fit:cover;display:block"></a>'
    for f, c in gal)
gallery = f'''
<!-- GALLERY -->
<section id="gallery" style="background:#EBE3E0">
<div style="{WRAP}">
<p style="{EYEBROW}">Gallery</p>
<h2 style="{H2}">A look around.</h2>
<p style="margin:0 0 32px;font-size:15px"><span {TAG}>[REPLACE: interim brochure renders; swap in the approved image set]</span></p>
<div style="display:grid;grid-template-columns:repeat(auto-fill,minmax(240px,1fr));gap:12px">{tiles}</div>
<div id="video" style="margin-top:48px;aspect-ratio:16/9;max-width:720px;background:#1C3A50;color:#FFFFFF;display:flex;align-items:center;justify-content:center;text-align:center;padding:24px">
<div><div style="font-size:24px;font-weight:300;margin-bottom:10px">Walkthrough film</div><span {TAG}>[ADD VIDEO: YouTube link]</span></div>
</div>
<p style="margin:16px 0 0;font-size:12px;color:#58595B">All images are artist's impressions.</p>
</div>
</section>
'''

# ---------- MASTER PLAN ----------
masterplan = f'''
<!-- MASTER PLAN -->
<section id="masterplan" style="background:#FFFFFF">
<div style="{WRAP}">
<p style="{EYEBROW}">Master plan</p>
<h2 style="{H2}">A thoughtfully planned community.</h2>
<p style="margin:0 0 32px;max-width:680px;font-size:18px;font-weight:300;line-height:1.6">One G+11 tower with landscaped spaces to relax, play, celebrate and connect, on Hanspukur Green Park Road.</p>
<a href="assets/masterplan.jpg" class="zoom" data-caption="Master plan (artist's impression)" style="display:block"><img src="assets/masterplan.jpg" alt="Master plan of 9 Minnutes showing the tower, landscaped areas, pool and amenities" loading="lazy" style="width:100%;height:auto;display:block;border:1px solid #E4DCD8"></a>
<p style="margin:12px 0 0;font-size:13px"><span {TAG}>[REPLACE: cleaned-up master plan from the designer (ghost lettering, legend fixes)]</span></p>
</div>
</section>
'''

# ---------- CONSULTANTS ----------
cons = [("Innate", "Architect", "Architecture becomes meaningful when it improves the way a home is lived in. We have deliberately balanced openness, proportion and privacy to give every 9 Minnutes apartment its own sense of space."),
        ("MNU Consultants Pvt. Ltd.", "Structural consultant", "A well-engineered structure should give architecture the freedom to do what it does best. Our focus at 9 Minnutes has been on precision, consistency and structural efficiency from the ground up."),
        ("Inscape Studio", "Landscape consultant", "We did not want the landscape to simply surround the building. We wanted it to become part of daily life, with places to move, pause, play, gather and simply be outdoors.")]
con_cards = "".join(
    f'<figure style="margin:0;padding:28px;border:1px solid #E4DCD8;display:flex;flex-direction:column;gap:14px">'
    f'<blockquote style="margin:0;font-size:17px;font-weight:300;line-height:1.6">“{e(q)}”</blockquote>'
    f'<figcaption style="margin-top:auto"><strong style="display:block;font-weight:500;color:#1C3A50">{e(n)}</strong><span style="font-size:14px;color:#58595B">{e(r)}</span></figcaption></figure>'
    for n, r, q in cons)
consultants = f'''
<!-- CONSULTANTS -->
<section id="team" style="background:#FFFFFF">
<div style="{WRAP}">
<p style="{EYEBROW}">The team</p>
<h2 style="{H2}">The vision behind your address.</h2>
<div style="margin-top:40px;display:grid;grid-template-columns:repeat(auto-fit,minmax(280px,1fr));gap:24px">{con_cards}</div>
</div>
</section>
'''

# ---------- FAQ ----------
faqs = [
    ("Where exactly is 9 Minnutes?", "61, Hanspukur Green Park Road, Joka, Kolkata 700104, about 150 metres from Diamond Harbour Road."),
    ("How far is the Metro?", "Joka Metro station on the Purple Line is 0.7 km away, about a 9-minute walk."),
    ("What types of homes are there?", "2 BHK (Essential), 3 BHK (Smart and Premium) and 4 BHK (Signature) apartments, with carpet areas from 600 to 1,099 sq. ft. Select 3 and 4 BHK homes have terraces."),
    ("How big is the project?", "A single G+11 tower with 127 residences and a 16,400 sq. ft. lifestyle club."),
    ("What are the prices?", "Prices will be announced at launch. Register your interest and our team will share them with you first."),
    ("When is possession?", "[ADD POSSESSION DATE]"),
    ("Is the project RERA registered?", "[ADD RERA REGISTRATION NUMBER AND STATUS]"),
    ("Does the area come under KMC?", "[CONFIRM: KMC or panchayat]"),
    ("Can I visit the site?", "Yes. Register your interest or call +91 98367 80001 and our team will set up a visit at a time that suits you."),
]
def faq_answer(a):
    return f'<span {TAG}>{e(a)}</span>' if a.startswith("[") else e(a)
faq_items = "".join(
    f'<details style="border-bottom:1px solid #D9D0CB;padding:18px 0"><summary style="cursor:pointer;font-size:18px;color:#1C3A50;list-style-position:outside">{e(q)}</summary>'
    f'<p style="margin:12px 0 0;font-size:16px;font-weight:300;line-height:1.6">{faq_answer(a)}</p></details>'
    for q, a in faqs)
faq = f'''
<!-- FAQ -->
<section id="faq" style="background:#EBE3E0">
<div style="{WRAP};max-width:960px">
<p style="{EYEBROW}">FAQ</p>
<h2 style="{H2}">Questions buyers ask.</h2>
<div style="margin-top:24px;border-top:1px solid #D9D0CB">{faq_items}</div>
</div>
</section>
'''

# ---------- MAP (inside location) ----------
mapblock = '''<div style="margin-top:40px">
<iframe title="Map showing 9 Minnutes at Hanspukur Green Park Road, Joka" src="https://maps.google.com/maps?q=Hanspukur+Green+Park+Road,+Joka,+Kolkata+700104&amp;z=15&amp;output=embed" loading="lazy" referrerpolicy="no-referrer-when-downgrade" style="width:100%;height:420px;border:0;display:block"></iframe>
<p style="margin:10px 0 0;font-size:14px"><a href="https://maps.google.com/?q=Hanspukur+Green+Park+Road,+Joka,+Kolkata+700104" target="_blank" rel="noopener">Open in Google Maps</a> <span style="background:#FFF3CD;color:#6A4700;padding:2px 6px">[CONFIRM: replace with the exact site pin]</span></p>
</div>
'''

# ---------- WhatsApp + lightbox ----------
whatsapp = '''<a href="https://wa.me/919836780001?text=Hi%2C%20I%27m%20interested%20in%209%20Minnutes%2C%20Joka." class="wa" target="_blank" rel="noopener" aria-label="Chat with us on WhatsApp">
<svg width="28" height="28" viewBox="0 0 24 24" aria-hidden="true"><path fill="#FFFFFF" d="M12 2a10 10 0 0 0-8.6 15.1L2 22l5-1.3A10 10 0 1 0 12 2Zm0 18.2a8.2 8.2 0 0 1-4.2-1.2l-.3-.2-3 .8.8-2.9-.2-.3A8.2 8.2 0 1 1 12 20.2Zm4.5-6.1c-.2-.1-1.5-.7-1.7-.8-.2-.1-.4-.1-.6.1l-.8 1c-.1.2-.3.2-.5.1a6.7 6.7 0 0 1-3.3-2.9c-.3-.4.2-.4.7-1.4.1-.2 0-.3 0-.4l-.8-1.8c-.2-.5-.4-.4-.6-.4h-.5a1 1 0 0 0-.7.3 3 3 0 0 0-.9 2.2 5.2 5.2 0 0 0 1.1 2.7 11.8 11.8 0 0 0 4.5 4c1.7.7 2.3.8 3.2.7a2.7 2.7 0 0 0 1.8-1.3 2.2 2.2 0 0 0 .2-1.3c-.1-.1-.3-.2-.5-.3Z"/></svg>
</a>
<dialog id="lightbox" aria-label="Enlarged image"><button type="button" class="lb-close" aria-label="Close">×</button><img alt=""><p class="lb-cap"></p></dialog>
'''

def insert_before(marker, block):
    global page
    assert page.count(marker) == 1, marker
    page = page.replace(marker, block + marker)

def insert_after_section(section_marker, block):
    global page
    i = page.index(section_marker)
    j = page.index("</section>", i) + len("</section>")
    page = page[:j] + "\n" + block + page[j:]

assert "<!-- FLOOR PLANS -->" not in page, "sections already added"
insert_after_section("<!-- RESIDENCES -->", floorplans + specifications)
insert_after_section("<!-- AMENITIES -->", gallery + masterplan)
insert_after_section("<!-- LOCATION -->", consultants + faq)
# map at the end of the location container
loc = page.index("<!-- LOCATION -->"); end = page.index("</section>", loc)
last_div = page.rindex("</div>", loc, end)
page = page[:last_div] + mapblock + page[last_div:]
insert_before("<script src=\"/app.js\" defer></script>", whatsapp)

# nav links
page = page.replace('<a href="#amenities" style="text-decoration:none">Amenities</a>',
                    '<a href="#plans" style="text-decoration:none">Floor plans</a>\n<a href="#amenities" style="text-decoration:none">Amenities</a>\n<a href="#gallery" style="text-decoration:none">Gallery</a>', 1)
page = page.replace('<a href="#location" style="text-decoration:none">Location</a>',
                    '<a href="#location" style="text-decoration:none">Location</a>\n<a href="#faq" style="text-decoration:none">FAQ</a>', 1)

# residences note now points to the plans
page = page.replace('Prices will be announced at launch. Registered buyers hear first. <a href="#register">Register now</a>',
                    'Prices will be announced at launch. Registered buyers hear first. <a href="#plans">See all floor plans</a> · <a href="#register">Register now</a>', 1)

# brochure download after registering
page = page.replace("Our team will call you before the launch.</p>",
                    'Our team will call you before the launch.</p>\n<p style="margin:18px 0 0"><a href="assets/9minnutes-brochure.pdf" download style="display:inline-block;background:#1C3A50;color:#FFFFFF;text-decoration:none;padding:14px 26px;border-radius:999px;font-weight:500">Download the brochure</a> <span style="background:#FFF3CD;color:#6A4700;padding:2px 6px;font-size:13px">[ADD BROCHURE PDF as public/assets/9minnutes-brochure.pdf]</span></p>', 1)
page = page.replace("Register your interest and our team will call you before prices are announced, with floor plans and pre-launch details.",
                    "Register your interest to download the brochure. Our team will call you before prices are announced, with floor plans and pre-launch details.", 1)

idx.write_text(page, encoding="utf-8")
print("sections added;", len(page), "bytes")
