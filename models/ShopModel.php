<?php

/**
 * S2 Mili - One shop (row of shops): private attributes, getters / setters and fromArray().
 * No SQL and no logic here: they are in ShopController, OwnerShopController and AdminShopController.
 * Filled from the JOINs of those controllers: planName and rankWeight (shop_plans), ownerUsername (users),
 * openNow (computed by the SQL from opens_at / closes_at).
 */
final class ShopModel
{
    /** Status keys stored in shops.status, with their labels. */
    public const STATUSES = [
        'pending'  => 'Waiting for approval',
        'approved' => 'Approved',
        'rejected' => 'Rejected',
    ];

    private int $id = 0;
    private int $ownerId = 0;
    private int $planId = 0;
    private string $name = '';
    private ?string $description = null;
    private ?string $address = null;
    private ?string $phone = null;
    private string $opensAt = '';
    private string $closesAt = '';
    private ?float $lat = null;
    private ?float $lng = null;
    private string $status = 'pending';
    private ?string $reviewNote = null;
    private string $createdAt = '';
    private string $planName = '';
    private int $rankWeight = 0;
    private string $ownerUsername = '';
    private bool $openNow = false;

    public function getId(): int { return $this->id; }
    public function getOwnerId(): int { return $this->ownerId; }
    public function getPlanId(): int { return $this->planId; }
    public function getName(): string { return $this->name; }
    public function getDescription(): ?string { return $this->description; }
    public function getAddress(): ?string { return $this->address; }
    public function getPhone(): ?string { return $this->phone; }
    public function getOpensAt(): string { return $this->opensAt; }
    public function getClosesAt(): string { return $this->closesAt; }
    public function getLat(): ?float { return $this->lat; }
    public function getLng(): ?float { return $this->lng; }
    public function getStatus(): string { return $this->status; }
    public function getReviewNote(): ?string { return $this->reviewNote; }
    public function getCreatedAt(): string { return $this->createdAt; }
    public function getPlanName(): string { return $this->planName; }
    public function getRankWeight(): int { return $this->rankWeight; }
    public function getOwnerUsername(): string { return $this->ownerUsername; }
    public function isOpenNow(): bool { return $this->openNow; }

    /** The label of the status, for example "Waiting for approval". */
    public function getStatusLabel(): string { return self::STATUSES[$this->status] ?? $this->status; }

    public function setId(int $id): void { $this->id = $id; }
    public function setOwnerId(int $ownerId): void { $this->ownerId = $ownerId; }
    public function setPlanId(int $planId): void { $this->planId = $planId; }
    public function setName(string $name): void { $this->name = $name; }
    public function setDescription(?string $description): void { $this->description = $description; }
    public function setAddress(?string $address): void { $this->address = $address; }
    public function setPhone(?string $phone): void { $this->phone = $phone; }
    public function setOpensAt(string $opensAt): void { $this->opensAt = $opensAt; }
    public function setClosesAt(string $closesAt): void { $this->closesAt = $closesAt; }
    public function setLat(?float $lat): void { $this->lat = $lat; }
    public function setLng(?float $lng): void { $this->lng = $lng; }
    public function setStatus(string $status): void { $this->status = $status; }
    public function setReviewNote(?string $reviewNote): void { $this->reviewNote = $reviewNote; }
    public function setCreatedAt(string $createdAt): void { $this->createdAt = $createdAt; }
    public function setPlanName(string $planName): void { $this->planName = $planName; }
    public function setRankWeight(int $rankWeight): void { $this->rankWeight = $rankWeight; }
    public function setOwnerUsername(string $ownerUsername): void { $this->ownerUsername = $ownerUsername; }
    public function setOpenNow(bool $openNow): void { $this->openNow = $openNow; }

    /** A row of shops, with the joined columns plan_name, rank_weight, owner_username, open_now -> ShopModel. */
    public static function fromArray(array $row): self
    {
        $shop = new self();
        $shop->setId((int) ($row['id'] ?? 0));
        $shop->setOwnerId((int) ($row['owner_id'] ?? 0));
        $shop->setPlanId((int) ($row['plan_id'] ?? 0));
        $shop->setName((string) ($row['name'] ?? ''));
        $shop->setDescription(isset($row['description']) && $row['description'] !== '' ? (string) $row['description'] : null);
        $shop->setAddress(isset($row['address']) && $row['address'] !== '' ? (string) $row['address'] : null);
        $shop->setPhone(isset($row['phone']) && $row['phone'] !== '' ? (string) $row['phone'] : null);
        $shop->setOpensAt((string) ($row['opens_at'] ?? ''));
        $shop->setClosesAt((string) ($row['closes_at'] ?? ''));
        $shop->setLat(isset($row['lat']) ? (float) $row['lat'] : null);
        $shop->setLng(isset($row['lng']) ? (float) $row['lng'] : null);
        $shop->setStatus((string) ($row['status'] ?? 'pending'));
        $shop->setReviewNote(isset($row['review_note']) && $row['review_note'] !== '' ? (string) $row['review_note'] : null);
        $shop->setCreatedAt((string) ($row['created_at'] ?? ''));
        $shop->setPlanName((string) ($row['plan_name'] ?? ''));
        $shop->setRankWeight((int) ($row['rank_weight'] ?? 0));
        $shop->setOwnerUsername((string) ($row['owner_username'] ?? ''));
        $shop->setOpenNow((bool) ($row['open_now'] ?? false));
        return $shop;
    }
}
