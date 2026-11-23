<?php

/**
 * S1 - Coupons in the front office: the Offers page (coupons valid today, JOIN coupon_types), the "My coupons" block
 * of the profile page, and the two public methods the order part calls at checkout:
 *   check(code, user, subtotal)  -> may this customer use this code on this subtotal, and for how much?
 *   recordUse(code, user, order, discount) -> one row in coupon_uses once the order is placed.
 * Every query is a prepared statement with named parameters.
 */
final class CouponController extends Controller
{
    private \PDO $db;

    public function __construct()
    {
        $this->db = \Database::getConnexion();
    }

    /** GET /offers: the coupons a customer can use today. Open to everybody; a signed-in user also sees their own uses left. */
    public function offers(): void
    {
        $stmt = $this->db->prepare('SELECT c.*, t.name AS type_name, t.key_name AS type_key, COUNT(u.id) AS uses_count
            FROM coupons c
            JOIN coupon_types t ON t.id = c.type_id
            LEFT JOIN coupon_uses u ON u.coupon_id = c.id
            WHERE c.is_active = 1 AND t.is_active = 1 AND c.starts_at <= CURDATE() AND c.ends_at >= CURDATE()
            GROUP BY c.id
            HAVING c.max_uses IS NULL OR COUNT(u.id) < c.max_uses
            ORDER BY c.ends_at, c.code');
        $stmt->execute();
        $coupons = array_map([CouponModel::class, 'fromArray'], $stmt->fetchAll());

        $mine = [];
        if (Auth::check()) {
            $stmt = $this->db->prepare('SELECT coupon_id, COUNT(*) AS n FROM coupon_uses WHERE user_id = :me GROUP BY coupon_id');
            $stmt->execute(['me' => Auth::id()]);
            $mine = array_map('intval', $stmt->fetchAll(\PDO::FETCH_KEY_PAIR));
        }
        $this->render('front/offers', [
            'title' => 'Offers | SKYDROP',
            'coupons' => $coupons,
            'describe' => array_combine(
                array_map(static fn (CouponModel $c): int => $c->getId(), $coupons),
                array_map([self::class, 'describe'], $coupons)
            ) ?: [],
            'myUses' => $mine,
            'signedIn' => Auth::check(),
        ]);
    }

    /**
     * May $userId use $code on an order of $subtotal TND? Returns ['ok' => bool, 'discount' => float, 'message' => string,
     * 'free_delivery' => bool]. The message says why a code is refused, or what it gives. The discount never exceeds
     * the subtotal. Called by the order part at checkout (inside class_exists).
     */
    public function check(string $code, int $userId, float $subtotal): array
    {
        $refuse = static fn (string $message): array => ['ok' => false, 'discount' => 0.0, 'message' => $message, 'free_delivery' => false];
        $code = strtoupper(trim($code));
        if (!preg_match('/^[A-Z0-9]{3,20}$/', $code)) {
            return $refuse('This coupon code does not exist.');
        }
        $stmt = $this->db->prepare('SELECT c.*, t.name AS type_name, t.key_name AS type_key, t.is_active AS type_active,
                COUNT(u.id) AS uses_count, SUM(u.user_id = :me) AS my_uses
            FROM coupons c
            JOIN coupon_types t ON t.id = c.type_id
            LEFT JOIN coupon_uses u ON u.coupon_id = c.id
            WHERE c.code = :code
            GROUP BY c.id');
        $stmt->execute(['code' => $code, 'me' => $userId]);
        $row = $stmt->fetch();
        if (!$row) {
            return $refuse('This coupon code does not exist.');
        }
        $coupon = CouponModel::fromArray($row);
        $today = date('Y-m-d');
        if (!$coupon->isActive() || !(int) $row['type_active']) {
            return $refuse('This coupon is not active.');
        }
        if ($coupon->getStartsAt() > $today) {
            return $refuse('This coupon starts on ' . $coupon->getStartsAt() . '.');
        }
        if ($coupon->getEndsAt() < $today) {
            return $refuse('This coupon expired on ' . $coupon->getEndsAt() . '.');
        }
        if ($coupon->getMaxUses() !== null && $coupon->getUsesCount() >= $coupon->getMaxUses()) {
            return $refuse('This coupon has been used the maximum number of times.');
        }
        $myUses = (int) $row['my_uses'];
        if ($coupon->getPerUserLimit() !== null && $myUses >= $coupon->getPerUserLimit()) {
            return $refuse('You have already used this coupon ' . $myUses . ' time' . ($myUses > 1 ? 's' : '')
                . ' (limit: ' . $coupon->getPerUserLimit() . ').');
        }
        if ($subtotal < $coupon->getMinOrder()) {
            return $refuse('This coupon needs an order of at least ' . money($coupon->getMinOrder()) . '.');
        }

        $discount = match ($coupon->getTypeKey()) {
            'percent' => round($subtotal * (float) $coupon->getValue() / 100, 3),
            'fixed' => (float) $coupon->getValue(),
            default => 0.0,
        };
        $discount = min($discount, max(0.0, $subtotal)); // never more than the subtotal
        $free = $coupon->getTypeKey() === 'free_delivery';
        return [
            'ok' => true,
            'discount' => $discount,
            'message' => $free ? 'Coupon ' . $code . ': free delivery.' : 'Coupon ' . $code . ': ' . money($discount) . ' off.',
            'free_delivery' => $free,
        ];
    }

    /** One row in coupon_uses once an order with this code is placed. An unknown code is ignored. */
    public function recordUse(string $code, int $userId, ?int $orderId, float $discount): void
    {
        $stmt = $this->db->prepare('INSERT INTO coupon_uses (coupon_id, user_id, order_id, discount)
            SELECT id, :user_id, :order_id, :discount FROM coupons WHERE code = :code');
        $stmt->bindValue('user_id', $userId, \PDO::PARAM_INT);
        $stmt->bindValue('order_id', $orderId, $orderId === null ? \PDO::PARAM_NULL : \PDO::PARAM_INT);
        $stmt->bindValue('discount', number_format(max(0.0, $discount), 3, '.', ''));
        $stmt->bindValue('code', strtoupper(trim($code)));
        $stmt->execute();
    }

    /** Data of the "My coupons" block of the profile page (slot "profile"): my uses with their code, and what I saved. */
    public function profileBlock(int $userId): array
    {
        $stmt = $this->db->prepare('SELECT u.*, c.code FROM coupon_uses u JOIN coupons c ON c.id = u.coupon_id
            WHERE u.user_id = :me ORDER BY u.used_at DESC LIMIT 10');
        $stmt->execute(['me' => $userId]);
        $uses = array_map([CouponUseModel::class, 'fromArray'], $stmt->fetchAll());
        $stmt = $this->db->prepare('SELECT COALESCE(SUM(discount), 0) FROM coupon_uses WHERE user_id = :me');
        $stmt->execute(['me' => $userId]);
        return ['uses' => $uses, 'saved' => (float) $stmt->fetchColumn()];
    }

    /** What a coupon gives, in plain words, for the Offers page. */
    public static function describe(CouponModel $c): string
    {
        $text = match ($c->getTypeKey()) {
            'percent' => rtrim(rtrim(number_format((float) $c->getValue(), 3, '.', ''), '0'), '.') . ' % off your order',
            'fixed' => money((float) $c->getValue()) . ' off your order',
            default => 'Free delivery on your order',
        };
        return $text . ($c->getMinOrder() > 0 ? ' from ' . money($c->getMinOrder()) : '') . '.';
    }
}
