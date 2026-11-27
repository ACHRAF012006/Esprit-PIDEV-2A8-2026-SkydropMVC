<?php

/**
 * S4 Zamel - Orders of the signed-in customer (front office): checkout, my orders, one order, cancel.
 * Auth::requireRole('customer') is the first line of every page; every query is a prepared statement with named
 * parameters and every query on a customer's order has "AND o.customer_id = :me".
 *
 * Rules: an empty cart cannot be checked out; totals are computed here, never read from the form; placing an order
 * takes the quantities out of stock (ProductController::changeStock with a negative delta) and is refused if one
 * product no longer has enough; a customer cancels only a 'placed' order, which gives the stock back.
 * The payment is a simulation: "Pay now" marks the order paid, "Pay on pickup" leaves it unpaid.
 */
final class OrderController extends Controller
{
    /** Payment choices of the checkout => label. */
    public const PAYMENTS = ['now' => 'Pay now', 'pickup' => 'Pay on pickup'];

    /** The only move allowed from each status: forward, one step. 'delivered' and 'cancelled' have none. */
    public const NEXT = ['placed' => 'preparing', 'preparing' => 'ready', 'ready' => 'delivered'];

    private \PDO $db;

    public function __construct()
    {
        $this->db = \Database::getConnexion();
    }

    // =================================================================================================
    // CHECKOUT
    // =================================================================================================

    /** GET /checkout: summary of my cart, optional coupon, note, simulated payment. */
    public function checkoutForm(): void
    {
        Auth::requireRole('customer');
        $summary = (new CartController())->summary();
        $this->refuseBadCart($summary);
        $errors = $_SESSION['errors'] ?? [];
        $old = $_SESSION['old'] ?? [];
        unset($_SESSION['errors'], $_SESSION['old']);
        $this->render('front/checkout', [
            'title' => 'Checkout | SKYDROP',
            'activeMenu' => 'cart',
            'pageStyles' => ['css/orders.css'],
            'couponsReady' => class_exists('CouponController'),
            'payments' => self::PAYMENTS,
            'errors' => $errors,
            'old' => $old,
        ] + $summary);
    }

    /** POST /checkout: places the order. */
    public function checkout(): void
    {
        Auth::requireRole('customer');
        Csrf::check();
        $cart = new CartController();
        $summary = $cart->summary();
        $this->refuseBadCart($summary);

        $v = (new Validator($_POST))
            ->required('payment')->in('payment', array_keys(self::PAYMENTS))
            ->maxLen('note', 300);
        $errors = $v->errors();
        $note = $v->get('note');
        $code = strtoupper($v->get('coupon_code'));
        $discount = 0.0;
        $couponsReady = class_exists('CouponController');
        if ($couponsReady && $code !== '') {
            // Same message as the data-pattern-message of the field in forms.js.
            if (!preg_match('/^[A-Z0-9]{3,20}$/', $code)) {
                $errors['coupon_code'] = 'This coupon code does not exist.';
            } else {
                $result = (new CouponController())->check($code, (int) Auth::id(), $summary['subtotal']);
                if (!$result['ok']) {
                    $errors['coupon_code'] = $result['message'];
                } else {
                    $discount = (float) $result['discount'];
                }
            }
        }
        if (!$couponsReady) {
            $code = '';
        }
        if ($errors) {
            $_SESSION['errors'] = $errors;
            $_SESSION['old'] = array_intersect_key($_POST, array_flip(['payment', 'note', 'coupon_code']));
            $this->redirect(route('checkout'));
        }

        // Totals computed on the server from the product prices read again by summary(), never from the form.
        $subtotal = $summary['subtotal'];
        $discount = min($discount, $subtotal);
        $total = round($subtotal - $discount, 3);
        $payment = $v->get('payment');

        $this->db->beginTransaction();
        try {
            // The stock is checked again inside the transaction: someone else may have bought the last ones meanwhile.
            foreach ($summary['items'] as $item) {
                $product = $cart->product($item->getProductId());
                if ($product === null || !$product['active'] || $product['stock'] < $item->getQuantity()) {
                    $this->db->rollBack();
                    flash('error', 'The order is not placed: ' . $item->getProductName() . ' has only '
                        . (int) ($product['stock'] ?? 0) . ' left in stock. Change the quantity in your cart.');
                    $this->redirect(route('cart'));
                }
            }

            $stmt = $this->db->prepare('INSERT INTO orders (customer_id, shop_id, status, subtotal, discount, total, coupon_code, payment_status, note, placed_at)
                VALUES (:customer, :shop, :status, :subtotal, :discount, :total, :code, :payment, :note, NOW())');
            $stmt->execute([
                'customer' => Auth::id(),
                'shop' => $summary['shopId'],
                'status' => 'placed',
                'subtotal' => number_format($subtotal, 3, '.', ''),
                'discount' => number_format($discount, 3, '.', ''),
                'total' => number_format($total, 3, '.', ''),
                'code' => $code !== '' ? $code : null,
                'payment' => $payment === 'now' ? 'paid' : 'unpaid',
                'note' => $note !== '' ? $note : null,
            ]);
            $orderId = (int) $this->db->lastInsertId();

            $line = $this->db->prepare('INSERT INTO order_items (order_id, product_id, product_name, unit_price, quantity)
                VALUES (:order_id, :product_id, :name, :price, :quantity)');
            $products = new ProductController();
            foreach ($summary['items'] as $item) {
                $line->execute([
                    'order_id' => $orderId,
                    'product_id' => $item->getProductId(),
                    'name' => $item->getProductName(),
                    'price' => number_format($item->getUnitPrice(), 3, '.', ''),
                    'quantity' => $item->getQuantity(),
                ]);
                $products->changeStock($item->getProductId(), -$item->getQuantity());
            }
            if ($code !== '' && method_exists('CouponController', 'recordUse')) {
                (new CouponController())->recordUse($code, (int) Auth::id(), $orderId, $discount);
            }
            $this->db->commit();
        } catch (Throwable $ex) {
            if ($this->db->inTransaction()) {
                $this->db->rollBack();
            }
            throw $ex;
        }

        $cart->emptyCart();
        flash('success', 'Order #' . $orderId . ' is placed. Total: ' . self::price($total)
            . ($payment === 'now' ? ' (paid, simulation).' : ' (to pay on pickup).'));
        $this->redirect(route('order', ['id' => $orderId]));
    }

    // =================================================================================================
    // MY ORDERS
    // =================================================================================================

    /** GET /orders: my orders, newest first, with their number of articles (JOIN order_items). */
    public function index(): void
    {
        Auth::requireRole('customer');
        $this->render('front/my-orders', [
            'title' => 'My orders | SKYDROP',
            'pageStyles' => ['css/orders.css'],
            'orders' => $this->ofCustomer((int) Auth::id()),
        ]);
    }

    /** GET /orders/{id}: one of MY orders with its lines; the order of somebody else is "not found". */
    public function show(string $id): void
    {
        Auth::requireRole('customer');
        $order = $this->findMine((int) $id) ?? abort(404, 'This order does not exist.');
        $this->render('front/order', [
            'title' => 'Order #' . $order->getId() . ' | SKYDROP',
            'activeMenu' => 'my-orders',
            'pageStyles' => ['css/orders.css'],
            'order' => $order,
            'items' => $this->items($order->getId()),
        ]);
    }

    /** POST /orders/{id}/cancel: only a 'placed' order of mine; the quantities go back into stock. */
    public function cancel(string $id): void
    {
        Auth::requireRole('customer');
        Csrf::check();
        $order = $this->findMine((int) $id) ?? abort(404, 'This order does not exist.');
        $back = route('order', ['id' => $order->getId()]);
        if ($order->getStatus() !== 'placed') {
            flash('error', 'Order #' . $order->getId() . ' is ' . strtolower($order->getStatusLabel())
                . ': only an order that the shop has not started (Placed) can be cancelled.');
            $this->redirect($back);
        }

        $this->db->beginTransaction();
        try {
            // "AND status = 'placed'" in the UPDATE itself: if the shop started it meanwhile, nothing changes.
            $stmt = $this->db->prepare("UPDATE orders SET status = 'cancelled' WHERE id = :id AND customer_id = :me AND status = 'placed'");
            $stmt->execute(['id' => $order->getId(), 'me' => Auth::id()]);
            if ($stmt->rowCount() === 1 && class_exists('ProductController')) {
                $products = new ProductController();
                foreach ($this->items($order->getId()) as $item) {
                    $products->changeStock($item->getProductId(), $item->getQuantity());
                }
            }
            $this->db->commit();
        } catch (Throwable $ex) {
            $this->db->rollBack();
            throw $ex;
        }
        flash('success', $stmt->rowCount() === 1
            ? 'Order #' . $order->getId() . ' is cancelled. The products are back in stock.'
            : 'Order #' . $order->getId() . ' could not be cancelled: the shop has just started it.');
        $this->redirect($back);
    }

    // =================================================================================================
    // DATA ACCESS used by this part and by the other parts
    // =================================================================================================

    /** Any order by id, with the customer name, the shop name and its number of articles. For the other parts. */
    public function find(int $id): ?OrderModel
    {
        return $this->findOne($id, null);
    }

    /** @return OrderItemModel[] the lines of one order. */
    public function items(int $orderId): array
    {
        $stmt = $this->db->prepare('SELECT * FROM order_items WHERE order_id = :id ORDER BY id');
        $stmt->execute(['id' => $orderId]);
        return array_map([OrderItemModel::class, 'fromArray'], $stmt->fetchAll());
    }

    /** @return OrderModel[] the orders of one customer, newest first ($limit for the profile block). */
    public function ofCustomer(int $customerId, ?int $limit = null): array
    {
        [$select, $join] = $this->shopJoin();
        $stmt = $this->db->prepare('SELECT o.*, ' . $select . ', COALESCE(SUM(i.quantity), 0) AS item_count
            FROM orders o
            ' . $join . '
            LEFT JOIN order_items i ON i.order_id = o.id
            WHERE o.customer_id = :me
            GROUP BY o.id
            ORDER BY o.placed_at DESC, o.id DESC' . ($limit !== null ? ' LIMIT :limit' : ''));
        $stmt->bindValue('me', $customerId, \PDO::PARAM_INT);
        if ($limit !== null) {
            $stmt->bindValue('limit', $limit, \PDO::PARAM_INT);
        }
        $stmt->execute();
        return array_map([OrderModel::class, 'fromArray'], $stmt->fetchAll());
    }

    /**
     * The shop name part of a query: a JOIN on Mili's shops table once it exists, otherwise "Shop #id".
     * Returns [select expression, join clause]. Fixed strings only, never text from the browser.
     */
    public function shopJoin(): array
    {
        static $ready = null;
        if ($ready === null) {
            $stmt = $this->db->prepare('SELECT COUNT(*) FROM information_schema.tables WHERE table_schema = DATABASE() AND table_name = :t');
            $stmt->execute(['t' => 'shops']);
            $ready = (int) $stmt->fetchColumn() > 0;
        }
        return $ready
            ? ['s.name AS shop_name', 'JOIN shops s ON s.id = o.shop_id']
            : ["CONCAT('Shop #', o.shop_id) AS shop_name", ''];
    }

    /** A price in TND with 3 decimals; money() of config/helpers.php once it is in main. */
    public static function price(float $amount): string
    {
        return function_exists('money') ? money($amount) : number_format($amount, 3, '.', ' ') . ' TND';
    }

    /** Tag class of each status for the front pages (the text always says the status, not only the colour). */
    public static function statusTag(string $status): string
    {
        return match ($status) {
            'delivered' => 'ds-tag ds-tag-ok',
            'ready', 'preparing' => 'ds-tag ds-tag-warn',
            'cancelled' => 'ds-tag ds-tag-danger',
            default => 'ds-tag',
        };
    }

    /** TailAdmin badge colours of each status for the back offices (light and dark theme). */
    public static function statusBadge(string $status): string
    {
        return match ($status) {
            'delivered' => 'bg-green-50 text-green-600 dark:bg-green-500/15 dark:text-green-400',
            'ready', 'preparing' => 'bg-orange-50 text-orange-600 dark:bg-orange-500/15 dark:text-orange-400',
            'cancelled' => 'bg-red-50 text-red-600 dark:bg-red-500/15 dark:text-red-400',
            default => 'bg-gray-100 text-gray-800 dark:bg-white/5 dark:text-white/80',
        };
    }

    /** One of MY orders: "AND o.customer_id = :me" in the SQL, so the order of another customer is never read. */
    private function findMine(int $id): ?OrderModel
    {
        return $this->findOne($id, (int) Auth::id());
    }

    /** One order with JOIN users, shops and order_items; only of $customerId when it is given. */
    private function findOne(int $id, ?int $customerId): ?OrderModel
    {
        [$select, $join] = $this->shopJoin();
        $stmt = $this->db->prepare('SELECT o.*, u.full_name AS customer_name, ' . $select . ', COALESCE(SUM(i.quantity), 0) AS item_count
            FROM orders o
            JOIN users u ON u.id = o.customer_id
            ' . $join . '
            LEFT JOIN order_items i ON i.order_id = o.id
            WHERE o.id = :id' . ($customerId !== null ? ' AND o.customer_id = :me' : '') . '
            GROUP BY o.id');
        $args = ['id' => $id];
        if ($customerId !== null) {
            $args['me'] = $customerId;
        }
        $stmt->execute($args);
        $row = $stmt->fetch();
        return $row ? OrderModel::fromArray($row) : null;
    }

    /** An empty cart, or a cart with a product problem, cannot be checked out: back to the cart with the reason. */
    private function refuseBadCart(array $summary): void
    {
        if (!$summary['items']) {
            flash('error', 'Your cart is empty: add a product before checking out.');
            $this->redirect(route('cart'));
        }
        if ($summary['problems']) {
            flash('error', 'Fix the products marked in your cart before checking out.');
            $this->redirect(route('cart'));
        }
    }
}
