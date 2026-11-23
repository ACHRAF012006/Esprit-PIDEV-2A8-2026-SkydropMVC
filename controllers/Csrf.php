<?php

/**
 * CSRF protection (security workshop 7.4): one random token per session, sent in every POST form as the hidden field
 * "csrf_token" (csrf_field()) and compared with hash_equals() by Csrf::check() at the top of every POST action.
 */
final class Csrf
{
    public static function token(): string
    {
        if (empty($_SESSION['csrf_token'])) {
            $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
        }
        return $_SESSION['csrf_token'];
    }

    public static function verify(?string $token): bool
    {
        return $token !== null && $token !== '' && !empty($_SESSION['csrf_token'])
            && hash_equals($_SESSION['csrf_token'], $token);
    }

    /** Call at the top of every POST action: an invalid token stops the request (HTTP 419). */
    public static function check(): void
    {
        if (!self::verify(isset($_POST['csrf_token']) ? (string) $_POST['csrf_token'] : null)) {
            abort(419, 'Invalid security token. Reload the page and try again.');
        }
    }
}
