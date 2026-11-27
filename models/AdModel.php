<?php

/**
 * S5 Karim - One text ad (a row of ads). It also holds values that come from the JOINs of the controllers:
 * placementName (ad_placements.name), advertiserName (users.full_name), and two values computed by the SQL:
 * days (number of days, both dates included) and running (approved AND today between its dates).
 * Only private attributes, getters / setters and fromArray(). No SQL and no logic: they are in the controllers.
 */
final class AdModel
{
    /** Status keys stored in ads.status, with their labels. */
    public const STATUSES = [
        'pending'  => 'Pending',
        'approved' => 'Approved',
        'rejected' => 'Rejected',
    ];

    private int $id = 0;
    private int $advertiserId = 0;
    private int $placementId = 0;
    private string $title = '';
    private string $body = '';
    private ?string $link = null;
    private string $startsAt = '';
    private string $endsAt = '';
    private float $totalPrice = 0.0;
    private string $status = 'pending';
    private ?string $reviewNote = null;
    private string $createdAt = '';
    private string $placementName = '';
    private string $advertiserName = '';
    private int $days = 0;
    private bool $running = false;

    public function getId(): int { return $this->id; }
    public function getAdvertiserId(): int { return $this->advertiserId; }
    public function getPlacementId(): int { return $this->placementId; }
    public function getTitle(): string { return $this->title; }
    public function getBody(): string { return $this->body; }
    public function getLink(): ?string { return $this->link; }
    public function getStartsAt(): string { return $this->startsAt; }
    public function getEndsAt(): string { return $this->endsAt; }
    public function getTotalPrice(): float { return $this->totalPrice; }
    public function getStatus(): string { return $this->status; }
    public function getReviewNote(): ?string { return $this->reviewNote; }
    public function getCreatedAt(): string { return $this->createdAt; }
    public function getPlacementName(): string { return $this->placementName; }
    public function getAdvertiserName(): string { return $this->advertiserName; }
    public function getDays(): int { return $this->days; }
    public function isRunning(): bool { return $this->running; }

    /** The label of the status, for example "Pending". */
    public function getStatusLabel(): string { return self::STATUSES[$this->status] ?? $this->status; }

    public function setId(int $id): void { $this->id = $id; }
    public function setAdvertiserId(int $advertiserId): void { $this->advertiserId = $advertiserId; }
    public function setPlacementId(int $placementId): void { $this->placementId = $placementId; }
    public function setTitle(string $title): void { $this->title = $title; }
    public function setBody(string $body): void { $this->body = $body; }
    public function setLink(?string $link): void { $this->link = $link; }
    public function setStartsAt(string $startsAt): void { $this->startsAt = $startsAt; }
    public function setEndsAt(string $endsAt): void { $this->endsAt = $endsAt; }
    public function setTotalPrice(float $totalPrice): void { $this->totalPrice = $totalPrice; }
    public function setStatus(string $status): void { $this->status = $status; }
    public function setReviewNote(?string $reviewNote): void { $this->reviewNote = $reviewNote; }
    public function setCreatedAt(string $createdAt): void { $this->createdAt = $createdAt; }
    public function setPlacementName(string $placementName): void { $this->placementName = $placementName; }
    public function setAdvertiserName(string $advertiserName): void { $this->advertiserName = $advertiserName; }
    public function setDays(int $days): void { $this->days = $days; }
    public function setRunning(bool $running): void { $this->running = $running; }

    /** A row of ads, with the optional JOIN columns placement_name, advertiser_name, days, is_running -> AdModel. */
    public static function fromArray(array $row): self
    {
        $ad = new self();
        $ad->setId((int) ($row['id'] ?? 0));
        $ad->setAdvertiserId((int) ($row['advertiser_id'] ?? 0));
        $ad->setPlacementId((int) ($row['placement_id'] ?? 0));
        $ad->setTitle((string) ($row['title'] ?? ''));
        $ad->setBody((string) ($row['body'] ?? ''));
        $ad->setLink(isset($row['link']) && $row['link'] !== '' ? (string) $row['link'] : null);
        $ad->setStartsAt((string) ($row['starts_at'] ?? ''));
        $ad->setEndsAt((string) ($row['ends_at'] ?? ''));
        $ad->setTotalPrice((float) ($row['total_price'] ?? 0));
        $ad->setStatus((string) ($row['status'] ?? 'pending'));
        $ad->setReviewNote(isset($row['review_note']) && $row['review_note'] !== '' ? (string) $row['review_note'] : null);
        $ad->setCreatedAt((string) ($row['created_at'] ?? ''));
        $ad->setPlacementName((string) ($row['placement_name'] ?? ''));
        $ad->setAdvertiserName((string) ($row['advertiser_name'] ?? ''));
        $ad->setDays((int) ($row['days'] ?? 0));
        $ad->setRunning((bool) ($row['is_running'] ?? false));
        return $ad;
    }
}
