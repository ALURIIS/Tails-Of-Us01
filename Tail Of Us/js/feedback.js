/* Feedback form: checks every input and shows clear errors. */
(function () {
  var form = document.getElementById("feedback-form");
  if (!form) { return; }
  form.noValidate = true; /* our messages replace the browser's; without JS the browser still validates */

  var summary = document.getElementById("error-summary");
  var summaryList = summary.querySelector("ul");
  var NAME_RE = /^\p{L}[\p{L}\s'.-]*$/u;
  var EMAIL_RE = /^[^\s@]+@[^\s@]+\.[^\s@]{2,}$/;
  var LINK_RE = /(https?:\/\/|www\.)/i;

  var fields = [
    { id: "fullname", label: "Full name", check: function (v) {
      if (!v) { return "Enter your full name."; }
      if (v.length < 2 || v.length > 60) { return "Your name must be between 2 and 60 characters."; }
      if (!NAME_RE.test(v)) { return "Use letters, spaces, hyphens or apostrophes only (no digits)."; }
      return ""; } },
    { id: "email", label: "Email address", check: function (v) {
      if (!v) { return "Enter your email address."; }
      if (!EMAIL_RE.test(v)) { return "Enter a valid email address, like name@example.com."; }
      return ""; } },
    { id: "subject", label: "Subject", check: function (v) {
      return v ? "" : "Choose a subject from the list."; } },
    { id: "message", label: "Message", check: function (v) {
      if (!v) { return "Write your feedback."; }
      if (v.length < 10) { return "Your message must be at least 10 characters."; }
      if (v.length > 1000) { return "Your message must be 1000 characters or fewer."; }
      if (LINK_RE.test(v)) { return "Links are not allowed in feedback."; }
      return ""; } }
  ];

  function show(el, errId, msg) {
    document.getElementById(errId).textContent = msg;
    if (msg) { el.setAttribute("aria-invalid", "true"); } else { el.removeAttribute("aria-invalid"); }
    return msg;
  }
  function checkField(f) {
    var el = document.getElementById(f.id);
    return show(el, f.id + "-error", f.check(el.value.trim()));
  }
  function checkRating() {
    var chosen = form.querySelector('input[name="satisfaction"]:checked');
    return show(document.getElementById("rating-group"), "satisfaction-error",
      chosen ? "" : "Choose how satisfied you were.");
  }

  fields.forEach(function (f) {
    var el = document.getElementById(f.id);
    el.addEventListener("blur", function () { checkField(f); });
    el.addEventListener("input", function () { if (el.getAttribute("aria-invalid")) { checkField(f); } });
  });
  form.addEventListener("change", function (e) {
    if (e.target.name === "satisfaction") { checkRating(); }
  });

  form.addEventListener("submit", function (e) {
    e.preventDefault();
    var problems = [];
    fields.forEach(function (f) {
      var msg = checkField(f);
      if (msg) { problems.push({ href: "#" + f.id, text: f.label + ": " + msg }); }
    });
    var ratingMsg = checkRating();
    if (ratingMsg) { problems.push({ href: "#rating-first", text: "Satisfaction: " + ratingMsg }); }

    summaryList.textContent = "";
    if (problems.length) {
      problems.forEach(function (p) {
        var li = document.createElement("li");
        var a = document.createElement("a");
        a.href = p.href;
        a.textContent = p.text;
        li.appendChild(a);
        summaryList.appendChild(li);
      });
      summary.hidden = false;
      summary.focus();
      return;
    }
    summary.hidden = true;
    form.submit(); /* real POST to process-feedback.php, which saves it */
  });

})();
