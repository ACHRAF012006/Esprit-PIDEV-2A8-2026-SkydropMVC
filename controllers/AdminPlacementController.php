<?php

/**
 * S5 Karim - Administrator: CRUD of the ad placements (ad_placements).
 * List with the number of ads of each placement (LEFT JOIN ads), add / edit, delete. A placement still used by an ad
 * cannot be deleted: the administrator deactivates it instead (its ads are then no longer shown).
 * Auth::requireRole('admin') is the first line of every action; every query is a prepared statement.
 */
final class AdminPlacementController extends Controller
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
        $this->render('admin/placements', [
            'title' => 'Ad placements | SKYDROP Admin',
            'placements' => $this->findAll(),
        ]);
    }

    /** Add (no id) or edit (?id=) a placement. */
    public function form(): void
    {
        Auth::requireRole('admin');
        $placement = null;
        if (isset($_GET['id'])) {
            $placement = $this->findById((int) $_GET['id']) ?? abort(404);
        }
        $this->render('admin/placement-form', [
            'title' => ($placement ? 'Edit ' . $placement->getName() : 'Add a placement') . ' | SKYDROP Admin',
            'placement' => $placement,
        ] + $this->formState());
    }

    public function save(): void
    {
        Auth::requireRole('admin');
        Csrf::check();
        $id = (int) ($_POST['id'] ?? 0);
        $existing = $id > 0 ? ($this->findById($id) ?? abort(404)) : null;

        $v = (new Validator($_POST))
            ->required('name')->minLen('name', 3)->maxLen('name', 60)
            ->maxLen('description', 255)
            ->required('price_per_day')->decimal('price_per_day')
            ->required('max_ads')->int('max_ads');
        $errors = $v->errors();
        // The ranges are checked here so that the message is the same as the one of forms.js (with the two limits).
        $price = (float) $v->get('price_per_day');
        if (!isset($errors['price_per_day']) && !preg_match('/^\d+(\.\d+)?$/', $v->get('price_per_day'))) {
            $errors['price_per_day'] = 'Must be a number.'; // same format as forms.js (is_numeric() would accept "1e3")
        } elseif (!isset($errors['price_per_day']) && ($price < 0.001 || $price > 9999.999)) {
            $errors['price_per_day'] = 'Number out of range (0.001 to 9999.999).';
        }
        $maxAds = (int) $v->get('max_ads');
        if (!isset($errors['max_ads']) && ($maxAds < 1 || $maxAds > 10)) {
            $errors['max_ads'] = 'Number out of range (1 to 10).';
        }
        if (!isset($errors['name']) && $this->nameTaken($v->get('name'), $id)) {
            $errors['name'] = 'Another placement already uses this name.';
        }
        if ($errors) {
            $_SESSION['errors'] = $errors;
            $_SESSION['old'] = array_intersect_key($_POST, array_flip(['name', 'description', 'price_per_day', 'max_ads', 'is_active']));
            $this->redirect($existing ? route('admin-placement-form', ['id' => $id]) : route('admin-placement-form'));
        }

        $args = [
            'name' => $v->get('name'),
            'description' => $v->get('description'),
            'price' => round($price, 3),
            'max_ads' => $maxAds,
            'active' => isset($_POST['is_active']) ? 1 : 0,
        ];
        if ($existing) {
            $stmt = $this->db->prepare('UPDATE ad_placements SET name = :name, description = :description, price_per_day = :price,
                max_ads = :max_ads, is_active = :active WHERE id = :id');
            $stmt->execute($args + ['id' => $id]);
        } else {
            $stmt = $this->db->prepare('INSERT INTO ad_placements (name, description, price_per_day, max_ads, is_active)
                VALUES (:name, :description, :price, :max_ads, :active)');
            $stmt->execute($args);
        }
        flash('success', 'The placement ' . $args['name'] . ' is saved.');
        $this->redirect(route('admin-placements'));
    }

    /** Rule: a placement still used by an ad cannot be deleted (deactivate it instead). */
    public function delete(string $id): void
    {
        Auth::requireRole('admin');
        Csrf::check();
        $placement = $this->findById((int) $id) ?? abort(404);
        $stmt = $this->db->prepare('SELECT COUNT(*) FROM ads WHERE placement_id = :id');
        $stmt->execute(['id' => $placement->getId()]);
        $used = (int) $stmt->fetchColumn();
        if ($used > 0) {
            flash('error', $placement->getName() . ' is still used by ' . $used . ' ad' . ($used === 1 ? '' : 's')
                . ', so it cannot be deleted. Deactivate it instead: Edit, then untick Active.');
        } else {
            $stmt = $this->db->prepare('DELETE FROM ad_placements WHERE id = :id');
            $stmt->execute(['id' => $placement->getId()]);
            flash('success', 'The placement ' . $placement->getName() . ' is deleted.');
        }
        $this->redirect(route('admin-placements'));
    }

    // =================================================================================================
    // DATA ACCESS used by this page and by the ad pages
    // =================================================================================================

    /** @return AdPlacementModel[] every placement with its number of ads (LEFT JOIN: a placement without ads counts 0). */
    public function findAll(): array
    {
        $stmt = $this->db->query('SELECT p.*, COUNT(a.id) AS ads_count FROM ad_placements p
            LEFT JOIN ads a ON a.placement_id = p.id GROUP BY p.id ORDER BY p.name');
        return array_map([AdPlacementModel::class, 'fromArray'], $stmt->fetchAll());
    }

    /** @return AdPlacementModel[] the active placements, for the list of the ad form. */
    public function active(): array
    {
        $stmt = $this->db->query('SELECT * FROM ad_placements WHERE is_active = 1 ORDER BY name');
        return array_map([AdPlacementModel::class, 'fromArray'], $stmt->fetchAll());
    }

    public function findById(int $id): ?AdPlacementModel
    {
        $stmt = $this->db->prepare('SELECT * FROM ad_placements WHERE id = :id');
        $stmt->execute(['id' => $id]);
        $row = $stmt->fetch();
        return $row ? AdPlacementModel::fromArray($row) : null;
    }

    private function nameTaken(string $name, int $exceptId): bool
    {
        $stmt = $this->db->prepare('SELECT id FROM ad_placements WHERE name = :name AND id <> :id LIMIT 1');
        $stmt->execute(['name' => $name, 'id' => $exceptId]);
        return (bool) $stmt->fetch();
    }

    /** Errors and typed values of the last failed POST (kept in the session across the redirect), read once. */
    private function formState(): array
    {
        $errors = $_SESSION['errors'] ?? [];
        $old = $_SESSION['old'] ?? [];
        unset($_SESSION['errors'], $_SESSION['old']);
        return ['errors' => $errors, 'old' => $old];
    }
}
