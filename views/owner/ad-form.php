<?php
/**
 * S5 Karim - Create or edit (while pending) one of my ads. Only the page content.
 * Variables: $ad (?AdModel, null = new ad), $placements (AdPlacementModel[], active), $today (Y-m-d), $maxDays,
 * $errors, $old. assets/js/ads.js shows the price before saving and checks the two date rules; the server checks
 * everything again and computes the price itself.
 */
$pageTitle = $ad ? 'Edit an ad' : 'Create an ad';
require VIEW_PATH . '/partials/back-title.php';
require View::file('partials/user-ui');
$value = static fn (string $name, string $current): string => (string) ($old[$name] ?? $current);
$placementId = $value('placement_id', $ad ? (string) $ad->getPlacementId() : '');
$datePattern = '^\d{4}-\d{2}-\d{2}$';
$dateMessage = 'Write a date, for example 2026-12-01.';
?>
<div class="<?= $ui['card'] ?> p-5 sm:p-6">
  <form method="post" action="<?= e(route('owner-ad-form')) ?>" novalidate data-validate data-ad-form
        data-today="<?= e($today) ?>" data-max-days="<?= (int) $maxDays ?>">
    <?= csrf_field() ?>
    <input type="hidden" name="id" value="<?= $ad ? (int) $ad->getId() : 0 ?>">
    <div class="grid grid-cols-1 gap-5 sm:grid-cols-2">
      <div class="sm:col-span-2">
        <label class="<?= $ui['label'] ?>" for="placement_id">Placement</label>
        <select class="<?= $field('placement_id') ?>" id="placement_id" name="placement_id" data-required>
          <option value="">Choose a placement</option>
          <?php foreach ($placements as $p) : ?>
            <option value="<?= (int) $p->getId() ?>" data-price="<?= e(number_format($p->getPricePerDay(), 3, '.', '')) ?>"
              <?= (string) $p->getId() === $placementId ? 'selected' : '' ?>><?= e($p->getName()) ?> (<?= e(number_format($p->getPricePerDay(), 3)) ?> TND per day)</option>
          <?php endforeach; ?>
        </select>
        <?= $error('placement_id', 'Where the ad is shown. The price per day depends on the placement.') ?>
      </div>
      <div class="sm:col-span-2">
        <label class="<?= $ui['label'] ?>" for="title">Title</label>
        <input class="<?= $field('title') ?>" type="text" id="title" name="title" data-required data-min-length="5" data-max-length="80"
               value="<?= e($value('title', $ad ? $ad->getTitle() : '')) ?>" maxlength="80" autocomplete="off">
        <?= $error('title', '5 to 80 characters.') ?>
      </div>
      <div class="sm:col-span-2">
        <label class="<?= $ui['label'] ?>" for="body">Text</label>
        <textarea class="<?= $field('body') ?> ads-textarea" id="body" name="body" rows="4" data-required data-min-length="10" data-max-length="300"
                  maxlength="300"><?= e($value('body', $ad ? $ad->getBody() : '')) ?></textarea>
        <?= $error('body', '10 to 300 characters. Text only: no image.') ?>
      </div>
      <div class="sm:col-span-2">
        <label class="<?= $ui['label'] ?>" for="link">Link (optional)</label>
        <input class="<?= $field('link') ?>" type="url" id="link" name="link" data-max-length="255"
               data-pattern="^https?://\S+$" data-pattern-message="Write a link that starts with http:// or https://, or leave it empty."
               value="<?= e($value('link', $ad ? (string) $ad->getLink() : '')) ?>" maxlength="255" placeholder="https://" autocomplete="off">
        <?= $error('link', 'The page the ad opens, starting with http:// or https://.') ?>
      </div>
      <div>
        <label class="<?= $ui['label'] ?>" for="starts_at">First day</label>
        <input class="<?= $field('starts_at') ?>" type="date" id="starts_at" name="starts_at" data-required min="<?= e($today) ?>"
               data-pattern="<?= e($datePattern) ?>" data-pattern-message="<?= e($dateMessage) ?>"
               value="<?= e($value('starts_at', $ad ? $ad->getStartsAt() : '')) ?>">
        <?= $error('starts_at', 'Today or later.') ?>
      </div>
      <div>
        <label class="<?= $ui['label'] ?>" for="ends_at">Last day</label>
        <input class="<?= $field('ends_at') ?>" type="date" id="ends_at" name="ends_at" data-required data-after="starts_at" min="<?= e($today) ?>"
               data-pattern="<?= e($datePattern) ?>" data-pattern-message="<?= e($dateMessage) ?>"
               value="<?= e($value('ends_at', $ad ? $ad->getEndsAt() : '')) ?>">
        <?= $error('ends_at', 'On or after the first day, at most ' . (int) $maxDays . ' days in all.') ?>
      </div>
    </div>

    <div class="ads-price mt-5 rounded-xl border border-gray-200 px-4 py-3 dark:border-white/5">
      <span class="<?= $ui['small'] ?>">Price <span class="simulation-badge">Simulation</span></span>
      <output class="<?= $ui['strong'] ?>" id="adPricePreview" for="placement_id starts_at ends_at" aria-live="polite">
        <?= $ad ? e((int) $ad->getDays() . ' days, ' . number_format($ad->getTotalPrice(), 3) . ' TND') : 'Number of days x price per day of the placement.' ?>
      </output>
      <span class="<?= $ui['small'] ?>">The server computes the final price again when you save. Nothing is paid now.</span>
    </div>

    <div class="mt-6 flex flex-wrap items-center gap-3">
      <button type="submit" class="<?= $ui['primary'] ?>"><?= $ad ? 'Save the ad' : 'Send the ad for approval' ?></button>
      <a class="<?= $ui['outline'] ?>" href="<?= e(route('owner-ads')) ?>">Cancel</a>
    </div>
  </form>
</div>
