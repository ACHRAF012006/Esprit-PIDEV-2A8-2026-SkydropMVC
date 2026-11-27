<?php

/**
 * S4 Zamel - The cart of the signed-in customer (front office). The cart is kept in the session, not in a table:
 *   $_SESSION['cart'] = ['shop_id' => 1, 'items' => [product id => quantity, ...]]
 * Product data (name, price, stock, shop, active) always comes from Islem's ProductController::find(), never from the
 * form, so a customer cannot change a price. Until Islem's part is merged the calls stay inside class_exists().
 *
 * Rules: quantity 1 to 99 and never more than the stock; an inactive or out-of-stock product cannot be added; the cart
 * holds products of ONE shop (adding from another shop asks to empty the cart first).
 * There is no SQL in this class: the cart lives in the session and the products belong to the products part.
 */
final class CartController extends Controller
{
    public const MAX_QUANTITY = 99;

    /** Same message as forms.js shows for data-integer data-min="1" data-max="99". */
    public const RANGE_MESSAGE = 'Number out of range (1 to 99).';

    // =================================================================================================
    // PAGES AND ACTIONS
    // =================================================================================================

    /** GET /cart: the lines of my cart with the current price and stock of each product. */
    public function index(): void
    {
        Auth::requireRole('customer');
        $errors = $_SESSION['errors'] ?? [];
        $pending = $_SESSION['cart_pending'] ?? null;
        unset($_SESSION['errors'], $_SESSION['cart_pending']);
        $summary = $this->summary();
        $pendingProduct = $pending ? $this->product((int) $pending['product_id']) : null;
        $this->render('front/cart', [
            'title' => 'My cart | SKYDROP',
            'productsReady' => class_exists('ProductController'),
            'pending' => $pendingProduct ? ['product' => $pendingProduct, 'quantity' => (int) $pending['quantity']] : null,
            'errors' => $errors,
        ] + $summary);
    }

    /** POST /cart/add: product_id and quantity. "replace=1" empties a cart of another shop first (the customer agreed). */
    public function add(): void
    {
        Auth::requireRole('customer');
        Csrf::check();
        [$productId, $quantity] = $this->readLine();
        $product = $this->checkProduct($productId);

        $cart = $this->cart();
        if ($cart['items'] && $cart['shop_id'] !== $product['shop_id']) {
            if (($_POST['replace'] ?? '') !== '1') {
                // Keep what the customer wanted to add: the cart page asks whether to empty the cart for it.
                $_SESSION['cart_pending'] = ['product_id' => $productId, 'quantity' => $quantity];
                flash('error', 'Your cart holds products of another shop. An order is prepared by one shop: empty your cart first to add ' . $product['name'] . '.');
                $this->redirect(route('cart'));
            }
            $cart = ['shop_id' => 0, 'items' => []];
        }

        $wanted = ($cart['items'][$productId] ?? 0) + $quantity;
        $this->checkQuantity($product, $wanted);
        $cart['shop_id'] = $product['shop_id'];
        $cart['items'][$productId] = $wanted;
        $_SESSION['cart'] = $cart;
        flash('success', $quantity . ' x ' . $product['name'] . ' added to your cart.');
        $this->redirect(route('cart'));
    }

    /** POST /cart/update: the new quantity of one line. */
    public function update(): void
    {
        Auth::requireRole('customer');
        Csrf::check();
        [$productId, $quantity] = $this->readLine();
        $cart = $this->cart();
        if (!isset($cart['items'][$productId])) {
            flash('error', 'This product is not in your cart.');
            $this->redirect(route('cart'));
        }
        $product = $this->checkProduct($productId);
        $this->checkQuantity($product, $quantity);
        $cart['items'][$productId] = $quantity;
        $_SESSION['cart'] = $cart;
        flash('success', 'Quantity of ' . $product['name'] . ' changed to ' . $quantity . '.');
        $this->redirect(route('cart'));
    }

    /** POST /cart/remove: one line out of the cart. */
    public function remove(): void
    {
        Auth::requireRole('customer');
        Csrf::check();
        $productId = (int) ($_POST['product_id'] ?? 0);
        $cart = $this->cart();
        unset($cart['items'][$productId]);
        $_SESSION['cart'] = $cart['items'] ? $cart : ['shop_id' => 0, 'items' => []];
        flash('success', 'The product is removed from your cart.');
        $this->redirect(route('cart'));
    }

    /** POST /cart/empty: every line out of the cart. */
    public function clear(): void
    {
        Auth::requireRole('customer');
        Csrf::check();
        $this->emptyCart();
        flash('success', 'Your cart is empty.');
        $this->redirect(route('cart'));
    }

    // =================================================================================================
    // USED BY OrderController (checkout)
    // =================================================================================================

    /**
     * My cart read again with the current product data:
     *   'items' => OrderItemModel[] (product id, name, unit price, quantity: what the order will copy),
     *   'stock' => [product id => stock], 'problems' => [product id => message] (inactive, gone, not enough stock),
     *   'shopId' => int, 'shopName' => string, 'subtotal' => float (computed here, on the server).
     */
    public function summary(): array
    {
        $cart = $this->cart();
        $items = [];
        $stock = [];
        $problems = [];
        $subtotal = 0.0;
        foreach ($cart['items'] as $productId => $quantity) {
            $product = $this->product((int) $productId);
            $item = new OrderItemModel();
            $item->setProductId((int) $productId);
            $item->setQuantity((int) $quantity);
            if ($product === null) {
                $item->setProductName('Product #' . $productId);
                $problems[$productId] = 'This product does not exist any more. Remove it from your cart.';
            } else {
                $item->setProductName($product['name']);
                $item->setUnitPrice($product['price']);
                $stock[$productId] = $product['stock'];
                if (!$product['active']) {
                    $problems[$productId] = 'This product is not sold any more. Remove it from your cart.';
                } elseif ($product['stock'] < $quantity) {
                    $problems[$productId] = $product['stock'] > 0
                        ? 'Only ' . $product['stock'] . ' left in stock. Lower the quantity.'
                        : 'This product is out of stock. Remove it from your cart.';
                }
            }
            $items[] = $item;
            $subtotal += $item->getLineTotal();
        }
        return [
            'items' => $items,
            'stock' => $stock,
            'problems' => $problems,
            'shopId' => $cart['shop_id'],
            'shopName' => $this->shopName($cart['shop_id']),
            'subtotal' => round($subtotal, 3),
        ];
    }

    /** After an order is placed. */
    public function emptyCart(): void
    {
        $_SESSION['cart'] = ['shop_id' => 0, 'items' => []];
    }

    /**
     * One product as the cart needs it: ['id', 'name', 'price', 'stock', 'shop_id', 'active'], or null when the product
     * does not exist or the products part is not merged yet. The only place that reads Islem's ProductModel.
     */
    public function product(int $id): ?array
    {
        if ($id < 1 || !class_exists('ProductController')) {
            return null;
        }
        $p = (new ProductController())->find($id);
        if ($p === null) {
            return null;
        }
        return [
            'id' => $p->getId(),
            'name' => $p->getName(),
            'price' => (float) $p->getPrice(),
            'stock' => (int) $p->getStock(),
            'shop_id' => (int) $p->getShopId(),
            'active' => (bool) $p->isActive(),
        ];
    }

    // =================================================================================================
    // CHECKS
    // =================================================================================================

    /** The cart of the session, always in the same shape. */
    private function cart(): array
    {
        $cart = $_SESSION['cart'] ?? [];
        return [
            'shop_id' => (int) ($cart['shop_id'] ?? 0),
            'items' => array_map('intval', is_array($cart['items'] ?? null) ? $cart['items'] : []),
        ];
    }

    /** product_id and quantity of the form, checked like in forms.js (same messages). Wrong -> back to the cart. */
    private function readLine(): array
    {
        $v = (new Validator($_POST))
            ->required('product_id')->int('product_id', 1)
            ->required('quantity')->int('quantity', 1, self::MAX_QUANTITY);
        $errors = $v->errors();
        if (($errors['quantity'] ?? '') === 'Number out of range.') {
            $errors['quantity'] = self::RANGE_MESSAGE;
        }
        if ($errors) {
            if (isset($errors['product_id'])) {
                flash('error', 'Choose a product.');
            } else {
                $_SESSION['errors'] = ['quantity_' . (int) $v->get('product_id') => $errors['quantity']];
                flash('error', 'Quantity: ' . $errors['quantity']);
            }
            $this->redirect($this->back());
        }
        return [(int) $v->get('product_id'), (int) $v->get('quantity')];
    }

    /** The product exists, is active and in stock, otherwise a clear message and back. */
    private function checkProduct(int $productId): array
    {
        if (!class_exists('ProductController')) {
            flash('error', 'Products cannot be ordered yet: the products part is not installed.');
            $this->redirect(route('cart'));
        }
        $product = $this->product($productId);
        if ($product === null) {
            flash('error', 'This product does not exist.');
            $this->redirect($this->back());
        }
        if (!$product['active']) {
            flash('error', $product['name'] . ' is not sold any more: it cannot be added to the cart.');
            $this->redirect($this->back());
        }
        if ($product['stock'] < 1) {
            flash('error', $product['name'] . ' is out of stock: it cannot be added to the cart.');
            $this->redirect($this->back());
        }
        return $product;
    }

    /** 1 to 99 and never more than the stock. */
    private function checkQuantity(array $product, int $quantity): void
    {
        if ($quantity > self::MAX_QUANTITY) {
            flash('error', 'At most ' . self::MAX_QUANTITY . ' of one product per order.');
            $this->redirect($this->back());
        }
        if ($quantity > $product['stock']) {
            flash('error', 'Only ' . $product['stock'] . ' of ' . $product['name'] . ' left in stock: you cannot have ' . $quantity . '.');
            $this->redirect($this->back());
        }
    }

    /** Back to the product page the form came from, or to the cart (only our own pages). */
    private function back(): string
    {
        if (($_POST['back'] ?? '') === 'product' && has_route('product')) {
            return route('product', ['id' => (int) ($_POST['product_id'] ?? 0)]);
        }
        return route('cart');
    }

    /** Name of the shop of the cart, from Mili's ShopController when the shops part is there. */
    private function shopName(int $shopId): string
    {
        if ($shopId < 1) {
            return '';
        }
        if (class_exists('ShopController') && method_exists('ShopController', 'findApproved')) {
            $shop = (new ShopController())->findApproved($shopId);
            if ($shop !== null) {
                return $shop->getName();
            }
        }
        return 'Shop #' . $shopId;
    }
}
