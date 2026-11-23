<?php
/**
 * Global helpers shared by every module.
 * Contract: see .tracking/CONTRACTS.md (section "Core helpers").
 */

/** Escape a value for HTML output. Every printed value goes through e(). */
function e(?string $s): string
{
    return htmlspecialchars($s ?? '', ENT_QUOTES, 'UTF-8');
}

/** Base path of the app, e.g. "" (php -S) or "/skydrop-mvc" (XAMPP). */
function base_path_url(): string
{
    $dir = str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME'] ?? '/index.php'));
    return rtrim($dir, '/');
}

/** URL of a clean path, e.g. url('/search') (needs the .htaccess rewrite or a router script). */
function url(string $path = '/'): string
{
    return base_path_url() . '/' . ltrim($path, '/');
}

/** URL of a named page, e.g. route('home') -> /index.php?page=home. Works without URL rewriting. */
function route(string $page, array $query = []): string
{
    $query = ['page' => $page] + $query;
    return base_path_url() . '/index.php?' . http_build_query($query);
}

/** URL of a static asset under assets/, e.g. asset('lib/jquery/3.7/jquery-3.7.0.min.js'). */
function asset(string $path): string
{
    return base_path_url() . '/assets/' . ltrim($path, '/');
}

/** Returns "active" when $page is the page being displayed (for navbar / sidebar links). */
function active(string $page, ?string $current = null, string $class = 'active'): string
{
    $current ??= View::currentPage();
    return $page === $current ? $class : '';
}

function redirect(string $location): never
{
    if (!preg_match('#^https?://#', $location) && !str_starts_with($location, base_path_url() . '/')) {
        $location = url($location);
    }
    header('Location: ' . $location);
    exit;
}

/** Flash message: flash('error', 'text') stores, flash('error') reads and clears. */
function flash(string $type, ?string $message = null): ?string
{
    if ($message !== null) {
        $_SESSION['flash'][$type] = $message;
        return null;
    }
    $value = $_SESSION['flash'][$type] ?? null;
    unset($_SESSION['flash'][$type]);
    return $value;
}

/** Hidden CSRF input for every POST form. */
function csrf_field(): string
{
    return '<input type="hidden" name="csrf_token" value="' . e(Csrf::token()) . '">';
}


/** Render an error page and stop. */
function abort(int $status, string $message = ''): never
{
    http_response_code($status);
    $file = VIEW_PATH . '/errors/' . (is_file(VIEW_PATH . "/errors/$status.php") ? $status : 'error') . '.php';
    $code = $status;
    require $file;
    exit;
}

/** The shared PDO connection (Database singleton, app/config/database.php). Used by controllers only. */
function db(): PDO
{
    return Database::getConnexion();
}


/** True when a page key is declared by a module (to show a link only once its module exists). */
function has_route(string $page): bool
{
    foreach (Registry::routes() as $r) {
        if (($r[3] ?? null) === $page) {
            return true;
        }
    }
    return false;
}

/**
 * Renders the views that modules registered for a slot (module.php "slots"), in module order.
 * Example: <?= slot('home') ?> on the home page shows the blocks of Shop, Produit, Ads...
 * $data is passed to each view. A missing view is skipped, so a slot never breaks the page.
 */
function slot(string $name, array $data = []): string
{
    $html = '';
    foreach (Registry::slot($name) as $view) {
        if (is_file(View::file($view))) {
            $html .= View::capture($view, $data);
        }
    }
    return $html;
}
