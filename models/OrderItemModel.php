<?php

/**
 * S4 Zamel - One line of an order (one row of order_items): private attributes, getters / setters and fromArray().
 * productName and unitPrice are copies made when the order was placed. No SQL and no business logic here.
 */
final class OrderItemModel
{
    private int $id = 0;
    private int $orderId = 0;
    private int $productId = 0;
    private string $productName = '';
    private float $unitPrice = 0.0;
    private int $quantity = 0;

    public function getId(): int { return $this->id; }
    public function getOrderId(): int { return $this->orderId; }
    public function getProductId(): int { return $this->productId; }
    public function getProductName(): string { return $this->productName; }
    public function getUnitPrice(): float { return $this->unitPrice; }
    public function getQuantity(): int { return $this->quantity; }

    /** Price of the line: unit price x quantity. */
    public function getLineTotal(): float { return round($this->unitPrice * $this->quantity, 3); }

    public function setId(int $id): void { $this->id = $id; }
    public function setOrderId(int $orderId): void { $this->orderId = $orderId; }
    public function setProductId(int $productId): void { $this->productId = $productId; }
    public function setProductName(string $productName): void { $this->productName = $productName; }
    public function setUnitPrice(float $unitPrice): void { $this->unitPrice = $unitPrice; }
    public function setQuantity(int $quantity): void { $this->quantity = $quantity; }

    /** A row of order_items (column names) -> OrderItemModel. */
    public static function fromArray(array $row): self
    {
        $item = new self();
        $item->setId((int) ($row['id'] ?? 0));
        $item->setOrderId((int) ($row['order_id'] ?? 0));
        $item->setProductId((int) ($row['product_id'] ?? 0));
        $item->setProductName((string) ($row['product_name'] ?? ''));
        $item->setUnitPrice((float) ($row['unit_price'] ?? 0));
        $item->setQuantity((int) ($row['quantity'] ?? 0));
        return $item;
    }
}
