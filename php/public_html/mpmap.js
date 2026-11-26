// Master plan placed on the aerial view. Used by the site and by the admin alignment tool.
// Each .mpmap stage has data-x / data-y (plan centre, % of the aerial), data-w (plan width,
// % of the aerial width), data-r (rotation in degrees) and data-z (zoom of the aerial in the frame).
(function () {
  "use strict";
  function num(el, k, d) { var v = parseFloat(el.dataset[k]); return isFinite(v) ? v : d; }

  function layout(stage) {
    var layer = stage.querySelector(".mp-layer"), aerial = stage.querySelector(".mp-aerial"), plan = stage.querySelector(".mp-plan");
    if (!layer || !aerial || !plan) return;
    var x = num(stage, "x", 50), y = num(stage, "y", 50), w = num(stage, "w", 20), r = num(stage, "r", 0), z = stage.classList.contains("mp-full") ? 1 : Math.max(1, num(stage, "z", 1));
    var sw = stage.clientWidth, sh = stage.clientHeight;
    if (sw < 600 && !stage.classList.contains("mp-full")) z *= 1.6; // phones: closer in
    var ar = (aerial.naturalHeight && aerial.naturalWidth) ? aerial.naturalHeight / aerial.naturalWidth : num(stage, "ar", 0.5429);
    var lw = sw * z, lh = lw * ar;
    if (lh < sh) { lh = sh; lw = lh / ar; } // the aerial always fills the frame
    var left = sw / 2 - (x / 100) * lw, top = sh / 2 - (y / 100) * lh;
    left = Math.min(0, Math.max(sw - lw, left));
    top = Math.min(0, Math.max(sh - lh, top));
    layer.style.width = lw + "px"; layer.style.height = lh + "px";
    layer.style.transform = "translate(" + left + "px," + top + "px)";
    plan.style.left = x + "%"; plan.style.top = y + "%"; plan.style.width = w + "%";
    plan.style.transform = "translate(-50%,-50%) rotate(" + r + "deg)";
    stage.classList.add("ready");
  }

  function all() { Array.prototype.forEach.call(document.querySelectorAll(".mpmap"), layout); }
  window.MPMap = { layout: layout };
  Array.prototype.forEach.call(document.querySelectorAll(".mpmap .mp-aerial"), function (img) {
    if (!img.complete) img.addEventListener("load", all);
  });
  window.addEventListener("resize", all);
  all();

  // Admin alignment tool: drag the plan, use the sliders, values go into the form.
  var tool = document.getElementById("mp-align");
  if (!tool) return;
  var stage = tool.querySelector(".mpmap.mp-full"), plan = stage.querySelector(".mp-plan"), layer = stage.querySelector(".mp-layer");
  var preview = tool.querySelector(".mpmap:not(.mp-full)");
  var fields = ["x", "y", "w", "r", "z"];
  function sync(from) {
    fields.forEach(function (k) {
      var inputs = tool.querySelectorAll('[data-k="' + k + '"]');
      Array.prototype.forEach.call(inputs, function (i) { if (i !== from) i.value = (+stage.dataset[k]).toFixed(k === "z" ? 2 : 1); });
    });
    if (preview) { fields.forEach(function (k) { preview.dataset[k] = stage.dataset[k]; }); layout(preview); }
    layout(stage);
  }
  tool.addEventListener("input", function (e) {
    var k = e.target.dataset && e.target.dataset.k;
    if (!k || !isFinite(parseFloat(e.target.value))) return;
    stage.dataset[k] = e.target.value; sync(e.target);
    tool.dataset.dirty = "1";
  });
  var drag = null;
  plan.addEventListener("pointerdown", function (e) {
    drag = { px: e.clientX, py: e.clientY, x: +stage.dataset.x, y: +stage.dataset.y };
    e.preventDefault();
  });
  document.addEventListener("pointermove", function (e) {
    if (!drag) return;
    stage.dataset.x = Math.min(100, Math.max(0, drag.x + (e.clientX - drag.px) / layer.offsetWidth * 100));
    stage.dataset.y = Math.min(100, Math.max(0, drag.y + (e.clientY - drag.py) / layer.offsetHeight * 100));
    plan.style.left = stage.dataset.x + "%"; plan.style.top = stage.dataset.y + "%";
    tool.dataset.dirty = "1";
  });
  function end() { if (drag) { drag = null; sync(null); } }
  document.addEventListener("pointerup", end);
  document.addEventListener("pointercancel", end);
  sync(null);
})();
