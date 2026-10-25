<?php
/* Storage for booking requests. Saved as a JSON list in data/bookings.json.
   The future admin area can read it with load_bookings() and change a request with update_booking_status(). */

function booking_species() {
    return ['dog' => 'Dog', 'cat' => 'Cat', 'rabbit' => 'Rabbit', 'bird' => 'Bird', 'rodent' => 'Small rodent', 'reptile' => 'Reptile', 'other' => 'Other'];
}

function booking_rooms() {
    return ['standard' => 'Standard', 'comfort' => 'Comfort', 'suite' => 'Suite'];
}

function booking_statuses() {
    return ['pending' => 'Pending', 'confirmed' => 'Confirmed', 'cancelled' => 'Cancelled'];
}

function booking_file() {
    return dirname(__DIR__) . '/data/bookings.json';
}

function load_bookings() {
    $file = booking_file();
    if (!is_file($file)) {
        return [];
    }
    $data = json_decode((string) file_get_contents($file), true);
    return is_array($data) ? $data : [];
}

/* Read, change and write the whole file while holding one lock, so two visitors never overwrite each other. */
function booking_modify(callable $change) {
    $ok = false;
    $dir = dirname(booking_file());
    if (is_dir($dir) || mkdir($dir, 0755, true)) {
        $fp = fopen(booking_file(), 'c+');
        if ($fp) {
            if (flock($fp, LOCK_EX)) {
                $data = json_decode((string) stream_get_contents($fp), true);
                $data = $change(is_array($data) ? $data : []);
                $json = json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
                $ok = $json !== false && ftruncate($fp, 0) && rewind($fp) && fwrite($fp, $json) !== false && fflush($fp);
                flock($fp, LOCK_UN);
            }
            fclose($fp);
        }
    }
    return $ok;
}

function save_booking(array $entry) {
    return booking_modify(function ($all) use ($entry) {
        $all[] = $entry;
        return $all;
    });
}

function update_booking_status($id, $status) {
    if (!array_key_exists($status, booking_statuses())) {
        return false;
    }
    return booking_modify(function ($all) use ($id, $status) {
        foreach ($all as &$b) {
            if (($b['id'] ?? '') === $id) {
                $b['status'] = $status;
            }
        }
        return $all;
    });
}
