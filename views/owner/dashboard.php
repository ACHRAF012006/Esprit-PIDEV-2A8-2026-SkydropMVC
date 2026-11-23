<?php
/**
 * S1 - Back-office overview for shop owners and advertisers. Only the page content.
 * The tiles come from the modules (slot "owner-dashboard": my shop and plan, products, orders, ads...).
 * Variables: $user (User\User).
 */
$pageTitle = 'Overview';
require VIEW_PATH . '/partials/back-title.php';
$tiles = slot('owner-dashboard', ['user' => $user]);
?>
<div class="rounded-2xl border border-gray-200 bg-white p-5 md:p-6 dark:border-gray-800 dark:bg-white/3">
  <h3 class="text-lg font-semibold text-gray-800 dark:text-white/90">Hello, <?= e($user->getFullName()) ?></h3>
  <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
    Signed in as <?= e($user->getRoleLabel()) ?>. Use the menu to manage your part of SKYDROP.
  </p>
</div>

<?php if (trim($tiles) !== '') : ?>
  <div class="mt-6"><?= $tiles ?></div>
<?php else : ?>
  <div class="mt-6 rounded-2xl border border-gray-200 bg-white p-5 text-sm text-gray-500 md:p-6 dark:border-gray-800 dark:bg-white/3 dark:text-gray-400">
    The shop, product, order and ad tiles appear here when their modules are added.
  </div>
<?php endif; ?>
