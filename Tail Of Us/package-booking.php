<?php
require_once __DIR__ . '/includes/booking-store.php';
require_once __DIR__ . '/includes/packages.php';
require_once __DIR__ . '/includes/booking-page.php';

$packages = booking_packages();
$slug     = (string) ($_GET['package'] ?? '');

if (!isset($packages[$slug])) {
    http_response_code(404);
    booking_page_open('Package Not Found');
    ?>
  <section class="booking-banner" aria-labelledby="nf-title">
    <nav class="breadcrumb" aria-label="Breadcrumb"><ol><li><a href="booking.html">Booking</a></li><li><a href="travel-packages.html">Travel Packages</a></li><li aria-current="page">Package Not Found</li></ol></nav>
    <h1 id="nf-title">Package Not Found</h1>
    <p>We could not find that package. Please select a package from our list or submit a custom booking request.</p>
    <p class="actions"><a class="btn" href="travel-packages.html">Travel Packages</a> <a class="btn btn-secondary" href="contact-booking.html">Custom Booking</a></p>
  </section>
<?php
    booking_page_close();
    exit;
}

$p        = $packages[$slug];
$waitlist = ($p['status'] === 'booked');
$heading  = ($waitlist ? 'Join the Waitlist: ' : 'Book: ') . $p['name'];

booking_page_open($heading, ['js/package-booking.js']);
?>
  <section class="booking-banner" aria-labelledby="pb-title">
    <nav class="breadcrumb" aria-label="Breadcrumb"><ol><li><a href="booking.html">Booking</a></li><li><a href="travel-packages.html">Travel Packages</a></li><li aria-current="page"><?= bk_h($p['name']) ?></li></ol></nav>
    <h1 id="pb-title"><?= bk_h($heading) ?></h1>
    <p><?= $waitlist ? 'This package is currently fully booked. Join the waitlist and our team will notify you as soon as a slot opens.' : 'Tell us about your pet and preferred start date. Our team will review and confirm your stay by phone.' ?></p>
  </section>

  <section class="card pkg-summary" aria-labelledby="sum-title" style="margin-top: 1.5rem; padding: 1.25rem;">
    <h2 id="sum-title" style="margin-top:0;"><?= bk_h($p['name']) ?></h2>
    <p class="status status-<?= bk_h($p['status']) ?>"><?= $waitlist ? 'Booked' : 'Available' ?></p>
    <p><?= bk_h($p['blurb']) ?></p>
    <p class="rating"><span aria-hidden="true">★</span> <?= bk_h($p['rating']) ?> <span class="visually-hidden">out of 5,</span> (<?= (int) $p['reviews'] ?> reviews)</p>
    <p><a href="travel-packages.html">Choose a different package</a></p>
  </section>

  <div class="booking-form-card">
    <div id="error-summary" class="error-summary" role="alert" aria-labelledby="error-title" tabindex="-1" hidden>
      <h2 id="error-title">Please fix the following</h2>
      <ul></ul>
    </div>

    <form id="booking-form" action="process-package-booking.php" method="post">
      <input type="hidden" name="package" value="<?= bk_h($slug) ?>">
      <p class="req-note">Fields marked <span class="req" aria-hidden="true">*</span> are required.</p>

      <div class="field-row">
        <div class="form-field">
          <label for="owner">Owner name <span class="req" aria-hidden="true">*</span></label>
          <input type="text" id="owner" name="owner" autocomplete="name" required minlength="2" maxlength="60" aria-describedby="owner-error">
          <p class="error-message" id="owner-error"></p>
        </div>
        <div class="form-field">
          <label for="phone">Phone number <span class="req" aria-hidden="true">*</span></label>
          <input type="tel" id="phone" name="phone" placeholder="+230 5123 4567" autocomplete="tel" required maxlength="25" aria-describedby="phone-hint phone-error">
          <p class="hint" id="phone-hint">Used for verification and lookup on other devices.</p>
          <p class="error-message" id="phone-error"></p>
        </div>
      </div>

      <div class="field-row">
        <div class="form-field">
          <label for="animal">Animal name <span class="req" aria-hidden="true">*</span></label>
          <input type="text" id="animal" name="animal" required maxlength="40" aria-describedby="animal-error">
          <p class="error-message" id="animal-error"></p>
        </div>
        <div class="form-field">
          <label for="species">Species <span class="req" aria-hidden="true">*</span></label>
          <select id="species" name="species" required aria-describedby="species-error">
            <option value="">Select</option>
<?php foreach (booking_species() as $value => $label): ?>
            <option value="<?= bk_h($value) ?>"><?= bk_h($label) ?></option>
<?php endforeach; ?>
          </select>
          <p class="error-message" id="species-error"></p>
        </div>
        <div class="form-field">
          <label for="startdate"><?= $waitlist ? 'Preferred start date' : 'Start date' ?> <span class="req" aria-hidden="true">*</span></label>
          <input type="date" id="startdate" name="startdate" required aria-describedby="startdate-error">
          <p class="error-message" id="startdate-error"></p>
        </div>
      </div>

      <div class="form-field">
        <label for="comments">Comments (diets, medications, habits)</label>
        <textarea id="comments" name="comments" rows="4" placeholder="Anything we should know..." maxlength="500" aria-describedby="comments-hint comments-error"></textarea>
        <p class="hint" id="comments-hint">Optional. Up to 500 characters. Links are not allowed.</p>
        <p class="error-message" id="comments-error"></p>
      </div>

      <div class="form-field">
        <label class="choice"><input type="checkbox" id="vaccinated" name="vaccinated" value="yes" required aria-describedby="vaccinated-error"> I confirm my animal's vaccinations are up to date <span class="req" aria-hidden="true">*</span></label>
        <p class="error-message" id="vaccinated-error"></p>
      </div>

      <div class="form-actions">
        <button type="submit" class="btn"><?= $waitlist ? 'Join Waitlist' : 'Book Now' ?></button>
      </div>
    </form>
  </div>
<?php booking_page_close(); ?>
