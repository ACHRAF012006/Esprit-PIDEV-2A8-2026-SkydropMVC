<?php
/**
 * S1 - Administrator: add or edit a coupon type. Only the page content.
 * Variables: $type (?CouponTypeModel, null = new type), $kinds [key => label], $freeKinds (kinds without a type yet),
 * $errors, $old.
 */
$pageTitle = $type ? 'Edit ' . $type->getName() : 'Add a coupon type';
require VIEW_PATH . '/partials/back-title.php';
require View::file('partials/user-ui');
$value = static fn (string $name, string $current): string => (string) ($old[$name] ?? $current);
$active = $old ? isset($old['is_active']) : ($type ? $type->isActive() : true);
$kind = $value('key_name', $type ? $type->getKeyName() : '');
?>
<div class="<?= $ui['card'] ?> p-5 sm:p-6">
  <?php if (!$type && !$freeKinds) : ?>
    <p class="text-sm text-gray-500 dark:text-gray-400">Every kind of coupon already has a type. Edit an existing type instead.</p>
    <a class="<?= $ui['outline'] ?> mt-4" href="<?= e(route('admin-coupon-types')) ?>">Back to the coupon types</a>
  <?php else : ?>
  <form method="post" action="<?= e(route('admin-coupon-type-form')) ?>" novalidate data-validate>
    <?= csrf_field() ?>
    <input type="hidden" name="id" value="<?= $type ? $type->getId() : 0 ?>">
    <div class="grid grid-cols-1 gap-5 sm:grid-cols-2">
      <div>
        <label class="<?= $ui['label'] ?>" for="key_name">Kind</label>
        <?php if ($type) : ?>
          <input class="<?= $ui['input'] ?>" type="text" id="key_name" value="<?= e($kinds[$type->getKeyName()] ?? $type->getKeyName()) ?>" disabled>
          <p class="<?= $ui['help'] ?>">The kind cannot change: the coupons of this type rely on it.</p>
        <?php else : ?>
          <select class="<?= $field('key_name') ?>" id="key_name" name="key_name" data-required>
            <option value="">Choose a kind</option>
            <?php foreach ($freeKinds as $key) : ?>
              <option value="<?= e($key) ?>" <?= $key === $kind ? 'selected' : '' ?>><?= e($kinds[$key]) ?></option>
            <?php endforeach; ?>
          </select>
          <?= $error('key_name', 'Free delivery coupons have no value; the other kinds need one.') ?>
        <?php endif; ?>
      </div>
      <div>
        <label class="<?= $ui['label'] ?>" for="name">Name</label>
        <input class="<?= $field('name') ?>" type="text" id="name" name="name" data-required data-min-length="3" data-max-length="60"
               value="<?= e($value('name', $type ? $type->getName() : '')) ?>" maxlength="60" autocomplete="off">
        <?= $error('name') ?>
      </div>
      <div class="sm:col-span-2">
        <label class="<?= $ui['label'] ?>" for="description">Description (optional)</label>
        <input class="<?= $field('description') ?>" type="text" id="description" name="description" data-max-length="255"
               value="<?= e($value('description', $type ? $type->getDescription() : '')) ?>" maxlength="255" autocomplete="off">
        <?= $error('description', 'What the customer gets, in a few words.') ?>
      </div>
    </div>
    <label class="mt-5 flex items-center gap-3 text-sm text-gray-700 dark:text-gray-400" for="is_active">
      <input type="checkbox" class="size-4" id="is_active" name="is_active" value="1" <?= $active ? 'checked' : '' ?>>
      Active (coupons of this type can be used)
    </label>
    <div class="mt-6 flex flex-wrap items-center gap-3">
      <button type="submit" class="<?= $ui['primary'] ?>"><?= $type ? 'Save the type' : 'Add the type' ?></button>
      <a class="<?= $ui['outline'] ?>" href="<?= e(route('admin-coupon-types')) ?>">Cancel</a>
    </div>
  </form>
  <?php endif; ?>
</div>
