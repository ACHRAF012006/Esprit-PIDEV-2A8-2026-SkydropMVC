<?php

/**
 * S1 - Administrator: coupon types (what a coupon does). List with the number of coupons of each type (LEFT JOIN on
 * coupons), add, edit, delete. Auth::requireRole('admin') is the first line of every action; every query is a
 * prepared statement. Rules: one type per kind; the kind decides whether coupons need a value; a type still used by
 * a coupon cannot be deleted (deactivate it instead).
 */
final class AdminCouponTypeController extends Controller
{
    protected string $layout = 'admin';
    private \PDO $db;

    public function __construct()
    {
        $this->db = \Database::getConnexion();
    }

    public function index(): void
    {
        Auth::requireRole('admin');
        $stmt = $this->db->prepare('SELECT t.*, COUNT(c.id) AS coupon_count
            FROM coupon_types t LEFT JOIN coupons c ON c.type_id = t.id
            GROUP BY t.id ORDER BY t.name');
        $stmt->execute();
        $this->render('admin/coupon-types', [
            'title' => 'Coupon types | SKYDROP Admin',
            'types' => array_map([CouponTypeModel::class, 'fromArray'], $stmt->fetchAll()),
            'kinds' => CouponTypeModel::KINDS,
        ]);
    }

    /** Add (no id) or edit (?id=) a coupon type. */
    public function form(): void
    {
        Auth::requireRole('admin');
        $type = isset($_GET['id']) ? ($this->find((int) $_GET['id']) ?? abort(404)) : null;
        $this->render('admin/coupon-type-form', [
            'title' => ($type ? 'Edit ' . $type->getName() : 'Add a coupon type') . ' | SKYDROP Admin',
            'type' => $type,
            'kinds' => CouponTypeModel::KINDS,
            'freeKinds' => $this->freeKinds(),
        ] + $this->formState());
    }

    public function save(): void
    {
        Auth::requireRole('admin');
        Csrf::check();
        $id = (int) ($_POST['id'] ?? 0);
        $type = $id > 0 ? ($this->find($id) ?? abort(404)) : null;

        $v = (new Validator($_POST))
            ->required('name')->minLen('name', 3)->maxLen('name', 60)
            ->maxLen('description', 255);
        if (!$type) {
            $v->required('key_name')->in('key_name', array_keys(CouponTypeModel::KINDS));
        }
        $errors = $v->errors();
        // The kind never changes after creation (coupons already rely on it). One type per kind.
        $key = $type ? $type->getKeyName() : $v->get('key_name');
        if (!$type && !isset($errors['key_name']) && !in_array($key, $this->freeKinds(), true)) {
            $errors['key_name'] = 'A coupon type of this kind already exists.';
        }
        if ($errors) {
            $_SESSION['errors'] = $errors;
            $_SESSION['old'] = array_intersect_key($_POST, array_flip(['key_name', 'name', 'description', 'is_active']));
            $this->redirect($type ? route('admin-coupon-type-form', ['id' => $id]) : route('admin-coupon-type-form'));
        }

        $args = [
            'name' => $v->get('name'),
            'description' => $v->get('description'),
            'active' => isset($_POST['is_active']) ? 1 : 0,
        ];
        if ($type) {
            $stmt = $this->db->prepare('UPDATE coupon_types SET name = :name, description = :description, is_active = :active WHERE id = :id');
            $stmt->execute($args + ['id' => $id]);
            flash('success', 'The coupon type ' . $args['name'] . ' is saved.');
        } else {
            // needs_value comes from the kind, never from the form: free delivery has no value.
            $stmt = $this->db->prepare('INSERT INTO coupon_types (key_name, name, description, needs_value, is_active)
                VALUES (:key_name, :name, :description, :needs_value, :active)');
            $stmt->execute($args + ['key_name' => $key, 'needs_value' => $key === 'free_delivery' ? 0 : 1]);
            flash('success', 'The coupon type ' . $args['name'] . ' is added.');
        }
        $this->redirect(route('admin-coupon-types'));
    }

    public function delete(string $id): void
    {
        Auth::requireRole('admin');
        Csrf::check();
        $type = $this->find((int) $id) ?? abort(404);
        $stmt = $this->db->prepare('SELECT COUNT(*) FROM coupons WHERE type_id = :id');
        $stmt->execute(['id' => $type->getId()]);
        $used = (int) $stmt->fetchColumn();
        if ($used > 0) {
            flash('error', 'The type ' . $type->getName() . ' is used by ' . $used . ' coupon' . ($used > 1 ? 's' : '')
                . ', so it cannot be deleted. Deactivate it instead.');
            $this->redirect(route('admin-coupon-types'));
        }
        $stmt = $this->db->prepare('DELETE FROM coupon_types WHERE id = :id');
        $stmt->execute(['id' => $type->getId()]);
        flash('success', 'The coupon type ' . $type->getName() . ' is deleted.');
        $this->redirect(route('admin-coupon-types'));
    }

    // =================================================================================================

    /** Every type, for the select of the coupon form (active ones first). */
    public function all(): array
    {
        $stmt = $this->db->prepare('SELECT * FROM coupon_types ORDER BY is_active DESC, name');
        $stmt->execute();
        return array_map([CouponTypeModel::class, 'fromArray'], $stmt->fetchAll());
    }

    public function find(int $id): ?CouponTypeModel
    {
        $stmt = $this->db->prepare('SELECT * FROM coupon_types WHERE id = :id');
        $stmt->execute(['id' => $id]);
        $row = $stmt->fetch();
        return $row ? CouponTypeModel::fromArray($row) : null;
    }

    /** The kinds that have no type yet (a new type can only use one of them). */
    private function freeKinds(): array
    {
        $stmt = $this->db->prepare('SELECT key_name FROM coupon_types');
        $stmt->execute();
        return array_values(array_diff(array_keys(CouponTypeModel::KINDS), $stmt->fetchAll(\PDO::FETCH_COLUMN)));
    }

    private function formState(): array
    {
        $errors = $_SESSION['errors'] ?? [];
        $old = $_SESSION['old'] ?? [];
        unset($_SESSION['errors'], $_SESSION['old']);
        return ['errors' => $errors, 'old' => $old];
    }
}
