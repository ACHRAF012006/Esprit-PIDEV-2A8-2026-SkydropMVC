<?php

/**
 * S2 Mili - Shops in the front office: the list of approved shops (best plan first) and one shop's page.
 * The SQL is here, in prepared statements; ShopModel only holds the data; the views only display.
 * Other parts read a shop with (new ShopController())->findApproved($id) or ->ofOwner($userId).
 */
final class ShopController extends Controller
{
    private \PDO $db;

    /**
     * Columns of a shop with its plan (JOIN shop_plans) and "open now" computed by MySQL from the hours.
     * closes_at is always after opens_at (rule of the owner form), so a simple comparison is enough.
     */
    public const SELECT = 'SELECT s.*, p.name AS plan_name, p.rank_weight,
            (CURTIME() >= s.opens_at AND CURTIME() < s.closes_at) AS open_now
        FROM shops s
        JOIN shop_plans p ON p.id = s.plan_id';

    public function __construct()
    {
        $this->db = \Database::getConnexion();
    }

    // =================================================================================================
    // PAGES
    // =================================================================================================

    /** GET /shops: approved shops, higher plan weight first, then by name; optional search by name (?q=). */
    public function index(): void
    {
        $q = trim(mb_substr((string) ($_GET['q'] ?? ''), 0, 80));
        $this->render('front/shops', [
            'title' => 'Shops | SKYDROP',
            'shops' => $this->findApprovedList($q),
            'q' => $q,
        ]);
    }

    /** GET /shops/{id}: one approved shop, 404 for a pending, rejected or unknown shop. */
    public function show(string $id): void
    {
        $shop = $this->findApproved((int) $id) ?? abort(404, 'This shop does not exist or is not open on SKYDROP yet.');
        $this->render('front/shop', [
            'title' => $shop->getName() . ' | SKYDROP',
            'shop' => $shop,
            'activeMenu' => 'shops',
            'pageStyles' => ['css/shops.css'],
            'pageScripts' => ['js/shops.js'],
        ]);
    }

    // =================================================================================================
    // DATA ACCESS (also used by the other parts: keep these signatures)
    // =================================================================================================

    /** One approved shop with its plan, or null. */
    public function findApproved(int $id): ?ShopModel
    {
        $stmt = $this->db->prepare(self::SELECT . " WHERE s.id = :id AND s.status = 'approved' LIMIT 1");
        $stmt->execute(['id' => $id]);
        $row = $stmt->fetch();
        return $row ? ShopModel::fromArray($row) : null;
    }

    /** The shop of a shop owner, whatever its status, or null when they have none. */
    public function ofOwner(int $userId): ?ShopModel
    {
        $stmt = $this->db->prepare(self::SELECT . ' WHERE s.owner_id = :owner LIMIT 1');
        $stmt->execute(['owner' => $userId]);
        $row = $stmt->fetch();
        return $row ? ShopModel::fromArray($row) : null;
    }

    /** @return ShopModel[] approved shops, ordered by plan weight (higher first) then name. */
    private function findApprovedList(string $search): array
    {
        $sql = self::SELECT . " WHERE s.status = 'approved'";
        $args = [];
        if ($search !== '') {
            $sql .= ' AND s.name LIKE :q';
            $args['q'] = '%' . $search . '%';
        }
        $stmt = $this->db->prepare($sql . ' ORDER BY p.rank_weight DESC, s.name ASC');
        $stmt->execute($args);
        return array_map([ShopModel::class, 'fromArray'], $stmt->fetchAll());
    }
}
