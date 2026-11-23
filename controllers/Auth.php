<?php

/**
 * Session state and access control (security workshop, Parts 5 and 6). Reads and writes $_SESSION only:
 * checking the password against the database is done by User\AuthController (the controller holds the SQL).
 *
 * Roles: customer, shop_owner, advertiser, admin (User\UserModel::ROLES).
 * The role comes from the database at login, is copied into the session, and is checked again on EVERY protected page.
 */
final class Auth
{
    /** After a successful password check: new session id (session fixation), then the user's id, name and role. */
    public static function login(int $id, string $username, string $role): void
    {
        session_regenerate_id(true);
        $_SESSION['user_id'] = $id;
        $_SESSION['username'] = $username;
        $_SESSION['role'] = $role;
        $_SESSION['last_activity'] = time();
    }

    public static function isAuthenticated(): bool
    {
        return !empty($_SESSION['user_id']);
    }

    /** Kept for older code: same as isAuthenticated(). */
    public static function check(): bool
    {
        return self::isAuthenticated();
    }

    public static function id(): ?int
    {
        return self::isAuthenticated() ? (int) $_SESSION['user_id'] : null;
    }

    public static function role(): ?string
    {
        return self::isAuthenticated() ? (string) ($_SESSION['role'] ?? '') : null;
    }

    public static function username(): ?string
    {
        return self::isAuthenticated() ? (string) ($_SESSION['username'] ?? '') : null;
    }

    public static function hasRole(string ...$roles): bool
    {
        return self::isAuthenticated() && in_array($_SESSION['role'] ?? '', $roles, true);
    }

    /** Guests are sent to the login page (and come back to this page after logging in). */
    public static function requireAuth(): void
    {
        if (!self::isAuthenticated()) {
            if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'GET') {
                // Only our own page address (index.php + query string), never the raw URL: a path such as
                // "//other-site/..." would otherwise send the user to another site after login (open redirect).
                parse_str((string) ($_SERVER['QUERY_STRING'] ?? ''), $query);
                $_SESSION['after_login'] = isset($query['page']) && is_string($query['page'])
                    ? base_path_url() . '/index.php?' . http_build_query($query) : '';
            }
            // Keep a message already waiting (for example "Your session ended after 30 minutes").
            if (empty($_SESSION['flash']['error'])) {
                flash('error', 'Please log in to open this page.');
            }
            redirect(route('login'));
        }
    }

    /** First line of every protected action: guest -> login page, wrong role -> 403 "Access denied". */
    public static function requireRole(string ...$roles): void
    {
        self::requireAuth();
        if ($roles && !in_array($_SESSION['role'] ?? '', $roles, true)) {
            abort(403, 'Access denied: you do not have the required permissions.');
        }
    }

    /** Kept for older code: same as requireRole(). */
    public static function require(string ...$roles): void
    {
        self::requireRole(...$roles);
    }

    /** Empties the session, deletes the session cookie, destroys the session file (workshop 5.1). */
    public static function logout(): void
    {
        $_SESSION = [];
        if (ini_get('session.use_cookies')) {
            $p = session_get_cookie_params();
            setcookie(session_name(), '', time() - 42000, $p['path'], $p['domain'], $p['secure'], $p['httponly']);
        }
        if (session_status() === PHP_SESSION_ACTIVE) {
            session_destroy();
        }
        session_start(); // a fresh, empty session for the flash message after the redirect
    }
}
