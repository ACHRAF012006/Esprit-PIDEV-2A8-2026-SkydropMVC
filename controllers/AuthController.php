<?php


/**
 * S1 - Accounts (security workshop, Parts 4 to 6): register, login, logout, profile, password change.
 * As in the workshop, the controller holds the logic AND the SQL (prepared statements); UserModel only holds data;
 * the views only display. Other modules read an account with (new \User\AuthController())->findById($id).
 */
final class AuthController extends Controller
{
    private \PDO $db;

    public function __construct()
    {
        $this->db = \Database::getConnexion();
    }

    // =================================================================================================
    // DATA ACCESS (prepared statements: the input is always data, never SQL code)
    // =================================================================================================

    public function findById(int $id): ?UserModel
    {
        $stmt = $this->db->prepare('SELECT * FROM users WHERE id = :id LIMIT 1');
        $stmt->execute(['id' => $id]);
        $row = $stmt->fetch();
        return $row ? UserModel::fromArray($row) : null;
    }

    private function findByEmail(string $email): ?UserModel
    {
        $stmt = $this->db->prepare('SELECT * FROM users WHERE email = :email LIMIT 1');
        $stmt->execute(['email' => $email]);
        $row = $stmt->fetch();
        return $row ? UserModel::fromArray($row) : null;
    }

    private function existsByEmailOrUsername(string $email, string $username): bool
    {
        $stmt = $this->db->prepare('SELECT id FROM users WHERE email = :email OR username = :username LIMIT 1');
        $stmt->execute(['email' => $email, 'username' => $username]);
        return (bool) $stmt->fetch();
    }

    private function insertUser(UserModel $user): bool
    {
        $stmt = $this->db->prepare('INSERT INTO users (username, email, password, role, full_name, phone)
            VALUES (:username, :email, :password, :role, :full_name, :phone)');
        return $stmt->execute([
            'username' => $user->getUsername(), 'email' => $user->getEmail(), 'password' => $user->getPassword(),
            'role' => $user->getRole(), 'full_name' => $user->getFullName(), 'phone' => $user->getPhone(),
        ]);
    }

    /** The page where a role lands after login: back office for owners, advertisers and admins, home for customers. */
    public static function homePageFor(string $role): string
    {
        return match ($role) {
            'admin' => 'admin-dashboard',
            'shop_owner', 'advertiser' => 'owner-dashboard',
            default => 'home',
        };
    }

    // =================================================================================================
    // REGISTER (workshop Part 4): server-side validation, unique email and username, password_hash()
    // =================================================================================================

    public function registerForm(): void
    {
        if (Auth::isAuthenticated()) {
            $this->redirect(route(self::homePageFor((string) Auth::role())));
        }
        $this->render('front/register', ['title' => 'Create an account | SKYDROP'] + $this->formState());
    }

    public function register(): void
    {
        Csrf::check();
        $v = (new Validator($_POST))
            ->required('full_name')->minLen('full_name', 3)->maxLen('full_name', 120)
            ->required('username')->minLen('username', 3)->maxLen('username', 50)
            ->required('email')->email('email')->maxLen('email', 150)
            ->required('password')->minLen('password', 8)
            ->required('password_confirm')
            ->required('role')->in('role', ['customer', 'shop_owner', 'advertiser']); // never "admin" from the public form
        $errors = $v->errors();
        if (!isset($errors['username']) && !preg_match('/^[a-zA-Z0-9_\-]{3,50}$/', $v->get('username'))) {
            $errors['username'] = 'Use 3 to 50 letters, digits, _ or -.';
        }
        if (!isset($errors['password']) && (!preg_match('/[A-Za-z]/', (string) $_POST['password']) || !preg_match('/\d/', (string) $_POST['password']))) {
            $errors['password'] = 'Use at least one letter and one digit.';
        }
        if (!isset($errors['password_confirm']) && ($_POST['password'] ?? '') !== ($_POST['password_confirm'] ?? '')) {
            $errors['password_confirm'] = 'The two passwords are different.';
        }
        if (!$errors && $this->existsByEmailOrUsername($v->get('email'), $v->get('username'))) {
            $errors['email'] = 'This email or username is already taken.';
        }
        if ($errors) {
            $this->backWithErrors('register', $errors, ['full_name', 'username', 'email', 'role']);
        }

        $user = new UserModel();
        $user->setFullName($v->get('full_name'));
        $user->setUsername($v->get('username'));
        $user->setEmail(strtolower($v->get('email')));
        $user->setPassword(password_hash((string) $_POST['password'], PASSWORD_DEFAULT)); // bcrypt, salted automatically
        $user->setRole($v->get('role'));
        $this->insertUser($user);

        flash('success', 'Your account is ready. You can log in now.');
        $this->redirect(route('login'));
    }

    // =================================================================================================
    // LOGIN / LOGOUT (workshop Part 5)
    // =================================================================================================

    public function loginForm(): void
    {
        if (Auth::isAuthenticated()) {
            $this->redirect(route(self::homePageFor((string) Auth::role())));
        }
        $this->render('front/login', ['title' => 'Login | SKYDROP'] + $this->formState());
    }

    public function login(): void
    {
        Csrf::check();
        $v = (new Validator($_POST))->required('email')->email('email')->required('password');
        if ($v->fails()) {
            $this->backWithErrors('login', $v->errors(), ['email']);
        }
        $email = strtolower($v->get('email'));
        // Password guessing: after 5 failures on this email within 15 minutes, refuse for 15 minutes.
        $wait = $this->minutesBlocked($email);
        if ($wait > 0) {
            $this->backWithErrors('login', ['email' => "Too many failed logins for this email. Try again in $wait minute" . ($wait > 1 ? 's' : '') . '.'], ['email']);
        }
        $user = $this->findByEmail($email);
        // One generic message: it never tells whether the email exists.
        if ($user === null || !password_verify((string) $_POST['password'], $user->getPassword())) {
            $stmt = $this->db->prepare('INSERT INTO login_attempts (email, ip_address) VALUES (:email, :ip)');
            $stmt->execute(['email' => $email, 'ip' => substr((string) ($_SERVER['REMOTE_ADDR'] ?? ''), 0, 45)]);
            $this->backWithErrors('login', ['email' => 'Incorrect email or password.'], ['email']);
        }
        if (!$user->isActive()) {
            $this->backWithErrors('login', ['email' => 'This account is deactivated. Contact an administrator.'], ['email']);
        }

        Auth::login($user->getId(), $user->getUsername(), $user->getRole()); // session_regenerate_id(true) inside
        $stmt = $this->db->prepare('UPDATE users SET last_login_at = NOW() WHERE id = :id');
        $stmt->execute(['id' => $user->getId()]);
        $stmt = $this->db->prepare('DELETE FROM login_attempts WHERE email = :email');
        $stmt->execute(['email' => $email]);
        if ($user->mustChangePassword()) {
            unset($_SESSION['after_login']);
            flash('error', 'An administrator gave you a new password. Choose your own password now.');
            $this->redirect(route('profile'));
        }

        $back = (string) ($_SESSION['after_login'] ?? '');
        unset($_SESSION['after_login']);
        flash('success', 'Welcome back, ' . $user->getFullName() . '.');
        // Back to the page that asked for a login (same site only, and only an office this role may open),
        // otherwise the space of the role.
        parse_str((string) parse_url($back, PHP_URL_QUERY), $q);
        $key = (string) ($q['page'] ?? '');
        $allowed = $back !== '' && str_starts_with($back, base_path_url() . '/index.php?')
            && !(str_starts_with($key, 'admin-') && $user->getRole() !== 'admin')
            && !(str_starts_with($key, 'owner-') && $user->getRole() === 'customer');
        $this->redirect($allowed ? $back : route(self::homePageFor($user->getRole())));
    }

    public function logout(): void
    {
        Csrf::check();
        Auth::logout();
        flash('success', 'You are logged out.');
        $this->redirect(route('login'));
    }

    // =================================================================================================
    // PROFILE (my account)
    // =================================================================================================

    public function profile(): void
    {
        Auth::requireAuth();
        $user = $this->currentUserOrLogout();
        $this->render('front/profile', [
            'title' => 'My profile | SKYDROP',
            'user' => $user,
        ] + $this->formState());
    }

    public function updateProfile(): void
    {
        Auth::requireAuth();
        Csrf::check();
        $v = (new Validator($_POST))->required('full_name')->minLen('full_name', 3)->maxLen('full_name', 120);
        $errors = $v->errors();
        $phone = preg_replace('/\s+/', '', (string) ($_POST['phone'] ?? ''));
        if ($phone !== '' && !preg_match('/^\+?\d{8,15}$/', $phone)) {
            $errors['phone'] = 'Write 8 to 15 digits, for example 20123456.';
        }
        if ($errors) {
            $this->backWithErrors('profile', $errors, ['full_name', 'phone']);
        }
        $stmt = $this->db->prepare('UPDATE users SET full_name = :full_name, phone = :phone WHERE id = :id');
        $stmt->execute(['full_name' => $v->get('full_name'), 'phone' => $phone !== '' ? $phone : null, 'id' => Auth::id()]);
        flash('success', 'Your profile is saved.');
        $this->redirect(route('profile'));
    }

    public function changePassword(): void
    {
        Auth::requireAuth();
        Csrf::check();
        $user = $this->currentUserOrLogout();
        $errors = (new Validator($_POST))->required('current_password')->required('new_password')->minLen('new_password', 8)->errors();
        if (!isset($errors['current_password']) && !password_verify((string) $_POST['current_password'], $user->getPassword())) {
            $errors['current_password'] = 'This is not your current password.';
        }
        if (!isset($errors['new_password']) && ($_POST['new_password'] ?? '') !== ($_POST['new_password_confirm'] ?? '')) {
            $errors['new_password_confirm'] = 'The two passwords are different.';
        }
        if ($errors) {
            $this->backWithErrors('profile', $errors, []);
        }
        if (!isset($errors['new_password']) && (!preg_match('/[A-Za-z]/', (string) $_POST['new_password']) || !preg_match('/\d/', (string) $_POST['new_password']))) {
            $errors['new_password'] = 'Use at least one letter and one digit.';
        }
        if ($errors) {
            $this->backWithErrors('profile', $errors, []);
        }
        $stmt = $this->db->prepare('UPDATE users SET password = :password, must_change_password = 0 WHERE id = :id');
        $stmt->execute(['password' => password_hash((string) $_POST['new_password'], PASSWORD_DEFAULT), 'id' => $user->getId()]);
        flash('success', 'Your password is changed.');
        $this->redirect(route('profile'));
    }

    /** A user deletes their own account: password asked again; an account other rows still use is deactivated instead. */
    public function deleteAccount(): void
    {
        Auth::requireAuth();
        Csrf::check();
        $user = $this->currentUserOrLogout();
        if (!password_verify((string) ($_POST['delete_password'] ?? ''), $user->getPassword())) {
            $this->backWithErrors('profile', ['delete_password' => 'This is not your current password.'], []);
        }
        if ($user->getRole() === 'admin') {
            $stmt = $this->db->prepare("SELECT COUNT(*) FROM users WHERE role = 'admin' AND is_active = 1 AND id <> :id");
            $stmt->execute(['id' => $user->getId()]);
            if ((int) $stmt->fetchColumn() === 0) {
                $this->backWithErrors('profile', ['delete_password' => 'You are the last administrator: give the role to someone else first.'], []);
            }
        }
        $message = $this->deleteOrDeactivate($user->getId())
            ? 'Your account is deleted.'
            : 'Your account is closed (other records still use it, so it is deactivated instead of deleted).';
        Auth::logout();
        flash('success', $message);
        $this->redirect(route('login'));
    }

    /**
     * Deletes an account; when other tables still reference it (foreign key error 1451), deactivates it instead.
     * Returns true when the row was deleted. Used by the profile and by the admin users page.
     */
    public function deleteOrDeactivate(int $id): bool
    {
        try {
            $stmt = $this->db->prepare('DELETE FROM users WHERE id = :id');
            $stmt->execute(['id' => $id]);
            return true;
        } catch (\PDOException $ex) {
            if ((int) ($ex->errorInfo[1] ?? 0) !== 1451) {
                throw $ex;
            }
            $stmt = $this->db->prepare('UPDATE users SET is_active = 0 WHERE id = :id');
            $stmt->execute(['id' => $id]);
            return false;
        }
    }

    /**
     * Called by public/index.php on every request of a signed-in user (workshop 1.4): the role and the active flag are read
     * again from the database, so a deactivated, deleted or demoted account loses its access at once, not at its next login.
     * An account with a password given by an admin may only open its profile until it chooses its own password.
     */
    public function refreshSession(string $page): void
    {
        $user = $this->findById((int) Auth::id());
        if ($user === null || !$user->isActive()) {
            Auth::logout();
            flash('error', 'Your account is no longer available.');
            $this->redirect(route('login'));
        }
        $_SESSION['role'] = $user->getRole();
        $_SESSION['username'] = $user->getUsername();
        if ($user->mustChangePassword() && !in_array($page, ['profile', 'profile-password', 'logout'], true)) {
            flash('error', 'Choose your own password first (an administrator gave you a temporary one).');
            $this->redirect(route('profile'));
        }
    }

    /** Minutes left before this email may try again (0 = not blocked): 5 failures within the last 15 minutes. */
    private function minutesBlocked(string $email): int
    {
        $stmt = $this->db->prepare('SELECT COUNT(*) AS n, MIN(attempted_at) AS first_at FROM login_attempts
            WHERE email = :email AND attempted_at > NOW() - INTERVAL 15 MINUTE');
        $stmt->execute(['email' => $email]);
        $row = $stmt->fetch();
        if ((int) $row['n'] < 5) {
            return 0;
        }
        $stmt = $this->db->prepare('SELECT GREATEST(1, CEIL(TIMESTAMPDIFF(SECOND, NOW(), MAX(attempted_at) + INTERVAL 15 MINUTE) / 60))
            FROM login_attempts WHERE email = :email');
        $stmt->execute(['email' => $email]);
        return (int) $stmt->fetchColumn();
    }

    /** The signed-in account; a session whose account was deleted or deactivated is closed (workshop 5.3). */
    private function currentUserOrLogout(): UserModel
    {
        $user = $this->findById((int) Auth::id());
        if ($user === null || !$user->isActive()) {
            Auth::logout();
            flash('error', 'Your account is no longer available.');
            $this->redirect(route('login'));
        }
        return $user;
    }

    // =================================================================================================
    // FORM STATE (errors and typed values kept for one page load, never a password)
    // =================================================================================================

    private function formState(): array
    {
        $errors = $_SESSION['errors'] ?? [];
        $old = $_SESSION['old'] ?? [];
        unset($_SESSION['errors'], $_SESSION['old']);
        return ['errors' => $errors, 'old' => $old];
    }

    private function backWithErrors(string $page, array $errors, array $keep): never
    {
        $_SESSION['errors'] = $errors;
        $_SESSION['old'] = array_intersect_key($_POST, array_flip($keep));
        $this->redirect(route($page));
    }
}
