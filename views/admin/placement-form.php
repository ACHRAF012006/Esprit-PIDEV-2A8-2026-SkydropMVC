<?php
/**
 * S5 Karim - Administrator: add or edit an ad placement. Only the page content.
 * Variables: $placement (?AdPlacementModel, null = new placement), $errors, $old.
 */
$pageTitle = $placement ? 'Edit ' . $placement->getName() : 'Add a placement';
require VIEW_PATH . '/partials/back-title.php';
require View::file('partials/user-ui');
$value = static fn (string $name, string $current): string => (string) ($old[$name] ?? $current);
$active = $old ? isset($old['is_active']) : ($placement ? $placement->isActive() : true);
?>
<div class="<?= $ui['card'] ?> p-5 sm:p-6">
  <form method="post" action="<?= e(route('admin-placement-form')) ?>" novalidate data-validate>
    <?= csrf_field() ?>
    <input type="hidden" name="id" value="<?= $placement ? (int) $placement->getId() : 0 ?>">
    <div class="grid grid-cols-1 gap-5 sm:grid-cols-2">
      <div>
        <label class="<?= $ui['label'] ?>" for="name">Name</label>
        <input class="<?= $field('name') ?>" type="text" id="name" name="name" data-required data-min-length="3" data-max-length="60"
               value="<?= e($value('name', $placement ? $placement->getName() : '')) ?>" maxlength="60" autocomplete="off">
        <?= $error('name', '3 to 60 characters, different from the other placements.') ?>
      </div>
      <div>
        <label class="<?= $ui['label'] ?>" for="description">Description (optional)</label>
        <input class="<?= $field('description') ?>" type="text" id="description" name="description" data-max-length="255"
               value="<?= e($value('description', $placement ? $placement->getDescription() : '')) ?>" maxlength="255" autocomplete="off">
        <?= $error('description', 'Where the ads appear, for the advertisers.') ?>
      </div>
      <div>
        <label class="<?= $ui['label'] ?>" for="price_per_day">Price per day (TND)</label>
        <input class="<?= $field('price_per_day') ?>" type="text" inputmode="decimal" id="price_per_day" name="price_per_day"
               data-required data-number data-min="0.001" data-max="9999.999"
               value="<?= e($value('price_per_day', $placement ? number_format($placement->getPricePerDay(), 3, '.', '') : '')) ?>" autocomplete="off">
        <?= $error('price_per_day', 'For example 12.000. An ad costs its number of days x this price.') ?>
      </div>
      <div>
        <label class="<?= $ui['label'] ?>" for="max_ads">Ads shown at the same time</label>
        <input class="<?= $field('max_ads') ?>" type="text" inputmode="numeric" id="max_ads" name="max_ads"
               data-required data-integer data-min="1" data-max="10"
               value="<?= e($value('max_ads', $placement ? (string) $placement->getMaxAds() : '')) ?>" autocomplete="off">
        <?= $error('max_ads', '1 to 10. Checked when an administrator approves an ad.') ?>
      </div>
    </div>
    <label class="mt-5 flex items-center gap-3 text-sm text-gray-700 dark:text-gray-400" for="is_active">
      <input type="checkbox" class="size-4" id="is_active" name="is_active" value="1" <?= $active ? 'checked' : '' ?>>
      Active (advertisers can choose it and its approved ads are shown)
    </label>
    <div class="mt-6 flex flex-wrap items-center gap-3">
      <button type="submit" class="<?= $ui['primary'] ?>"><?= $placement ? 'Save the placement' : 'Add the placement' ?></button>
      <a class="<?= $ui['outline'] ?>" href="<?= e(route('admin-placements')) ?>">Cancel</a>
    </div>
  </form>
</div>
