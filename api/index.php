<?php

$tmpViews = '/tmp/views';
if (!is_dir($tmpViews)) {
    mkdir($tmpViews, 0777, true);
}

$tmpDb = '/tmp/database.sqlite';
$seedDb = __DIR__ . '/../database/seed_database.sqlite';
if (!file_exists($tmpDb) && file_exists($seedDb)) {
    copy($seedDb, $tmpDb);
}

require __DIR__ . '/../public/index.php';
