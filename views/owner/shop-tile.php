<?php
/**
 * S2 Mili - Tile of the owner overview (slot "owner-dashboard"): my shop's status.
 * Shown to shop owners only. The overview page belongs to another part and passes only $user to its slot,
 * so this tile reads my shop with ShopController::ofOwner() (the SQL stays in the controller).
 * Variables: $user (UserModel, the signed-in account).
 */
if ($user->getRole() !== 'shop_owner') {
    return;
}
$shop = (new ShopController())->ofOwner($user->getId());
$statusClass = [
    'pending' => 'bg-orange-50 text-orange-600 dark:bg-orange-500/15 dark:text-orange-400',
    'approved' => 'bg-green-50 text-green-600 dark:bg-green-500/15 dark:text-green-400',
    'rejected' => 'bg-red-50 text-red-600 dark:bg-red-500/15 dark:text-red-400',
];
?>
<div class="mb-6 rounded-2xl border border-gray-200 bg-white p-5 md:p-6 dark:border-gray-800 dark:bg-white/3">
  <div class="flex flex-wrap items-start justify-between gap-3">
    <div>
      <span class="ds-metric-label text-sm text-gray-500 dark:text-gray-400"><i class="bi bi-shop" aria-hidden="true"></i>My shop</span>
      <?php if ($shop === null) : ?>
        <h4 class="mt-2 text-lg font-semibold text-gray-800 dark:text-white/90">No shop yet</h4>
        <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">Create your shop: an administrator approves it before customers see it.</p>
      <?php else : ?>
        <h4 class="mt-2 text-lg font-semibold text-gray-800 dark:text-white/90"><?= e($shop->getName()) ?></h4>
        <p class="mt-1 text-sm text-gray-500 dark:text-gray-400"><?= e($shop->getPlanName()) ?> plan</p>
      <?php endif; ?>
    </div>
    <div class="flex flex-col items-start gap-2">
      <?php if ($shop !== null) : ?>
        <span class="inline-flex h-6 w-fit items-center rounded-lg px-2.5 py-0.5 text-sm font-medium whitespace-nowrap <?= e((string) ($statusClass[$shop->getStatus()] ?? '')) ?>"><?= e($shop->getStatusLabel()) ?></span>
      <?php endif; ?>
      <a href="<?= e(route('owner-shop')) ?>" class="sky-link text-sm font-medium"><?= e((string) ($shop === null ? 'Create my shop' : 'Open my shop')) ?></a>
    </div>
  </div>
</div>
