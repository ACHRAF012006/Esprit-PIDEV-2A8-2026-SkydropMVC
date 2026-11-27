<?php
/**
 * S4 Zamel - Shop owner: the orders of my shop, with a status filter. Only the page content.
 * Variables: $orders (OrderModel[] with customerName and itemCount from the JOINs), $status (filter), $statuses
 * [key => label], $shopsReady (false while the shops part is not installed).
 */
$pageTitle = 'Orders';
require VIEW_PATH . '/partials/back-title.php';
require View::file('partials/user-ui');
?>
<div class="<?= $ui['card'] ?>">
  <div class="flex flex-wrap items-center justify-between gap-3 px-5 py-4 sm:px-6">
    <h3 class="text-lg font-semibold text-gray-800 dark:text-white/90"><?= count($orders) ?> order<?= count($orders) === 1 ? '' : 's' ?> of my shop</h3>
    <form method="get" action="<?= e(route('owner-orders')) ?>" class="flex flex-wrap items-end gap-3">
      <input type="hidden" name="page" value="owner-orders">
      <div>
        <label class="<?= $ui['label'] ?>" for="orderStatus">Status</label>
        <select class="<?= $ui['input'] ?>" id="orderStatus" name="status">
          <option value="">All statuses</option>
          <?php foreach ($statuses as $key => $label) : ?>
            <option value="<?= e($key) ?>" <?= $key === $status ? 'selected' : '' ?>><?= e($label) ?></option>
          <?php endforeach; ?>
        </select>
      </div>
      <button type="submit" class="<?= $ui['outline'] ?>">Filter</button>
      <?php if ($status !== '') : ?>
        <a class="sky-link text-sm font-medium" href="<?= e(route('owner-orders')) ?>">Clear the filter</a>
      <?php endif; ?>
    </form>
  </div>
  <div class="max-w-full overflow-x-auto">
    <table class="min-w-full ds-table">
      <thead class="border-y border-gray-100 dark:border-white/5">
        <tr>
          <th class="<?= $ui['th'] ?>">Order</th>
          <th class="<?= $ui['th'] ?>">Customer</th>
          <th class="<?= $ui['th'] ?>">Placed</th>
          <th class="<?= $ui['th'] ?>">Articles</th>
          <th class="<?= $ui['th'] ?>">Status</th>
          <th class="<?= $ui['th'] ?>">Total</th>
        </tr>
      </thead>
      <tbody class="divide-y divide-gray-100 dark:divide-white/5">
        <?php if (!$orders) : ?>
          <tr><td colspan="6" class="px-5 py-6 text-center text-sm text-gray-500 sm:px-6 dark:text-gray-400">
            <?= $shopsReady ? 'No order matches this filter.' : 'The orders of your shop appear here once the shops part is installed.' ?>
          </td></tr>
        <?php endif; ?>
        <?php foreach ($orders as $o) : ?>
          <tr class="transition hover:bg-gray-50/50 dark:hover:bg-white/2">
            <td class="px-5 py-4 text-start sm:px-6"><a class="<?= $ui['strong'] ?> sky-link" href="<?= e(route('owner-order', ['id' => $o->getId()])) ?>">#<?= (int) $o->getId() ?></a></td>
            <td class="<?= $ui['td'] ?>"><?= e($o->getCustomerName()) ?></td>
            <td class="<?= $ui['td'] ?>"><?= e(substr($o->getPlacedAt(), 0, 16)) ?></td>
            <td class="<?= $ui['td'] ?>"><?= (int) $o->getItemCount() ?></td>
            <td class="px-5 py-4 text-start sm:px-6"><span class="<?= $ui['badge'] ?> <?= e(OrderController::statusBadge($o->getStatus())) ?>"><?= e($o->getStatusLabel()) ?></span></td>
            <td class="<?= $ui['td'] ?> whitespace-nowrap"><?= e(OrderController::price($o->getTotal())) ?></td>
          </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>
</div>
