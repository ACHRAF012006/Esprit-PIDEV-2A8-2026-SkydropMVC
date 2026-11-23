<?php



/**
 * S1 - Administrator: accounts CRUD (security workshop Parts 3 and 6).
 * List (search, role and status filters, sort, 20 per page, CSV export), add, edit, see one account, delete,
 * activate / deactivate, change the role, give a new password. requireRole('admin') is the first line of every action;
 * the SQL is here, in prepared statements. An administrator never deletes, deactivates or demotes their own account.
 */
final class AdminUserController extends Controller
{
    protected string $layout = 'admin';
    private \PDO $db;

    public const PER_PAGE = 20;

    /** Sort keys of the list => SQL column (never the browser's text inside the SQL). */
    private const SORTS = ['name' => 'full_name', 'created' => 'created_at', 'login' => 'last_login_at'];

    /** Actions of the bulk bar (S2): value sent by the form => label of the select. */
    public const BULK_ACTIONS = ['activate' => 'Activate', 'deactivate' => 'Deactivate', 'delete' => 'Delete'];

    public function __construct()
    {
        $this->db = \Database::getConnexion();
    }

    // =================================================================================================
    // LIST, EXPORT
    // =================================================================================================

    public function index(): void
    {
        Auth::requireRole('admin');
        $f = $this->filters();
        $total = $this->countFiltered($f);
        $pages = max(1, (int) ceil($total / self::PER_PAGE));
        $page = min(max(1, (int) ($_GET['p'] ?? 1)), $pages);
        $this->render('admin/users', [
            'title' => 'Users | SKYDROP Admin',
            'users' => $this->findFiltered($f, self::PER_PAGE, ($page - 1) * self::PER_PAGE),
            'roles' => UserModel::ROLES,
            'filters' => $f,
            'total' => $total,
            'page' => $page,
            'pages' => $pages,
            'me' => (int) Auth::id(),
            'bulkActions' => self::BULK_ACTIONS,
            'pageScripts' => ['js/users-bulk.js'],
        ] + $this->formState());
    }

    /** The filtered list as a CSV file (UTF-8 with BOM so that Excel shows accents), no password. */
    public function export(): void
    {
        Auth::requireRole('admin');
        $users = $this->findFiltered($this->filters(), null, 0);
        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename="skydrop-users-' . date('Y-m-d') . '.csv"');
        $out = fopen('php://output', 'w');
        fwrite($out, "\xEF\xBB\xBF");
        // Separator, enclosure and an empty escape are given explicitly (PHP 8.4 deprecates the old default escape).
        fputcsv($out, ['id', 'username', 'full_name', 'email', 'phone', 'role', 'status', 'created_at', 'last_login_at'], ',', '"', '');
        foreach ($users as $u) {
            fputcsv($out, array_map([self::class, 'csvCell'], [$u->getId(), $u->getUsername(), $u->getFullName(), $u->getEmail(),
                (string) $u->getPhone(), $u->getRoleLabel(), $u->isActive() ? 'active' : 'deactivated', $u->getCreatedAt(), (string) $u->getLastLoginAt()]), ',', '"', '');
        }
        fclose($out);
        exit;
    }

    /** A cell starting with = + - @ would run as a formula in a spreadsheet (CSV injection): prefix it with a quote. */
    private static function csvCell(mixed $value): string
    {
        $value = (string) $value;
        return $value !== '' && in_array($value[0], ['=', '+', '-', '@'], true) ? "'" . $value : $value;
    }

    // =================================================================================================
    // ONE ACCOUNT: see, add / edit, delete
    // =================================================================================================

    public function show(string $id): void
    {
        Auth::requireRole('admin');
        $user = (new AuthController())->findById((int) $id) ?? abort(404);
        $this->render('admin/user', [
            'title' => $user->getFullName() . ' | SKYDROP Admin',
            'user' => $user,
            'me' => (int) Auth::id(),
        ] + $this->formState());
    }

    /** Add (no id) or edit (?id=) an account. */
    public function form(): void
    {
        Auth::requireRole('admin');
        $user = null;
        if (isset($_GET['id'])) {
            $user = (new AuthController())->findById((int) $_GET['id']) ?? abort(404);
        }
        $this->render('admin/user-form', [
            'title' => ($user ? 'Edit ' . $user->getFullName() : 'Add a user') . ' | SKYDROP Admin',
            'user' => $user,
            'roles' => UserModel::ROLES,
            'me' => (int) Auth::id(),
        ] + $this->formState());
    }

    public function save(): void
    {
        Auth::requireRole('admin');
        Csrf::check();
        $id = (int) ($_POST['id'] ?? 0);
        $existing = $id > 0 ? ((new AuthController())->findById($id) ?? abort(404)) : null;
        $back = $existing ? route('admin-user-form', ['id' => $id]) : route('admin-user-form');

        $v = (new Validator($_POST))
            ->required('full_name')->minLen('full_name', 3)->maxLen('full_name', 120)
            ->required('username')->minLen('username', 3)->maxLen('username', 50)
            ->required('email')->email('email')->maxLen('email', 150)
            ->required('role')->in('role', array_keys(UserModel::ROLES));
        if (!$existing) {
            $v->required('password')->minLen('password', 8);
        } else {
            $v->minLen('password', 8);
        }
        $errors = $v->errors();
        if (!isset($errors['username']) && !preg_match('/^[a-zA-Z0-9_\-]{3,50}$/', $v->get('username'))) {
            $errors['username'] = 'Use 3 to 50 letters, digits, _ or -.';
        }
        $password = (string) ($_POST['password'] ?? '');
        if (!isset($errors['password']) && $password !== '' && (!preg_match('/[A-Za-z]/', $password) || !preg_match('/\d/', $password))) {
            $errors['password'] = 'Use at least one letter and one digit.';
        }
        $phone = preg_replace('/\s+/', '', (string) ($_POST['phone'] ?? ''));
        if ($phone !== '' && !preg_match('/^\+?\d{8,15}$/', $phone)) {
            $errors['phone'] = 'Write 8 to 15 digits, for example 20123456.';
        }
        $email = strtolower($v->get('email'));
        if (!isset($errors['email']) && $this->taken('email', $email, $id)) {
            $errors['email'] = 'Another account already uses this email.';
        }
        if (!isset($errors['username']) && $this->taken('username', $v->get('username'), $id)) {
            $errors['username'] = 'Another account already uses this username.';
        }
        $active = isset($_POST['is_active']) ? 1 : 0;
        if ($existing && $id === Auth::id() && ($v->get('role') !== 'admin' || !$active)) {
            $errors['role'] = 'You cannot remove your own administrator role or deactivate your own account.';
        }
        if ($errors) {
            $_SESSION['errors'] = $errors;
            $_SESSION['old'] = array_intersect_key($_POST, array_flip(['full_name', 'username', 'email', 'phone', 'role', 'is_active']));
            $this->redirect($back);
        }

        $args = ['full_name' => $v->get('full_name'), 'username' => $v->get('username'), 'email' => $email,
            'phone' => $phone !== '' ? $phone : null, 'role' => $v->get('role'), 'active' => $active];
        if ($existing) {
            $sql = 'UPDATE users SET full_name = :full_name, username = :username, email = :email, phone = :phone, role = :role, is_active = :active';
            if ($password !== '') {
                $sql .= ', password = :password, must_change_password = 1';
                $args['password'] = password_hash($password, PASSWORD_DEFAULT);
            }
            $stmt = $this->db->prepare($sql . ' WHERE id = :id');
            $stmt->execute($args + ['id' => $id]);
            flash('success', $v->get('full_name') . ' is saved.');
        } else {
            // A first password given by the admin: the user must choose their own at the first login.
            $stmt = $this->db->prepare('INSERT INTO users (full_name, username, email, phone, role, is_active, password, must_change_password)
                VALUES (:full_name, :username, :email, :phone, :role, :active, :password, 1)');
            $stmt->execute($args + ['password' => password_hash($password, PASSWORD_DEFAULT)]);
            $id = (int) $this->db->lastInsertId();
            flash('success', $v->get('full_name') . ' is added. They choose their own password at their first login.');
        }
        $this->redirect(route('admin-user', ['id' => $id]));
    }

    public function delete(string $id): void
    {
        Auth::requireRole('admin');
        Csrf::check();
        $user = (new AuthController())->findById((int) $id) ?? abort(404);
        if ($user->getId() === Auth::id()) {
            flash('error', 'You cannot delete your own account here.');
            $this->redirect(route('admin-user', ['id' => $user->getId()]));
        }
        $deleted = (new AuthController())->deleteOrDeactivate($user->getId());
        flash('success', $deleted
            ? $user->getFullName() . ' is deleted.'
            : $user->getFullName() . ' is still used by other records (shop, orders...), so the account is deactivated instead.');
        $this->redirect(route('admin-users'));
    }

    // =================================================================================================
    // QUICK ACTIONS: activate / deactivate, role, new password
    // =================================================================================================

    public function toggle(string $id): void
    {
        Auth::requireRole('admin');
        Csrf::check();
        $user = (new AuthController())->findById((int) $id) ?? abort(404);
        if ($user->getId() === Auth::id()) {
            flash('error', 'You cannot deactivate your own account.');
        } else {
            $stmt = $this->db->prepare('UPDATE users SET is_active = :active WHERE id = :id');
            $stmt->execute(['active' => $user->isActive() ? 0 : 1, 'id' => $user->getId()]);
            flash('success', $user->getFullName() . ($user->isActive() ? ' is deactivated.' : ' is active again.'));
        }
        $this->redirect($this->backToList());
    }

    public function changeRole(string $id): void
    {
        Auth::requireRole('admin');
        Csrf::check();
        $user = (new AuthController())->findById((int) $id) ?? abort(404);
        $role = (string) ($_POST['role'] ?? '');
        if (!isset(UserModel::ROLES[$role])) {
            flash('error', 'Choose a role from the list.');
        } elseif ($user->getId() === Auth::id()) {
            flash('error', 'You cannot change your own role.');
        } else {
            $stmt = $this->db->prepare('UPDATE users SET role = :role WHERE id = :id');
            $stmt->execute(['role' => $role, 'id' => $user->getId()]);
            flash('success', $user->getFullName() . ' is now: ' . UserModel::ROLES[$role] . '.');
        }
        $this->redirect($this->backToList());
    }

    /** The admin gives a new password; the user must change it at the next login (column must_change_password). */
    public function setPassword(string $id): void
    {
        Auth::requireRole('admin');
        Csrf::check();
        $user = (new AuthController())->findById((int) $id) ?? abort(404);
        $password = (string) ($_POST['new_password'] ?? '');
        $error = null;
        if (mb_strlen($password) < 8) {
            $error = 'At least 8 characters.';
        } elseif (!preg_match('/[A-Za-z]/', $password) || !preg_match('/\d/', $password)) {
            $error = 'Use at least one letter and one digit.';
        } elseif ($password !== (string) ($_POST['new_password_confirm'] ?? '')) {
            $error = 'The two passwords are different.';
        }
        if ($error !== null) {
            $_SESSION['errors'] = ['new_password' => $error];
            $this->redirect(route('admin-user', ['id' => $user->getId()]));
        }
        $stmt = $this->db->prepare('UPDATE users SET password = :password, must_change_password = :must WHERE id = :id');
        $stmt->execute(['password' => password_hash($password, PASSWORD_DEFAULT), 'must' => $user->getId() === Auth::id() ? 0 : 1, 'id' => $user->getId()]);
        flash('success', 'New password saved for ' . $user->getFullName() . '. Give it to them: they choose their own at the next login.');
        $this->redirect(route('admin-user', ['id' => $user->getId()]));
    }

    // =================================================================================================
    // BULK ACTIONS (S2 Youssef Mili): activate, deactivate or delete the accounts checked in the list
    // =================================================================================================

    public function bulk(): void
    {
        Auth::requireRole('admin');
        Csrf::check();
        // Back to the same list: the form action carries the filters and the page in its query string.
        $back = route('admin-users', array_filter($this->filters() + ['p' => max(1, (int) ($_GET['p'] ?? 1))], static fn ($v) => $v !== ''));

        $v = (new Validator($_POST))->required('action')->in('action', array_keys(self::BULK_ACTIONS));
        $errors = $v->errors();
        $action = $v->get('action');

        // ids[]: keep whole numbers above 0, each one once (text, 0 and repeats are dropped).
        $ids = [];
        foreach ((array) ($_POST['ids'] ?? []) as $id) {
            $id = is_scalar($id) ? (int) $id : 0;
            if ($id > 0 && !in_array($id, $ids, true)) {
                $ids[] = $id;
            }
        }
        // Same rule as the single actions: an administrator never changes their own account here.
        $me = (int) Auth::id();
        if (in_array($me, $ids, true)) {
            $ids = array_values(array_diff($ids, [$me]));
            flash('error', 'Your own account was skipped: you cannot deactivate or delete it here.');
        }
        // Only the ids that still exist (an account may have been deleted in another tab).
        if ($ids) {
            [$in, $args] = $this->inList($ids);
            $stmt = $this->db->prepare('SELECT id FROM users WHERE id IN ' . $in);
            $stmt->execute($args);
            $ids = array_map('intval', $stmt->fetchAll(\PDO::FETCH_COLUMN));
        }
        if (!$ids) {
            $errors['ids'] = 'Select at least one account.';
        }
        if ($errors) {
            $_SESSION['errors'] = $errors;
            $_SESSION['old'] = ['action' => $action, 'ids' => $ids];
            $this->redirect($back);
        }

        if ($action === 'delete') {
            // The single delete's logic: an account still used by other tables is deactivated instead.
            $auth = new AuthController();
            $deleted = 0;
            foreach ($ids as $id) {
                if ($auth->deleteOrDeactivate($id)) {
                    $deleted++;
                }
            }
            $kept = count($ids) - $deleted;
            $message = $deleted > 0 ? self::accounts($deleted) . ' deleted.' : '';
            if ($kept > 0) {
                $message .= ' ' . self::accounts($kept) . ($kept === 1
                    ? ' is still used by other records (shop, orders...), so it is deactivated instead.'
                    : ' are still used by other records (shop, orders...), so they are deactivated instead.');
            }
            flash('success', trim($message));
        } else {
            [$in, $args] = $this->inList($ids);
            $stmt = $this->db->prepare('UPDATE users SET is_active = :active WHERE id IN ' . $in);
            $stmt->execute($args + ['active' => $action === 'activate' ? 1 : 0]);
            flash('success', self::accounts(count($ids)) . ($action === 'activate' ? ' activated.' : ' deactivated.'));
        }
        $this->redirect($back);
    }

    /** "(:id0, :id1, ...)" and its parameters ['id0' => 5, 'id1' => 9, ...]: one named parameter per id, never the id in the SQL. */
    private function inList(array $ids): array
    {
        $names = [];
        $args = [];
        foreach (array_values($ids) as $i => $id) {
            $names[] = ':id' . $i;
            $args['id' . $i] = $id;
        }
        return ['(' . implode(', ', $names) . ')', $args];
    }

    /** "1 account" / "3 accounts". */
    private static function accounts(int $n): string
    {
        return $n . ' account' . ($n === 1 ? '' : 's');
    }

    // =================================================================================================
    // DATA ACCESS used by this page and by the overview
    // =================================================================================================

    /** @return UserModel[] newest first, optionally one role and a name / username / email search. */
    public function getAllUsers(string $role = '', string $search = ''): array
    {
        return $this->findFiltered(['role' => $role, 'status' => '', 'q' => $search, 'sort' => 'created', 'dir' => 'desc'], null, 0);
    }

    /** role => number of accounts (every role, 0 when none). */
    public function countByRole(): array
    {
        $out = array_fill_keys(array_keys(UserModel::ROLES), 0);
        foreach ($this->db->query('SELECT role, COUNT(*) AS n FROM users GROUP BY role') as $row) {
            $out[$row['role']] = (int) $row['n'];
        }
        return $out;
    }

    /** ['active' => n, 'deactivated' => n] */
    public function countByStatus(): array
    {
        $row = $this->db->query('SELECT SUM(is_active = 1) AS active, SUM(is_active = 0) AS deactivated FROM users')->fetch();
        return ['active' => (int) $row['active'], 'deactivated' => (int) $row['deactivated']];
    }

    /** New accounts per week for the last $weeks weeks, oldest first: [['week' => 'YYYY-MM-DD' (Monday), 'n' => int], ...]. */
    public function newAccountsPerWeek(int $weeks = 8): array
    {
        $stmt = $this->db->prepare('SELECT DATE(created_at - INTERVAL WEEKDAY(created_at) DAY) AS week, COUNT(*) AS n FROM users
            WHERE created_at >= CURDATE() - INTERVAL WEEKDAY(CURDATE()) DAY - INTERVAL :w WEEK GROUP BY week');
        $stmt->bindValue('w', $weeks - 1, \PDO::PARAM_INT);
        $stmt->execute();
        $counts = array_column($stmt->fetchAll(), 'n', 'week');
        $monday = new \DateTimeImmutable('monday this week');
        $out = [];
        for ($i = $weeks - 1; $i >= 0; $i--) {
            $day = $monday->modify("-$i week")->format('Y-m-d');
            $out[] = ['week' => $day, 'n' => (int) ($counts[$day] ?? 0)];
        }
        return $out;
    }

    /** Filters of the list from the query string, each one checked against a fixed list. */
    private function filters(): array
    {
        $role = (string) ($_GET['role'] ?? '');
        $status = (string) ($_GET['status'] ?? '');
        $sort = (string) ($_GET['sort'] ?? 'created');
        return [
            'role' => isset(UserModel::ROLES[$role]) ? $role : '',
            'status' => in_array($status, ['active', 'deactivated'], true) ? $status : '',
            'q' => trim(mb_substr((string) ($_GET['q'] ?? ''), 0, 80)),
            'sort' => isset(self::SORTS[$sort]) ? $sort : 'created',
            'dir' => ($_GET['dir'] ?? '') === 'asc' ? 'asc' : 'desc',
        ];
    }

    /** WHERE part and its named parameters for the filters. */
    private function where(array $f): array
    {
        $sql = ' WHERE 1 = 1';
        $args = [];
        if ($f['role'] !== '') {
            $sql .= ' AND role = :role';
            $args['role'] = $f['role'];
        }
        if ($f['status'] !== '') {
            $sql .= ' AND is_active = :active';
            $args['active'] = $f['status'] === 'active' ? 1 : 0;
        }
        if ($f['q'] !== '') {
            $sql .= ' AND (full_name LIKE :s1 OR username LIKE :s2 OR email LIKE :s3)';
            $args['s1'] = $args['s2'] = $args['s3'] = '%' . $f['q'] . '%';
        }
        return [$sql, $args];
    }

    private function countFiltered(array $f): int
    {
        [$where, $args] = $this->where($f);
        $stmt = $this->db->prepare('SELECT COUNT(*) FROM users' . $where);
        $stmt->execute($args);
        return (int) $stmt->fetchColumn();
    }

    /** @return UserModel[] */
    private function findFiltered(array $f, ?int $limit, int $offset): array
    {
        [$where, $args] = $this->where($f);
        // The column and the direction come from fixed lists (SORTS, asc/desc): never the browser's text.
        $order = ' ORDER BY ' . self::SORTS[$f['sort']] . ($f['dir'] === 'asc' ? ' ASC' : ' DESC') . ', id DESC';
        $stmt = $this->db->prepare('SELECT * FROM users' . $where . $order . ($limit !== null ? ' LIMIT :limit OFFSET :offset' : ''));
        foreach ($args as $k => $val) {
            $stmt->bindValue($k, $val, is_int($val) ? \PDO::PARAM_INT : \PDO::PARAM_STR);
        }
        if ($limit !== null) {
            $stmt->bindValue('limit', $limit, \PDO::PARAM_INT);
            $stmt->bindValue('offset', $offset, \PDO::PARAM_INT);
        }
        $stmt->execute();
        return array_map([UserModel::class, 'fromArray'], $stmt->fetchAll());
    }

    private function taken(string $column, string $value, int $exceptId): bool
    {
        $sql = $column === 'email' ? 'SELECT id FROM users WHERE email = :v AND id <> :id' : 'SELECT id FROM users WHERE username = :v AND id <> :id';
        $stmt = $this->db->prepare($sql . ' LIMIT 1');
        $stmt->execute(['v' => $value, 'id' => $exceptId]);
        return (bool) $stmt->fetch();
    }

    /** Back to the page the quick action came from (the list or one account), only our own pages. */
    private function backToList(): string
    {
        return ($_POST['back'] ?? '') === 'admin-user' && isset($_POST['back_id'])
            ? route('admin-user', ['id' => (int) $_POST['back_id']])
            : route('admin-users');
    }

    private function formState(): array
    {
        $errors = $_SESSION['errors'] ?? [];
        $old = $_SESSION['old'] ?? [];
        unset($_SESSION['errors'], $_SESSION['old']);
        return ['errors' => $errors, 'old' => $old];
    }
}
