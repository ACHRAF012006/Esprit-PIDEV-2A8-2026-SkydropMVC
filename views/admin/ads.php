<?php
/**
 * S5 Karim - Administrator: every ad (JOIN placements for the placement, JOIN users for the advertiser). Only the page content.
 * Variables: $ads (AdModel[]), $status (filter, '' = all), $statuses [key => label], $counts [key => number of ads].
 */
$pageTitle = 'Ads';
require VIEW_PATH . '/partials/back-title.php';
require View::file('partials/user-ui');
$tags = ['pending' => 'bg-orange-50 text-orange-600', 'approved' => 'bg-green-50 text-green-600', 'rejected' => 'bg-red-50 text-red-600'];
?>
<div class="<?= $ui['card'] ?>">
  <div class="flex flex-wrap items-center justify-between gap-3 px-5 py-4 sm:px-6">
    <h3 class="text-lg font-semibold text-gray-800 dark:text-white/90"><?= count($ads) ?> ad<?= count($ads) === 1 ? '' : 's' ?></h3>
    <a class="<?= $ui['outline'] ?>" href="<?= e(route('admin-placements')) ?>"><i class="bi bi-layout-text-window" aria-hidden="true"></i> Placements</a>
  </div>
  <form method="get" action="<?= e(route('admin-ads')) ?>" class="flex flex-wrap items-end gap-3 border-t border-gray-100 px-5 py-4 sm:px-6 dark:border-white/5">
    <input type="hidden" name="page" value="admin-ads">
    <div>
      <label class="<?= $ui['label'] ?>" for="adStatus">Status</label>
      <select class="<?= $ui['input'] ?>" id="adStatus" name="status">
        <option value="">All statuses (<?= (int) array_sum($counts) ?>)</option>
        <?php foreach ($statuses as $key => $label) : ?>
          <option value="<?= e($key) ?>" <?= $key === $status ? 'selected' : '' ?>><?= e($label) ?> (<?= (int) $counts[$key] ?>)</option>
        <?php endforeach; ?>
      </select>
    </div>
    <button type="submit" class="<?= $ui['outline'] ?>">Filter</button>
    <?php if ($status !== '') : ?>
      <a class="sky-link text-sm font-medium" href="<?= e(route('admin-ads')) ?>">Clear the filter</a>
    <?php endif; ?>
  </form>
  <div class="max-w-full overflow-x-auto">
    <table class="min-w-full">
      <thead class="border-y border-gray-100 dark:border-white/5">
        <tr>
          <th class="<?= $ui['th'] ?>">Ad</th>
          <th class="<?= $ui['th'] ?>">Placement</th>
          <th class="<?= $ui['th'] ?>">Dates</th>
          <th class="<?= $ui['th'] ?>">Price</th>
          <th class="<?= $ui['th'] ?>">Status</th>
          <th class="<?= $ui['th'] ?>">Actions</th>
        </tr>
      </thead>
      <tbody class="divide-y divide-gray-100 dark:divide-white/5">
        <?php if (!$ads) : ?>
          <tr><td colspan="6" class="px-5 py-6 text-center text-sm text-gray-500 sm:px-6 dark:text-gray-400">No ad has this status.</td></tr>
        <?php endif; ?>
        <?php foreach ($ads as $ad) : ?>
          <tr>
            <td class="px-5 py-4 text-start sm:px-6">
              <a class="<?= $ui['strong'] ?> sky-link" href="<?= e(route('admin-ad', ['id' => $ad->getId()])) ?>"><?= e($ad->getTitle()) ?></a>
              <span class="<?= $ui['small'] ?>">by <?= e($ad->getAdvertiserName()) ?></span>
            </td>
            <td class="<?= $ui['td'] ?>"><?= e($ad->getPlacementName()) ?></td>
            <td class="<?= $ui['td'] ?> whitespace-nowrap">
              <?= e($ad->getStartsAt()) ?> to <?= e($ad->getEndsAt()) ?>
              <span class="<?= $ui['small'] ?>"><?= (int) $ad->getDays() ?> day<?= $ad->getDays() === 1 ? '' : 's' ?><?= $ad->isRunning() ? ', shown now' : '' ?></span>
            </td>
            <td class="<?= $ui['td'] ?> whitespace-nowrap"><?= e(number_format($ad->getTotalPrice(), 3)) ?> TND</td>
            <td class="px-5 py-4 text-start sm:px-6">
              <span class="<?= $ui['badge'] ?> <?= $tags[$ad->getStatus()] ?? '' ?>"><?= e($ad->getStatusLabel()) ?></span>
            </td>
            <td class="<?= $ui['td'] ?>">
              <div class="flex flex-wrap items-center gap-3">
                <a class="sky-link text-sm font-medium" href="<?= e(route('admin-ad', ['id' => $ad->getId()])) ?>"><?= $ad->getStatus() === 'pending' ? 'Review' : 'View' ?></a>
                <form method="post" action="<?= e(route('admin-ad-delete', ['id' => $ad->getId()])) ?>">
                  <?= csrf_field() ?>
                  <button type="submit" class="sky-link text-sm font-medium" data-confirm="Delete the ad &quot;<?= e($ad->getTitle()) ?>&quot;? This cannot be undone.">Delete</button>
                </form>
              </div>
            </td>
          </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>
</div>
