<?php

/**
 * S2 Mili - Administrator: the plan tiers (shop_plans). List with the number of shops of each plan
 * (LEFT JOIN shops), add, edit, delete. Rules: name 3-40 and unique, price >= 0, max_products >= 1,
 * a plan still used by a shop cannot be deleted (deactivate it instead).
 */
final class AdminPlanController extends Controller
{
    protected string $layout = 'admin';
    private \PDO $db;

    /** Same pattern and message in the view (data-pattern) and here: at most 7 digits and 3 decimals (DECIMAL(10,3)). */
    public const PRICE_PATTERN = '^[0-9]{1,7}(\.[0-9]{1,3})?$';
    public const PRICE_MESSAGE = 'Write a price such as 29.000 (at most 3 decimals).';

    private const FIELDS = ['name', 'monthly_price', 'rank_weight', 'max_products', 'is_active'];

    public function __construct()
    {
        $this->db = \Database::getConnexion();
    }

    /** GET /admin/plans: every plan, highest weight first, with its number of shops. */
    public function index(): void
    {
        Auth::requireRole('admin');
        $stmt = $this->db->prepare('SELECT p.*, COUNT(s.id) AS shop_count
            FROM shop_plans p
            LEFT JOIN shops s ON s.plan_id = p.id
            GROUP BY p.id
            ORDER BY p.rank_weight DESC, p.name ASC');
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
            'pricePattern' => self::PRICE_PATTERN,
            'priceMessage' => self::PRICE_MESSAGE,
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
        $v = (new Validator($_POST))
            ->required('name')->minLen('name', 3)->maxLen('name', 40)
            ->required('monthly_price')->decimal('monthly_price', 0)
            ->required('rank_weight')->maxLen('rank_weight', 6)->int('rank_weight', 0)
            ->required('max_products')->maxLen('max_products', 6)->int('max_products', 1);
        $errors = $v->errors();
        if (!isset($errors['monthly_price']) && !preg_match('/' . self::PRICE_PATTERN . '/', $v->get('monthly_price'))) {
            $errors['monthly_price'] = self::PRICE_MESSAGE;
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
