<?php
/**
 * S2 Mili - Administrator: one shop, its owner and plan, then approve, reject (with a review note) and delete.
 * Only the page content. Variables: $shop (ShopModel), $errors (review_note), $old (review_note typed before).
 */
$pageTitle = $shop->getName();
require VIEW_PATH . '/partials/back-title.php';
require View::file('partials/user-ui');
$statusClass = [
    'pending' => 'bg-orange-50 text-orange-600 dark:bg-orange-500/15 dark:text-orange-400',
    'approved' => 'bg-green-50 text-green-600 dark:bg-green-500/15 dark:text-green-400',
    'rejected' => 'bg-red-50 text-red-600 dark:bg-red-500/15 dark:text-red-400',
];
$textarea = str_replace('h-11', 'h-auto', $field('review_note'));
?>
<div class="grid grid-cols-12 gap-6">
  <div class="<?= $ui['card'] ?> col-span-12 p-5 sm:p-6 xl:col-span-7">
    <div class="flex flex-wrap items-start justify-between gap-3">
      <div>
        <h3 class="text-lg font-semibold text-gray-800 dark:text-white/90"><?= e($shop->getName()) ?></h3>
        <p class="<?= $ui['small'] ?>">Shop #<?= (int) $shop->getId() ?>, created on <?= e(substr($shop->getCreatedAt(), 0, 16)) ?></p>
      </div>
      <span class="<?= $ui['badge'] ?> <?= $statusClass[$shop->getStatus()] ?? '' ?>"><?= e($shop->getStatusLabel()) ?></span>
    </div>
    <dl class="mt-5 grid grid-cols-1 gap-4 sm:grid-cols-2">
      <div><dt class="<?= $ui['small'] ?>">Owner</dt><dd class="<?= $ui['strong'] ?>"><a class="sky-link" href="<?= e(route('admin-user', ['id' => $shop->getOwnerId()])) ?>">@<?= e($shop->getOwnerUsername()) ?></a></dd></div>
      <div><dt class="<?= $ui['small'] ?>">Plan</dt><dd class="<?= $ui['strong'] ?>"><?= e($shop->getPlanName()) ?> (weight <?= (int) $shop->getRankWeight() ?>)</dd></div>
      <div><dt class="<?= $ui['small'] ?>">Phone</dt><dd class="<?= $ui['strong'] ?>"><?= e($shop->getPhone() ?? 'Not given') ?></dd></div>
      <div><dt class="<?= $ui['small'] ?>">Hours</dt><dd class="<?= $ui['strong'] ?>"><?= e(substr($shop->getOpensAt(), 0, 5)) ?> to <?= e(substr($shop->getClosesAt(), 0, 5)) ?></dd></div>
      <div><dt class="<?= $ui['small'] ?>">Address</dt><dd class="<?= $ui['strong'] ?>"><?= e($shop->getAddress() ?? 'Not given') ?></dd></div>
      <div><dt class="<?= $ui['small'] ?>">Position</dt><dd class="<?= $ui['strong'] ?>">
        <?php if ($shop->getLat() !== null && $shop->getLng() !== null) : ?>
          <?= e(number_format($shop->getLat(), 6)) ?>, <?= e(number_format($shop->getLng(), 6)) ?> <span class="simulation-badge">Simulation</span>
        <?php else : ?>
          Not given
        <?php endif; ?>
      </dd></div>
    </dl>
    <div class="mt-4">
      <p class="<?= $ui['small'] ?>">Description</p>
      <p class="mt-1 text-sm text-gray-800 dark:text-white/90"><?= e($shop->getDescription() ?? 'No description.') ?></p>
    </div>
    <?php if ($shop->getReviewNote() !== null) : ?>
      <div class="mt-4 rounded-lg bg-red-50 p-3 text-sm text-red-600 dark:bg-red-500/15 dark:text-red-400">
        <strong>Review note:</strong> <?= e($shop->getReviewNote()) ?>
      </div>
    <?php endif; ?>
    <div class="mt-6 flex flex-wrap items-center gap-3">
      <?php if ($shop->getStatus() !== 'approved') : ?>
        <form method="post" action="<?= e(route('admin-shop-approve', ['id' => $shop->getId()])) ?>">
          <?= csrf_field() ?>
          <button type="submit" class="<?= $ui['primary'] ?>">Approve</button>
        </form>
      <?php else : ?>
        <a class="<?= $ui['outline'] ?>" href="<?= e(route('shop', ['id' => $shop->getId()])) ?>">See the public page</a>
      <?php endif; ?>
      <form method="post" action="<?= e(route('admin-shop-delete', ['id' => $shop->getId()])) ?>">
        <?= csrf_field() ?>
        <button type="submit" class="<?= $ui['outline'] ?>" data-confirm="Delete the shop <?= e($shop->getName()) ?>? This cannot be undone.">Delete</button>
      </form>
      <a class="sky-link text-sm font-medium" href="<?= e(route('admin-shops')) ?>">Back to the shops</a>
    </div>
  </div>

  <div class="<?= $ui['card'] ?> col-span-12 p-5 sm:p-6 xl:col-span-5">
    <h3 class="text-lg font-semibold text-gray-800 dark:text-white/90"><?= $shop->getStatus() === 'rejected' ? 'Change the review note' : 'Reject this shop' ?></h3>
    <p class="<?= $ui['help'] ?>">The shop is hidden from customers. Its owner reads your note on his shop page.</p>
    <form class="mt-4" method="post" action="<?= e(route('admin-shop-reject', ['id' => $shop->getId()])) ?>" novalidate data-validate>
      <?= csrf_field() ?>
      <label class="<?= $ui['label'] ?>" for="review_note">Review note</label>
      <textarea class="<?= $textarea ?>" id="review_note" name="review_note" rows="4" data-required data-min-length="5" data-max-length="255" maxlength="255"><?= e((string) ($old['review_note'] ?? $shop->getReviewNote() ?? '')) ?></textarea>
      <?= $error('review_note', 'Required: 5 to 255 characters, for example what to correct.') ?>
      <button type="submit" class="<?= $ui['outline'] ?> mt-4">Reject with this note</button>
    </form>
  </div>
</div>
