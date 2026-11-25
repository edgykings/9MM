// Local preview: serves public/ and /api/lead on http://localhost:3000
//   FARVISION_CLIENT_ID=... node tools/dev-server.js
// Set FARVISION_API_URL to a test endpoint if you don't want real leads in Farvision.
const http = require("http");
const fs = require("fs");
const path = require("path");
const handler = require("../api/lead");

const PUB = path.join(__dirname, "..", "public");
const TYPES = { ".html": "text/html; charset=utf-8", ".js": "text/javascript", ".css": "text/css", ".svg": "image/svg+xml", ".jpg": "image/jpeg", ".png": "image/png" };

http.createServer((req, res) => {
  if (req.url.startsWith("/api/lead")) {
    let raw = "";
    req.on("data", (c) => (raw += c));
    req.on("end", () => {
      const shim = {
        status(c) { res.statusCode = c; return this; },
        json(b) { res.setHeader("Content-Type", "application/json"); res.end(JSON.stringify(b)); return this; },
        setHeader: (k, v) => res.setHeader(k, v),
      };
      let body = {};
      try { body = JSON.parse(raw || "{}"); } catch (e) {}
      handler({ method: req.method, body }, shim);
    });
    return;
  }
  let p = decodeURIComponent(req.url.split("?")[0]);
  if (p === "/") p = "/index.html";
  const file = path.join(PUB, path.normalize(p).replace(/^(\.\.[/\\])+/, ""));
  fs.readFile(file, (err, data) => {
    if (err) { res.statusCode = 404; return res.end("Not found"); }
    res.setHeader("Content-Type", TYPES[path.extname(file)] || "application/octet-stream");
    res.end(data);
  });
}).listen(process.env.PORT || 3000, () => console.log("Preview on http://localhost:" + (process.env.PORT || 3000)));
