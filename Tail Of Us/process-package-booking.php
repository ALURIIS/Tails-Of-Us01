<?php
require_once __DIR__ . '/includes/booking-store.php';
require_once __DIR__ . '/includes/packages.php';
require_once __DIR__ . '/includes/booking-session.php';
require_once __DIR__ . '/includes/booking-page.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: travel-packages.html');
    exit;
}

$slug     = (string) ($_POST['package'] ?? '');
$packages = booking_packages();
$back     = isset($packages[$slug]) ? 'package-booking.php?package=' . urlencode($slug) : 'travel-packages.html';

$owner      = trim((string) ($_POST['owner'] ?? ''));
$phone      = trim((string) ($_POST['phone'] ?? ''));
$animal     = trim((string) ($_POST['animal'] ?? ''));
$species    = (string) ($_POST['species'] ?? '');
$startdate  = trim((string) ($_POST['startdate'] ?? ''));
$comments   = trim((string) ($_POST['comments'] ?? ''));
$vaccinated = (($_POST['vaccinated'] ?? '') === 'yes');

$errors = [];

if (!isset($packages[$slug])) {
    $errors[] = 'Please select a valid package from the Travel Packages page.';
}

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

$len = mb_strlen($animal);
if ($animal === '') {
    $errors[] = 'Enter your animal\'s name.';
} elseif ($len > 40) {
    $errors[] = 'The animal\'s name must be 40 characters or fewer.';
} elseif (!preg_match('/^[\p{L}\p{N}][\p{L}\p{N}\s\'.-]*$/u', $animal)) {
    $errors[] = 'The animal\'s name can only contain letters, numbers, spaces, hyphens and apostrophes.';
}

if (!array_key_exists($species, booking_species())) {
    $errors[] = 'Choose a species from the list.';
}

if ($startdate === '') {
    $errors[] = 'Enter the start date.';
} else {
    $d = DateTime::createFromFormat('!Y-m-d', $startdate);
    if (!$d || $d->format('Y-m-d') !== $startdate) {
        $errors[] = 'Enter a valid start date.';
    } elseif ($d < new DateTime('today')) {
        $errors[] = 'The start date cannot be in the past.';
    } elseif ($d > new DateTime('today +1 year')) {
        $errors[] = 'The start date must be within the next year.';
    }
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
    booking_fail_page(422, 'We could not send your request', $errors, $back);
}

$waitlist = ($packages[$slug]['status'] === 'booked');
$refId    = 'TU-' . strtoupper(substr(bin2hex(random_bytes(3)), 0, 6));

$entry = [
    'id'         => $refId,
    'date'       => gmdate('c'),
    'type'       => 'package',
    'status'     => $waitlist ? 'waitlisted' : 'pending',
    'package'    => $slug,
    'waitlist'   => $waitlist,
    'owner'      => $owner,
    'phone'      => $phone,
    'animal'     => $animal,
    'species'    => $species,
    'startdate'  => $startdate,
    'comments'   => $comments,
    'vaccinated' => true,
];

if (!save_booking($entry)) {
    booking_fail_page(500, 'Something went wrong', ['Your request could not be saved. Please try again later.'], $back);
}

booking_remember($refId);
header('Location: my-bookings.php?placed=' . urlencode($refId));
exit;
