<?php
/* Shared helpers for the feedback pages: allowed values, saving and loading data/feedback.json */

function feedback_subjects() {
    return [
        'general'    => 'General feedback',
        'booking'    => 'Booking experience',
        'care'       => 'Veterinary care',
        'facilities' => 'Rooms and facilities',
        'staff'      => 'Staff',
        'other'      => 'Other',
    ];
}

function feedback_ratings() {
    return [
        'not-satisfied'  => 'Not Satisfied',
        'satisfied'      => 'Satisfied',
        'very-satisfied' => 'Very Satisfied',
    ];
}

function feedback_file() {
    return dirname(__DIR__) . '/data/feedback.json';
}

/* Escape anything that comes from a visitor before printing it in a page */
function h($text) {
    return htmlspecialchars((string) $text, ENT_QUOTES, 'UTF-8');
}

/* "Jolene Diana" becomes "Jolene D." so full names are never shown */
function display_name($full) {
    $parts = preg_split('/\s+/u', trim((string) $full));
    if (count($parts) < 2) {
        return $parts[0];
    }
    return $parts[0] . ' ' . mb_strtoupper(mb_substr(end($parts), 0, 1)) . '.';
}

function load_feedback() {
    $file = feedback_file();
    if (!is_file($file)) {
        return [];
    }
    $data = json_decode((string) file_get_contents($file), true);
    return is_array($data) ? $data : [];
}

/* Appends one entry. The lock stops two visitors overwriting each other. */
function save_feedback($entry) {
    $file = feedback_file();
    if (!is_dir(dirname($file)) && !mkdir(dirname($file), 0755, true)) {
        return false;
    }
    $fp = fopen($file, 'c+');
    if ($fp === false) {
        return false;
    }
    flock($fp, LOCK_EX);
    $data = json_decode((string) stream_get_contents($fp), true);
    if (!is_array($data)) {
        $data = [];
    }
    $data[] = $entry;
    ftruncate($fp, 0);
    rewind($fp);
    $ok = fwrite($fp, json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES)) !== false;
    fflush($fp);
    flock($fp, LOCK_UN);
    fclose($fp);
    return $ok;
}
