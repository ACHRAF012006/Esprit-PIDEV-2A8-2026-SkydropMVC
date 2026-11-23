<?php

/**
 * S1 - One use of a coupon (one row of coupon_uses), plus the coupon's code read through the JOIN on coupons.
 * Private attributes, getters / setters and fromArray(). No SQL and no business logic here.
 */
final class CouponUseModel
{
    private int $id = 0;
    private int $couponId = 0;
    private int $userId = 0;
    private ?int $orderId = null;
    private float $discount = 0.0;
    private string $usedAt = '';
    private string $code = ''; // coupons.code (JOIN)

    public function getId(): int { return $this->id; }
    public function getCouponId(): int { return $this->couponId; }
    public function getUserId(): int { return $this->userId; }
    public function getOrderId(): ?int { return $this->orderId; }
    public function getDiscount(): float { return $this->discount; }
    public function getUsedAt(): string { return $this->usedAt; }
    public function getCode(): string { return $this->code; }

    public function setId(int $id): void { $this->id = $id; }
    public function setCouponId(int $couponId): void { $this->couponId = $couponId; }
    public function setUserId(int $userId): void { $this->userId = $userId; }
    public function setOrderId(?int $orderId): void { $this->orderId = $orderId; }
    public function setDiscount(float $discount): void { $this->discount = $discount; }
    public function setUsedAt(string $usedAt): void { $this->usedAt = $usedAt; }
    public function setCode(string $code): void { $this->code = $code; }

    public static function fromArray(array $row): self
    {
        $use = new self();
        $use->setId((int) ($row['id'] ?? 0));
        $use->setCouponId((int) ($row['coupon_id'] ?? 0));
        $use->setUserId((int) ($row['user_id'] ?? 0));
        $use->setOrderId(isset($row['order_id']) ? (int) $row['order_id'] : null);
        $use->setDiscount((float) ($row['discount'] ?? 0));
        $use->setUsedAt((string) ($row['used_at'] ?? ''));
        $use->setCode((string) ($row['code'] ?? ''));
        return $use;
    }
}
