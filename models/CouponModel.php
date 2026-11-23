<?php

/**
 * S1 - One coupon (one row of coupons), plus the columns its lists read through the JOINs: the type's name and kind
 * (coupon_types) and the number of uses (coupon_uses). Private attributes, getters / setters and fromArray().
 * No SQL and no business logic here: they are in the controllers.
 */
final class CouponModel
{
    private int $id = 0;
    private string $code = '';
    private int $typeId = 0;
    private ?float $value = null;
    private float $minOrder = 0.0;
    private string $startsAt = '';
    private string $endsAt = '';
    private ?int $maxUses = null;
    private ?int $perUserLimit = 1;
    private bool $isActive = true;
    private ?int $createdBy = null;
    private string $createdAt = '';
    private string $typeName = '';   // coupon_types.name (JOIN)
    private string $typeKey = '';    // coupon_types.key_name (JOIN)
    private int $usesCount = 0;      // COUNT(coupon_uses.id) (JOIN)

    public function getId(): int { return $this->id; }
    public function getCode(): string { return $this->code; }
    public function getTypeId(): int { return $this->typeId; }
    public function getValue(): ?float { return $this->value; }
    public function getMinOrder(): float { return $this->minOrder; }
    public function getStartsAt(): string { return $this->startsAt; }
    public function getEndsAt(): string { return $this->endsAt; }
    public function getMaxUses(): ?int { return $this->maxUses; }
    public function getPerUserLimit(): ?int { return $this->perUserLimit; }
    public function isActive(): bool { return $this->isActive; }
    public function getCreatedBy(): ?int { return $this->createdBy; }
    public function getCreatedAt(): string { return $this->createdAt; }
    public function getTypeName(): string { return $this->typeName; }
    public function getTypeKey(): string { return $this->typeKey; }
    public function getUsesCount(): int { return $this->usesCount; }

    public function setId(int $id): void { $this->id = $id; }
    public function setCode(string $code): void { $this->code = $code; }
    public function setTypeId(int $typeId): void { $this->typeId = $typeId; }
    public function setValue(?float $value): void { $this->value = $value; }
    public function setMinOrder(float $minOrder): void { $this->minOrder = $minOrder; }
    public function setStartsAt(string $startsAt): void { $this->startsAt = $startsAt; }
    public function setEndsAt(string $endsAt): void { $this->endsAt = $endsAt; }
    public function setMaxUses(?int $maxUses): void { $this->maxUses = $maxUses; }
    public function setPerUserLimit(?int $perUserLimit): void { $this->perUserLimit = $perUserLimit; }
    public function setIsActive(bool $isActive): void { $this->isActive = $isActive; }
    public function setCreatedBy(?int $createdBy): void { $this->createdBy = $createdBy; }
    public function setCreatedAt(string $createdAt): void { $this->createdAt = $createdAt; }
    public function setTypeName(string $typeName): void { $this->typeName = $typeName; }
    public function setTypeKey(string $typeKey): void { $this->typeKey = $typeKey; }
    public function setUsesCount(int $usesCount): void { $this->usesCount = $usesCount; }

    public static function fromArray(array $row): self
    {
        $coupon = new self();
        $coupon->setId((int) ($row['id'] ?? 0));
        $coupon->setCode((string) ($row['code'] ?? ''));
        $coupon->setTypeId((int) ($row['type_id'] ?? 0));
        $coupon->setValue(isset($row['value']) ? (float) $row['value'] : null);
        $coupon->setMinOrder((float) ($row['min_order'] ?? 0));
        $coupon->setStartsAt((string) ($row['starts_at'] ?? ''));
        $coupon->setEndsAt((string) ($row['ends_at'] ?? ''));
        $coupon->setMaxUses(isset($row['max_uses']) ? (int) $row['max_uses'] : null);
        $coupon->setPerUserLimit(isset($row['per_user_limit']) ? (int) $row['per_user_limit'] : null);
        $coupon->setIsActive((bool) ($row['is_active'] ?? true));
        $coupon->setCreatedBy(isset($row['created_by']) ? (int) $row['created_by'] : null);
        $coupon->setCreatedAt((string) ($row['created_at'] ?? ''));
        $coupon->setTypeName((string) ($row['type_name'] ?? ''));
        $coupon->setTypeKey((string) ($row['type_key'] ?? ''));
        $coupon->setUsesCount((int) ($row['uses_count'] ?? 0));
        return $coupon;
    }
}
