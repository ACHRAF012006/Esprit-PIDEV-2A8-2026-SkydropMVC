<?php
/**
 * S1 - Administrator: add or edit a coupon. Only the page content.
 * Variables: $coupon (?CouponModel, null = new coupon), $types (CouponTypeModel[]), $errors, $old.
 * assets/js/coupons.js adapts the value field to the chosen type (required or hidden, 1-100 for a percentage).
 */
$pageTitle = $coupon ? 'Edit ' . $coupon->getCode() : 'Add a coupon';
require VIEW_PATH . '/partials/back-title.php';
require View::file('partials/user-ui');
$value = static fn (string $name, string $current): string => (string) ($old[$name] ?? $current);
$active = $old ? isset($old['is_active']) : ($coupon ? $coupon->isActive() : true);
$typeId = (int) $value('type_id', $coupon ? (string) $coupon->getTypeId() : '');
$number = static fn (?float $n): string => $n === null ? '' : rtrim(rtrim(number_format($n, 3, '.', ''), '0'), '.');
?>
<div class="<?= $ui['card'] ?> p-5 sm:p-6">
  <form method="post" action="<?= e(route('admin-coupon-form')) ?>" novalidate data-validate>
    <?= csrf_field() ?>
    <input type="hidden" name="id" value="<?= $coupon ? $coupon->getId() : 0 ?>">
    <div class="grid grid-cols-1 gap-5 sm:grid-cols-2">
      <div>
        <label class="<?= $ui['label'] ?>" for="code">Code</label>
        <input class="<?= $field('code') ?>" type="text" id="code" name="code" data-required data-min-length="3" data-max-length="20"
               data-pattern="^[A-Za-z0-9]{3,20}$" data-pattern-message="Use 3 to 20 letters or digits, no spaces."
               value="<?= e($value('code', $coupon ? $coupon->getCode() : '')) ?>" maxlength="20" autocomplete="off">
        <?= $error('code', 'Saved in capitals, for example WELCOME10.') ?>
      </div>
      <div>
        <label class="<?= $ui['label'] ?>" for="type_id">Type</label>
        <select class="<?= $field('type_id') ?>" id="type_id" name="type_id" data-required data-coupon-type>
          <option value="">Choose a type</option>
          <?php foreach ($types as $t) : ?>
            <option value="<?= $t->getId() ?>" data-kind="<?= e($t->getKeyName()) ?>" <?= $t->getId() === $typeId ? 'selected' : '' ?>>
              <?= e($t->getName()) ?><?= $t->isActive() ? '' : ' (inactive)' ?>
            </option>
          <?php endforeach; ?>
        </select>
        <?= $error('type_id') ?>
      </div>
      <div data-coupon-value>
        <label class="<?= $ui['label'] ?>" for="value">Value <span data-coupon-unit></span></label>
        <input class="<?= $field('value') ?>" type="text" inputmode="decimal" id="value" name="value" data-number
               value="<?= e($value('value', $coupon ? $number($coupon->getValue()) : '')) ?>" autocomplete="off">
        <?= $error('value', 'A percentage from 1 to 100, or an amount in TND. Free delivery has no value.') ?>
      </div>
      <div>
        <label class="<?= $ui['label'] ?>" for="min_order">Minimum order in TND (optional)</label>
        <input class="<?= $field('min_order') ?>" type="text" inputmode="decimal" id="min_order" name="min_order" data-number data-min="0"
               value="<?= e($value('min_order', $coupon ? $number($coupon->getMinOrder()) : '')) ?>" autocomplete="off">
        <?= $error('min_order', 'Empty or 0: any order.') ?>
      </div>
      <div>
        <label class="<?= $ui['label'] ?>" for="starts_at">Starts on</label>
        <input class="<?= $field('starts_at') ?>" type="date" id="starts_at" name="starts_at" data-required
               value="<?= e($value('starts_at', $coupon ? $coupon->getStartsAt() : date('Y-m-d'))) ?>">
        <?= $error('starts_at') ?>
      </div>
      <div>
        <label class="<?= $ui['label'] ?>" for="ends_at">Ends on</label>
        <input class="<?= $field('ends_at') ?>" type="date" id="ends_at" name="ends_at" data-required data-after="starts_at"
               value="<?= e($value('ends_at', $coupon ? $coupon->getEndsAt() : '')) ?>">
        <?= $error('ends_at', 'The coupon works until the end of this day.') ?>
      </div>
      <div>
        <label class="<?= $ui['label'] ?>" for="max_uses">Total uses allowed (optional)</label>
        <input class="<?= $field('max_uses') ?>" type="text" inputmode="numeric" id="max_uses" name="max_uses" data-integer data-min="1"
               value="<?= e($value('max_uses', $coupon && $coupon->getMaxUses() !== null ? (string) $coupon->getMaxUses() : '')) ?>" autocomplete="off">
        <?= $error('max_uses', 'Empty: no total limit.' . ($coupon ? ' Used ' . $coupon->getUsesCount() . ' times so far.' : '')) ?>
      </div>
      <div>
        <label class="<?= $ui['label'] ?>" for="per_user_limit">Uses per customer (optional)</label>
        <input class="<?= $field('per_user_limit') ?>" type="text" inputmode="numeric" id="per_user_limit" name="per_user_limit" data-integer data-min="1"
               value="<?= e($value('per_user_limit', $coupon ? ($coupon->getPerUserLimit() !== null ? (string) $coupon->getPerUserLimit() : '') : '1')) ?>" autocomplete="off">
        <?= $error('per_user_limit', 'Empty: no limit per customer.') ?>
      </div>
    </div>
    <label class="mt-5 flex items-center gap-3 text-sm text-gray-700 dark:text-gray-400" for="is_active">
      <input type="checkbox" class="size-4" id="is_active" name="is_active" value="1" <?= $active ? 'checked' : '' ?>>
      Active (customers can use it between its dates)
    </label>
    <div class="mt-6 flex flex-wrap items-center gap-3">
      <button type="submit" class="<?= $ui['primary'] ?>"><?= $coupon ? 'Save the coupon' : 'Add the coupon' ?></button>
      <a class="<?= $ui['outline'] ?>" href="<?= e(route('admin-coupons')) ?>">Cancel</a>
    </div>
  </form>
</div>
