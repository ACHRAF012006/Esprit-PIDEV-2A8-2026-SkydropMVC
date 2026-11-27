<?php

/**
 * S5 Karim - Ads in the front office. There is no page of its own: the slot "home-top" (config/routes.php) shows
 * views/front/ads-home.php inside the home page, and that view asks this controller for the ads to show.
 * An ad is shown only when it is approved AND today is between its two dates AND its placement is active.
 * The SELECT with the two JOINs is also used by OwnerAdController and AdminAdController.
 */
final class AdController extends Controller
{
    /** Id of the demo placement "Home banner" (database/skydrop.sql). */
    public const HOME_BANNER = 1;

    /**
     * An ad with the name of its placement (JOIN ad_placements) and of its advertiser (JOIN users), plus two values
     * computed by MySQL: days (both dates included) and is_running (approved and today between the dates).
     */
    public const SELECT = "SELECT a.*, p.name AS placement_name, u.full_name AS advertiser_name,
            DATEDIFF(a.ends_at, a.starts_at) + 1 AS days,
            (a.status = 'approved' AND CURDATE() BETWEEN a.starts_at AND a.ends_at) AS is_running
        FROM ads a
        JOIN ad_placements p ON p.id = a.placement_id
        JOIN users u ON u.id = a.advertiser_id";

    private \PDO $db;

    public function __construct()
    {
        $this->db = \Database::getConnexion();
    }

    /** @return AdModel[] the approved ads of a placement that run today, oldest start first. */
    public function running(int $placementId): array
    {
        $stmt = $this->db->prepare(self::SELECT . " WHERE a.placement_id = :placement AND a.status = 'approved'
            AND p.is_active = 1 AND CURDATE() BETWEEN a.starts_at AND a.ends_at
            ORDER BY a.starts_at, a.id LIMIT 10");
        $stmt->execute(['placement' => $placementId]);
        return array_map([AdModel::class, 'fromArray'], $stmt->fetchAll());
    }

    /** One ad (any advertiser): for the administrator pages. */
    public function findById(int $id): ?AdModel
    {
        $stmt = $this->db->prepare(self::SELECT . ' WHERE a.id = :id');
        $stmt->execute(['id' => $id]);
        $row = $stmt->fetch();
        return $row ? AdModel::fromArray($row) : null;
    }
}
