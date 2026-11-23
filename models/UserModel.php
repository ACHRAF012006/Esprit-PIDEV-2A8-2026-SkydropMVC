<?php


/**
 * S1 - One account (security workshop 3.1): private attributes, getters / setters, and fromArray() which turns a row
 * of the users table into an object. No SQL and no business logic here: they are in the controllers.
 */
final class UserModel
{
    /** Role keys stored in users.role, with their labels. */
    public const ROLES = [
        'customer'   => 'Customer',
        'shop_owner' => 'Shop owner',
        'advertiser' => 'Advertiser',
        'admin'      => 'Administrator',
    ];

    private int $id = 0;
    private string $username = '';
    private string $email = '';
    private string $password = '';
    private string $role = 'customer';
    private string $fullName = '';
    private ?string $phone = null;
    private bool $isActive = true;
    private string $createdAt = '';
    private ?string $lastLoginAt = null;
    private bool $mustChangePassword = false;

    public function getId(): int { return $this->id; }
    public function getUsername(): string { return $this->username; }
    public function getEmail(): string { return $this->email; }
    public function getPassword(): string { return $this->password; }
    public function getRole(): string { return $this->role; }
    public function getFullName(): string { return $this->fullName; }
    public function getPhone(): ?string { return $this->phone; }
    public function isActive(): bool { return $this->isActive; }
    public function getCreatedAt(): string { return $this->createdAt; }
    public function getLastLoginAt(): ?string { return $this->lastLoginAt; }
    public function mustChangePassword(): bool { return $this->mustChangePassword; }

    /** The label of the role, for example "Shop owner". */
    public function getRoleLabel(): string { return self::ROLES[$this->role] ?? $this->role; }

    public function setId(int $id): void { $this->id = $id; }
    public function setUsername(string $username): void { $this->username = $username; }
    public function setEmail(string $email): void { $this->email = $email; }
    public function setPassword(string $password): void { $this->password = $password; }
    public function setRole(string $role): void { $this->role = $role; }
    public function setFullName(string $fullName): void { $this->fullName = $fullName; }
    public function setPhone(?string $phone): void { $this->phone = $phone; }
    public function setActive(bool $isActive): void { $this->isActive = $isActive; }
    public function setCreatedAt(string $createdAt): void { $this->createdAt = $createdAt; }
    public function setLastLoginAt(?string $lastLoginAt): void { $this->lastLoginAt = $lastLoginAt; }
    public function setMustChangePassword(bool $must): void { $this->mustChangePassword = $must; }

    /** A row of the users table (column names) -> UserModel. */
    public static function fromArray(array $row): self
    {
        $user = new self();
        $user->setId((int) ($row['id'] ?? 0));
        $user->setUsername((string) ($row['username'] ?? ''));
        $user->setEmail((string) ($row['email'] ?? ''));
        $user->setPassword((string) ($row['password'] ?? ''));
        $user->setRole((string) ($row['role'] ?? 'customer'));
        $user->setFullName((string) ($row['full_name'] ?? ''));
        $user->setPhone(isset($row['phone']) && $row['phone'] !== '' ? (string) $row['phone'] : null);
        $user->setActive((bool) ($row['is_active'] ?? true));
        $user->setCreatedAt((string) ($row['created_at'] ?? ''));
        $user->setLastLoginAt($row['last_login_at'] ?? null);
        $user->setMustChangePassword((bool) ($row['must_change_password'] ?? false));
        return $user;
    }
}
