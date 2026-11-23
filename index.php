<?php
/**
 * index.php - the single entry point of SKYDROP (MVC, as in the security workshop).
 *
 *   index.php?page=<key>  ->  Router finds the route in config/routes.php
 *                         ->  calls the action of the controller (controllers/), which runs the SQL and fills the models (models/)
 *                         ->  the controller renders the view (views/) inside its layout (views/layouts/front|owner|admin.php)
 *
 * XAMPP:  http://localhost/skydrop-mvc/index.php
 * PHP:    php -S localhost:8000 index.php
 */
declare(strict_types=1);

// php -S with this file as router: serve only the static files of assets/ and uploads/ (never the PHP of the project).
if (PHP_SAPI === 'cli-server') {
    $path = (string) parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
    $file = realpath(__DIR__ . $path);
    if ($file !== false && is_file($file)
        && (str_starts_with($file, __DIR__ . '/assets/') || str_starts_with($file, __DIR__ . '/uploads/'))) {
        return false;
    }
}

define('ROOT_PATH', __DIR__);
define('CONFIG_PATH', ROOT_PATH . '/config');
define('VIEW_PATH', ROOT_PATH . '/views');
define('PUBLIC_PATH', ROOT_PATH);
define('UPLOAD_PATH', ROOT_PATH . '/uploads');

// Autoloader: a class name is its file name, in controllers/ or models/.
//   AuthController -> controllers/AuthController.php     UserModel -> models/UserModel.php
spl_autoload_register(static function (string $class): void {
    foreach (['controllers', 'models'] as $dir) {
        $file = ROOT_PATH . "/$dir/$class.php";
        if (is_file($file)) {
            require $file;
            return;
        }
    }
});

require CONFIG_PATH . '/helpers.php';
require CONFIG_PATH . '/database.php';

// 1. Secure session configuration (security workshop 5.5). The ini_set() calls must come BEFORE session_start().
ini_set('session.use_strict_mode', '1');    // reject session ids that the server did not generate
ini_set('session.cookie_httponly', '1');    // the PHPSESSID cookie is invisible to JavaScript (session theft through XSS)
ini_set('session.cookie_samesite', 'Lax');  // the cookie is not sent by forms of other sites (first barrier against CSRF)
if (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') {
    ini_set('session.cookie_secure', '1');  // over HTTPS only
}
session_start();

// 2. Session expiry after 30 minutes without activity (workshop 5.5).
$sessionLifetime = 1800;
if (isset($_SESSION['last_activity']) && time() - $_SESSION['last_activity'] > $sessionLifetime) {
    $_SESSION = [];
    session_destroy();
    session_start();
    flash('error', 'Your session ended after 30 minutes without activity. Please log in again.');
}
$_SESSION['last_activity'] = time();

// 3. HTTP security headers (workshop 7.5). The back-office template script (Alpine.js) needs 'unsafe-eval';
//    map tiles come from OpenStreetMap. No inline <script> anywhere: page scripts are files in assets/.
header('X-Frame-Options: DENY');                                    // no framing by another site (clickjacking)
header('X-Content-Type-Options: nosniff');                          // no MIME-type guessing
header('Referrer-Policy: strict-origin-when-cross-origin');
header("Content-Security-Policy: default-src 'self'; script-src 'self' 'unsafe-eval'; style-src 'self' 'unsafe-inline'; "
    . "img-src 'self' data: https://*.tile.openstreetmap.org https://tile.openstreetmap.org; "
    . "connect-src 'self'; frame-ancestors 'none'; form-action 'self'; base-uri 'self'");

set_exception_handler(static function (Throwable $ex): void {
    error_log(sprintf('%s in %s:%d', $ex->getMessage(), $ex->getFile(), $ex->getLine())); // PHP's own error log (XAMPP: php/logs/php_error_log)
    if (!headers_sent()) {
        http_response_code(500);
    }
    $debug = in_array($_SERVER['SERVER_NAME'] ?? '', ['localhost', '127.0.0.1'], true);
    $message = $debug ? $ex->getMessage() : '';
    $status = 500;
    $code = 500;
    require VIEW_PATH . '/errors/error.php';
});

// 4. A signed-in account is read again from the database on every request (workshop 1.4): a deactivated, deleted or
//    demoted account loses its access at once. The SQL is in AuthController.
if (Auth::isAuthenticated()) {
    (new AuthController())->refreshSession(isset($_GET['page']) && is_string($_GET['page']) ? $_GET['page'] : '');
}

// 5. Routing: config/routes.php -> controller action.
(new Router(Registry::routes()))->dispatch($_SERVER['REQUEST_METHOD'] ?? 'GET', $_SERVER['REQUEST_URI'] ?? '/');
