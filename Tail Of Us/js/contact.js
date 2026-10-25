/* Contact form: checks every field in the browser (process-contact.php checks again on the server). */
(function () {
  'use strict';

  var form = document.getElementById('contact-form');
  var summary = document.getElementById('error-summary');
  var notice = document.getElementById('sent-notice');

  if (notice && /[?&]sent=1(&|$)/.test(window.location.search)) {
    notice.hidden = false;
  }
  if (!form) { return; }

  form.noValidate = true; // our messages replace the browser's; without JS the native checks still run

  var rules = {
    name: function (v) {
      if (v === '') { return 'Enter your name.'; }
      if (v.length < 2 || v.length > 60) { return 'Your name must be between 2 and 60 characters.'; }
      if (!/^[A-Za-zÀ-ÖØ-öø-ÿ][A-Za-zÀ-ÖØ-öø-ÿ\s'.\-]*$/.test(v)) { return 'Your name can only contain letters, spaces, hyphens and apostrophes.'; }
      return '';
    },
    email: function (v) {
      if (v === '' || v.length > 100 || !/^[^\s@]+@[^\s@]+\.[^\s@]{2,}$/.test(v)) { return 'Enter a valid email address, like name@example.com.'; }
      return '';
    },
    message: function (v) {
      if (v.length < 10 || v.length > 1000) { return 'Your message must be between 10 and 1000 characters.'; }
      if (/(https?:\/\/|www\.)/i.test(v)) { return 'Links are not allowed in messages.'; }
      return '';
    }
  };

  function setError(field, text) {
    var box = document.getElementById(field.id + '-error');
    var wrap = field.closest('.form-field');
    box.textContent = text ? 'Error: ' + text : '';
    if (text) {
      field.setAttribute('aria-invalid', 'true');
      wrap.classList.add('has-error');
    } else {
      field.removeAttribute('aria-invalid');
      wrap.classList.remove('has-error');
    }
  }

  function check(field) {
    var text = rules[field.name](field.value.trim());
    setError(field, text);
    return text;
  }

  Object.keys(rules).forEach(function (n) {
    var f = form.elements[n];
    f.addEventListener('blur', function () { check(f); });
    f.addEventListener('input', function () { if (f.getAttribute('aria-invalid')) { check(f); } });
  });

  form.addEventListener('submit', function (e) {
    var list = summary.querySelector('ul');
    var first = null;
    list.innerHTML = '';

    Object.keys(rules).forEach(function (n) {
      var f = form.elements[n];
      var text = check(f);
      if (text) {
        var li = document.createElement('li');
        var a = document.createElement('a');
        a.href = '#' + f.id;
        a.textContent = text;
        a.addEventListener('click', function (ev) { ev.preventDefault(); f.focus(); });
        li.appendChild(a);
        list.appendChild(li);
        if (!first) { first = f; }
      }
    });

    if (first) {
      e.preventDefault();
      summary.hidden = false;
      summary.focus();
    } else {
      summary.hidden = true;
    }
  });
}());
