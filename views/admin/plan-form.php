<?php
/**
 * S2 Mili - Administrator: add or edit a plan tier. Only the page content.
 * Variables: $plan (?ShopPlanModel, null = new plan), $pricePattern, $priceMessage (same rule as AdminPlanController),
 * $errors, $old.
 */
$pageTitle = $plan ? 'Edit the plan ' . $plan->getName() : 'Add a plan';
require VIEW_PATH . '/partials/back-title.php';
require View::file('partials/user-ui');
$value = static fn (string $name, string $current): string => (string) ($old[$name] ?? $current);
$active = $old ? isset($old['is_active']) : ($plan ? $plan->isActive() : true);
?>
<div class="<?= $ui['card'] ?> p-5 sm:p-6">
  <form method="post" action="<?= e(route('admin-plan-form')) ?>" novalidate data-validate>
    <?= csrf_field() ?>
    <input type="hidden" name="id" value="<?= $plan ? (int) $plan->getId() : 0 ?>">
    <div class="grid grid-cols-1 gap-5 sm:grid-cols-2">
      <div>
        <label class="<?= $ui['label'] ?>" for="name">Name</label>
        <input class="<?= $field('name') ?>" type="text" id="name" name="name" data-required data-min-length="3" data-max-length="40"
               value="<?= e($value('name', $plan ? $plan->getName() : '')) ?>" maxlength="40" autocomplete="off">
        <?= $error('name', '3 to 40 characters, not used by another plan.') ?>
      </div>
      <div>
        <label class="<?= $ui['label'] ?>" for="monthly_price">Monthly price (TND)</label>
        <input class="<?= $field('monthly_price') ?>" type="text" inputmode="decimal" id="monthly_price" name="monthly_price" data-required data-number data-min="0"
               data-pattern="<?= e($pricePattern) ?>" data-pattern-message="<?= e($priceMessage) ?>"
               value="<?= e($value('monthly_price', $plan ? number_format($plan->getMonthlyPrice(), 3, '.', '') : '')) ?>" maxlength="11">
        <?= $error('monthly_price', '0 or more, for example 29.000. 0.000 is a free plan.') ?>
      </div>
      <div>
        <label class="<?= $ui['label'] ?>" for="rank_weight">Rank weight</label>
        <input class="<?= $field('rank_weight') ?>" type="text" inputmode="numeric" id="rank_weight" name="rank_weight" data-required data-max-length="6" data-integer data-min="0"
               value="<?= e($value('rank_weight', $plan ? (string) $plan->getRankWeight() : '')) ?>" maxlength="6">
        <?= $error('rank_weight', 'A whole number, 0 or more. Higher = the shops of this plan are shown first.') ?>
      </div>
      <div>
        <label class="<?= $ui['label'] ?>" for="max_products">Max products</label>
        <input class="<?= $field('max_products') ?>" type="text" inputmode="numeric" id="max_products" name="max_products" data-required data-max-length="6" data-integer data-min="1"
               value="<?= e($value('max_products', $plan ? (string) $plan->getMaxProducts() : '')) ?>" maxlength="6">
        <?= $error('max_products', 'A whole number, 1 or more.') ?>
      </div>
    </div>
    <label class="mt-5 flex items-center gap-3 text-sm text-gray-700 dark:text-gray-400" for="is_active">
      <input type="checkbox" class="size-4" id="is_active" name="is_active" value="1" <?= $active ? 'checked' : '' ?>>
      Active (shop owners can choose this plan)
    </label>
    <div class="mt-6 flex flex-wrap items-center gap-3">
      <button type="submit" class="<?= $ui['primary'] ?>"><?= $plan ? 'Save the plan' : 'Add the plan' ?></button>
      <a class="<?= $ui['outline'] ?>" href="<?= e(route('admin-plans')) ?>">Cancel</a>
    </div>
  </form>
</div>
