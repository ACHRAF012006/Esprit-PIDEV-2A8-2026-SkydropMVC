<?php
/**
 * S5 Karim - Tile of the administrator overview (slot "admin-dashboard"): the ads waiting for approval.
 * A slot view has no controller of its own: it asks AdminAdController::waitingForApproval() (no SQL here).
 */
$waiting = (new AdminAdController())->waitingForApproval(5);
?>
<div class="overflow-hidden rounded-2xl border border-gray-200 bg-white dark:border-white/5 dark:bg-white/3">
  <div class="flex flex-wrap items-center justify-between gap-3 px-5 py-4 sm:px-6">
    <h3 class="text-lg font-semibold text-gray-800 dark:text-white/90">Ads waiting for approval (<?= (int) $waiting['count'] ?>)</h3>
    <a href="<?= e(route('admin-ads', ['status' => 'pending'])) ?>" class="sky-link text-sm font-medium">All pending ads</a>
  </div>
  <?php if (!$waiting['ads']) : ?>
    <p class="border-t border-gray-100 px-5 py-4 text-sm text-gray-500 sm:px-6 dark:border-white/5 dark:text-gray-400">No ad is waiting.</p>
  <?php else : ?>
    <ul class="divide-y divide-gray-100 border-t border-gray-100 dark:divide-white/5 dark:border-white/5">
      <?php foreach ($waiting['ads'] as $ad) : ?>
        <li class="flex flex-wrap items-center justify-between gap-3 px-5 py-3 sm:px-6">
          <span>
            <span class="block text-sm font-medium text-gray-800 dark:text-white/90"><?= e($ad->getTitle()) ?></span>
            <span class="block text-xs text-gray-500 dark:text-gray-400"><?= e($ad->getPlacementName()) ?>, from <?= e($ad->getStartsAt()) ?>, by <?= e($ad->getAdvertiserName()) ?></span>
          </span>
          <a class="sky-link text-sm font-medium" href="<?= e(route('admin-ad', ['id' => $ad->getId()])) ?>">Review</a>
        </li>
      <?php endforeach; ?>
    </ul>
  <?php endif; ?>
</div>
