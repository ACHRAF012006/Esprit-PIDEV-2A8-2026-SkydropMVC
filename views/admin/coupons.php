<?php
/**
 * S1 - Administrator: coupons. Only the page content.
 * Variables: $coupons (CouponModel[] with type name, kind and number of uses), $statuses [key => label],
 * $filters (status, q), $today (Y-m-d).
 */
$pageTitle = 'Coupons';
require VIEW_PATH . '/partials/back-title.php';
require View::file('partials/user-ui');
$tone = [
    'running' => 'bg-green-50 text-green-600',
    'scheduled' => 'bg-brand-50 text-brand-600',
    'expired' => 'bg-gray-100 text-gray-800 dark:bg-white/5 dark:text-white/80',
    'inactive' => 'bg-gray-100 text-gray-800 dark:bg-white/5 dark:text-white/80',
];
?>
<div class="<?= $ui['card'] ?>">
  <div class="flex flex-wrap items-center justify-between gap-3 px-5 py-4 sm:px-6">
    <h3 class="text-lg font-semibold text-gray-800 dark:text-white/90"><?= count($coupons) ?> coupon<?= count($coupons) === 1 ? '' : 's' ?></h3>
    <div class="flex flex-wrap items-center gap-2">
      <a class="<?= $ui['outline'] ?>" href="<?= e(route('admin-coupon-types')) ?>">Coupon types</a>
      <a class="<?= $ui['primary'] ?>" href="<?= e(route('admin-coupon-form')) ?>"><i class="bi bi-plus-lg" aria-hidden="true"></i> Add a coupon</a>
    </div>
  </div>
  <form method="get" action="<?= e(route('admin-coupons')) ?>" class="flex flex-wrap items-end gap-3 border-t border-gray-100 px-5 py-4 sm:px-6 dark:border-white/5">
    <input type="hidden" name="page" value="admin-coupons">
    <div>
      <label class="<?= $ui['label'] ?>" for="couponSearch">Code</label>
      <input class="<?= $ui['input'] ?>" type="search" id="couponSearch" name="q" value="<?= e($filters['q']) ?>" maxlength="20">
    </div>
    <div>
      <label class="<?= $ui['label'] ?>" for="couponStatus">Status</label>
      <select class="<?= $ui['input'] ?>" id="couponStatus" name="status">
        <option value="">Any status</option>
        <?php foreach ($statuses as $key => $label) : ?>
          <option value="<?= e($key) ?>" <?= $key === $filters['status'] ? 'selected' : '' ?>><?= e($label) ?></option>
        <?php endforeach; ?>
      </select>
    </div>
    <button type="submit" class="<?= $ui['outline'] ?>">Filter</button>
    <?php if ($filters['q'] !== '' || $filters['status'] !== '') : ?>
      <a class="sky-link text-sm font-medium" href="<?= e(route('admin-coupons')) ?>">Clear the filters</a>
    <?php endif; ?>
  </form>
  <div class="max-w-full overflow-x-auto">
    <table class="min-w-full">
      <thead class="border-y border-gray-100 dark:border-white/5">
        <tr>
          <th class="<?= $ui['th'] ?>">Code</th>
          <th class="<?= $ui['th'] ?>">Type</th>
          <th class="<?= $ui['th'] ?>">Value</th>
          <th class="<?= $ui['th'] ?>">Minimum order</th>
          <th class="<?= $ui['th'] ?>">Valid</th>
          <th class="<?= $ui['th'] ?>">Uses</th>
          <th class="<?= $ui['th'] ?>">Status</th>
          <th class="<?= $ui['th'] ?>">Actions</th>
        </tr>
      </thead>
      <tbody class="divide-y divide-gray-100 dark:divide-white/5">
        <?php if (!$coupons) : ?>
          <tr><td colspan="8" class="px-5 py-6 text-center text-sm text-gray-500 sm:px-6 dark:text-gray-400">No coupon matches these filters.</td></tr>
        <?php endif; ?>
        <?php foreach ($coupons as $c) :
            $state = !$c->isActive() ? 'inactive' : ($c->getEndsAt() < $today ? 'expired' : ($c->getStartsAt() > $today ? 'scheduled' : 'running'));
            $value = match ($c->getTypeKey()) {
                'percent' => rtrim(rtrim(number_format((float) $c->getValue(), 3, '.', ''), '0'), '.') . ' %',
                'fixed' => money((float) $c->getValue()),
                default => 'None',
            };
        ?>
          <tr class="transition hover:bg-gray-50/50 dark:hover:bg-white/2">
            <td class="px-5 py-4 text-start sm:px-6"><span class="<?= $ui['strong'] ?>"><?= e($c->getCode()) ?></span></td>
            <td class="<?= $ui['td'] ?>"><?= e($c->getTypeName()) ?></td>
            <td class="<?= $ui['td'] ?>"><?= e($value) ?></td>
            <td class="<?= $ui['td'] ?>"><?= $c->getMinOrder() > 0 ? e(money($c->getMinOrder())) : 'None' ?></td>
            <td class="<?= $ui['td'] ?>"><?= e($c->getStartsAt()) ?> to <?= e($c->getEndsAt()) ?></td>
            <td class="<?= $ui['td'] ?>"><?= $c->getUsesCount() ?><?= $c->getMaxUses() !== null ? ' / ' . $c->getMaxUses() : '' ?></td>
            <td class="px-5 py-4 text-start sm:px-6">
              <span class="<?= $ui['badge'] ?> <?= $tone[$state] ?>"><?= e($statuses[$state]) ?></span>
            </td>
            <td class="<?= $ui['td'] ?>">
              <div class="flex flex-wrap items-center gap-3">
                <a class="sky-link text-sm font-medium" href="<?= e(route('admin-coupon-form', ['id' => $c->getId()])) ?>">Edit</a>
                <form method="post" action="<?= e(route('admin-coupon-toggle', ['id' => $c->getId()])) ?>">
                  <?= csrf_field() ?>
                  <button type="submit" class="sky-link text-sm font-medium"><?= $c->isActive() ? 'Deactivate' : 'Activate' ?></button>
                </form>
                <?php if ($c->getUsesCount() === 0) : ?>
                  <form method="post" action="<?= e(route('admin-coupon-delete', ['id' => $c->getId()])) ?>">
                    <?= csrf_field() ?>
                    <button type="submit" class="sky-link text-sm font-medium" data-confirm="Delete the coupon <?= e($c->getCode()) ?>?">Delete</button>
                  </form>
                <?php endif; ?>
              </div>
            </td>
          </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>
</div>
