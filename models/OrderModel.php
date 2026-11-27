<?php

/**
 * S4 Zamel - One order (one row of the orders table): private attributes, getters / setters and fromArray().
 * customerName, shopName and itemCount are not columns of orders: they come from JOINs in the controllers
 * (users.full_name, shops.name, COUNT / SUM of order_items). No SQL and no business logic here.
 */
final class OrderModel
{
    /** Values of orders.status, in the order an order moves through them, with their labels. */
    public const STATUSES = [
        'placed'    => 'Placed',
        'preparing' => 'Preparing',
        'ready'     => 'Ready',
        'delivered' => 'Delivered',
        'cancelled' => 'Cancelled',
    ];

    private int $id = 0;
    private int $customerId = 0;
    private int $shopId = 0;
    private string $status = 'placed';
    private float $subtotal = 0.0;
    private float $discount = 0.0;
    private float $total = 0.0;
    private ?string $couponCode = null;
    private string $paymentStatus = 'unpaid';
    private ?string $note = null;
    private string $placedAt = '';
    private string $updatedAt = '';
    private string $customerName = '';
    private string $shopName = '';
    private int $itemCount = 0;

    public function getId(): int { return $this->id; }
    public function getCustomerId(): int { return $this->customerId; }
    public function getShopId(): int { return $this->shopId; }
    public function getStatus(): string { return $this->status; }
    public function getSubtotal(): float { return $this->subtotal; }
    public function getDiscount(): float { return $this->discount; }
    public function getTotal(): float { return $this->total; }
    public function getCouponCode(): ?string { return $this->couponCode; }
    public function getPaymentStatus(): string { return $this->paymentStatus; }
    public function getNote(): ?string { return $this->note; }
    public function getPlacedAt(): string { return $this->placedAt; }
    public function getUpdatedAt(): string { return $this->updatedAt; }
    public function getCustomerName(): string { return $this->customerName; }
    public function getShopName(): string { return $this->shopName; }
    public function getItemCount(): int { return $this->itemCount; }

    /** The label of the status, for example "Preparing". */
    public function getStatusLabel(): string { return self::STATUSES[$this->status] ?? $this->status; }

    public function setId(int $id): void { $this->id = $id; }
    public function setCustomerId(int $customerId): void { $this->customerId = $customerId; }
    public function setShopId(int $shopId): void { $this->shopId = $shopId; }
    public function setStatus(string $status): void { $this->status = $status; }
    public function setSubtotal(float $subtotal): void { $this->subtotal = $subtotal; }
    public function setDiscount(float $discount): void { $this->discount = $discount; }
    public function setTotal(float $total): void { $this->total = $total; }
    public function setCouponCode(?string $couponCode): void { $this->couponCode = $couponCode; }
    public function setPaymentStatus(string $paymentStatus): void { $this->paymentStatus = $paymentStatus; }
    public function setNote(?string $note): void { $this->note = $note; }
    public function setPlacedAt(string $placedAt): void { $this->placedAt = $placedAt; }
    public function setUpdatedAt(string $updatedAt): void { $this->updatedAt = $updatedAt; }
    public function setCustomerName(string $customerName): void { $this->customerName = $customerName; }
    public function setShopName(string $shopName): void { $this->shopName = $shopName; }
    public function setItemCount(int $itemCount): void { $this->itemCount = $itemCount; }

    /** A row of orders (column names, plus customer_name, shop_name, item_count from JOINs) -> OrderModel. */
    public static function fromArray(array $row): self
    {
        $order = new self();
        $order->setId((int) ($row['id'] ?? 0));
        $order->setCustomerId((int) ($row['customer_id'] ?? 0));
        $order->setShopId((int) ($row['shop_id'] ?? 0));
        $order->setStatus((string) ($row['status'] ?? 'placed'));
        $order->setSubtotal((float) ($row['subtotal'] ?? 0));
        $order->setDiscount((float) ($row['discount'] ?? 0));
        $order->setTotal((float) ($row['total'] ?? 0));
        $order->setCouponCode(isset($row['coupon_code']) && $row['coupon_code'] !== '' ? (string) $row['coupon_code'] : null);
        $order->setPaymentStatus((string) ($row['payment_status'] ?? 'unpaid'));
        $order->setNote(isset($row['note']) && $row['note'] !== '' ? (string) $row['note'] : null);
        $order->setPlacedAt((string) ($row['placed_at'] ?? ''));
        $order->setUpdatedAt((string) ($row['updated_at'] ?? ''));
        $order->setCustomerName((string) ($row['customer_name'] ?? ''));
        $order->setShopName((string) ($row['shop_name'] ?? ''));
        $order->setItemCount((int) ($row['item_count'] ?? 0));
        return $order;
    }
}
