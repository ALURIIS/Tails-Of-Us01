/* Contact Booking form: checks every field in the browser (process-booking.php checks again on the server). */
(function () {
  'use strict';

  var form = document.getElementById('booking-form');
  var summary = document.getElementById('error-summary');
  var notice = document.getElementById('sent-notice');

  if (notice && /[?&]sent=1(&|$)/.test(window.location.search)) {
    notice.hidden = false;
  }
  if (!form) { return; }

  form.noValidate = true; // our messages replace the browser's; without JS the native checks still run

  var LETTERS = 'A-Za-zÀ-ÖØ-öø-ÿ';
  var MAX_AGE_YEARS = 40;

  function pad(n) { return (n < 10 ? '0' : '') + n; }
  var now = new Date();
  var todayStr = now.getFullYear() + '-' + pad(now.getMonth() + 1) + '-' + pad(now.getDate());
  var oldestStr = (now.getFullYear() - MAX_AGE_YEARS) + '-' + pad(now.getMonth() + 1) + '-' + pad(now.getDate());
  form.elements.birthdate.max = todayStr;
  form.elements.birthdate.min = oldestStr;

  function val(name) { return String(form.elements[name].value).trim(); }

  /* key = field name; focus = element that receives focus; error = id prefix of its message */
  var fields = [
    { key: 'owner', focus: 'owner', check: function () {
      var v = val('owner');
      if (v === '') { return 'Enter the owner\'s name.'; }
      if (v.length < 2 || v.length > 60) { return 'The owner\'s name must be between 2 and 60 characters.'; }
      if (!new RegExp('^[' + LETTERS + '][' + LETTERS + '\\s\'.\\-]*$').test(v)) { return 'The owner\'s name can only contain letters, spaces, hyphens and apostrophes.'; }
      return '';
    } },
    { key: 'phone', focus: 'phone', check: function () {
      var v = val('phone');
      var digits = v.replace(/\D/g, '').length;
      if (v === '') { return 'Enter a phone number.'; }
      if (!/^\+?[0-9\s().\-]+$/.test(v) || digits < 7 || digits > 15) { return 'Enter a valid phone number, like +230 5123 4567.'; }
      return '';
    } },
    { key: 'address', focus: 'address', check: function () {
      var v = val('address');
      if (v === '') { return 'Enter your address.'; }
      if (v.length < 5 || v.length > 150) { return 'Your address must be between 5 and 150 characters.'; }
      return '';
    } },
    { key: 'days', focus: 'days', check: function () {
      var v = val('days');
      if (v === '') { return 'Enter the number of days.'; }
      if (!/^\d+$/.test(v) || +v < 1 || +v > 90) { return 'The number of days must be a whole number from 1 to 90.'; }
      return '';
    } },
    { key: 'animal', focus: 'animal', check: function () {
      var v = val('animal');
      if (v === '') { return 'Enter your animal\'s name.'; }
      if (v.length > 40) { return 'The animal\'s name must be 40 characters or fewer.'; }
      if (!new RegExp('^[' + LETTERS + '0-9][' + LETTERS + '0-9\\s\'.\\-]*$').test(v)) { return 'The animal\'s name can only contain letters, numbers, spaces, hyphens and apostrophes.'; }
      return '';
    } },
    { key: 'birthdate', focus: 'birthdate', check: function () {
      var v = val('birthdate');
      var m = /^(\d{4})-(\d{2})-(\d{2})$/.exec(v);
      if (v === '') { return 'Enter your animal\'s birth date.'; }
      if (!m) { return 'Enter a valid birth date.'; }
      var d = new Date(+m[1], +m[2] - 1, +m[3]);
      if (d.getFullYear() !== +m[1] || d.getMonth() !== +m[2] - 1 || d.getDate() !== +m[3]) { return 'Enter a valid birth date.'; }
      if (v > todayStr) { return 'The birth date cannot be in the future.'; }
      if (v < oldestStr) { return 'The birth date cannot be more than ' + MAX_AGE_YEARS + ' years ago.'; }
      return '';
    } },
    { key: 'species', focus: 'species', check: function () {
      return val('species') === '' ? 'Choose a species from the list.' : '';
    } },
    { key: 'room', focus: 'room-standard', check: function () {
      return val('room') === '' ? 'Choose a room type.' : '';
    } },
    { key: 'comments', focus: 'comments', check: function () {
      var v = val('comments');
      if (v.length > 500) { return 'Comments must be 500 characters or fewer.'; }
      if (/(https?:\/\/|www\.)/i.test(v)) { return 'Links are not allowed in comments.'; }
      return '';
    } },
    { key: 'vaccinated', focus: 'vaccinated', check: function () {
      return form.elements.vaccinated.checked ? '' : 'Confirm that your animal\'s vaccinations are up to date.';
    } }
  ];

  var byKey = {};
  fields.forEach(function (f) { byKey[f.key] = f; });

  function show(f, text) {
    var box = document.getElementById(f.key + '-error');
    var wrap = box.closest('.form-field');
    var input = document.getElementById(f.focus);
    box.textContent = text ? 'Error: ' + text : '';
    wrap.classList.toggle('has-error', !!text);
    if (f.key !== 'room') {
      if (text) { input.setAttribute('aria-invalid', 'true'); } else { input.removeAttribute('aria-invalid'); }
    }
  }

  function run(f) {
    var text = f.check();
    show(f, text);
    return text;
  }

  function fieldOf(target) { return byKey[target.name]; }

  form.addEventListener('focusout', function (e) {
    var f = fieldOf(e.target);
    if (f && e.target.type !== 'radio' && e.target.type !== 'checkbox') { run(f); }
  });
  form.addEventListener('change', function (e) {
    var f = fieldOf(e.target);
    if (f) { run(f); }
  });
  form.addEventListener('input', function (e) {
    var f = fieldOf(e.target);
    if (f && e.target.getAttribute('aria-invalid')) { run(f); }
  });

  form.addEventListener('submit', function (e) {
    var list = summary.querySelector('ul');
    var first = null;
    list.innerHTML = '';

    fields.forEach(function (f) {
      var text = run(f);
      if (text) {
        var li = document.createElement('li');
        var a = document.createElement('a');
        a.href = '#' + f.focus;
        a.textContent = text;
        a.addEventListener('click', function (ev) { ev.preventDefault(); document.getElementById(f.focus).focus(); });
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
