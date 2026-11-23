<?php



/**
 * S3 Islem - Activity log of the administrators' actions on accounts (grid item 1.2, business rules and roles).
 * record() writes one line (called by AdminUserController after each successful change), forUser() reads the last lines
 * of one account (block "Recent activity" of the account page), index() is the admin page "Activity" with filters by
 * action and by date, 30 lines per page, newest first. requireRole('admin') is the first line of the page; every query
 * is a prepared statement with named parameters; the filters are checked against fixed lists before they reach the SQL.
 */
final class UserActivityController extends Controller
{
    protected string $layout = 'admin';
    private \PDO $db;

    public const PER_PAGE = 30;

    /** The columns read by every list: the line + the usernames of the administrator and of the account (LEFT JOIN, they may be deleted). */
    private const SELECT = 'SELECT a.*, actor.username AS actor_username, target.username AS target_username
        FROM user_activity a
        LEFT JOIN users actor ON actor.id = a.actor_id
        LEFT JOIN users target ON target.id = a.target_id';

    public function __construct()
    {
        $this->db = \Database::getConnexion();
    }

    // =================================================================================================
    // WRITE AND READ, used by AdminUserController
    // =================================================================================================

    /** One line of the log: $actorId (the admin) did $action (a key of UserActivityModel::ACTIONS) to $targetId. */
    public function record(int $actorId, ?int $targetId, string $action, string $details = ''): void
    {
        $stmt = $this->db->prepare('INSERT INTO user_activity (actor_id, target_id, action, details)
            VALUES (:actor, :target, :action, :details)');
        $stmt->bindValue('actor', $actorId > 0 ? $actorId : null, $actorId > 0 ? \PDO::PARAM_INT : \PDO::PARAM_NULL);
        $stmt->bindValue('target', $targetId, $targetId !== null ? \PDO::PARAM_INT : \PDO::PARAM_NULL);
        $stmt->bindValue('action', mb_substr($action, 0, 40));
        $stmt->bindValue('details', mb_substr($details, 0, 255));
        $stmt->execute();
    }

    /** @return UserActivityModel[] the last $limit lines about one account, newest first. */
    public function forUser(int $userId, int $limit = 10): array
    {
        $stmt = $this->db->prepare(self::SELECT . ' WHERE a.target_id = :id ORDER BY a.created_at DESC, a.id DESC LIMIT :limit');
        $stmt->bindValue('id', $userId, \PDO::PARAM_INT);
        $stmt->bindValue('limit', max(1, $limit), \PDO::PARAM_INT);
        $stmt->execute();
        return array_map([UserActivityModel::class, 'fromArray'], $stmt->fetchAll());
    }

    // =================================================================================================
    // PAGE "Activity"
    // =================================================================================================

    public function index(): void
    {
        Auth::requireRole('admin');
        [$f, $errors] = $this->filters();
        $total = $this->countFiltered($f);
        $pages = max(1, (int) ceil($total / self::PER_PAGE));
        $page = min(max(1, (int) ($_GET['p'] ?? 1)), $pages);
        $this->render('admin/activity', [
            'title' => 'Activity | SKYDROP Admin',
            'items' => $this->findFiltered($f, ($page - 1) * self::PER_PAGE),
            'actions' => UserActivityModel::ACTIONS,
            'filters' => $f,
            'errors' => $errors,
            'total' => $total,
            'page' => $page,
            'pages' => $pages,
        ]);
    }

    /**
     * Filters from the query string. The action must be a key of UserActivityModel::ACTIONS (allow-list), otherwise it is
     * ignored; a date must be a real YYYY-MM-DD date; "to" must be on or after "from" (same message as forms.js data-after).
     * Returns [filters, errors]: a wrong date is dropped and its message shown under its field.
     */
    private function filters(): array
    {
        $action = (string) ($_GET['action'] ?? '');
        $f = [
            'action' => isset(UserActivityModel::ACTIONS[$action]) ? $action : '',
            'from' => '',
            'to' => '',
        ];
        $errors = [];
        foreach (['from', 'to'] as $key) {
            $value = trim((string) ($_GET[$key] ?? ''));
            if ($value === '') {
                continue;
            }
            $date = \DateTimeImmutable::createFromFormat('!Y-m-d', $value);
            if ($date === false || $date->format('Y-m-d') !== $value) {
                $errors[$key] = 'Enter a valid date.';
            } else {
                $f[$key] = $value;
            }
        }
        if ($f['from'] !== '' && $f['to'] !== '' && $f['to'] < $f['from']) {
            $errors['to'] = 'The end must be on or after the start.';
            $f['to'] = '';
        }
        return [$f, $errors];
    }

    /** WHERE part and its named parameters for the filters. */
    private function where(array $f): array
    {
        $sql = ' WHERE 1 = 1';
        $args = [];
        if ($f['action'] !== '') {
            $sql .= ' AND a.action = :action';
            $args['action'] = $f['action'];
        }
        if ($f['from'] !== '') {
            $sql .= ' AND a.created_at >= :from';
            $args['from'] = $f['from'] . ' 00:00:00';
        }
        if ($f['to'] !== '') {
            // The whole "to" day is included: before the next midnight.
            $sql .= ' AND a.created_at < :to';
            $args['to'] = (new \DateTimeImmutable($f['to']))->modify('+1 day')->format('Y-m-d') . ' 00:00:00';
        }
        return [$sql, $args];
    }

    private function countFiltered(array $f): int
    {
        [$where, $args] = $this->where($f);
        $stmt = $this->db->prepare('SELECT COUNT(*) FROM user_activity a' . $where);
        $stmt->execute($args);
        return (int) $stmt->fetchColumn();
    }

    /** @return UserActivityModel[] one page of the filtered log, newest first. */
    private function findFiltered(array $f, int $offset): array
    {
        [$where, $args] = $this->where($f);
        $stmt = $this->db->prepare(self::SELECT . $where . ' ORDER BY a.created_at DESC, a.id DESC LIMIT :limit OFFSET :offset');
        foreach ($args as $k => $val) {
            $stmt->bindValue($k, $val);
        }
        $stmt->bindValue('limit', self::PER_PAGE, \PDO::PARAM_INT);
        $stmt->bindValue('offset', $offset, \PDO::PARAM_INT);
        $stmt->execute();
        return array_map([UserActivityModel::class, 'fromArray'], $stmt->fetchAll());
    }
}
