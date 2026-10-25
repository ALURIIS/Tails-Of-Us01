(function () {
  var root = document.documentElement;
  var btn = document.querySelector(".theme-toggle");
  function apply(on) {
    if (on) { root.setAttribute("data-theme", "contrast"); } else { root.removeAttribute("data-theme"); }
    if (btn) { btn.setAttribute("aria-pressed", on ? "true" : "false"); }
  }
  var saved = false;
  try { saved = localStorage.getItem("tou-contrast") === "on"; } catch (e) { saved = false; }
  apply(saved);
  if (btn) {
    btn.addEventListener("click", function () {
      var on = root.getAttribute("data-theme") !== "contrast";
      apply(on);
      try { localStorage.setItem("tou-contrast", on ? "on" : "off"); } catch (e) { }
    });
  }
})();
