<?php
/**
 * S4 Zamel - Slot "profile": my last 3 orders. A block of the profile page, not a page.
 * Variables: $user (UserModel, the signed-in user), passed by the profile page. The data comes from
 * OrderController::ofCustomer() (the SQL stays in the controller). Customers only.
 */
if ($user->getRole() !== 'customer') {
    return;
}
$orders = (new OrderController())->ofCustomer($user->getId(), 3);
?>
<div class="ds-card mb-4" id="my-last-orders">
    <div class="ds-card-body">
        <div class="ds-section-head">
            <h2 class="h4">My last orders</h2>
            <a href="<?= e(route('my-orders')) ?>">See all my orders</a>
        </div>
        <?php if (!$orders) : ?>
            <p class="ds-muted mb-0">You have not ordered yet.</p>
        <?php else : ?>
            <div class="table-responsive">
                <table class="ds-table">
                    <thead><tr><th>Order</th><th>Date</th><th>Status</th><th class="ds-right">Total</th></tr></thead>
                    <tbody>
                        <?php foreach ($orders as $o) : ?>
                            <tr>
                                <td><a href="<?= e(route('order', ['id' => $o->getId()])) ?>">#<?= (int) $o->getId() ?></a> <span class="ds-muted"><?= e($o->getShopName()) ?></span></td>
                                <td><?= e(substr($o->getPlacedAt(), 0, 10)) ?></td>
                                <td><span class="<?= e(OrderController::statusTag($o->getStatus())) ?>"><?= e($o->getStatusLabel()) ?></span></td>
                                <td class="ds-right order-price"><?= e(OrderController::price($o->getTotal())) ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </div>
</div>
