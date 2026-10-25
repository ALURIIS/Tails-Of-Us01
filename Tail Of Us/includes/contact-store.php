<?php
function contact_statuses() {
    return ['new' => 'New', 'read' => 'Read', 'replied' => 'Replied', 'closed' => 'Closed'];
}

function contact_file() {
    return dirname(__DIR__) . '/data/contacts.json';
}

function load_contacts() {
    $file = contact_file();
    if (!is_file($file)) {
        return [];
    }
    $data = json_decode((string) file_get_contents($file), true);
    return is_array($data) ? $data : [];
}

/* Read, change and write the whole file while holding one lock, so two visitors never overwrite each other. */
function contact_modify(callable $change) {
    $ok = false;
    $dir = dirname(contact_file());
    if (is_dir($dir) || mkdir($dir, 0755, true)) {
        $fp = fopen(contact_file(), 'c+');
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

function save_contact(array $entry) {
    return contact_modify(function ($all) use ($entry) {
        $all[] = $entry;
        return $all;
    });
}

function update_contact_status($id, $status) {
    if (!array_key_exists($status, contact_statuses())) {
        return false;
    }
    return contact_modify(function ($all) use ($id, $status) {
        foreach ($all as &$c) {
            if (($c['id'] ?? '') === $id) {
                $c['status'] = $status;
            }
        }
        return $all;
    });
}
