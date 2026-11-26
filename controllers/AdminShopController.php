<?php

/**
 * S2 Mili - Administrator: the shops. List with a status filter (JOIN shop_plans and users), one shop in detail,
 * approve, reject (a review note is required: the owner reads it on his page) and delete.
 * requireRole('admin') is the first line of every action; every POST checks the CSRF token first.
 */
final class AdminShopController extends Controller
{
    protected string $layout = 'admin';
    private \PDO $db;

    /** A shop with its plan (JOIN shop_plans) and the username of its owner (JOIN users). */
    private const SELECT = 'SELECT s.*, p.name AS plan_name, p.rank_weight, u.username AS owner_username,
            (CURTIME() >= s.opens_at AND CURTIME() < s.closes_at) AS open_now
        FROM shops s
        JOIN shop_plans p ON p.id = s.plan_id
        JOIN users u ON u.id = s.owner_id';

    public function __construct()
    {
        $this->db = \Database::getConnexion();
    }

    // =================================================================================================
    // PAGES
    // =================================================================================================

    /** GET /admin/shops: every shop, optionally one status (?status=pending|approved|rejected), newest first. */
    public function index(): void
    {
        Auth::requireRole('admin');
        $status = (string) ($_GET['status'] ?? '');
        $status = isset(ShopModel::STATUSES[$status]) ? $status : '';
        $sql = self::SELECT;
        $args = [];
        if ($status !== '') {
            $sql .= ' WHERE s.status = :status';
            $args['status'] = $status;
        }
        $stmt = $this->db->prepare($sql . ' ORDER BY s.created_at DESC, s.id DESC');
        $stmt->execute($args);
        $this->render('admin/shops', [
            'title' => 'Shops | SKYDROP Admin',
            'shops' => array_map([ShopModel::class, 'fromArray'], $stmt->fetchAll()),
            'status' => $status,
            'statuses' => ShopModel::STATUSES,
            'counts' => $this->countByStatus(),
        ]);
    }

    /** GET /admin/shops/{id}: one shop, its owner and plan, and the approve / reject / delete forms. */
    public function show(string $id): void
    {
        Auth::requireRole('admin');
        $shop = $this->find((int) $id) ?? abort(404);
        $errors = $_SESSION['errors'] ?? [];
        $old = $_SESSION['old'] ?? [];
        unset($_SESSION['errors'], $_SESSION['old']);
        $this->render('admin/shop', [
            'title' => $shop->getName() . ' | SKYDROP Admin',
            'shop' => $shop,
            'errors' => $errors,
            'old' => $old,
        ]);
    }

    // =================================================================================================
    // ACTIONS
    // =================================================================================================

    public function approve(string $id): void
    {
        Auth::requireRole('admin');
        Csrf::check();
        $shop = $this->find((int) $id) ?? abort(404);
        $stmt = $this->db->prepare("UPDATE shops SET status = 'approved', review_note = NULL WHERE id = :id");
        $stmt->execute(['id' => $shop->getId()]);
        flash('success', $shop->getName() . ' is approved: it appears in the shop list now.');
        $this->redirect(route('admin-shop', ['id' => $shop->getId()]));
    }

    /** Rejecting needs a review note (5 to 255 characters), shown to the owner on his shop page. */
    public function reject(string $id): void
    {
        Auth::requireRole('admin');
        Csrf::check();
        $shop = $this->find((int) $id) ?? abort(404);
        $v = (new Validator($_POST))->required('review_note')->minLen('review_note', 5)->maxLen('review_note', 255);
        if ($v->fails()) {
            $_SESSION['errors'] = $v->errors();
            $_SESSION['old'] = ['review_note' => (string) ($_POST['review_note'] ?? '')];
            $this->redirect(route('admin-shop', ['id' => $shop->getId()]));
        }
        $stmt = $this->db->prepare("UPDATE shops SET status = 'rejected', review_note = :note WHERE id = :id");
        $stmt->execute(['note' => $v->get('review_note'), 'id' => $shop->getId()]);
        flash('success', $shop->getName() . ' is rejected. Its owner sees your note on his shop page.');
        $this->redirect(route('admin-shop', ['id' => $shop->getId()]));
    }

    /** A shop still used by other rows (products, orders... foreign key error 1451) cannot be deleted: reject it instead. */
    public function delete(string $id): void
    {
        Auth::requireRole('admin');
        Csrf::check();
        $shop = $this->find((int) $id) ?? abort(404);
        try {
            $stmt = $this->db->prepare('DELETE FROM shops WHERE id = :id');
            $stmt->execute(['id' => $shop->getId()]);
        } catch (\PDOException $ex) {
            if ((int) ($ex->errorInfo[1] ?? 0) !== 1451) {
                throw $ex;
            }
            flash('error', $shop->getName() . ' is still used by other records (products, orders...), so it cannot be deleted. Reject it instead.');
            $this->redirect(route('admin-shop', ['id' => $shop->getId()]));
        }
        flash('success', $shop->getName() . ' is deleted.');
        $this->redirect(route('admin-shops'));
    }

    // =================================================================================================
    // DATA ACCESS (also used by the admin overview tile views/admin/shop-approvals.php)
    // =================================================================================================

    /** status => number of shops (every status, 0 when none). */
    public function countByStatus(): array
    {
        $out = array_fill_keys(array_keys(ShopModel::STATUSES), 0);
        $stmt = $this->db->prepare('SELECT status, COUNT(*) AS n FROM shops GROUP BY status');
        $stmt->execute();
        foreach ($stmt->fetchAll() as $row) {
            $out[$row['status']] = (int) $row['n'];
        }
        return $out;
    }

    private function find(int $id): ?ShopModel
    {
        $stmt = $this->db->prepare(self::SELECT . ' WHERE s.id = :id LIMIT 1');
        $stmt->execute(['id' => $id]);
        $row = $stmt->fetch();
        return $row ? ShopModel::fromArray($row) : null;
    }
}
