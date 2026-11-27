<?php
/**
 * S4 Zamel - My orders, newest first. Only the page content.
 * Variables: $orders (OrderModel[], with shopName and itemCount from the JOINs).
 */
$bannerTitle = 'My orders';
require VIEW_PATH . '/partials/page-banner.php';
?>
    <div class="container ds-main">
        <?php if (!$orders) : ?>
            <div class="ds-empty">
                <h2 class="h5">No order yet</h2>
                <p>Your orders appear here once you check out a cart.</p>
                <a class="btn btn-outline" href="<?= e(route('cart')) ?>">Open my cart</a>
            </div>
        <?php else : ?>
            <div class="ds-card">
                <div class="ds-card-body">
                    <div class="table-responsive">
                        <table class="ds-table">
                            <thead>
                                <tr><th>Order</th><th>Shop</th><th>Date</th><th>Articles</th><th>Status</th><th class="ds-right">Total</th></tr>
                            </thead>
                            <tbody>
                                <?php foreach ($orders as $o) : ?>
                                    <tr>
                                        <td><a href="<?= e(route('order', ['id' => $o->getId()])) ?>">#<?= (int) $o->getId() ?></a></td>
                                        <td><?= e($o->getShopName()) ?></td>
                                        <td><?= e(substr($o->getPlacedAt(), 0, 16)) ?></td>
                                        <td><?= (int) $o->getItemCount() ?></td>
                                        <td><span class="<?= e(OrderController::statusTag($o->getStatus())) ?>"><?= e($o->getStatusLabel()) ?></span></td>
                                        <td class="ds-right"><?= e(OrderController::price($o->getTotal())) ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        <?php endif; ?>
    </div>
