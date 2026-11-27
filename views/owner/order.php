<?php
/**
 * S4 Zamel - Shop owner: one order of my shop with its lines and the button to the next status. Only the page content.
 * Variables: $order (OrderModel), $items (OrderItemModel[]), $next (the next status key, or null when there is none).
 */
$pageTitle = 'Order #' . $order->getId();
require VIEW_PATH . '/partials/back-title.php';
require View::file('partials/user-ui');
?>
<div class="grid grid-cols-12 gap-6">
  <div class="<?= $ui['card'] ?> col-span-12 xl:col-span-8">
    <div class="flex flex-wrap items-center justify-between gap-3 px-5 py-4 sm:px-6">
      <h3 class="text-lg font-semibold text-gray-800 dark:text-white/90">Lines</h3>
      <span class="<?= $ui['badge'] ?> <?= e(OrderController::statusBadge($order->getStatus())) ?>"><?= e($order->getStatusLabel()) ?></span>
    </div>
    <div class="max-w-full overflow-x-auto">
      <table class="min-w-full ds-table">
        <thead class="border-y border-gray-100 dark:border-white/5">
          <tr>
            <th class="<?= $ui['th'] ?>">Product</th>
            <th class="<?= $ui['th'] ?>">Unit price</th>
            <th class="<?= $ui['th'] ?>">Quantity</th>
            <th class="<?= $ui['th'] ?>">Price</th>
          </tr>
        </thead>
        <tbody class="divide-y divide-gray-100 dark:divide-white/5">
          <?php foreach ($items as $item) : ?>
            <tr>
              <td class="px-5 py-4 text-start sm:px-6"><span class="<?= $ui['strong'] ?>"><?= e($item->getProductName()) ?></span></td>
              <td class="<?= $ui['td'] ?>"><?= e(OrderController::price($item->getUnitPrice())) ?></td>
              <td class="<?= $ui['td'] ?>"><?= (int) $item->getQuantity() ?></td>
              <td class="<?= $ui['td'] ?>"><?= e(OrderController::price($item->getLineTotal())) ?></td>
            </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>
  </div>

  <div class="<?= $ui['card'] ?> col-span-12 p-5 sm:p-6 xl:col-span-4">
    <dl class="grid grid-cols-1 gap-4">
      <div><dt class="<?= $ui['small'] ?>">Customer</dt><dd class="<?= $ui['strong'] ?>"><?= e($order->getCustomerName()) ?></dd></div>
      <div><dt class="<?= $ui['small'] ?>">Placed</dt><dd class="<?= $ui['strong'] ?>"><?= e(substr($order->getPlacedAt(), 0, 16)) ?></dd></div>
      <div><dt class="<?= $ui['small'] ?>">Total (<?= (int) $order->getItemCount() ?> articles)</dt><dd class="<?= $ui['strong'] ?>"><?= e(OrderController::price($order->getTotal())) ?></dd></div>
      <div><dt class="<?= $ui['small'] ?>">Payment <span class="simulation-badge">Simulation</span></dt><dd class="<?= $ui['strong'] ?>"><?= $order->getPaymentStatus() === 'paid' ? 'Paid' : 'To pay on pickup' ?></dd></div>
      <?php if ($order->getNote() !== null) : ?>
        <div><dt class="<?= $ui['small'] ?>">Note of the customer</dt><dd class="<?= $ui['strong'] ?>"><?= e($order->getNote()) ?></dd></div>
      <?php endif; ?>
    </dl>
    <div class="mt-6">
      <?php if ($next !== null) : ?>
        <form method="post" action="<?= e(route('owner-order-status', ['id' => $order->getId()])) ?>">
          <?= csrf_field() ?>
          <input type="hidden" name="status" value="<?= e($next) ?>">
          <button type="submit" class="<?= $ui['primary'] ?>">Mark as <?= e(strtolower(OrderModel::STATUSES[$next])) ?></button>
        </form>
        <p class="<?= $ui['help'] ?>">An order moves forward one step at a time: placed, preparing, ready, delivered.</p>
      <?php else : ?>
        <p class="<?= $ui['small'] ?>">This order is <?= e(strtolower($order->getStatusLabel())) ?>: its status cannot change any more.</p>
      <?php endif; ?>
      <a class="sky-link mt-4 inline-block text-sm font-medium" href="<?= e(route('owner-orders')) ?>">Back to the orders</a>
    </div>
  </div>
</div>
