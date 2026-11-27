<?php
/**
 * S4 Zamel - My cart. Only the page content.
 * Variables: $items (OrderItemModel[], the cart lines with the current name and price), $stock [product id => stock],
 * $problems [product id => message], $shopId, $shopName, $subtotal (computed by the server), $productsReady (bool),
 * $pending (null or ['product' => [...], 'quantity' => n]: a product of another shop waiting), $errors.
 */
$bannerTitle = 'My cart';
$bannerLead = $items ? 'Products of ' . $shopName . '. One order is prepared by one shop.' : '';
require VIEW_PATH . '/partials/page-banner.php';
?>
    <div class="container ds-main">
        <?php if ($pending) : ?>
            <div class="ds-card mb-4">
                <div class="ds-card-body">
                    <h2 class="h5 mb-2">Start a new cart?</h2>
                    <p class="mb-3">Your cart holds products of <?= e($shopName) ?>. To add <strong><?= (int) $pending['quantity'] ?> x <?= e($pending['product']['name']) ?></strong>, your cart is emptied first.</p>
                    <div class="d-flex flex-wrap gap-2">
                        <form method="post" action="<?= e(route('cart-add')) ?>" class="m-0">
                            <?= csrf_field() ?>
                            <input type="hidden" name="product_id" value="<?= (int) $pending['product']['id'] ?>">
                            <input type="hidden" name="quantity" value="<?= (int) $pending['quantity'] ?>">
                            <input type="hidden" name="replace" value="1">
                            <button type="submit" class="btn btn-primary" data-confirm="Empty your cart and add <?= e($pending['product']['name']) ?>?">Empty my cart and add it</button>
                        </form>
                        <a class="btn btn-outline" href="<?= e(route('cart')) ?>">Keep my cart</a>
                    </div>
                </div>
            </div>
        <?php endif; ?>

        <?php if (!$items) : ?>
            <div class="ds-empty">
                <h2 class="h5">Your cart is empty</h2>
                <?php if ($productsReady) : ?>
                    <p>Add products from a shop page, then come back here to order them.</p>
                <?php else : ?>
                    <p>Products can be added as soon as the products part is installed.</p>
                <?php endif; ?>
                <a class="btn btn-outline" href="<?= e(route('my-orders')) ?>">See my orders</a>
            </div>
        <?php else : ?>
            <div class="row g-4">
                <div class="col-lg-8">
                    <div class="ds-card">
                        <div class="ds-card-body">
                            <div class="table-responsive">
                                <table class="ds-table">
                                    <thead>
                                        <tr><th>Product</th><th>Quantity</th><th class="ds-right">Price</th><th><span class="visually-hidden">Remove</span></th></tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($items as $item) : ?>
                                            <?php $pid = $item->getProductId(); $fieldId = 'quantity_' . $pid; ?>
                                            <tr>
                                                <td>
                                                    <strong><?= e($item->getProductName()) ?></strong>
                                                    <span class="d-block ds-muted small"><?= e(OrderController::price($item->getUnitPrice())) ?> each<?= isset($stock[$pid]) ? ', ' . (int) $stock[$pid] . ' in stock' : '' ?></span>
                                                    <?php if (isset($problems[$pid])) : ?>
                                                        <span class="ds-tag ds-tag-danger mt-1"><?= e($problems[$pid]) ?></span>
                                                    <?php endif; ?>
                                                </td>
                                                <td>
                                                    <form method="post" action="<?= e(route('cart-update')) ?>" class="d-flex flex-wrap align-items-start gap-2 m-0" novalidate data-validate>
                                                        <?= csrf_field() ?>
                                                        <input type="hidden" name="product_id" value="<?= (int) $pid ?>">
                                                        <div>
                                                            <label class="visually-hidden" for="<?= e($fieldId) ?>">Quantity of <?= e($item->getProductName()) ?></label>
                                                            <input type="number" class="form-control <?= isset($errors[$fieldId]) ? 'is-invalid' : '' ?>" id="<?= e($fieldId) ?>" name="quantity"
                                                                   value="<?= (int) $item->getQuantity() ?>" min="1" max="99" inputmode="numeric"
                                                                   data-stepper data-required data-integer data-min="1" data-max="99">
                                                            <?php if (isset($errors[$fieldId])) : ?><div class="invalid-feedback d-block"><?= e($errors[$fieldId]) ?></div><?php endif; ?>
                                                        </div>
                                                        <button type="submit" class="btn btn-sm btn-outline">Update</button>
                                                    </form>
                                                </td>
                                                <td class="ds-right"><?= e(OrderController::price($item->getLineTotal())) ?></td>
                                                <td class="ds-right">
                                                    <form method="post" action="<?= e(route('cart-remove')) ?>" class="m-0">
                                                        <?= csrf_field() ?>
                                                        <input type="hidden" name="product_id" value="<?= (int) $pid ?>">
                                                        <button type="submit" class="btn btn-sm btn-outline" data-confirm="Remove <?= e($item->getProductName()) ?> from your cart?">Remove</button>
                                                    </form>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                    <form method="post" action="<?= e(route('cart-empty')) ?>" class="mt-3">
                        <?= csrf_field() ?>
                        <button type="submit" class="btn btn-sm btn-outline" data-confirm="Remove every product from your cart?">Empty my cart</button>
                    </form>
                </div>

                <div class="col-lg-4">
                    <div class="ds-card">
                        <div class="ds-card-body">
                            <h2 class="h5 mb-3">Summary</h2>
                            <dl class="ds-rows mb-3">
                                <div><dt>Shop</dt><dd><?= e($shopName) ?></dd></div>
                                <div><dt>Articles</dt><dd><?= (int) array_sum(array_map(static fn ($i) => $i->getQuantity(), $items)) ?></dd></div>
                                <div class="ds-total"><dt>Subtotal</dt><dd><?= e(OrderController::price($subtotal)) ?></dd></div>
                            </dl>
                            <?php if ($problems) : ?>
                                <p class="ds-muted small">Fix the products marked in red to check out.</p>
                            <?php else : ?>
                                <a class="btn btn-primary w-100" href="<?= e(route('checkout')) ?>">Go to checkout</a>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>
        <?php endif; ?>
    </div>
