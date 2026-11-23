<?php
/**
 * S1 - Administrator: coupon types. Only the page content.
 * Variables: $types (CouponTypeModel[], with the number of coupons of each type), $kinds [key => label].
 */
$pageTitle = 'Coupon types';
require VIEW_PATH . '/partials/back-title.php';
require View::file('partials/user-ui');
?>
<div class="<?= $ui['card'] ?>">
  <div class="flex flex-wrap items-center justify-between gap-3 px-5 py-4 sm:px-6">
    <h3 class="text-lg font-semibold text-gray-800 dark:text-white/90"><?= count($types) ?> coupon type<?= count($types) === 1 ? '' : 's' ?></h3>
    <div class="flex flex-wrap items-center gap-2">
      <a class="<?= $ui['outline'] ?>" href="<?= e(route('admin-coupons')) ?>">Coupons</a>
      <?php if (count($types) < count($kinds)) : ?>
        <a class="<?= $ui['primary'] ?>" href="<?= e(route('admin-coupon-type-form')) ?>"><i class="bi bi-plus-lg" aria-hidden="true"></i> Add a coupon type</a>
      <?php endif; ?>
    </div>
  </div>
  <div class="max-w-full overflow-x-auto">
    <table class="min-w-full">
      <thead class="border-y border-gray-100 dark:border-white/5">
        <tr>
          <th class="<?= $ui['th'] ?>">Type</th>
          <th class="<?= $ui['th'] ?>">Kind</th>
          <th class="<?= $ui['th'] ?>">Needs a value</th>
          <th class="<?= $ui['th'] ?>">Coupons</th>
          <th class="<?= $ui['th'] ?>">Status</th>
          <th class="<?= $ui['th'] ?>">Actions</th>
        </tr>
      </thead>
      <tbody class="divide-y divide-gray-100 dark:divide-white/5">
        <?php if (!$types) : ?>
          <tr><td colspan="6" class="px-5 py-6 text-center text-sm text-gray-500 sm:px-6 dark:text-gray-400">No coupon type yet. Add one to create coupons.</td></tr>
        <?php endif; ?>
        <?php foreach ($types as $t) : ?>
          <tr class="transition hover:bg-gray-50/50 dark:hover:bg-white/2">
            <td class="px-5 py-4 text-start sm:px-6">
              <span class="<?= $ui['strong'] ?>"><?= e($t->getName()) ?></span>
              <span class="<?= $ui['small'] ?>"><?= e($t->getDescription()) ?></span>
            </td>
            <td class="<?= $ui['td'] ?>"><?= e($kinds[$t->getKeyName()] ?? $t->getKeyName()) ?></td>
            <td class="<?= $ui['td'] ?>"><?= $t->needsValue() ? 'Yes' : 'No' ?></td>
            <td class="<?= $ui['td'] ?>"><?= $t->getCouponCount() ?></td>
            <td class="px-5 py-4 text-start sm:px-6">
              <span class="<?= $ui['badge'] ?> <?= $ui['status'][$t->isActive() ? 'active' : 'deactivated'] ?>"><?= $t->isActive() ? 'Active' : 'Inactive' ?></span>
            </td>
            <td class="<?= $ui['td'] ?>">
              <div class="flex flex-wrap items-center gap-3">
                <a class="sky-link text-sm font-medium" href="<?= e(route('admin-coupon-type-form', ['id' => $t->getId()])) ?>">Edit</a>
                <?php if ($t->getCouponCount() === 0) : ?>
                  <form method="post" action="<?= e(route('admin-coupon-type-delete', ['id' => $t->getId()])) ?>">
                    <?= csrf_field() ?>
                    <button type="submit" class="sky-link text-sm font-medium" data-confirm="Delete the coupon type <?= e($t->getName()) ?>?">Delete</button>
                  </form>
                <?php else : ?>
                  <span class="<?= $ui['small'] ?>">In use, cannot be deleted</span>
                <?php endif; ?>
              </div>
            </td>
          </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>
</div>
