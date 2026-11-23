<?php
/**
 * app/config/database.php - secure PDO connection to MySQL (security workshop, Part 2).
 *
 * Singleton: the connection is created once and shared by the whole application. Controllers get it with
 * Database::getConnexion() (or the db() helper). Settings come from app/config/config.php (copy config.example.php).
 */
final class Database
{
    private static ?PDO $pdo = null;

    public static function getConnexion(): PDO
    {
        if (self::$pdo === null) {
            $file = __DIR__ . '/config.php';
            $cfg = require (is_file($file) ? $file : __DIR__ . '/config.example.php');
            try {
                self::$pdo = new PDO(
                    "mysql:host={$cfg['host']};dbname={$cfg['name']};charset=utf8mb4",
                    $cfg['user'],
                    $cfg['pass'],
                    [
                        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,      // an SQL error throws an exception
                        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                        PDO::ATTR_EMULATE_PREPARES => false,              // real prepared statements: protection against SQL injection
                    ]
                );
            } catch (PDOException $ex) {
                // Never show the connection details to the visitor.
                throw new RuntimeException('The database is not reachable. Start MySQL in XAMPP, then run: php database/install.php', 0, $ex);
            }
        }
        return self::$pdo;
    }
}
