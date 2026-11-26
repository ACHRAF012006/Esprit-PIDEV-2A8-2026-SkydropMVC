<?php

/**
 * S2 Mili - One plan tier (row of shop_plans): private attributes, getters / setters and fromArray().
 * No SQL and no logic here: they are in AdminPlanController and OwnerShopController.
 */
final class ShopPlanModel
{
    private int $id = 0;
    private string $name = '';
    private float $monthlyPrice = 0.0;
    private int $rankWeight = 1;
    private int $maxProducts = 20;
    private bool $isActive = true;
    private string $createdAt = '';

    public function getId(): int { return $this->id; }
    public function getName(): string { return $this->name; }
    public function getMonthlyPrice(): float { return $this->monthlyPrice; }
    public function getRankWeight(): int { return $this->rankWeight; }
    public function getMaxProducts(): int { return $this->maxProducts; }
    public function isActive(): bool { return $this->isActive; }
    public function getCreatedAt(): string { return $this->createdAt; }

    public function setId(int $id): void { $this->id = $id; }
    public function setName(string $name): void { $this->name = $name; }
    public function setMonthlyPrice(float $monthlyPrice): void { $this->monthlyPrice = $monthlyPrice; }
    public function setRankWeight(int $rankWeight): void { $this->rankWeight = $rankWeight; }
    public function setMaxProducts(int $maxProducts): void { $this->maxProducts = $maxProducts; }
    public function setActive(bool $isActive): void { $this->isActive = $isActive; }
    public function setCreatedAt(string $createdAt): void { $this->createdAt = $createdAt; }

    /** A row of shop_plans (column names) -> ShopPlanModel. */
    public static function fromArray(array $row): self
    {
        $plan = new self();
        $plan->setId((int) ($row['id'] ?? 0));
        $plan->setName((string) ($row['name'] ?? ''));
        $plan->setMonthlyPrice((float) ($row['monthly_price'] ?? 0));
        $plan->setRankWeight((int) ($row['rank_weight'] ?? 1));
        $plan->setMaxProducts((int) ($row['max_products'] ?? 20));
        $plan->setActive((bool) ($row['is_active'] ?? true));
        $plan->setCreatedAt((string) ($row['created_at'] ?? ''));
        return $plan;
    }
}
