// Small helpers for the admin panel (no inline scripts, so the strict CSP stays on).
(function () {
  "use strict";
  // FAQ: add a blank question row.
  var add = document.getElementById("faq-add");
  if (add) {
    add.addEventListener("click", function () {
      var i = parseInt(add.dataset.next, 10);
      add.dataset.next = String(i + 1);
      var row = document.createElement("div");
      row.className = "faq-row";
      row.innerHTML =
        '<label>Question<input name="faq[' + i + '][q]" maxlength="200"></label>' +
        '<label>Answer<textarea name="faq[' + i + '][a]" rows="2" maxlength="1500"></textarea></label>';
      document.getElementById("faq-rows").appendChild(row);
      row.querySelector("input").focus();
    });
  }
  // Ask before destructive actions.
  document.querySelectorAll("button.danger").forEach(function (b) {
    b.addEventListener("click", function (e) {
      if (!window.confirm("Are you sure? This can't be undone.")) e.preventDefault();
    });
  });
  // Warn before leaving the content page with unsaved edits.
  var form = document.getElementById("content-form");
  if (form) {
    var dirty = false;
    form.addEventListener("input", function () { dirty = true; });
    form.addEventListener("submit", function () { dirty = false; });
    window.addEventListener("beforeunload", function (e) { if (dirty) { e.preventDefault(); e.returnValue = ""; } });
  }
})();
