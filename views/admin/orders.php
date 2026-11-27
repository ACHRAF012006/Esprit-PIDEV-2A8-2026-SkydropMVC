<?php
/**
 * S4 Zamel - Administrator: every order (JOIN users and shops), filters by status and by date. Only the page content.
 * Variables: $orders (OrderModel[] with customerName, shopName, itemCount), $filters (status, from, to and, for a
 * wrong date, from_typed / to_typed), $statuses [key => label], $errors (field => message).
 */
$pageTitle = 'Orders';
require VIEW_PATH . '/partials/back-title.php';
require View::file('partials/user-ui');
$typed = static fn (string $key): string => (string) ($filters[$key . '_typed'] ?? $filters[$key]);
$filtered = $filters['status'] !== '' || $typed('from') !== '' || $typed('to') !== '';
?>
<div class="<?= $ui['card'] ?>">
  <div class="flex flex-wrap items-center justify-between gap-3 px-5 py-4 sm:px-6">
    <h3 class="text-lg font-semibold text-gray-800 dark:text-white/90"><?= count($orders) ?> order<?= count($orders) === 1 ? '' : 's' ?></h3>
  </div>
  <form method="get" action="<?= e(route('admin-orders')) ?>" class="flex flex-wrap items-start gap-3 border-t border-gray-100 px-5 py-4 sm:px-6 dark:border-white/5" novalidate data-validate>
    <input type="hidden" name="page" value="admin-orders">
    <div>
      <label class="<?= $ui['label'] ?>" for="orderStatus">Status</label>
      <select class="<?= $ui['input'] ?>" id="orderStatus" name="status">
        <option value="">All statuses</option>
        <?php foreach ($statuses as $key => $label) : ?>
          <option value="<?= e($key) ?>" <?= $key === $filters['status'] ? 'selected' : '' ?>><?= e($label) ?></option>
        <?php endforeach; ?>
      </select>
    </div>
    <div>
      <label class="<?= $ui['label'] ?>" for="from">Placed from</label>
      <input class="<?= $field('from') ?>" type="date" id="from" name="from" value="<?= e($typed('from')) ?>"
             data-pattern="^\d{4}-\d{2}-\d{2}$" data-pattern-message="<?= e(AdminOrderController::DATE_MESSAGE) ?>">
      <?= $error('from') ?>
    </div>
    <div>
      <label class="<?= $ui['label'] ?>" for="to">Placed until</label>
      <input class="<?= $field('to') ?>" type="date" id="to" name="to" value="<?= e($typed('to')) ?>"
             data-pattern="^\d{4}-\d{2}-\d{2}$" data-pattern-message="<?= e(AdminOrderController::DATE_MESSAGE) ?>" data-after="from">
      <?= $error('to') ?>
    </div>
    <div class="flex items-center gap-3 pt-7">
      <button type="submit" class="<?= $ui['outline'] ?>">Filter</button>
      <?php if ($filtered) : ?>
        <a class="sky-link text-sm font-medium" href="<?= e(route('admin-orders')) ?>">Clear the filters</a>
      <?php endif; ?>
    </div>
  </form>
  <div class="max-w-full overflow-x-auto">
    <table class="min-w-full ds-table">
      <thead class="border-y border-gray-100 dark:border-white/5">
        <tr>
          <th class="<?= $ui['th'] ?>">Order</th>
          <th class="<?= $ui['th'] ?>">Customer</th>
          <th class="<?= $ui['th'] ?>">Shop</th>
          <th class="<?= $ui['th'] ?>">Placed</th>
          <th class="<?= $ui['th'] ?>">Articles</th>
          <th class="<?= $ui['th'] ?>">Status</th>
          <th class="<?= $ui['th'] ?>">Total</th>
        </tr>
      </thead>
      <tbody class="divide-y divide-gray-100 dark:divide-white/5">
        <?php if (!$orders) : ?>
          <tr><td colspan="7" class="px-5 py-6 text-center text-sm text-gray-500 sm:px-6 dark:text-gray-400">No order matches these filters.</td></tr>
        <?php endif; ?>
        <?php foreach ($orders as $o) : ?>
          <tr class="transition hover:bg-gray-50/50 dark:hover:bg-white/2">
            <td class="px-5 py-4 text-start sm:px-6"><a class="<?= $ui['strong'] ?> sky-link" href="<?= e(route('admin-order', ['id' => $o->getId()])) ?>">#<?= (int) $o->getId() ?></a></td>
            <td class="<?= $ui['td'] ?>"><?= e($o->getCustomerName()) ?></td>
            <td class="<?= $ui['td'] ?>"><?= e($o->getShopName()) ?></td>
            <td class="<?= $ui['td'] ?>"><?= e(substr($o->getPlacedAt(), 0, 16)) ?></td>
            <td class="<?= $ui['td'] ?>"><?= (int) $o->getItemCount() ?></td>
            <td class="px-5 py-4 text-start sm:px-6"><span class="<?= $ui['badge'] ?> <?= e(OrderController::statusBadge($o->getStatus())) ?>"><?= e($o->getStatusLabel()) ?></span></td>
            <td class="<?= $ui['td'] ?>"><?= e(OrderController::price($o->getTotal())) ?></td>
          </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>
</div>
