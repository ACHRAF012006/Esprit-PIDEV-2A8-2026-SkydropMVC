<?php
/**
 * S1 - Administrator overview. Only the page content: the admin layout adds sidebar, header, footer, scripts.
 * Accounts by role and the newest accounts, then the tiles of the modules (slot "admin-dashboard").
 * Variables: $byRole [role => count], $roleLabels [role => label], $total, $latest (User\UserModel[]),
 * $byStatus [active, deactivated], $weekLabels (Monday of each week, "3 Nov"), $weekCounts (new accounts that week).
 */
$pageTitle = 'Platform overview';
require VIEW_PATH . '/partials/back-title.php';
$icons = ['customer' => 'bi-person', 'shop_owner' => 'bi-shop', 'advertiser' => 'bi-megaphone', 'admin' => 'bi-shield-lock'];
$tiles = slot('admin-dashboard');
?>
<!-- Metric Group Start -->
<div class="grid grid-cols-1 gap-4 sm:grid-cols-2 md:gap-6 lg:grid-cols-4">
  <?php foreach ($byRole as $role => $count) : ?>
    <div class="rounded-2xl border border-gray-200 bg-white p-5 md:p-6 dark:border-gray-800 dark:bg-white/3">
      <div class="flex items-end justify-between">
        <div>
          <span class="ds-metric-label text-sm text-gray-500 dark:text-gray-400"><i class="bi <?= e($icons[$role] ?? 'bi-person') ?>" aria-hidden="true"></i><?= e($roleLabels[$role] ?? $role) ?></span>
          <h4 class="mt-2 text-title-sm font-bold text-gray-800 dark:text-white/90"><?= e(number_format($count)) ?></h4>
        </div>
        <a href="<?= e(route('admin-users', ['role' => $role])) ?>" class="sky-link text-sm font-medium">View</a>
      </div>
    </div>
  <?php endforeach; ?>
</div>
<!-- Metric Group End -->

<div class="mt-6 grid grid-cols-12 gap-6">
  <div class="rounded-2xl border border-gray-200 bg-white p-5 md:p-6 col-span-12 xl:col-span-7 dark:border-gray-800 dark:bg-white/3">
    <h3 class="text-lg font-semibold text-gray-800 dark:text-white/90">New accounts per week</h3>
    <p class="text-sm text-gray-500 dark:text-gray-400">The last 8 weeks, week starting on Monday.</p>
    <div class="mt-4 h-53">
      <canvas id="chartNewAccounts" role="img" aria-label="New accounts per week: <?= e(implode(', ', array_map(static fn ($l, $n) => "$l $n", $weekLabels, $weekCounts))) ?>"
              data-labels="<?= e(json_encode($weekLabels)) ?>" data-values="<?= e(json_encode($weekCounts)) ?>"></canvas>
    </div>
  </div>
  <div class="col-span-12 rounded-2xl border border-gray-200 bg-white p-5 md:p-6 xl:col-span-5 dark:border-gray-800 dark:bg-white/3">
    <h3 class="text-lg font-semibold text-gray-800 dark:text-white/90">Active and deactivated</h3>
    <dl class="mt-4 flex flex-col gap-3">
      <?php foreach (['active' => 'Active accounts', 'deactivated' => 'Deactivated accounts'] as $key => $label) : ?>
        <div class="flex items-center justify-between">
          <dt class="text-sm text-gray-500 dark:text-gray-400"><?= e($label) ?></dt>
          <dd><a class="sky-link text-sm font-medium" href="<?= e(route('admin-users', ['status' => $key])) ?>"><?= (int) $byStatus[$key] ?></a></dd>
        </div>
      <?php endforeach; ?>
    </dl>
    <div class="mt-4 h-53">
      <canvas id="chartStatus" role="img" aria-label="<?= (int) $byStatus['active'] ?> active, <?= (int) $byStatus['deactivated'] ?> deactivated"
              data-labels="<?= e(json_encode(['Active', 'Deactivated'])) ?>" data-values="<?= e(json_encode([$byStatus['active'], $byStatus['deactivated']])) ?>"></canvas>
    </div>
  </div>
</div>

<div class="mt-6 overflow-hidden rounded-2xl border border-gray-200 bg-white dark:border-white/5 dark:bg-white/3">
  <div class="flex items-center justify-between px-5 py-4 sm:px-6">
    <h3 class="text-lg font-semibold text-gray-800 dark:text-white/90">Newest accounts</h3>
    <a href="<?= e(route('admin-users')) ?>" class="sky-link text-sm font-medium">All <?= (int) $total ?> accounts</a>
  </div>
  <div class="max-w-full overflow-x-auto">
    <table class="min-w-full">
      <thead class="border-b border-gray-100 dark:border-white/5">
        <tr>
          <th class="px-5 py-3 text-start text-xs font-medium text-gray-500 sm:px-6 dark:text-gray-400">User</th>
          <th class="px-5 py-3 text-start text-xs font-medium text-gray-500 sm:px-6 dark:text-gray-400">Role</th>
          <th class="px-5 py-3 text-start text-xs font-medium text-gray-500 sm:px-6 dark:text-gray-400">Registered</th>
        </tr>
      </thead>
      <tbody class="divide-y divide-gray-100 dark:divide-white/5">
        <?php foreach ($latest as $u) : ?>
          <tr>
            <td class="px-5 py-4 text-start sm:px-6">
              <span class="block text-sm font-medium text-gray-800 dark:text-white/90"><?= e($u->getFullName()) ?></span>
              <span class="block text-xs text-gray-500 dark:text-gray-400"><?= e($u->getEmail()) ?></span>
            </td>
            <td class="px-5 py-4 text-start text-sm text-gray-500 sm:px-6 dark:text-gray-400"><?= e($u->getRoleLabel()) ?></td>
            <td class="px-5 py-4 text-start text-sm text-gray-500 sm:px-6 dark:text-gray-400"><?= e(substr($u->getCreatedAt(), 0, 10)) ?></td>
          </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>
</div>

<?php if (trim($tiles) !== '') : ?>
  <div class="mt-6"><?= $tiles ?></div>
<?php endif; ?>
