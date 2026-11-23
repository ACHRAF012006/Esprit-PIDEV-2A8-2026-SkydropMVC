<?php
/**
 * S3 Islem - Administrator: the activity log (who did what to which account, and when). Only the page content.
 * Variables: $items (UserActivityModel[], this page), $actions [action => label], $filters (action, from, to),
 * $errors [field => message], $total (lines matching the filters), $page, $pages.
 */
$pageTitle = 'Activity';
require VIEW_PATH . '/partials/back-title.php';
require View::file('partials/user-ui');
$filtered = $filters['action'] !== '' || $filters['from'] !== '' || $filters['to'] !== '';
// A link to this list with the current filters and some of them changed.
$link = static fn (array $change = []): string => route('admin-activity', array_filter($change + $filters + ['p' => $page], static fn ($v) => $v !== '' && $v !== null));
?>
<div class="<?= $ui['card'] ?>">
  <div class="flex flex-wrap items-center justify-between gap-3 px-5 py-4 sm:px-6">
    <h3 class="text-lg font-semibold text-gray-800 dark:text-white/90"><?= (int) $total ?> action<?= $total === 1 ? '' : 's' ?></h3>
    <p class="<?= $ui['small'] ?>">Changes made by administrators to accounts, newest first.</p>
  </div>
  <form method="get" action="<?= e(route('admin-activity')) ?>" class="flex flex-wrap items-start gap-3 border-t border-gray-100 px-5 py-4 sm:px-6 dark:border-white/5" novalidate data-validate>
    <input type="hidden" name="page" value="admin-activity">
    <div>
      <label class="<?= $ui['label'] ?>" for="activityAction">Action</label>
      <select class="<?= $ui['input'] ?>" id="activityAction" name="action">
        <option value="">All actions</option>
        <?php foreach ($actions as $key => $label) : ?>
          <option value="<?= e($key) ?>" <?= $key === $filters['action'] ? 'selected' : '' ?>><?= e($label) ?></option>
        <?php endforeach; ?>
      </select>
    </div>
    <div>
      <label class="<?= $ui['label'] ?>" for="activityFrom">From</label>
      <input class="<?= $field('from') ?>" type="date" id="activityFrom" name="from" value="<?= e($filters['from']) ?>">
      <?= $error('from') ?>
    </div>
    <div>
      <label class="<?= $ui['label'] ?>" for="activityTo">To</label>
      <input class="<?= $field('to') ?>" type="date" id="activityTo" name="to" value="<?= e($filters['to']) ?>" data-after="activityFrom">
      <?= $error('to') ?>
    </div>
    <div class="mt-6 flex items-center gap-3">
      <button type="submit" class="<?= $ui['outline'] ?>">Filter</button>
      <?php if ($filtered || $errors) : ?>
        <a class="sky-link text-sm font-medium" href="<?= e(route('admin-activity')) ?>">Clear the filters</a>
      <?php endif; ?>
    </div>
  </form>
  <div class="max-w-full overflow-x-auto">
    <table class="min-w-full">
      <thead class="border-y border-gray-100 dark:border-white/5">
        <tr>
          <th class="<?= $ui['th'] ?>">Date</th>
          <th class="<?= $ui['th'] ?>">Administrator</th>
          <th class="<?= $ui['th'] ?>">Action</th>
          <th class="<?= $ui['th'] ?>">Account</th>
          <th class="<?= $ui['th'] ?>">Details</th>
        </tr>
      </thead>
      <tbody class="divide-y divide-gray-100 dark:divide-white/5">
        <?php if (!$items) : ?>
          <tr><td colspan="5" class="px-5 py-6 text-center text-sm text-gray-500 sm:px-6 dark:text-gray-400">
            <?= $filtered ? 'No action matches these filters.' : 'No action has been recorded yet.' ?>
          </td></tr>
        <?php endif; ?>
        <?php foreach ($items as $item) : ?>
          <tr class="transition hover:bg-gray-50/50 dark:hover:bg-white/2">
            <td class="<?= $ui['td'] ?> whitespace-nowrap"><?= e(substr($item->getCreatedAt(), 0, 16)) ?></td>
            <td class="<?= $ui['td'] ?>"><?= e($item->getActorUsername() !== null ? '@' . $item->getActorUsername() : 'Deleted account') ?></td>
            <td class="px-5 py-4 text-start sm:px-6"><span class="<?= $ui['strong'] ?>"><?= e($item->getActionLabel()) ?></span></td>
            <td class="<?= $ui['td'] ?>">
              <?php if ($item->getTargetId() !== null) : ?>
                <a class="sky-link" href="<?= e(route('admin-user', ['id' => $item->getTargetId()])) ?>">@<?= e((string) $item->getTargetUsername()) ?></a>
              <?php else : ?>
                Deleted account
              <?php endif; ?>
            </td>
            <td class="<?= $ui['td'] ?>"><?= e($item->getDetails() !== '' ? $item->getDetails() : '-') ?></td>
          </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>
  <?php if ($pages > 1) : ?>
    <nav class="flex flex-wrap items-center justify-between gap-3 border-t border-gray-100 px-5 py-4 text-sm sm:px-6 dark:border-white/5" aria-label="Pages of the list">
      <span class="text-gray-500 dark:text-gray-400">Page <?= (int) $page ?> of <?= (int) $pages ?></span>
      <span class="flex items-center gap-3">
        <?php if ($page > 1) : ?><a class="<?= $ui['outline'] ?>" href="<?= e($link(['p' => $page - 1])) ?>">Previous</a><?php endif; ?>
        <?php if ($page < $pages) : ?><a class="<?= $ui['outline'] ?>" href="<?= e($link(['p' => $page + 1])) ?>">Next</a><?php endif; ?>
      </span>
    </nav>
  <?php endif; ?>
</div>
