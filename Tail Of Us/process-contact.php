<?php
/* Receives the Contact Us form, checks every input again (never trust the browser) and saves it as a ticket. */
require_once __DIR__ . '/includes/contact-store.php';
require_once __DIR__ . '/includes/layout.php';

function fail_Page($code, $heading, $lines) {
    http_response_code($code);
    page_open($heading);
    echo '<main id="main" tabindex="-1"><div class="container fb-page"><section class="fb-banner"><h1>' . h($heading) . '</h1><ul>';
    foreach ($lines as $line) {
        echo '<li>' . h($line) . '</li>';
    }
    echo '</ul><p><a class="btn" href="contact.html">Back to the form</a></p></section></div></main>';
    page_close();
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: contact.html');
    exit;
}

$name    = trim((string) ($_POST['name'] ?? ''));
$email   = trim((string) ($_POST['email'] ?? ''));
$message = trim((string) ($_POST['message'] ?? ''));

$errors = [];

$len = mb_strlen($name);
if ($name === '') {
    $errors[] = 'Enter your name.';
} elseif ($len < 2 || $len > 60) {
    $errors[] = 'Your name must be between 2 and 60 characters.';
} elseif (!preg_match('/^\p{L}[\p{L}\s\'.-]*$/u', $name)) {
    $errors[] = 'Your name can only contain letters, spaces, hyphens and apostrophes.';
}

if ($email === '' || strlen($email) > 100 || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
    $errors[] = 'Enter a valid email address, like name@example.com.';
}

$len = mb_strlen($message);
if ($len < 10 || $len > 1000) {
    $errors[] = 'Your message must be between 10 and 1000 characters.';
} elseif (preg_match('~(https?://|www\.)~i', $message)) {
    $errors[] = 'Links are not allowed in messages.';
}

if ($errors) {
    fail_page(422, 'We could not send your message', $errors);
}

$entry = [
    'id'      => bin2hex(random_bytes(6)),
    'date'    => gmdate('c'),
    'name'    => $name,
    'email'   => $email,
    'message' => $message,
    'status'  => 'new',
];

if (!save_contact($entry)) {
    fail_page(500, 'Something went wrong', ['Your message could not be saved. Please try again later.']);
}

header('Location: contact.html?sent=1');
exit;
