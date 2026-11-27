<?php
/**
 * S5 Karim - Administrator: ad placements, with the number of ads of each one (LEFT JOIN ads). Only the page content.
 * Variables: $placements (AdPlacementModel[]).
 */
$pageTitle = 'Ad placements';
require VIEW_PATH . '/partials/back-title.php';
require View::file('partials/user-ui');
?>
<div class="<?= $ui['card'] ?>">
  <div class="flex flex-wrap items-center justify-between gap-3 px-5 py-4 sm:px-6">
    <div>
      <h3 class="text-lg font-semibold text-gray-800 dark:text-white/90"><?= count($placements) ?> placement<?= count($placements) === 1 ? '' : 's' ?></h3>
      <p class="<?= $ui['help'] ?>">A placement still used by an ad cannot be deleted: deactivate it instead, and its ads are no longer shown.</p>
    </div>
    <a class="<?= $ui['primary'] ?>" href="<?= e(route('admin-placement-form')) ?>"><i class="bi bi-plus-lg" aria-hidden="true"></i> Add a placement</a>
  </div>
  <div class="max-w-full overflow-x-auto">
    <table class="min-w-full">
      <thead class="border-y border-gray-100 dark:border-white/5">
        <tr>
          <th class="<?= $ui['th'] ?>">Placement</th>
          <th class="<?= $ui['th'] ?>">Price per day</th>
          <th class="<?= $ui['th'] ?>">Ads at the same time</th>
          <th class="<?= $ui['th'] ?>">Ads</th>
          <th class="<?= $ui['th'] ?>">Status</th>
          <th class="<?= $ui['th'] ?>">Actions</th>
        </tr>
      </thead>
      <tbody class="divide-y divide-gray-100 dark:divide-white/5">
        <?php if (!$placements) : ?>
          <tr><td colspan="6" class="px-5 py-6 text-center text-sm text-gray-500 sm:px-6 dark:text-gray-400">No placement yet. Add one so that advertisers can create ads.</td></tr>
        <?php endif; ?>
        <?php foreach ($placements as $p) : ?>
          <tr>
            <td class="px-5 py-4 text-start sm:px-6">
              <span class="<?= $ui['strong'] ?>"><?= e($p->getName()) ?></span>
              <span class="<?= $ui['small'] ?>"><?= e($p->getDescription() !== '' ? $p->getDescription() : 'No description') ?></span>
            </td>
            <td class="<?= $ui['td'] ?> whitespace-nowrap"><?= e(number_format($p->getPricePerDay(), 3)) ?> TND</td>
            <td class="<?= $ui['td'] ?>"><?= (int) $p->getMaxAds() ?></td>
            <td class="<?= $ui['td'] ?>"><?= (int) $p->getAdsCount() ?></td>
            <td class="px-5 py-4 text-start sm:px-6">
              <span class="<?= $ui['badge'] ?> <?= $ui['status'][$p->isActive() ? 'active' : 'deactivated'] ?>"><?= $p->isActive() ? 'Active' : 'Deactivated' ?></span>
            </td>
            <td class="<?= $ui['td'] ?>">
              <div class="flex flex-wrap items-center gap-3">
                <a class="sky-link text-sm font-medium" href="<?= e(route('admin-placement-form', ['id' => $p->getId()])) ?>">Edit</a>
                <form method="post" action="<?= e(route('admin-placement-delete', ['id' => $p->getId()])) ?>">
                  <?= csrf_field() ?>
                  <button type="submit" class="sky-link text-sm font-medium" data-confirm="Delete the placement &quot;<?= e($p->getName()) ?>&quot;? This cannot be undone.">Delete</button>
                </form>
              </div>
            </td>
          </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>
</div>
