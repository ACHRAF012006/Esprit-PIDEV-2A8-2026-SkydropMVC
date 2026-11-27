<?php

/**
 * S5 Karim - One placement (a row of ad_placements): a place of the site where text ads are shown.
 * Only private attributes, getters / setters and fromArray(). No SQL and no logic: they are in the controllers.
 */
final class AdPlacementModel
{
    private int $id = 0;
    private string $name = '';
    private string $description = '';
    private float $pricePerDay = 0.0;
    private int $maxAds = 1;
    private bool $isActive = true;
    private string $createdAt = '';
    /** Number of ads using this placement (COUNT of a LEFT JOIN on ads), 0 when the query does not compute it. */
    private int $adsCount = 0;

    public function getId(): int { return $this->id; }
    public function getName(): string { return $this->name; }
    public function getDescription(): string { return $this->description; }
    public function getPricePerDay(): float { return $this->pricePerDay; }
    public function getMaxAds(): int { return $this->maxAds; }
    public function isActive(): bool { return $this->isActive; }
    public function getCreatedAt(): string { return $this->createdAt; }
    public function getAdsCount(): int { return $this->adsCount; }

    public function setId(int $id): void { $this->id = $id; }
    public function setName(string $name): void { $this->name = $name; }
    public function setDescription(string $description): void { $this->description = $description; }
    public function setPricePerDay(float $pricePerDay): void { $this->pricePerDay = $pricePerDay; }
    public function setMaxAds(int $maxAds): void { $this->maxAds = $maxAds; }
    public function setActive(bool $isActive): void { $this->isActive = $isActive; }
    public function setCreatedAt(string $createdAt): void { $this->createdAt = $createdAt; }
    public function setAdsCount(int $adsCount): void { $this->adsCount = $adsCount; }

    /** A row of ad_placements (column names) -> AdPlacementModel. */
    public static function fromArray(array $row): self
    {
        $placement = new self();
        $placement->setId((int) ($row['id'] ?? 0));
        $placement->setName((string) ($row['name'] ?? ''));
        $placement->setDescription((string) ($row['description'] ?? ''));
        $placement->setPricePerDay((float) ($row['price_per_day'] ?? 0));
        $placement->setMaxAds((int) ($row['max_ads'] ?? 1));
        $placement->setActive((bool) ($row['is_active'] ?? true));
        $placement->setCreatedAt((string) ($row['created_at'] ?? ''));
        $placement->setAdsCount((int) ($row['ads_count'] ?? 0));
        return $placement;
    }
}
