<?php

/**
 * S1 - One coupon type (one row of coupon_types): what a coupon does. Private attributes, getters / setters and
 * fromArray(). No SQL and no business logic here: they are in the controllers.
 */
final class CouponTypeModel
{
    /** The kinds the code understands (coupon_types.key_name) with their labels. */
    public const KINDS = [
        'percent'       => 'Percentage off',
        'fixed'         => 'Fixed amount off',
        'free_delivery' => 'Free delivery',
    ];

    private int $id = 0;
    private string $keyName = 'percent';
    private string $name = '';
    private string $description = '';
    private bool $needsValue = true;
    private bool $isActive = true;
    private string $createdAt = '';
    private int $couponCount = 0; // filled by the list's JOIN on coupons

    public function getId(): int { return $this->id; }
    public function getKeyName(): string { return $this->keyName; }
    public function getName(): string { return $this->name; }
    public function getDescription(): string { return $this->description; }
    public function needsValue(): bool { return $this->needsValue; }
    public function isActive(): bool { return $this->isActive; }
    public function getCreatedAt(): string { return $this->createdAt; }
    public function getCouponCount(): int { return $this->couponCount; }

    public function setId(int $id): void { $this->id = $id; }
    public function setKeyName(string $keyName): void { $this->keyName = $keyName; }
    public function setName(string $name): void { $this->name = $name; }
    public function setDescription(string $description): void { $this->description = $description; }
    public function setNeedsValue(bool $needsValue): void { $this->needsValue = $needsValue; }
    public function setIsActive(bool $isActive): void { $this->isActive = $isActive; }
    public function setCreatedAt(string $createdAt): void { $this->createdAt = $createdAt; }
    public function setCouponCount(int $couponCount): void { $this->couponCount = $couponCount; }

    public static function fromArray(array $row): self
    {
        $type = new self();
        $type->setId((int) ($row['id'] ?? 0));
        $type->setKeyName((string) ($row['key_name'] ?? 'percent'));
        $type->setName((string) ($row['name'] ?? ''));
        $type->setDescription((string) ($row['description'] ?? ''));
        $type->setNeedsValue((bool) ($row['needs_value'] ?? true));
        $type->setIsActive((bool) ($row['is_active'] ?? true));
        $type->setCreatedAt((string) ($row['created_at'] ?? ''));
        $type->setCouponCount((int) ($row['coupon_count'] ?? 0));
        return $type;
    }
}
