<?php
/* "My bookings" memory. After a visitor books (or finds a booking with reference + phone), its reference is kept in
   their session, so they can see and cancel only their own bookings. Also provides the CSRF token for the cancel form. */

const BOOKING_ID_PATTERN = '/^TU-[0-9A-F]{6}$/';

/* True when the visitor reached the site over HTTPS (directly, or through a proxy that says so). */
function booking_is_https() {
    if (!empty($_SERVER['HTTPS']) && strtolower((string) $_SERVER['HTTPS']) !== 'off') {
        return true;
    }
    if ((int) ($_SERVER['SERVER_PORT'] ?? 0) === 443) {
        return true;
    }
    return strtolower((string) ($_SERVER['HTTP_X_FORWARDED_PROTO'] ?? '')) === 'https';
}

function booking_session_start() {
    if (session_status() === PHP_SESSION_ACTIVE) {
        return;
    }
    $month = 60 * 60 * 24 * 30;
    ini_set('session.gc_maxlifetime', (string) $month);
    // Secure only when the visit is over HTTPS: a Secure cookie is never sent back over plain http,
    // so My Bookings would forget the visitor on http://127.0.0.1 or a local network address.
    session_set_cookie_params(['lifetime' => $month, 'path' => '/', 'secure' => booking_is_https(), 'httponly' => true, 'samesite' => 'Lax']);
    session_start();
}

function booking_remember($id) {
    booking_session_start();
    $ids = booking_owned_ids();
    if (!in_array($id, $ids, true)) {
        $ids[] = $id;
    }
    $_SESSION['booking_ids'] = $ids;
}

function booking_owned_ids() {
    booking_session_start();
    $ids = $_SESSION['booking_ids'] ?? [];
    return is_array($ids) ? $ids : [];
}

function booking_csrf() {
    booking_session_start();
    if (empty($_SESSION['csrf'])) {
        $_SESSION['csrf'] = bin2hex(random_bytes(16));
    }
    return $_SESSION['csrf'];
}

function booking_csrf_check($token) {
    booking_session_start();
    return !empty($_SESSION['csrf']) && is_string($token) && hash_equals($_SESSION['csrf'], $token);
}
