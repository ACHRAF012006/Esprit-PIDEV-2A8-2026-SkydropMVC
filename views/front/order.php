<?php
/**
 * S4 Zamel - One of my orders: progress, lines, totals, cancel while it is still 'placed'. Only the page content.
 * Variables: $order (OrderModel), $items (OrderItemModel[]).
 */
$bannerTitle = 'Order #' . $order->getId();
$bannerParents = [['My orders', 'my-orders']];
require VIEW_PATH . '/partials/page-banner.php';
$steps = ['placed', 'preparing', 'ready', 'delivered'];
$reached = array_search($order->getStatus(), $steps, true);
?>
    <div class="container ds-main">
        <div class="row g-4">
            <div class="col-lg-8">
                <div class="ds-card">
                    <div class="ds-card-body">
                        <div class="d-flex flex-wrap align-items-center justify-content-between gap-2 mb-3">
                            <h2 class="h5 mb-0"><?= e($order->getShopName()) ?></h2>
                            <span class="<?= e(OrderController::statusTag($order->getStatus())) ?>"><?= e($order->getStatusLabel()) ?></span>
                        </div>
                        <?php if ($reached !== false) : ?>
                            <ol class="order-steps mb-3" aria-label="Progress of the order">
                                <?php foreach ($steps as $i => $step) : ?>
                                    <li class="<?= $i < $reached ? 'is-done' : ($i === $reached ? 'is-current' : '') ?>" <?= $i === $reached ? 'aria-current="step"' : '' ?>><?= e(OrderModel::STATUSES[$step]) ?></li>
                                <?php endforeach; ?>
                            </ol>
                        <?php endif; ?>
                        <div class="table-responsive">
                            <table class="ds-table">
                                <thead><tr><th>Product</th><th>Unit price</th><th>Quantity</th><th class="ds-right">Price</th></tr></thead>
                                <tbody>
                                    <?php foreach ($items as $item) : ?>
                                        <tr>
                                            <td><?= e($item->getProductName()) ?></td>
                                            <td><?= e(OrderController::price($item->getUnitPrice())) ?></td>
                                            <td><?= (int) $item->getQuantity() ?></td>
                                            <td class="ds-right"><?= e(OrderController::price($item->getLineTotal())) ?></td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-lg-4">
                <div class="ds-card">
                    <div class="ds-card-body">
                        <h2 class="h5 mb-3">Summary</h2>
                        <dl class="ds-rows mb-3">
                            <div><dt>Placed</dt><dd><?= e(substr($order->getPlacedAt(), 0, 16)) ?></dd></div>
                            <div><dt>Subtotal</dt><dd><?= e(OrderController::price($order->getSubtotal())) ?></dd></div>
                            <?php if ($order->getDiscount() > 0) : ?>
                                <div><dt>Coupon <?= e((string) $order->getCouponCode()) ?></dt><dd>- <?= e(OrderController::price($order->getDiscount())) ?></dd></div>
                            <?php endif; ?>
                            <div class="ds-total"><dt>Total</dt><dd><?= e(OrderController::price($order->getTotal())) ?></dd></div>
                            <div><dt>Payment <span class="simulation-badge">Simulation</span></dt><dd><?= $order->getPaymentStatus() === 'paid' ? 'Paid' : 'To pay on pickup' ?></dd></div>
                        </dl>
                        <?php if ($order->getNote() !== null) : ?>
                            <p class="mb-3"><span class="ds-muted">Note:</span> <?= e($order->getNote()) ?></p>
                        <?php endif; ?>
                        <?php if ($order->getStatus() === 'placed') : ?>
                            <form method="post" action="<?= e(route('order-cancel', ['id' => $order->getId()])) ?>" class="m-0">
                                <?= csrf_field() ?>
                                <button type="submit" class="btn btn-outline w-100" data-confirm="Cancel order #<?= (int) $order->getId() ?>? The products go back into stock.">Cancel this order</button>
                            </form>
                            <p class="ds-muted small mt-2 mb-0">You can cancel until the shop starts preparing it.</p>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
