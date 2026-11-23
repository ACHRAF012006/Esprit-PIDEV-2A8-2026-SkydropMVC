<?php
// Copy this file to app/config/config.php (never committed) and change the values if your MySQL differs.
// XAMPP default: user root, empty password. The SKYDROP_DB_* environment variables override the values (used by GitHub).
return [
    'host' => getenv('SKYDROP_DB_HOST') ?: '127.0.0.1',
    'name' => getenv('SKYDROP_DB_NAME') ?: 'skydrop_v2',
    'user' => getenv('SKYDROP_DB_USER') ?: 'root',
    'pass' => getenv('SKYDROP_DB_PASS') ?: '',
];
