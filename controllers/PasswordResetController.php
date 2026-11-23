<?php

/**
 * S5 Karim - Forgotten password (grid item 1.3, simulated: no email is sent).
 *
 *   1. password-forgot: the visitor types an email. If it belongs to an active account, a random token is created;
 *      only hash('sha256', token) is stored, with an expiry 30 minutes later. The answer is ALWAYS the same message,
 *      so nobody can test which emails have an account. Simulation: the link the email would contain is shown.
 *   2. password-reset&token=...: the token is hashed and looked up; a missing, expired or used link is refused.
 *      The new password is saved with password_hash(), the link is marked used (it works only once).
 *
 * The controller holds the logic and the SQL (prepared statements, named parameters); PasswordResetModel holds one row.
 */
final class PasswordResetController extends Controller
{
    /** Same answer for every email (known or not): it never tells whether an account exists. */
    private const SENT_MESSAGE = 'If this email has an account, a reset link was created.';
    private const INVALID_MESSAGE = 'This link is invalid or has expired.';
    private const MAX_REQUESTS = 3;        // per email ...
    private const WINDOW_SECONDS = 900;    // ... per 15 minutes

    private \PDO $db;

    public function __construct()
    {
        $this->db = \Database::getConnexion();
    }

    // =================================================================================================
    // DATA ACCESS (prepared statements: the input is always data, never SQL code)
    // =================================================================================================

    private function findActiveUserByEmail(string $email): ?UserModel
    {
        $stmt = $this->db->prepare('SELECT * FROM users WHERE email = :email AND is_active = 1 LIMIT 1');
        $stmt->execute(['email' => $email]);
        $row = $stmt->fetch();
        return $row ? UserModel::fromArray($row) : null;
    }

    /** The reset row of a token that is still usable (not used, not expired), or null. */
    private function findValidReset(string $token): ?PasswordResetModel
    {
        if (!preg_match('/^[0-9a-f]{64}$/', $token)) {
            return null; // not a token we could have created: no need to ask the database
        }
        $stmt = $this->db->prepare('SELECT * FROM password_resets
            WHERE token_hash = :hash AND used_at IS NULL AND expires_at > NOW() LIMIT 1');
        $stmt->execute(['hash' => hash('sha256', $token)]);
        $row = $stmt->fetch();
        return $row ? PasswordResetModel::fromArray($row) : null;
    }

    /** Creates a new link for this user and returns the token in clear (it is never stored). */
    private function createReset(int $userId): string
    {
        // Only the newest link works: the older unused links of this user are deleted.
        $stmt = $this->db->prepare('DELETE FROM password_resets WHERE user_id = :user_id AND used_at IS NULL');
        $stmt->execute(['user_id' => $userId]);

        $token = bin2hex(random_bytes(32)); // 64 hexadecimal characters, cryptographically random
        $stmt = $this->db->prepare('INSERT INTO password_resets (user_id, token_hash, expires_at)
            VALUES (:user_id, :hash, NOW() + INTERVAL 30 MINUTE)');
        $stmt->execute(['user_id' => $userId, 'hash' => hash('sha256', $token)]);
        return $token;
    }

    // =================================================================================================
    // 1. FORGOT YOUR PASSWORD? (email form)
    // =================================================================================================

    public function requestForm(): void
    {
        $this->redirectIfSignedIn();
        $link = $_SESSION['reset_link'] ?? null; // simulation: the link of the last request, shown once
        unset($_SESSION['reset_link']);
        $this->render('front/password-forgot', [
            'title' => 'Forgot your password? | SKYDROP',
            'resetLink' => is_string($link) ? $link : null,
        ] + $this->formState());
    }

    public function request(): void
    {
        $this->redirectIfSignedIn();
        Csrf::check();
        $v = (new Validator($_POST))->required('email')->email('email')->maxLen('email', 150);
        if ($v->fails()) {
            $this->backWithErrors('password-forgot', $v->errors(), ['email']);
        }
        $email = strtolower($v->get('email'));

        // At most 3 requests per email per 15 minutes. Counted the same way for every email, known or not,
        // so the limit itself does not reveal which emails have an account.
        $wait = $this->minutesBeforeNextRequest($email);
        if ($wait > 0) {
            $this->backWithErrors('password-forgot', ['email' => "Too many requests for this email. Try again in $wait minute" . ($wait > 1 ? 's' : '') . '.'], ['email']);
        }

        $user = $this->findActiveUserByEmail($email);
        if ($user !== null) {
            $token = $this->createReset($user->getId());
            // Simulation: a real site would send this link by email instead of showing it.
            $_SESSION['reset_link'] = route('password-reset', ['token' => $token]);
        }
        flash('success', self::SENT_MESSAGE);
        $this->redirect(route('password-forgot'));
    }

    // =================================================================================================
    // 2. CHOOSE A NEW PASSWORD (the link of the email)
    // =================================================================================================

    public function resetForm(): void
    {
        $this->redirectIfSignedIn();
        $token = $this->tokenFromRequest();
        if ($this->findValidReset($token) === null) {
            flash('error', self::INVALID_MESSAGE);
            $this->redirect(route('password-forgot'));
        }
        $this->render('front/password-reset', [
            'title' => 'Choose a new password | SKYDROP',
            'token' => $token,
        ] + $this->formState());
    }

    public function reset(): void
    {
        $this->redirectIfSignedIn();
        Csrf::check();
        $token = $this->tokenFromRequest();
        $reset = $this->findValidReset($token);
        if ($reset === null) {
            flash('error', self::INVALID_MESSAGE);
            $this->redirect(route('password-forgot'));
        }

        // Same rules and messages as the registration form (AuthController::register).
        $errors = (new Validator($_POST))->required('password')->minLen('password', 8)->required('password_confirm')->errors();
        $password = (string) ($_POST['password'] ?? '');
        if (!isset($errors['password']) && (!preg_match('/[A-Za-z]/', $password) || !preg_match('/\d/', $password))) {
            $errors['password'] = 'Use at least one letter and one digit.';
        }
        if (!isset($errors['password_confirm']) && $password !== (string) ($_POST['password_confirm'] ?? '')) {
            $errors['password_confirm'] = 'The two passwords are different.';
        }
        if ($errors) {
            $this->backWithErrors('password-reset', $errors, [], ['token' => $token]);
        }

        // "used_at IS NULL" in the UPDATE: if two requests use the same link at the same time, only one succeeds.
        $this->db->beginTransaction();
        $stmt = $this->db->prepare('UPDATE password_resets SET used_at = NOW() WHERE id = :id AND used_at IS NULL');
        $stmt->execute(['id' => $reset->getId()]);
        if ($stmt->rowCount() !== 1) {
            $this->db->rollBack();
            flash('error', self::INVALID_MESSAGE);
            $this->redirect(route('password-forgot'));
        }
        $stmt = $this->db->prepare('UPDATE users SET password = :password, must_change_password = 0 WHERE id = :id');
        $stmt->execute(['password' => password_hash($password, PASSWORD_DEFAULT), 'id' => $reset->getUserId()]);
        // The failed logins of this account no longer count: the owner just proved they read the link.
        $stmt = $this->db->prepare('DELETE FROM login_attempts WHERE email = (SELECT email FROM users WHERE id = :id)');
        $stmt->execute(['id' => $reset->getUserId()]);
        $this->db->commit();

        flash('success', 'Your password is changed. You can log in with it now.');
        $this->redirect(route('login'));
    }

    // =================================================================================================
    // HELPERS
    // =================================================================================================

    /** A signed-in user does not need these pages. */
    private function redirectIfSignedIn(): void
    {
        if (Auth::isAuthenticated()) {
            $this->redirect(route('home'));
        }
    }

    /** The token from the link (GET) or from the hidden field of the form (POST). */
    private function tokenFromRequest(): string
    {
        $token = $_POST['token'] ?? $_GET['token'] ?? '';
        return is_string($token) ? $token : '';
    }

    /**
     * Minutes before this email may ask again (0 = allowed), and counts this request when it is allowed.
     * The times are kept in the session under the email's hash (no extra table: the ticket fixes the schema).
     */
    private function minutesBeforeNextRequest(string $email): int
    {
        $key = hash('sha256', $email);
        $now = time();
        $times = array_filter(
            (array) ($_SESSION['reset_requests'][$key] ?? []),
            static fn ($t): bool => is_int($t) && $t > $now - self::WINDOW_SECONDS
        );
        if (count($times) >= self::MAX_REQUESTS) {
            return (int) max(1, ceil((min($times) + self::WINDOW_SECONDS - $now) / 60));
        }
        $times[] = $now;
        $_SESSION['reset_requests'][$key] = array_values($times);
        return 0;
    }

    /** Errors and typed values kept for one page load (never a password). */
    private function formState(): array
    {
        $errors = $_SESSION['errors'] ?? [];
        $old = $_SESSION['old'] ?? [];
        unset($_SESSION['errors'], $_SESSION['old']);
        return ['errors' => $errors, 'old' => $old];
    }

    private function backWithErrors(string $page, array $errors, array $keep, array $query = []): never
    {
        $_SESSION['errors'] = $errors;
        $_SESSION['old'] = array_intersect_key($_POST, array_flip($keep));
        $this->redirect(route($page, $query));
    }
}
