"""Turn the Claude Design artboard (tools/design-source.dc.html) into the static public/index.html.

Run from the repo root:  python3 tools/build_html.py
Only needed when the design is re-exported; normal edits can go straight into public/index.html.
"""
import html, re, pathlib

ROOT = pathlib.Path(__file__).resolve().parent.parent
src = (ROOT / "tools/design-source.dc.html").read_text(encoding="utf-8")

BLOBS = {
    "33997858a3e84722d43adda38e64fd1c": "assets/logo-9minnutes.svg",
    "6168cc26e096e49233f512f6c0bbc0fe": "assets/hero.jpg",
    "ec49ad8d451ae204fa2ae70ba5a9a99a": "assets/citymap.jpg",
    "16921c951c053fdb6cf886b1ff38d5bc": "assets/metro-tower.jpg",
    "a82b5f3f75a4334916765473b63ddb95": "assets/terrace.jpg",
    "7f63720d1d5501e2ef2c9ba225a6d9c9": "assets/pool.jpg",
    "d2ece3bd7695cc26ebaefb2915572701": "assets/gym.jpg",
    "e9f3e1385ff0c0282f1ee0294410859b": "assets/lawn.jpg",
    "f79d3d9b1a7d02135c24ad445c9597f3": "assets/banquet.jpg",
    "c58a9d1d725c887b509d68dce8cd0d8d": "assets/aerial.jpg",
    "bae4894d83190f7a6b30dc3a9fe1aa17": "assets/logo-realmark.svg",
}

# ---- data the design's loops render (kept in sync with the design's renderVals) ----
script = src[src.index("renderVals()"):]
def grab(name):
    m = re.search(name + r":\s*(\[.*?\n\s*\])", script, re.S)
    return m.group(1)
def js_to_py(s):
    import json
    s = re.sub(r"'((?:[^'\\]|\\.)*)'", lambda m: json.dumps(m.group(1), ensure_ascii=False), s)
    s = re.sub(r"(\{|,)\s*([A-Za-z_]+):", r'\1"\2":', s)
    return json.loads(s)
data = {k: js_to_py(grab(k)) for k in ["metro", "homes", "amen", "conn", "nearby"]}

body = src[src.index("<x-dc>") + 6: src.index("</x-dc>")]
body = re.sub(r"<helmet>.*?</helmet>", "", body, flags=re.S)
for k, v in BLOBS.items():
    body = body.replace(f"/_blob/{k}", v)

def block(start_marker):
    i = body.index(start_marker)
    depth, j = 0, i
    while True:
        o = body.find("<sc-for", j); c = body.find("</sc-for>", j)
        if o != -1 and o < c:
            depth += 1; j = o + 7
        else:
            depth -= 1; j = c + 9
            if depth == 0:
                return body[i:j]

def fill(tpl, ctx):
    return re.sub(r"\{\{\s*([\w.$]+)\s*\}\}", lambda m: html.escape(str(ctx.get(m.group(1), ""))), tpl)

def expand(name, alias, items, inner=None):
    global body
    blk = block(f'<sc-for list="{{{{{name}}}}}"')
    tpl = blk[blk.index(">") + 1: blk.rindex("</sc-for>")]
    out = []
    for it in items:
        t = tpl
        if inner:
            iname, ialias, ikey = inner
            iblk = re.search(r'<sc-for list="\{\{' + re.escape(alias + "." + ikey) + r'\}\}".*?</sc-for>', t, re.S).group(0)
            itpl = iblk[iblk.index(">") + 1: iblk.rindex("</sc-for>")]
            rendered = ""
            for x in it[ikey]:
                ctx = {ialias: x} if not isinstance(x, dict) else {f"{ialias}.{k}": v for k, v in x.items()}
                rendered += fill(itpl, ctx)
            t = t.replace(iblk, rendered)
        # sc-if on a flag inside the loop (resolve before filling holes)
        t = re.sub(r'<sc-if value="\{\{[^}]+\}\}"[^>]*>(.*?)</sc-if>', lambda m: m.group(1) if it.get("flag") else "", t, flags=re.S)
        ctx = {f"{alias}.{k}": v for k, v in it.items()} if isinstance(it, dict) else {alias: it}
        t = fill(t, ctx)
        out.append(t)
    body = body.replace(blk, "".join(out))

expand("metro", "m", data["metro"])
expand("homes", "h", data["homes"])
expand("amen", "g", data["amen"], inner=("items", "it", "items"))
expand("conn", "c", data["conn"])
expand("nearby", "n", data["nearby"], inner=("items", "i", "items"))

# ---- the register form: real fields + honeypot + thank-you panel ----
form_start = body.index('<sc-if value="{{showForm}}"')
form_end = body.index("</sc-if>", body.index('<sc-if value="{{sent}}"')) + len("</sc-if>")
form_html = (ROOT / "tools/form.html").read_text(encoding="utf-8")
body = body[:form_start] + form_html + body[form_end:]

left = re.findall(r"\{\{[^}]*\}\}|<sc-for|<sc-if", body)
assert not left, f"unrendered template left: {left[:5]}"

page = (ROOT / "tools/page-shell.html").read_text(encoding="utf-8").replace("<!--BODY-->", body.strip())
(ROOT / "public/index.html").write_text(page, encoding="utf-8")
print("wrote public/index.html", len(page), "bytes")
