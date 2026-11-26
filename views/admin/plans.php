<?php
/**
 * S2 Mili - Administrator: the plan tiers with their number of shops (LEFT JOIN shops). Only the page content.
 * Variables: $plans (ShopPlanModel[], highest weight first).
 */
$pageTitle = 'Plan tiers';
require VIEW_PATH . '/partials/back-title.php';
require View::file('partials/user-ui');
?>
<div class="<?= $ui['card'] ?>">
  <div class="flex flex-wrap items-center justify-between gap-3 px-5 py-4 sm:px-6">
    <div>
      <h3 class="text-lg font-semibold text-gray-800 dark:text-white/90"><?= count($plans) ?> plan<?= count($plans) === 1 ? '' : 's' ?></h3>
      <p class="<?= $ui['small'] ?>">A higher weight shows the shops of the plan first. A plan used by a shop cannot be deleted: deactivate it.</p>
    </div>
    <a class="<?= $ui['primary'] ?>" href="<?= e(route('admin-plan-form')) ?>"><i class="bi bi-plus-lg" aria-hidden="true"></i> Add a plan</a>
  </div>
  <div class="max-w-full overflow-x-auto">
    <table class="min-w-full ds-table">
      <thead class="border-y border-gray-100 dark:border-white/5">
        <tr>
          <th class="<?= $ui['th'] ?>">Plan</th>
          <th class="<?= $ui['th'] ?>">Monthly price</th>
          <th class="<?= $ui['th'] ?>">Weight</th>
          <th class="<?= $ui['th'] ?>">Max products</th>
          <th class="<?= $ui['th'] ?>">Shops</th>
          <th class="<?= $ui['th'] ?>">Status</th>
          <th class="<?= $ui['th'] ?>">Actions</th>
        </tr>
      </thead>
      <tbody class="divide-y divide-gray-100 dark:divide-white/5">
        <?php if (!$plans) : ?>
          <tr><td colspan="7" class="px-5 py-6 text-center text-sm text-gray-500 sm:px-6 dark:text-gray-400">No plan yet.</td></tr>
        <?php endif; ?>
        <?php foreach ($plans as $plan) : ?>
          <tr class="transition hover:bg-gray-50/50 dark:hover:bg-white/2">
            <td class="px-5 py-4 text-start sm:px-6"><span class="<?= $ui['strong'] ?>"><?= e($plan->getName()) ?></span></td>
            <td class="<?= $ui['td'] ?>"><?= e(number_format($plan->getMonthlyPrice(), 3)) ?> TND</td>
            <td class="<?= $ui['td'] ?>"><?= (int) $plan->getRankWeight() ?></td>
            <td class="<?= $ui['td'] ?>"><?= (int) $plan->getMaxProducts() ?></td>
            <td class="<?= $ui['td'] ?>"><?= (int) $plan->getShopCount() ?></td>
            <td class="px-5 py-4 text-start sm:px-6">
              <span class="<?= $ui['badge'] ?> <?= $ui['status'][$plan->isActive() ? 'active' : 'deactivated'] ?>"><?= $plan->isActive() ? 'Active' : 'Inactive' ?></span>
            </td>
            <td class="<?= $ui['td'] ?>">
              <div class="flex flex-wrap items-center gap-3">
                <a class="sky-link text-sm font-medium" href="<?= e(route('admin-plan-form', ['id' => $plan->getId()])) ?>">Edit</a>
                <form method="post" action="<?= e(route('admin-plan-delete', ['id' => $plan->getId()])) ?>">
                  <?= csrf_field() ?>
                  <button type="submit" class="sky-link text-sm font-medium" data-confirm="Delete the plan <?= e($plan->getName()) ?>? This cannot be undone.">Delete</button>
                </form>
              </div>
            </td>
          </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>
</div>
