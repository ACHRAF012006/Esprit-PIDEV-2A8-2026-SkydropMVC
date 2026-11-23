<?php

/**
 * S1 - Administrator: coupons. List (JOIN coupon_types for the type, LEFT JOIN coupon_uses for the number of uses,
 * filters by status and code), add, edit, activate / deactivate, delete. Auth::requireRole('admin') is the first line
 * of every action; every query is a prepared statement with named parameters.
 * Rules: unique code in capitals; the value depends on the type's kind (percent 1-100, fixed > 0, free delivery none);
 * the end date is on or after the start date; limits empty or >= 1; a coupon already used cannot be deleted.
 */
final class AdminCouponController extends Controller
{
    protected string $layout = 'admin';
    private \PDO $db;

    /** Status filters of the list => their label. */
    public const STATUSES = [
        'running'   => 'Running now',
        'scheduled' => 'Not started',
        'expired'   => 'Expired',
        'inactive'  => 'Deactivated',
    ];

    public function __construct()
    {
        $this->db = \Database::getConnexion();
    }

    public function index(): void
    {
        Auth::requireRole('admin');
        $status = (string) ($_GET['status'] ?? '');
        $status = isset(self::STATUSES[$status]) ? $status : '';
        $q = strtoupper(trim(mb_substr((string) ($_GET['q'] ?? ''), 0, 20)));

        $where = [];
        $args = [];
        if ($q !== '') {
            $where[] = 'c.code LIKE :q';
            $args['q'] = '%' . $q . '%';
        }
        // The status is computed from is_active and the dates (CURDATE() = today on the database server).
        $where[] = match ($status) {
            'running' => 'c.is_active = 1 AND c.starts_at <= CURDATE() AND c.ends_at >= CURDATE()',
            'scheduled' => 'c.is_active = 1 AND c.starts_at > CURDATE()',
            'expired' => 'c.ends_at < CURDATE()',
            'inactive' => 'c.is_active = 0',
            default => '1 = 1',
        };
        $stmt = $this->db->prepare('SELECT c.*, t.name AS type_name, t.key_name AS type_key, COUNT(u.id) AS uses_count
            FROM coupons c
            JOIN coupon_types t ON t.id = c.type_id
            LEFT JOIN coupon_uses u ON u.coupon_id = c.id
            WHERE ' . implode(' AND ', $where) . '
            GROUP BY c.id
            ORDER BY c.ends_at DESC, c.code');
        $stmt->execute($args);

        $this->render('admin/coupons', [
            'title' => 'Coupons | SKYDROP Admin',
            'coupons' => array_map([CouponModel::class, 'fromArray'], $stmt->fetchAll()),
            'statuses' => self::STATUSES,
            'filters' => ['status' => $status, 'q' => $q],
            'today' => date('Y-m-d'),
        ]);
    }

    /** Add (no id) or edit (?id=) a coupon. */
    public function form(): void
    {
        Auth::requireRole('admin');
        $coupon = isset($_GET['id']) ? ($this->find((int) $_GET['id']) ?? abort(404)) : null;
        $this->render('admin/coupon-form', [
            'title' => ($coupon ? 'Edit ' . $coupon->getCode() : 'Add a coupon') . ' | SKYDROP Admin',
            'coupon' => $coupon,
            'types' => (new AdminCouponTypeController())->all(),
            'pageScripts' => ['js/coupons.js'],
        ] + $this->formState());
    }

    public function save(): void
    {
        Auth::requireRole('admin');
        Csrf::check();
        $id = (int) ($_POST['id'] ?? 0);
        $coupon = $id > 0 ? ($this->find($id) ?? abort(404)) : null;
        $back = $coupon ? route('admin-coupon-form', ['id' => $id]) : route('admin-coupon-form');

        $v = (new Validator($_POST))
            ->required('code')->minLen('code', 3)->maxLen('code', 20)
            ->required('type_id')->int('type_id', 1)
            ->decimal('min_order', 0)
            ->required('starts_at')->required('ends_at')
            ->int('max_uses', 1)->int('per_user_limit', 1);
        $errors = $v->errors();

        $code = strtoupper($v->get('code'));
        if (!isset($errors['code']) && !preg_match('/^[A-Z0-9]{3,20}$/', $code)) {
            $errors['code'] = 'Use 3 to 20 letters or digits, no spaces.';
        }
        if (!isset($errors['code']) && $this->codeTaken($code, $id)) {
            $errors['code'] = 'Another coupon already uses this code.';
        }

        // The value depends on the kind of the chosen type (read from the database, never from the form).
        $type = isset($errors['type_id']) ? null : (new AdminCouponTypeController())->find((int) $v->get('type_id'));
        if (!isset($errors['type_id']) && !$type) {
            $errors['type_id'] = 'Invalid choice.';
        }
        $value = null;
        if ($type && $type->needsValue()) {
            $raw = $v->get('value');
            if ($raw === '') {
                $errors['value'] = 'This field is required.';
            } elseif (!is_numeric($raw)) {
                $errors['value'] = 'Must be a number.';
            } elseif ($type->getKeyName() === 'percent' && ((float) $raw < 1 || (float) $raw > 100)) {
                $errors['value'] = 'Number out of range (1 to 100).';
            } elseif ($type->getKeyName() !== 'percent' && (float) $raw < 0.001) {
                $errors['value'] = 'Number out of range.';
            } else {
                $value = round((float) $raw, 3);
            }
        }

        $starts = $v->get('starts_at');
        $ends = $v->get('ends_at');
        if (!isset($errors['starts_at']) && !$this->isDate($starts)) {
            $errors['starts_at'] = 'Write a date, for example 2026-12-01.';
        }
        if (!isset($errors['ends_at']) && !$this->isDate($ends)) {
            $errors['ends_at'] = 'Write a date, for example 2026-12-31.';
        }
        if (!isset($errors['starts_at']) && !isset($errors['ends_at']) && $ends < $starts) {
            $errors['ends_at'] = 'The end must be on or after the start.';
        }

        $maxUses = $v->get('max_uses') === '' ? null : (int) $v->get('max_uses');
        if ($coupon && $maxUses !== null && !isset($errors['max_uses']) && $maxUses < $coupon->getUsesCount()) {
            $errors['max_uses'] = 'This coupon was already used ' . $coupon->getUsesCount() . ' times: the limit cannot be lower.';
        }

        if ($errors) {
            $_SESSION['errors'] = $errors;
            $_SESSION['old'] = array_intersect_key($_POST, array_flip(['code', 'type_id', 'value', 'min_order', 'starts_at', 'ends_at', 'max_uses', 'per_user_limit', 'is_active']));
            $this->redirect($back);
        }

        $args = [
            'code' => $code,
            'type_id' => $type->getId(),
            'value' => $value,
            'min_order' => $v->get('min_order') === '' ? 0 : round((float) $v->get('min_order'), 3),
            'starts_at' => $starts,
            'ends_at' => $ends,
            'max_uses' => $maxUses,
            'per_user_limit' => $v->get('per_user_limit') === '' ? null : (int) $v->get('per_user_limit'),
            'active' => isset($_POST['is_active']) ? 1 : 0,
        ];
        if ($coupon) {
            $stmt = $this->db->prepare('UPDATE coupons SET code = :code, type_id = :type_id, value = :value, min_order = :min_order,
                starts_at = :starts_at, ends_at = :ends_at, max_uses = :max_uses, per_user_limit = :per_user_limit, is_active = :active
                WHERE id = :id');
            $stmt->execute($args + ['id' => $id]);
            flash('success', 'The coupon ' . $code . ' is saved.');
        } else {
            $stmt = $this->db->prepare('INSERT INTO coupons (code, type_id, value, min_order, starts_at, ends_at, max_uses, per_user_limit, is_active, created_by)
                VALUES (:code, :type_id, :value, :min_order, :starts_at, :ends_at, :max_uses, :per_user_limit, :active, :created_by)');
            $stmt->execute($args + ['created_by' => Auth::id()]);
            flash('success', 'The coupon ' . $code . ' is added.');
        }
        $this->redirect(route('admin-coupons'));
    }

    /** Activate or deactivate a coupon (a deactivated coupon is refused at checkout and hidden from the offers). */
    public function toggle(string $id): void
    {
        Auth::requireRole('admin');
        Csrf::check();
        $coupon = $this->find((int) $id) ?? abort(404);
        $stmt = $this->db->prepare('UPDATE coupons SET is_active = :active WHERE id = :id');
        $stmt->execute(['active' => $coupon->isActive() ? 0 : 1, 'id' => $coupon->getId()]);
        flash('success', 'The coupon ' . $coupon->getCode() . ($coupon->isActive() ? ' is deactivated.' : ' is activated.'));
        $this->redirect(route('admin-coupons'));
    }

    public function delete(string $id): void
    {
        Auth::requireRole('admin');
        Csrf::check();
        $coupon = $this->find((int) $id) ?? abort(404);
        if ($coupon->getUsesCount() > 0) {
            flash('error', 'The coupon ' . $coupon->getCode() . ' was used ' . $coupon->getUsesCount() . ' time'
                . ($coupon->getUsesCount() > 1 ? 's' : '') . ', so it cannot be deleted (its uses must stay). Deactivate it instead.');
            $this->redirect(route('admin-coupons'));
        }
        $stmt = $this->db->prepare('DELETE FROM coupons WHERE id = :id');
        $stmt->execute(['id' => $coupon->getId()]);
        flash('success', 'The coupon ' . $coupon->getCode() . ' is deleted.');
        $this->redirect(route('admin-coupons'));
    }

    // =================================================================================================

    /** One coupon with its type and its number of uses. */
    private function find(int $id): ?CouponModel
    {
        $stmt = $this->db->prepare('SELECT c.*, t.name AS type_name, t.key_name AS type_key, COUNT(u.id) AS uses_count
            FROM coupons c
            JOIN coupon_types t ON t.id = c.type_id
            LEFT JOIN coupon_uses u ON u.coupon_id = c.id
            WHERE c.id = :id
            GROUP BY c.id');
        $stmt->execute(['id' => $id]);
        $row = $stmt->fetch();
        return $row ? CouponModel::fromArray($row) : null;
    }

    private function codeTaken(string $code, int $exceptId): bool
    {
        $stmt = $this->db->prepare('SELECT 1 FROM coupons WHERE code = :code AND id <> :id LIMIT 1');
        $stmt->execute(['code' => $code, 'id' => $exceptId]);
        return (bool) $stmt->fetchColumn();
    }

    /** A real calendar date written as YYYY-MM-DD (what <input type="date"> sends). */
    private function isDate(string $value): bool
    {
        $d = \DateTime::createFromFormat('!Y-m-d', $value);
        return $d !== false && $d->format('Y-m-d') === $value;
    }

    private function formState(): array
    {
        $errors = $_SESSION['errors'] ?? [];
        $old = $_SESSION['old'] ?? [];
        unset($_SESSION['errors'], $_SESSION['old']);
        return ['errors' => $errors, 'old' => $old];
    }
}
