<?php

/**
 * S2 Mili - Administrator: the plan tiers (shop_plans). List, add, edit, delete.
 * Rules: name unique, price >= 0, max_products >= 1, a plan still used by a shop cannot be deleted
 * (deactivate it instead). The other limits are the sizes of the columns (VARCHAR(40), DECIMAL(10,3), INT).
 */
final class AdminPlanController extends Controller
{
    protected string $layout = 'admin';
    private \PDO $db;

    /** Largest value of DECIMAL(10,3). */
    public const MAX_PRICE = 9999999.999;

    private const FIELDS = ['name', 'monthly_price', 'rank_weight', 'max_products', 'is_active'];

    public function __construct()
    {
        $this->db = \Database::getConnexion();
    }

    /** GET /admin/plans: every plan, highest weight first. */
    public function index(): void
    {
        Auth::requireRole('admin');
        $stmt = $this->db->prepare('SELECT * FROM shop_plans ORDER BY rank_weight DESC, name ASC');
        $stmt->execute();
        $this->render('admin/plans', [
            'title' => 'Plan tiers | SKYDROP Admin',
            'plans' => array_map([ShopPlanModel::class, 'fromArray'], $stmt->fetchAll()),
        ]);
    }

    /** GET /admin/plans/form: add (no id) or edit (?id=) a plan. */
    public function form(): void
    {
        Auth::requireRole('admin');
        $plan = isset($_GET['id']) ? ($this->find((int) $_GET['id']) ?? abort(404)) : null;
        $errors = $_SESSION['errors'] ?? [];
        $old = $_SESSION['old'] ?? [];
        unset($_SESSION['errors'], $_SESSION['old']);
        $this->render('admin/plan-form', [
            'title' => ($plan ? 'Edit ' . $plan->getName() : 'Add a plan') . ' | SKYDROP Admin',
            'plan' => $plan,
            'maxPrice' => self::MAX_PRICE,
            'errors' => $errors,
            'old' => $old,
        ]);
    }

    /** POST /admin/plans/form: validate, then INSERT or UPDATE. */
    public function save(): void
    {
        Auth::requireRole('admin');
        Csrf::check();
        $id = (int) ($_POST['id'] ?? 0);
        $plan = $id > 0 ? ($this->find($id) ?? abort(404)) : null;

        // The order of the rules is the order of forms.js, so both give the same first message.
        // maxLen 9 on the whole numbers keeps them inside the INT column.
        $v = (new Validator($_POST))
            ->required('name')->maxLen('name', 40)
            ->required('monthly_price')->decimal('monthly_price', 0, self::MAX_PRICE)
            ->required('rank_weight')->maxLen('rank_weight', 9)->int('rank_weight')
            ->required('max_products')->maxLen('max_products', 9)->int('max_products', 1);
        $errors = $v->errors();
        // forms.js adds the range to "Number out of range" when there is a min and a max: same message here.
        if (($errors['monthly_price'] ?? '') === 'Number out of range.') {
            $errors['monthly_price'] = 'Number out of range (0 to ' . self::MAX_PRICE . ').';
        }
        if (!isset($errors['name']) && $this->nameTaken($v->get('name'), $id)) {
            $errors['name'] = 'Another plan already uses this name.';
        }
        if ($errors) {
            $_SESSION['errors'] = $errors;
            $_SESSION['old'] = array_intersect_key($_POST, array_flip(self::FIELDS));
            $this->redirect($plan ? route('admin-plan-form', ['id' => $id]) : route('admin-plan-form'));
        }

        $args = [
            'name' => $v->get('name'),
            'price' => $v->get('monthly_price'),
            'weight' => (int) $v->get('rank_weight'),
            'max' => (int) $v->get('max_products'),
            'active' => isset($_POST['is_active']) ? 1 : 0,
        ];
        if ($plan) {
            $stmt = $this->db->prepare('UPDATE shop_plans SET name = :name, monthly_price = :price, rank_weight = :weight,
                max_products = :max, is_active = :active WHERE id = :id');
            $stmt->execute($args + ['id' => $id]);
            flash('success', 'The plan ' . $args['name'] . ' is saved.');
        } else {
            $stmt = $this->db->prepare('INSERT INTO shop_plans (name, monthly_price, rank_weight, max_products, is_active)
                VALUES (:name, :price, :weight, :max, :active)');
            $stmt->execute($args);
            flash('success', 'The plan ' . $args['name'] . ' is added.');
        }
        $this->redirect(route('admin-plans'));
    }

    /** POST /admin/plans/{id}/delete: refused while a shop still uses the plan. */
    public function delete(string $id): void
    {
        Auth::requireRole('admin');
        Csrf::check();
        $plan = $this->find((int) $id) ?? abort(404);
        $stmt = $this->db->prepare('SELECT COUNT(*) FROM shops WHERE plan_id = :id');
        $stmt->execute(['id' => $plan->getId()]);
        $used = (int) $stmt->fetchColumn();
        if ($used > 0) {
            flash('error', 'The plan ' . $plan->getName() . ' is used by ' . $used . ' shop' . ($used > 1 ? 's' : '')
                . ', so it cannot be deleted. Deactivate it instead (Edit, then untick "Active").');
            $this->redirect(route('admin-plans'));
        }
        $stmt = $this->db->prepare('DELETE FROM shop_plans WHERE id = :id');
        $stmt->execute(['id' => $plan->getId()]);
        flash('success', 'The plan ' . $plan->getName() . ' is deleted.');
        $this->redirect(route('admin-plans'));
    }

    private function find(int $id): ?ShopPlanModel
    {
        $stmt = $this->db->prepare('SELECT * FROM shop_plans WHERE id = :id LIMIT 1');
        $stmt->execute(['id' => $id]);
        $row = $stmt->fetch();
        return $row ? ShopPlanModel::fromArray($row) : null;
    }

    private function nameTaken(string $name, int $exceptId): bool
    {
        $stmt = $this->db->prepare('SELECT id FROM shop_plans WHERE name = :name AND id <> :id LIMIT 1');
        $stmt->execute(['name' => $name, 'id' => $exceptId]);
        return (bool) $stmt->fetch();
    }
}
