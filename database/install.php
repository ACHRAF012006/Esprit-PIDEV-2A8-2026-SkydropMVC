<?php
/**
 * Creates the SKYDROP database from database/skydrop.sql (all tables, then the demo rows).
 *
 *   php database/install.php
 *
 * The file uses CREATE TABLE IF NOT EXISTS and INSERT IGNORE, so running it again is safe.
 * Connection settings: config/config.php (copy config/config.example.php); the XAMPP default works without it.
 * You can also import database/skydrop.sql in phpMyAdmin.
 */
declare(strict_types=1);

$root = dirname(__DIR__);
$cfg = require (is_file("$root/config/config.php") ? "$root/config/config.php" : "$root/config/config.example.php");

try {
    $pdo = new PDO("mysql:host={$cfg['host']};charset=utf8mb4", $cfg['user'], $cfg['pass'], [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);
} catch (PDOException $ex) {
    fwrite(STDERR, "Cannot reach MySQL at {$cfg['host']}: start MySQL in the XAMPP control panel, then run this again.\n");
    exit(1);
}
$name = str_replace('`', '', $cfg['name']);
$pdo->exec("CREATE DATABASE IF NOT EXISTS `$name` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
$pdo->exec("USE `$name`");
// Off while the file runs: a table may point at a table of another part that comes later in the file.
$pdo->exec('SET FOREIGN_KEY_CHECKS = 0');

$sql = (string) file_get_contents(__DIR__ . '/skydrop.sql');
// One statement per ";" at the end of a line (no stored procedures).
foreach (preg_split('/;\s*(\R|$)/', $sql) as $statement) {
    $statement = trim((string) preg_replace('/^\s*--.*$/m', '', $statement));
    if ($statement === '') {
        continue;
    }
    try {
        $pdo->exec($statement);
    } catch (PDOException $ex) {
        fwrite(STDERR, "FAIL: {$ex->getMessage()}\n" . substr($statement, 0, 200) . "\n");
        exit(1);
    }
}
$pdo->exec('SET FOREIGN_KEY_CHECKS = 1');
echo "Database `$name` is ready (database/skydrop.sql).\n";
