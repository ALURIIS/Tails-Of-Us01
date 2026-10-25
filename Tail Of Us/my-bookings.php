<?php
require_once __DIR__ . '/includes/booking-store.php';
require_once __DIR__ . '/includes/packages.php';
require_once __DIR__ . '/includes/booking-session.php';
require_once __DIR__ . '/includes/booking-page.php';

booking_session_start();

$packages  = booking_packages();
$species   = booking_species();
$rooms     = booking_rooms();
$statuses  = booking_statuses();
$error     = '';
$lookupRef = '';

function fmt_Date($iso) {
    $t = strtotime((string) $iso);
    return $t ? date('j F Y', $t) : '';
}

// Keep existing calls working under their original name
function fmt_Date_Alias($iso) {
    return fmt_Date($iso);
}

function booking_Title($b, $packages) {
    if (($b['type'] ?? '') === 'package') {
        $name = $packages[$b['package'] ?? '']['name'] ?? 'Package';
        return (!empty($b['waitlist']) ? 'Waitlist: ' : 'Package: ') . $name;
    }
    return 'Custom Booking';
}

function cancel_Booking_Entry($id) {
    return booking_modify(function ($all) use ($id) {
        foreach ($all as &$b) {
            if (($b['id'] ?? '') === $id) {
                $b['status'] = 'cancelled';
            }
        }
        return $all;
    });
}

function find_Booking_ByRef($ref) {
    foreach (load_bookings() as $b) {
        if (strtoupper($b['id'] ?? '') === strtoupper($ref)) {
            return $b;
        }
    }
    return null;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!booking_csrf_check($_POST['csrf'] ?? '')) {
        $error = 'Your session expired. Please try again.';
    } else {
        $action = (string) ($_POST['action'] ?? '');

        if ($action === 'lookup') {
            $fails     = (int) ($_SESSION['lookup_fails'] ?? 0);
            $lookupRef = strtoupper(trim((string) ($_POST['reference'] ?? '')));
            $phone     = trim((string) ($_POST['phone'] ?? ''));

            if ($fails >= 10) {
                $error = 'Too many failed lookup attempts. Please try again later or call us directly.';
            } else {
                $b = find_Booking_ByRef($lookupRef);
                $d = preg_replace('/\D/', '', $phone);
                $bd = preg_replace('/\D/', '', $b['phone'] ?? '');

                if ($b && strlen($d) >= 7 && $d === $bd) {
                    booking_remember($b['id']);
                    $_SESSION['lookup_fails'] = 0;
                    header('Location: my-bookings.php?found=' . urlencode($b['id']));
                    exit;
                }
                $_SESSION['lookup_fails'] = $fails + 1;
                $error = 'No booking found matching that reference and phone number. Please check both and try again.';
            }
        } elseif ($action === 'cancel') {
            $id = (string) ($_POST['id'] ?? '');
            if (!in_array($id, booking_owned_ids(), true)) {
                $error = 'That booking was not found in your session list.';
            } elseif (!cancel_Booking_Entry($id)) {
                $error = 'The booking could not be cancelled. Please try again later.';
            } else {
                header('Location: my-bookings.php?cancelled=' . urlencode($id));
                exit;
            }
        }
    }
}

$owned = booking_owned_ids();
$mine  = [];
foreach (load_bookings() as $b) {
    if (in_array($b['id'] ?? '', $owned, true)) {
        $mine[] = $b;
    }
}
usort($mine, function ($a, $b) {
    return strcmp((string) ($b['date'] ?? ''), (string) ($a['date'] ?? ''));
});

$notices = [];
$ref = (string) ($_GET['placed'] ?? '');
if ($ref !== '' && in_array($ref, $owned, true)) {
    $notices[] = 'Thank you! Your request has been received. Your reference code is ' . $ref . '. Save this code along with your phone number to find your booking on other devices.';
}
$ref = (string) ($_GET['found'] ?? '');
if ($ref !== '' && in_array($ref, $owned, true)) {
    $notices[] = 'We located booking ' . $ref . ' and added it to your current list.';
}
$ref = (string) ($_GET['cancelled'] ?? '');
if ($ref !== '' && in_array($ref, $owned, true)) {
    $notices[] = 'Booking ' . $ref . ' has been successfully cancelled.';
}

$confirmId = (string) ($_GET['confirm'] ?? '');
$confirm   = null;
foreach ($mine as $b) {
    if (($b['id'] ?? '') === $confirmId && ($b['status'] ?? '') !== 'cancelled') {
        $confirm = $b;
    }
}

booking_page_open('My Bookings');
?>
  <section class="booking-banner" aria-labelledby="mb-title">
    <nav class="breadcrumb" aria-label="Breadcrumb"><ol><li><a href="booking.html">Booking</a></li><li aria-current="page">My Bookings</li></ol></nav>
    <h1 id="mb-title">My Bookings</h1>
    <p>Review your submitted booking requests, check their status, or cancel them if your plans change.</p>
  </section>

<?php foreach ($notices as $notice): ?>
  <output class="sent-notice" style="display: block; width: 100%; box-sizing: border-box; margin-top: 1.5rem; padding: 1rem; border: 2px solid var(--success); border-radius: 8px; font-weight: 600;"><?= bk_h($notice) ?></output>
<?php endforeach; ?>

<?php if ($error !== ''): ?>
  <p class="sent-notice" role="alert" style="display: block; width: 100%; box-sizing: border-box; margin-top: 1.5rem; padding: 1rem; border: 2px solid var(--error); border-radius: 8px; font-weight: 600; color: var(--error);"><?= bk_h($error) ?></p>
<?php endif; ?>

<?php if ($confirm): ?>
  <section class="card confirm-card" aria-labelledby="confirm-title" style="margin-top: 2rem; padding: 1.5rem;">
    <h2 id="confirm-title" style="margin-top:0;"><?= ($confirm['status'] ?? '') === 'waitlisted' ? 'Leave the waitlist?' : 'Cancel this booking?' ?></h2>
    <p><strong><?= bk_h(booking_title($confirm, $packages)) ?></strong> for <?= bk_h($confirm['animal'] ?? '') ?> (Reference: <code><?= bk_h($confirm['id']) ?></code>).</p>
    <p>This action cannot be undone. To book again later, you will need to submit a new request.</p>
    <form action="my-bookings.php" method="post" class="confirm-actions" style="margin-top: 1.5rem; display: flex; gap: 1rem;">
      <input type="hidden" name="action" value="cancel">
      <input type="hidden" name="id" value="<?= bk_h($confirm['id']) ?>">
      <input type="hidden" name="csrf" value="<?= bk_h(booking_csrf()) ?>">
      <button type="submit" class="btn" style="background: var(--error); border-color: var(--error);"><?= ($confirm['status'] ?? '') === 'waitlisted' ? 'Yes, leave waitlist' : 'Yes, cancel booking' ?></button>
      <a class="btn btn-secondary" href="my-bookings.php">No, keep it</a>
    </form>
  </section>
<?php else: ?>

  <section class="about-block" aria-labelledby="list-title" style="margin-top: 2rem;">
    <h2 id="list-title">Your Active & Past Requests</h2>
<?php if ($mine): ?>
    <ul class="list-plain booking-list" style="display: grid; gap: 1.5rem; margin-top: 1rem;">
<?php foreach ($mine as $b):
    $status = (string) ($b['status'] ?? 'pending');
    $isPkg  = ($b['type'] ?? '') === 'package'; ?>
      <li class="card booking-item" style="padding: 1.25rem;">
        <h3 style="margin-top:0;"><?= bk_h($b['animal'] ?? 'Pet') ?> – <?= bk_h(booking_title($b, $packages)) ?></h3>
        <p class="booking-status status-<?= bk_h($status) ?>" style="display: inline-block; padding: 0.2rem 0.75rem; border-radius: 2rem; font-weight: 700; font-size: 0.875rem;">Status: <?= bk_h($statuses[$status] ?? ucfirst($status)) ?></p>
        <dl class="booking-details" style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 0.75rem; margin: 1rem 0;">
          <div><dt style="font-weight:700;">Reference</dt><dd><code><?= bk_h($b['id'] ?? '') ?></code></dd></div>
          <div><dt style="font-weight:700;">Species</dt><dd><?= bk_h($species[$b['species'] ?? ''] ?? $b['species'] ?? '') ?></dd></div>
<?php if ($isPkg): ?>
          <div><dt style="font-weight:700;"><?= !empty($b['waitlist']) ? 'Preferred Start' : 'Start Date' ?></dt><dd><?= bk_h(fmt_date($b['startdate'] ?? '')) ?></dd></div>
<?php else: ?>
          <div><dt style="font-weight:700;">Length of Stay</dt><dd><?= (int) ($b['days'] ?? 0) ?> day<?= (int) ($b['days'] ?? 0) === 1 ? '' : 's' ?></dd></div>
          <div><dt style="font-weight:700;">Room Type</dt><dd><?= bk_h($rooms[$b['room'] ?? ''] ?? $b['room'] ?? '') ?></dd></div>
<?php endif; ?>
          <div><dt style="font-weight:700;">Submitted On</dt><dd><?= bk_h(fmt_date($b['date'] ?? '')) ?></dd></div>
<?php if (!empty($b['comments'])): ?>
          <div style="grid-column: 1 / -1;"><dt style="font-weight:700;">Comments</dt><dd><?= nl2br(bk_h($b['comments'])) ?></dd></div>
<?php endif; ?>
        </dl>
<?php if ($status !== 'cancelled'): ?>
        <p style="margin-bottom:0;"><a class="btn btn-secondary" href="my-bookings.php?confirm=<?= bk_h($b['id']) ?>"><?= $status === 'waitlisted' ? 'Leave Waitlist' : 'Cancel Booking' ?><span class="visually-hidden"> – <?= bk_h($b['id']) ?></span></a></p>
<?php endif; ?>
      </li>
<?php endforeach; ?>
    </ul>
<?php else: ?>
    <p class="card empty" style="padding: 1.5rem; margin-top: 1rem;">No bookings found in this session. You can submit a <a href="contact-booking.html">custom booking request</a> or browse our <a href="travel-packages.html">travel packages</a>.</p>
<?php endif; ?>
  </section>

  <section class="about-block lookup" aria-labelledby="find-title" style="margin-top: 2.5rem; padding-top: 1.5rem; border-top: 1px solid rgba(128,128,128,0.35);">
    <h2 id="find-title">Find a Booking Placed on Another Device</h2>
    <p>Enter your Reference ID and the phone number used when making the reservation to bring it into your current session.</p>
    <form action="my-bookings.php" method="post" class="lookup-form" style="margin-top: 1rem;">
      <input type="hidden" name="action" value="lookup">
      <input type="hidden" name="csrf" value="<?= bk_h(booking_csrf()) ?>">
      <div class="field-row">
        <div class="form-field">
          <label for="reference">Reference ID <span class="req" aria-hidden="true">*</span></label>
          <input type="text" id="reference" name="reference" placeholder="TU-1A2B3C" maxlength="9" autocomplete="off" required value="<?= bk_h($lookupRef) ?>">
        </div>
        <div class="form-field">
          <label for="lookup-phone">Phone Number <span class="req" aria-hidden="true">*</span></label>
          <input type="tel" id="lookup-phone" name="phone" placeholder="+230 5123 4567" maxlength="25" autocomplete="tel" required>
        </div>
      </div>
      <button type="submit" class="btn" style="margin-top: 0.5rem;">Find Booking</button>
    </form>
  </section>
<?php endif; ?>
<?php booking_page_close(); ?>
