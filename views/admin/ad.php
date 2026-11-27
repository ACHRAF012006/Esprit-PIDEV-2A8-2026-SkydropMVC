<?php
/**
 * S5 Karim - Administrator: one ad in detail, with approve, reject (review note required) and delete. Only the page content.
 * Variables: $ad (AdModel), $placement (?AdPlacementModel), $errors, $old.
 */
$pageTitle = 'Ad: ' . $ad->getTitle();
require VIEW_PATH . '/partials/back-title.php';
require View::file('partials/user-ui');
$tags = ['pending' => 'bg-orange-50 text-orange-600', 'approved' => 'bg-green-50 text-green-600', 'rejected' => 'bg-red-50 text-red-600'];
$pending = $ad->getStatus() === 'pending';
?>
<div class="grid grid-cols-12 gap-6">
  <div class="<?= $ui['card'] ?> col-span-12 p-5 sm:p-6 xl:col-span-7">
    <div class="flex flex-wrap items-start justify-between gap-3">
      <div>
        <h3 class="text-lg font-semibold text-gray-800 dark:text-white/90"><?= e($ad->getTitle()) ?></h3>
        <p class="<?= $ui['small'] ?>">by <?= e($ad->getAdvertiserName()) ?>, sent on <?= e(substr($ad->getCreatedAt(), 0, 10)) ?></p>
      </div>
      <span class="<?= $ui['badge'] ?> <?= $tags[$ad->getStatus()] ?? '' ?>"><?= e($ad->getStatusLabel()) ?></span>
    </div>

    <div class="mt-5 rounded-xl border border-gray-200 p-4 dark:border-white/5">
      <span class="<?= $ui['small'] ?>">Text of the ad, as the customers read it</span>
      <p class="ads-preview mt-2 text-sm text-gray-800 dark:text-white/90"><?= e($ad->getBody()) ?></p>
    </div>

    <dl class="mt-5 grid grid-cols-1 gap-4 sm:grid-cols-2">
      <div><dt class="<?= $ui['small'] ?>">Placement</dt><dd class="<?= $ui['strong'] ?>"><?= e($ad->getPlacementName()) ?><?= $placement && !$placement->isActive() ? ' (deactivated)' : '' ?></dd></div>
      <div><dt class="<?= $ui['small'] ?>">Ads shown at the same time there</dt><dd class="<?= $ui['strong'] ?>">At most <?= $placement ? (int) $placement->getMaxAds() : 0 ?></dd></div>
      <div><dt class="<?= $ui['small'] ?>">Dates</dt><dd class="<?= $ui['strong'] ?>"><?= e($ad->getStartsAt()) ?> to <?= e($ad->getEndsAt()) ?> (<?= (int) $ad->getDays() ?> days)</dd></div>
      <div><dt class="<?= $ui['small'] ?>">Price</dt><dd class="<?= $ui['strong'] ?>"><?= e(number_format($ad->getTotalPrice(), 3)) ?> TND <span class="simulation-badge">Simulation</span></dd></div>
      <div class="sm:col-span-2"><dt class="<?= $ui['small'] ?>">Link</dt><dd class="<?= $ui['strong'] ?> ads-preview"><?= e($ad->getLink() ?? 'No link') ?></dd></div>
      <?php if ($ad->getReviewNote() !== null) : ?>
        <div class="sm:col-span-2"><dt class="<?= $ui['small'] ?>">Review note (shown to the advertiser)</dt><dd class="<?= $ui['strong'] ?>"><?= e($ad->getReviewNote()) ?></dd></div>
      <?php endif; ?>
      <div class="sm:col-span-2"><dt class="<?= $ui['small'] ?>">Shown on the site</dt>
        <dd class="<?= $ui['strong'] ?>"><?= $ad->isRunning() ? 'Yes, today' : 'No: an ad is shown only when it is approved and today is between its dates' ?></dd></div>
    </dl>

    <div class="mt-6 flex flex-wrap items-center gap-3">
      <?php if ($pending) : ?>
        <form method="post" action="<?= e(route('admin-ad-approve', ['id' => $ad->getId()])) ?>">
          <?= csrf_field() ?>
          <button type="submit" class="<?= $ui['primary'] ?>">Approve</button>
        </form>
      <?php endif; ?>
      <form method="post" action="<?= e(route('admin-ad-delete', ['id' => $ad->getId()])) ?>">
        <?= csrf_field() ?>
        <button type="submit" class="<?= $ui['outline'] ?>" data-confirm="Delete the ad &quot;<?= e($ad->getTitle()) ?>&quot;? This cannot be undone.">Delete</button>
      </form>
      <a class="<?= $ui['outline'] ?>" href="<?= e(route('admin-ads')) ?>">Back to the ads</a>
    </div>
  </div>

  <div class="<?= $ui['card'] ?> col-span-12 p-5 sm:p-6 xl:col-span-5">
    <h3 class="text-lg font-semibold text-gray-800 dark:text-white/90">Reject the ad</h3>
    <?php if ($pending) : ?>
      <p class="<?= $ui['help'] ?>">Say what to change. The advertiser reads this note on their list of ads.</p>
      <form class="mt-4" method="post" action="<?= e(route('admin-ad-reject', ['id' => $ad->getId()])) ?>" novalidate data-validate>
        <?= csrf_field() ?>
        <label class="<?= $ui['label'] ?>" for="review_note">Review note</label>
        <textarea class="<?= $field('review_note') ?> ads-textarea" id="review_note" name="review_note" rows="4" maxlength="255"
                  data-required data-min-length="5" data-max-length="255"><?= e((string) ($old['review_note'] ?? '')) ?></textarea>
        <?= $error('review_note', '5 to 255 characters.') ?>
        <button type="submit" class="<?= $ui['outline'] ?> mt-4">Reject with this note</button>
      </form>
    <?php else : ?>
      <p class="<?= $ui['help'] ?>">This ad is <?= e(strtolower($ad->getStatusLabel())) ?>. Only a pending ad can be approved or rejected.</p>
    <?php endif; ?>
  </div>
</div>
