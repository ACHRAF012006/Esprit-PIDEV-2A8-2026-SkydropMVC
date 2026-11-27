<?php
/**
 * S5 Karim - My ads (advertiser or shop owner). Only the page content.
 * Variables: $ads (AdModel[], mine, with placementName from the JOIN), $today (Y-m-d).
 */
$pageTitle = 'My ads';
require VIEW_PATH . '/partials/back-title.php';
require View::file('partials/user-ui');
$tags = ['pending' => 'bg-orange-50 text-orange-600', 'approved' => 'bg-green-50 text-green-600', 'rejected' => 'bg-red-50 text-red-600'];
?>
<div class="<?= $ui['card'] ?>">
  <div class="flex flex-wrap items-center justify-between gap-3 px-5 py-4 sm:px-6">
    <div>
      <h3 class="text-lg font-semibold text-gray-800 dark:text-white/90"><?= count($ads) ?> ad<?= count($ads) === 1 ? '' : 's' ?></h3>
      <p class="<?= $ui['help'] ?>">An administrator checks every ad before it is shown. You can edit an ad while it is pending, and delete it while it is pending or rejected.</p>
    </div>
    <a class="<?= $ui['primary'] ?>" href="<?= e(route('owner-ad-form')) ?>"><i class="bi bi-plus-lg" aria-hidden="true"></i> Create an ad</a>
  </div>
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
          <tr><td colspan="6" class="px-5 py-6 text-center text-sm text-gray-500 sm:px-6 dark:text-gray-400">You have no ad yet. Create one: it is shown once an administrator approves it.</td></tr>
        <?php endif; ?>
        <?php foreach ($ads as $ad) : ?>
          <tr>
            <td class="px-5 py-4 text-start sm:px-6">
              <span class="<?= $ui['strong'] ?>"><?= e($ad->getTitle()) ?></span>
              <span class="<?= $ui['small'] ?>"><?= e($ad->getLink() ?? 'No link') ?></span>
            </td>
            <td class="<?= $ui['td'] ?>"><?= e($ad->getPlacementName()) ?></td>
            <td class="<?= $ui['td'] ?> whitespace-nowrap">
              <?= e($ad->getStartsAt()) ?> to <?= e($ad->getEndsAt()) ?>
              <span class="<?= $ui['small'] ?>"><?= (int) $ad->getDays() ?> day<?= $ad->getDays() === 1 ? '' : 's' ?></span>
            </td>
            <td class="<?= $ui['td'] ?> whitespace-nowrap"><?= e(number_format($ad->getTotalPrice(), 3)) ?> TND</td>
            <td class="px-5 py-4 text-start sm:px-6">
              <span class="<?= $ui['badge'] ?> <?= $tags[$ad->getStatus()] ?? '' ?>"><?= e($ad->getStatusLabel()) ?></span>
              <?php if ($ad->getStatus() === 'pending') : ?>
                <span class="<?= $ui['small'] ?> mt-1">Waiting for an administrator.</span>
              <?php elseif ($ad->getStatus() === 'rejected') : ?>
                <span class="<?= $ui['small'] ?> mt-1">Reason: <?= e($ad->getReviewNote() ?? 'no note') ?></span>
              <?php elseif ($ad->isRunning()) : ?>
                <span class="<?= $ui['small'] ?> mt-1">Shown now.</span>
              <?php elseif ($ad->getStartsAt() > $today) : ?>
                <span class="<?= $ui['small'] ?> mt-1">Shown from <?= e($ad->getStartsAt()) ?>.</span>
              <?php else : ?>
                <span class="<?= $ui['small'] ?> mt-1">Ended on <?= e($ad->getEndsAt()) ?>.</span>
              <?php endif; ?>
            </td>
            <td class="<?= $ui['td'] ?>">
              <div class="flex flex-wrap items-center gap-3">
                <?php if ($ad->getStatus() === 'pending') : ?>
                  <a class="sky-link text-sm font-medium" href="<?= e(route('owner-ad-form', ['id' => $ad->getId()])) ?>">Edit</a>
                <?php endif; ?>
                <?php if ($ad->getStatus() !== 'approved') : ?>
                  <form method="post" action="<?= e(route('owner-ad-delete', ['id' => $ad->getId()])) ?>">
                    <?= csrf_field() ?>
                    <button type="submit" class="sky-link text-sm font-medium" data-confirm="Delete the ad &quot;<?= e($ad->getTitle()) ?>&quot;? This cannot be undone.">Delete</button>
                  </form>
                <?php else : ?>
                  <span class="<?= $ui['small'] ?>">Approved: locked</span>
                <?php endif; ?>
              </div>
            </td>
          </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>
</div>
