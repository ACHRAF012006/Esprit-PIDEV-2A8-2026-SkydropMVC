<?php

/**
 * S5 Karim - One password reset link (a row of password_resets): private attributes, getters / setters and fromArray().
 * No SQL and no logic here: PasswordResetController holds them. tokenHash is hash('sha256', token), never the token.
 */
final class PasswordResetModel
{
    private int $id = 0;
    private int $userId = 0;
    private string $tokenHash = '';
    private string $expiresAt = '';
    private ?string $usedAt = null;
    private string $createdAt = '';

    public function getId(): int { return $this->id; }
    public function getUserId(): int { return $this->userId; }
    public function getTokenHash(): string { return $this->tokenHash; }
    public function getExpiresAt(): string { return $this->expiresAt; }
    public function getUsedAt(): ?string { return $this->usedAt; }
    public function getCreatedAt(): string { return $this->createdAt; }

    public function setId(int $id): void { $this->id = $id; }
    public function setUserId(int $userId): void { $this->userId = $userId; }
    public function setTokenHash(string $tokenHash): void { $this->tokenHash = $tokenHash; }
    public function setExpiresAt(string $expiresAt): void { $this->expiresAt = $expiresAt; }
    public function setUsedAt(?string $usedAt): void { $this->usedAt = $usedAt; }
    public function setCreatedAt(string $createdAt): void { $this->createdAt = $createdAt; }

    /** A row of the password_resets table (column names) -> PasswordResetModel. */
    public static function fromArray(array $row): self
    {
        $reset = new self();
        $reset->setId((int) ($row['id'] ?? 0));
        $reset->setUserId((int) ($row['user_id'] ?? 0));
        $reset->setTokenHash((string) ($row['token_hash'] ?? ''));
        $reset->setExpiresAt((string) ($row['expires_at'] ?? ''));
        $reset->setUsedAt(isset($row['used_at']) ? (string) $row['used_at'] : null);
        $reset->setCreatedAt((string) ($row['created_at'] ?? ''));
        return $reset;
    }
}
