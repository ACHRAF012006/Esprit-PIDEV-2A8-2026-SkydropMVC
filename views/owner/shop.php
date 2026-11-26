<?php
/**
 * S2 Mili - Shop owner: my shop (status, review note) and its form, to create it or to edit it. Only the page content.
 * Variables: $shop (?ShopModel, null = no shop yet), $plans (ShopPlanModel[] active plans), $errors, $old,
 * $phonePattern, $phoneMessage, $timePattern, $timeMessage (same rules as OwnerShopController).
 */
$pageTitle = $shop ? 'My shop' : 'Create my shop';
require VIEW_PATH . '/partials/back-title.php';
require View::file('partials/user-ui');
$value = static fn (string $name, string $current): string => (string) ($old[$name] ?? $current);
$planId = $value('plan_id', $shop ? (string) $shop->getPlanId() : '');
$statusClass = [
    'pending' => 'bg-orange-50 text-orange-600 dark:bg-orange-500/15 dark:text-orange-400',
    'approved' => 'bg-green-50 text-green-600 dark:bg-green-500/15 dark:text-green-400',
    'rejected' => 'bg-red-50 text-red-600 dark:bg-red-500/15 dark:text-red-400',
];
$textarea = str_replace('h-11', 'h-auto', isset($errors['description']) ? $ui['input_error'] : $ui['input']);
?>
<div class="<?= $ui['card'] ?> mb-6 p-5 sm:p-6">
  <?php if ($shop === null) : ?>
    <h3 class="text-lg font-semibold text-gray-800 dark:text-white/90">You have no shop yet</h3>
    <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">Fill in the form below. Your shop starts as "Waiting for approval" and appears in the shop list once an administrator approves it.</p>
  <?php else : ?>
    <div class="flex flex-wrap items-center justify-between gap-3">
      <div>
        <h3 class="text-lg font-semibold text-gray-800 dark:text-white/90"><?= e($shop->getName()) ?></h3>
        <p class="<?= $ui['small'] ?>"><?= e($shop->getPlanName()) ?> plan, created on <?= e(substr($shop->getCreatedAt(), 0, 10)) ?></p>
      </div>
      <span class="<?= $ui['badge'] ?> <?= $statusClass[$shop->getStatus()] ?? '' ?>"><?= e($shop->getStatusLabel()) ?></span>
    </div>
    <?php if ($shop->getStatus() === 'pending') : ?>
      <p class="mt-3 text-sm text-gray-500 dark:text-gray-400">An administrator checks your shop. It is hidden from the shop list until it is approved.</p>
    <?php elseif ($shop->getStatus() === 'approved') : ?>
      <p class="mt-3 text-sm text-gray-500 dark:text-gray-400">
        Customers can see your shop. If you change its name, it must be approved again.
        <a class="sky-link font-medium" href="<?= e(route('shop', ['id' => $shop->getId()])) ?>">See my shop page</a>
      </p>
    <?php else : ?>
      <div class="mt-3 rounded-lg bg-red-50 p-3 text-sm text-red-600 dark:bg-red-500/15 dark:text-red-400">
        <strong>Note of the administrator:</strong> <?= e((string) $shop->getReviewNote()) ?>
      </div>
    <?php endif; ?>
  <?php endif; ?>
</div>

<div class="<?= $ui['card'] ?> p-5 sm:p-6">
  <form method="post" action="<?= e(route('owner-shop')) ?>" novalidate data-validate data-shop-hours>
    <?= csrf_field() ?>
    <div class="grid grid-cols-1 gap-5 sm:grid-cols-2">
      <div>
        <label class="<?= $ui['label'] ?>" for="name">Shop name</label>
        <input class="<?= $field('name') ?>" type="text" id="name" name="name" data-required data-min-length="3" data-max-length="80"
               value="<?= e($value('name', $shop ? $shop->getName() : '')) ?>" maxlength="80" autocomplete="organization">
        <?= $error('name', '3 to 80 characters, not used by another shop.') ?>
      </div>
      <div>
        <label class="<?= $ui['label'] ?>" for="plan_id">Plan</label>
        <select class="<?= $field('plan_id') ?>" id="plan_id" name="plan_id" data-required>
          <option value="">Choose a plan</option>
          <?php foreach ($plans as $plan) : ?>
            <option value="<?= (int) $plan->getId() ?>" <?= (string) $plan->getId() === $planId ? 'selected' : '' ?>>
              <?= e($plan->getName()) ?>: <?= e(number_format($plan->getMonthlyPrice(), 3)) ?> TND per month, up to <?= (int) $plan->getMaxProducts() ?> products
            </option>
          <?php endforeach; ?>
        </select>
        <?= $error('plan_id', 'A higher plan shows your shop earlier in the list.') ?>
      </div>
      <div>
        <label class="<?= $ui['label'] ?>" for="phone">Phone</label>
        <input class="<?= $field('phone') ?>" type="tel" id="phone" name="phone" data-required
               data-pattern="<?= e($phonePattern) ?>" data-pattern-message="<?= e($phoneMessage) ?>"
               value="<?= e($value('phone', $shop ? $shop->getPhone() : '')) ?>" maxlength="20" autocomplete="tel">
        <?= $error('phone', '8 digits, optionally after +216, without spaces.') ?>
      </div>
      <div>
        <label class="<?= $ui['label'] ?>" for="address">Address</label>
        <input class="<?= $field('address') ?>" type="text" id="address" name="address" data-required data-min-length="5" data-max-length="160"
               value="<?= e($value('address', $shop ? $shop->getAddress() : '')) ?>" maxlength="160" autocomplete="street-address">
        <?= $error('address') ?>
      </div>
      <div>
        <label class="<?= $ui['label'] ?>" for="opens_at">Opens at</label>
        <input class="<?= $field('opens_at') ?>" type="time" id="opens_at" name="opens_at" data-required
               data-pattern="<?= e($timePattern) ?>" data-pattern-message="<?= e($timeMessage) ?>"
               value="<?= e(substr($value('opens_at', $shop ? $shop->getOpensAt() : ''), 0, 5)) ?>">
        <?= $error('opens_at') ?>
      </div>
      <div>
        <label class="<?= $ui['label'] ?>" for="closes_at">Closes at</label>
        <input class="<?= $field('closes_at') ?>" type="time" id="closes_at" name="closes_at" data-required
               data-pattern="<?= e($timePattern) ?>" data-pattern-message="<?= e($timeMessage) ?>"
               value="<?= e(substr($value('closes_at', $shop ? $shop->getClosesAt() : ''), 0, 5)) ?>">
        <?= $error('closes_at', 'Must be after the opening time.') ?>
      </div>
      <div>
        <label class="<?= $ui['label'] ?>" for="lat">Latitude</label>
        <input class="<?= $field('lat') ?>" type="text" inputmode="decimal" id="lat" name="lat" data-required data-number data-min="-90" data-max="90"
               value="<?= e($value('lat', $shop ? (string) $shop->getLat() : '')) ?>" maxlength="12">
        <?= $error('lat', 'For example 36.8381 (Tunis). Right-click the place on openstreetmap.org to read it.') ?>
      </div>
      <div>
        <label class="<?= $ui['label'] ?>" for="lng">Longitude</label>
        <input class="<?= $field('lng') ?>" type="text" inputmode="decimal" id="lng" name="lng" data-required data-number data-min="-180" data-max="180"
               value="<?= e($value('lng', $shop ? (string) $shop->getLng() : '')) ?>" maxlength="12">
        <?= $error('lng', 'For example 10.2420 (Tunis).') ?>
      </div>
    </div>
    <div class="mt-5">
      <label class="<?= $ui['label'] ?>" for="description">Description (optional)</label>
      <textarea class="<?= $textarea ?>" id="description" name="description" rows="3" data-max-length="500" maxlength="500"><?= e($value('description', $shop ? (string) $shop->getDescription() : '')) ?></textarea>
      <?= $error('description', 'At most 500 characters.') ?>
    </div>
    <div class="mt-6 flex flex-wrap items-center gap-3">
      <button type="submit" class="<?= $ui['primary'] ?>"><?= $shop ? 'Save my shop' : 'Create my shop' ?></button>
      <a class="<?= $ui['outline'] ?>" href="<?= e(route('owner-dashboard')) ?>">Cancel</a>
    </div>
  </form>
</div>
