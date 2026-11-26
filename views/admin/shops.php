<?php
/**
 * S2 Mili - Administrator: the shops with their plan and owner (JOIN shop_plans and users), filtered by status.
 * Only the page content. Variables: $shops (ShopModel[]), $status (filter, '' = all), $statuses [key => label].
 */
$pageTitle = 'Shops';
require VIEW_PATH . '/partials/back-title.php';
require View::file('partials/user-ui');
$statusClass = [
    'pending' => 'bg-orange-50 text-orange-600 dark:bg-orange-500/15 dark:text-orange-400',
    'approved' => 'bg-green-50 text-green-600 dark:bg-green-500/15 dark:text-green-400',
    'rejected' => 'bg-red-50 text-red-600 dark:bg-red-500/15 dark:text-red-400',
];
?>
<div class="<?= $ui['card'] ?>">
  <div class="px-5 py-4 sm:px-6">
    <h3 class="text-lg font-semibold text-gray-800 dark:text-white/90"><?= count($shops) ?> shop<?= count($shops) === 1 ? '' : 's' ?></h3>
  </div>
  <form method="get" action="<?= e(route('admin-shops')) ?>" class="flex flex-wrap items-end gap-3 border-t border-gray-100 px-5 py-4 sm:px-6 dark:border-white/5" novalidate data-validate>
    <input type="hidden" name="page" value="admin-shops">
    <div>
      <label class="<?= $ui['label'] ?>" for="shopStatus">Status</label>
      <select class="<?= $ui['input'] ?>" id="shopStatus" name="status">
        <option value="">All statuses</option>
        <?php foreach ($statuses as $key => $label) : ?>
          <option value="<?= e($key) ?>" <?= $key === $status ? 'selected' : '' ?>><?= e($label) ?></option>
        <?php endforeach; ?>
      </select>
    </div>
    <button type="submit" class="<?= $ui['outline'] ?>">Filter</button>
    <?php if ($status !== '') : ?>
      <a class="sky-link text-sm font-medium" href="<?= e(route('admin-shops')) ?>">Clear the filter</a>
    <?php endif; ?>
  </form>
  <div class="max-w-full overflow-x-auto">
    <table class="min-w-full ds-table">
      <thead class="border-y border-gray-100 dark:border-white/5">
        <tr>
          <th class="<?= $ui['th'] ?>">Shop</th>
          <th class="<?= $ui['th'] ?>">Owner</th>
          <th class="<?= $ui['th'] ?>">Plan</th>
          <th class="<?= $ui['th'] ?>">Status</th>
          <th class="<?= $ui['th'] ?>">Created</th>
          <th class="<?= $ui['th'] ?>">Actions</th>
        </tr>
      </thead>
      <tbody class="divide-y divide-gray-100 dark:divide-white/5">
        <?php if (!$shops) : ?>
          <tr><td colspan="6" class="px-5 py-6 text-center text-sm text-gray-500 sm:px-6 dark:text-gray-400">No shop has this status.</td></tr>
        <?php endif; ?>
        <?php foreach ($shops as $shop) : ?>
          <tr class="transition hover:bg-gray-50/50 dark:hover:bg-white/2">
            <td class="px-5 py-4 text-start sm:px-6">
              <a class="<?= $ui['strong'] ?> sky-link" href="<?= e(route('admin-shop', ['id' => $shop->getId()])) ?>"><?= e($shop->getName()) ?></a>
              <span class="<?= $ui['small'] ?>"><?= e($shop->getAddress()) ?></span>
            </td>
            <td class="<?= $ui['td'] ?>">@<?= e($shop->getOwnerUsername()) ?></td>
            <td class="<?= $ui['td'] ?>"><?= e($shop->getPlanName()) ?></td>
            <td class="px-5 py-4 text-start sm:px-6">
              <span class="<?= $ui['badge'] ?> <?= $statusClass[$shop->getStatus()] ?? '' ?>"><?= e($shop->getStatusLabel()) ?></span>
            </td>
            <td class="<?= $ui['td'] ?>"><?= e(substr($shop->getCreatedAt(), 0, 10)) ?></td>
            <td class="<?= $ui['td'] ?>">
              <a class="sky-link text-sm font-medium" href="<?= e(route('admin-shop', ['id' => $shop->getId()])) ?>"><?= $shop->getStatus() === 'pending' ? 'Review' : 'Open' ?></a>
            </td>
          </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>
</div>
