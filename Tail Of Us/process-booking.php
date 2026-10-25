<?php
/* Receives the Contact Booking form, checks every input again (never trust the browser) and saves it. */
require_once __DIR__ . '/includes/booking-store.php';
require_once __DIR__ . '/includes/layout.php';

function fail_page($code, $heading, $lines) {
    http_response_code($code);
    page_open($heading);
    echo '<main id="main" tabindex="-1"><div class="container fb-page"><section class="fb-banner"><h1>' . h($heading) . '</h1><ul>';
    foreach ($lines as $line) {
        echo '<li>' . h($line) . '</li>';
    }
    echo '</ul><p><a class="btn" href="contact-booking.html">Back to the form</a></p></section></div></main>';
    page_close();
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: contact-booking.html');
    exit;
}

$owner      = trim((string) ($_POST['owner'] ?? ''));
$phone      = trim((string) ($_POST['phone'] ?? ''));
$address    = trim((string) ($_POST['address'] ?? ''));
$days       = trim((string) ($_POST['days'] ?? ''));
$animal     = trim((string) ($_POST['animal'] ?? ''));
$birthdate  = trim((string) ($_POST['birthdate'] ?? ''));
$species    = (string) ($_POST['species'] ?? '');
$room       = (string) ($_POST['room'] ?? '');
$comments   = trim((string) ($_POST['comments'] ?? ''));
$vaccinated = (($_POST['vaccinated'] ?? '') === 'yes');

$errors = [];

$len = mb_strlen($owner);
if ($owner === '') {
    $errors[] = 'Enter the owner\'s name.';
} elseif ($len < 2 || $len > 60) {
    $errors[] = 'The owner\'s name must be between 2 and 60 characters.';
} elseif (!preg_match('/^\p{L}[\p{L}\s\'.-]*$/u', $owner)) {
    $errors[] = 'The owner\'s name can only contain letters, spaces, hyphens and apostrophes.';
}

$digits = strlen(preg_replace('/\D/', '', $phone));
if ($phone === '') {
    $errors[] = 'Enter a phone number.';
} elseif (!preg_match('/^\+?[0-9\s().-]+$/', $phone) || $digits < 7 || $digits > 15 || strlen($phone) > 25) {
    $errors[] = 'Enter a valid phone number, like +230 5123 4567.';
}

$len = mb_strlen($address);
if ($address === '') {
    $errors[] = 'Enter your address.';
} elseif ($len < 5 || $len > 150) {
    $errors[] = 'Your address must be between 5 and 150 characters.';
}

if ($days === '') {
    $errors[] = 'Enter the number of days.';
} elseif (!ctype_digit($days) || (int) $days < 1 || (int) $days > 90) {
    $errors[] = 'The number of days must be a whole number from 1 to 90.';
}

$len = mb_strlen($animal);
if ($animal === '') {
    $errors[] = 'Enter your animal\'s name.';
} elseif ($len > 40) {
    $errors[] = 'The animal\'s name must be 40 characters or fewer.';
} elseif (!preg_match('/^[\p{L}\p{N}][\p{L}\p{N}\s\'.-]*$/u', $animal)) {
    $errors[] = 'The animal\'s name can only contain letters, numbers, spaces, hyphens and apostrophes.';
}

if ($birthdate === '') {
    $errors[] = 'Enter your animal\'s birth date.';
} else {
    $d = DateTime::createFromFormat('!Y-m-d', $birthdate);
    if (!$d || $d->format('Y-m-d') !== $birthdate) {
        $errors[] = 'Enter a valid birth date.';
    } elseif ($d > new DateTime('today')) {
        $errors[] = 'The birth date cannot be in the future.';
    } elseif ($d < new DateTime('-40 years today')) {
        $errors[] = 'The birth date cannot be more than 40 years ago.';
    }
}

if (!array_key_exists($species, booking_species())) {
    $errors[] = 'Choose a species from the list.';
}

if (!array_key_exists($room, booking_rooms())) {
    $errors[] = 'Choose a room type.';
}

if (mb_strlen($comments) > 500) {
    $errors[] = 'Comments must be 500 characters or fewer.';
} elseif (preg_match('~(https?://|www\.)~i', $comments)) {
    $errors[] = 'Links are not allowed in comments.';
}

if (!$vaccinated) {
    $errors[] = 'Confirm that your animal\'s vaccinations are up to date.';
}

if ($errors) {
    fail_page(422, 'We could not send your booking request', $errors);
}

$entry = [
    'id'         => bin2hex(random_bytes(6)),
    'date'       => gmdate('c'),
    'owner'      => $owner,
    'phone'      => $phone,
    'address'    => $address,
    'days'       => (int) $days,
    'animal'     => $animal,
    'birthdate'  => $birthdate,
    'species'    => $species,
    'room'       => $room,
    'comments'   => $comments,
    'vaccinated' => true,
    'status'     => 'pending',
];

if (!save_booking($entry)) {
    fail_page(500, 'Something went wrong', ['Your booking request could not be saved. Please try again later.']);
}

header('Location: contact-booking.html?sent=1');
exit;
