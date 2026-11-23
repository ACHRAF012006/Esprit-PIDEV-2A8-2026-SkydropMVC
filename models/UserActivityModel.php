<?php


/**
 * S3 Islem - One line of the activity log (table user_activity): which administrator did what to which account, and when.
 * Private attributes, getters / setters, and fromArray() which turns a row (with the two usernames of the JOIN) into an
 * object. No SQL and no business logic here: they are in UserActivityController.
 */
final class UserActivityModel
{
    /** Action keys stored in user_activity.action, with their labels. */
    public const ACTIONS = [
        'created'        => 'Account created',
        'updated'        => 'Account edited',
        'role_changed'   => 'Role changed',
        'activated'      => 'Account activated',
        'deactivated'    => 'Account deactivated',
        'deleted'        => 'Account deleted',
        'password_reset' => 'New password given',
    ];

    private int $id = 0;
    private ?int $actorId = null;
    private ?int $targetId = null;
    private string $action = '';
    private string $details = '';
    private string $createdAt = '';
    private ?string $actorUsername = null;
    private ?string $targetUsername = null;

    public function getId(): int { return $this->id; }
    public function getActorId(): ?int { return $this->actorId; }
    public function getTargetId(): ?int { return $this->targetId; }
    public function getAction(): string { return $this->action; }
    public function getDetails(): string { return $this->details; }
    public function getCreatedAt(): string { return $this->createdAt; }
    public function getActorUsername(): ?string { return $this->actorUsername; }
    public function getTargetUsername(): ?string { return $this->targetUsername; }

    /** The label of the action, for example "Role changed". */
    public function getActionLabel(): string { return self::ACTIONS[$this->action] ?? $this->action; }

    public function setId(int $id): void { $this->id = $id; }
    public function setActorId(?int $actorId): void { $this->actorId = $actorId; }
    public function setTargetId(?int $targetId): void { $this->targetId = $targetId; }
    public function setAction(string $action): void { $this->action = $action; }
    public function setDetails(string $details): void { $this->details = $details; }
    public function setCreatedAt(string $createdAt): void { $this->createdAt = $createdAt; }
    public function setActorUsername(?string $username): void { $this->actorUsername = $username; }
    public function setTargetUsername(?string $username): void { $this->targetUsername = $username; }

    /** A row of user_activity (+ actor_username, target_username from the JOIN) -> UserActivityModel. */
    public static function fromArray(array $row): self
    {
        $item = new self();
        $item->setId((int) ($row['id'] ?? 0));
        $item->setActorId(isset($row['actor_id']) ? (int) $row['actor_id'] : null);
        $item->setTargetId(isset($row['target_id']) ? (int) $row['target_id'] : null);
        $item->setAction((string) ($row['action'] ?? ''));
        $item->setDetails((string) ($row['details'] ?? ''));
        $item->setCreatedAt((string) ($row['created_at'] ?? ''));
        $item->setActorUsername(isset($row['actor_username']) ? (string) $row['actor_username'] : null);
        $item->setTargetUsername(isset($row['target_username']) ? (string) $row['target_username'] : null);
        return $item;
    }
}
