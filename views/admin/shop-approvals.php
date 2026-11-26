<?php
/**
 * S2 Mili - Tile of the admin overview (slot "admin-dashboard"): the number of shops waiting for approval.
 * The overview page belongs to another part and passes no shop data to its slot, so this tile asks
 * AdminShopController for the counts (the SQL stays in the controller). The page already did requireRole('admin').
 */
$counts = (new AdminShopController())->countByStatus();
$pending = $counts['pending'];
?>
<div class="rounded-2xl border border-gray-200 bg-white p-5 md:p-6 dark:border-gray-800 dark:bg-white/3">
  <div class="flex flex-wrap items-end justify-between gap-3">
    <div>
      <span class="ds-metric-label text-sm text-gray-500 dark:text-gray-400"><i class="bi bi-shop" aria-hidden="true"></i>Shops waiting for approval</span>
      <h4 class="mt-2 text-title-sm font-bold text-gray-800 dark:text-white/90"><?= (int) $pending ?></h4>
      <p class="mt-1 text-sm text-gray-500 dark:text-gray-400"><?= (int) $counts['approved'] ?> approved, <?= (int) $counts['rejected'] ?> rejected</p>
    </div>
    <a href="<?= e(route('admin-shops', $pending > 0 ? ['status' => 'pending'] : [])) ?>" class="sky-link text-sm font-medium"><?= $pending > 0 ? 'Review them' : 'All shops' ?></a>
  </div>
</div>
