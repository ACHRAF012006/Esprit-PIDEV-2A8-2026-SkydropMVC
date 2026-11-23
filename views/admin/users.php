<?php
/**
 * S1 - Administrator: accounts. Only the page content.
 * Variables: $users (User\UserModel[], this page), $roles [role => label], $filters (role, status, q, sort, dir),
 * $total (accounts matching the filters), $page, $pages, $me (my id),
 * $bulkActions [action => label], $errors and $old of the bulk bar (S2 Youssef Mili).
 */
$pageTitle = 'Users';
require VIEW_PATH . '/partials/back-title.php';
require View::file('partials/user-ui');
// A link to this list with the current filters and some of them changed.
$link = static fn (array $change = []): string => route('admin-users', array_filter($change + $filters + ['p' => $page], static fn ($v) => $v !== '' && $v !== null));
$sortLink = static function (string $key, string $label) use ($filters, $link): string {
    $on = $filters['sort'] === $key;
    $dir = $on && $filters['dir'] === 'desc' ? 'asc' : 'desc';
    $mark = $on ? ($filters['dir'] === 'desc' ? ' <i class="bi bi-arrow-down" aria-hidden="true"></i>' : ' <i class="bi bi-arrow-up" aria-hidden="true"></i>') : '';
    return '<a class="sky-link" href="' . e($link(['sort' => $key, 'dir' => $dir, 'p' => 1])) . '"' . ($on ? ' aria-current="true"' : '') . '>' . e($label) . $mark . '</a>';
};
?>
<div class="<?= $ui['card'] ?>">
  <div class="flex flex-wrap items-center justify-between gap-3 px-5 py-4 sm:px-6">
    <h3 class="text-lg font-semibold text-gray-800 dark:text-white/90"><?= (int) $total ?> account<?= $total === 1 ? '' : 's' ?></h3>
    <div class="flex flex-wrap items-center gap-2">
      <a class="<?= $ui['outline'] ?>" href="<?= e(route('admin-users-export', array_filter($filters, static fn ($v) => $v !== ''))) ?>"><i class="bi bi-download" aria-hidden="true"></i> Export CSV</a>
      <a class="<?= $ui['primary'] ?>" href="<?= e(route('admin-user-form')) ?>"><i class="bi bi-person-plus" aria-hidden="true"></i> Add a user</a>
    </div>
  </div>
  <form method="get" action="<?= e(route('admin-users')) ?>" class="flex flex-wrap items-end gap-3 border-t border-gray-100 px-5 py-4 sm:px-6 dark:border-white/5">
    <input type="hidden" name="page" value="admin-users">
    <input type="hidden" name="sort" value="<?= e($filters['sort']) ?>">
    <input type="hidden" name="dir" value="<?= e($filters['dir']) ?>">
    <div>
      <label class="<?= $ui['label'] ?>" for="userSearch">Name, username or email</label>
      <input class="<?= $ui['input'] ?>" type="search" id="userSearch" name="q" value="<?= e($filters['q']) ?>" maxlength="80">
    </div>
    <div>
      <label class="<?= $ui['label'] ?>" for="userRole">Role</label>
      <select class="<?= $ui['input'] ?>" id="userRole" name="role">
        <option value="">All roles</option>
        <?php foreach ($roles as $key => $label) : ?>
          <option value="<?= e($key) ?>" <?= $key === $filters['role'] ? 'selected' : '' ?>><?= e($label) ?></option>
        <?php endforeach; ?>
      </select>
    </div>
    <div>
      <label class="<?= $ui['label'] ?>" for="userStatus">Status</label>
      <select class="<?= $ui['input'] ?>" id="userStatus" name="status">
        <option value="">Any status</option>
        <option value="active" <?= $filters['status'] === 'active' ? 'selected' : '' ?>>Active</option>
        <option value="deactivated" <?= $filters['status'] === 'deactivated' ? 'selected' : '' ?>>Deactivated</option>
      </select>
    </div>
    <button type="submit" class="<?= $ui['outline'] ?>">Filter</button>
    <?php if ($filters['q'] !== '' || $filters['role'] !== '' || $filters['status'] !== '') : ?>
      <a class="sky-link text-sm font-medium" href="<?= e(route('admin-users')) ?>">Clear the filters</a>
    <?php endif; ?>
  </form>
  <?php if ($users) : ?>
    <?php /* Bulk bar (S2). The row checkboxes join this form with form="bulkForm" (forms cannot be nested). */ ?>
    <form method="post" id="bulkForm" action="<?= e(route('admin-users-bulk', array_filter($filters + ['p' => $page], static fn ($v) => $v !== ''))) ?>" class="flex flex-wrap items-end gap-3 border-t border-gray-100 px-5 py-4 sm:px-6 dark:border-white/5" novalidate data-validate>
      <?= csrf_field() ?>
      <div>
        <label class="<?= $ui['label'] ?>" for="bulkAction">Action</label>
        <select class="<?= $field('action') ?>" id="bulkAction" name="action" data-required<?= isset($errors['action']) ? ' aria-invalid="true" aria-describedby="action-error"' : '' ?>>
          <option value="">Choose an action</option>
          <?php foreach ($bulkActions as $key => $label) : ?>
            <option value="<?= e($key) ?>" <?= $key === ($old['action'] ?? '') ? 'selected' : '' ?>><?= e($label) ?></option>
          <?php endforeach; ?>
        </select>
        <?= $error('action') ?>
      </div>
      <button type="submit" class="<?= $ui['outline'] ?>" id="bulkApply" data-confirm="Apply this action to the selected accounts?">Apply</button>
      <span class="text-sm text-gray-500 dark:text-gray-400" id="bulkCount" aria-live="polite"></span>
      <?php if (isset($errors['ids'])) : ?>
        <div class="w-full"><?= $error('ids') ?></div>
      <?php endif; ?>
    </form>
  <?php endif; ?>
  <div class="max-w-full overflow-x-auto">
    <table class="min-w-full">
      <thead class="border-y border-gray-100 dark:border-white/5">
        <tr>
          <th class="<?= $ui['th'] ?>">
            <label class="sr-only" for="bulkAll">Select all the accounts of this page</label>
            <input type="checkbox" class="size-4" id="bulkAll">
          </th>
          <th class="<?= $ui['th'] ?>"><?= $sortLink('name', 'User') ?></th>
          <th class="<?= $ui['th'] ?>">Role</th>
          <th class="<?= $ui['th'] ?>">Status</th>
          <th class="<?= $ui['th'] ?>"><?= $sortLink('created', 'Registered') ?></th>
          <th class="<?= $ui['th'] ?>"><?= $sortLink('login', 'Last login') ?></th>
          <th class="<?= $ui['th'] ?>">Actions</th>
        </tr>
      </thead>
      <tbody class="divide-y divide-gray-100 dark:divide-white/5">
        <?php if (!$users) : ?>
          <tr><td colspan="7" class="px-5 py-6 text-center text-sm text-gray-500 sm:px-6 dark:text-gray-400">No account matches these filters.</td></tr>
        <?php endif; ?>
        <?php foreach ($users as $u) : ?>
          <tr class="transition hover:bg-gray-50/50 dark:hover:bg-white/2">
            <td class="px-5 py-4 text-start sm:px-6">
              <label class="sr-only" for="bulk<?= (int) $u->getId() ?>">Select <?= e($u->getFullName()) ?></label>
              <input type="checkbox" class="size-4" id="bulk<?= (int) $u->getId() ?>" name="ids[]" value="<?= (int) $u->getId() ?>" form="bulkForm" data-bulk-row <?= in_array($u->getId(), $old['ids'] ?? [], true) ? 'checked' : '' ?>>
            </td>
            <td class="px-5 py-4 text-start sm:px-6">
              <a class="<?= $ui['strong'] ?> sky-link" href="<?= e(route('admin-user', ['id' => $u->getId()])) ?>"><?= e($u->getFullName()) ?></a>
              <span class="<?= $ui['small'] ?>"><?= e($u->getEmail()) ?></span>
            </td>
            <td class="<?= $ui['td'] ?>">
              <?php if ($u->getId() === $me) : ?>
                <?= e($u->getRoleLabel()) ?> (you)
              <?php else : ?>
                <form method="post" action="<?= e(route('admin-user-role', ['id' => $u->getId()])) ?>" class="flex items-center gap-2">
                  <?= csrf_field() ?>
                  <label class="sr-only" for="role<?= $u->getId() ?>">Role of <?= e($u->getFullName()) ?></label>
                  <select class="<?= $ui['input'] ?>" id="role<?= $u->getId() ?>" name="role">
                    <?php foreach ($roles as $key => $label) : ?>
                      <option value="<?= e($key) ?>" <?= $key === $u->getRole() ? 'selected' : '' ?>><?= e($label) ?></option>
                    <?php endforeach; ?>
                  </select>
                  <button type="submit" class="sky-link text-sm font-medium">Save</button>
                </form>
              <?php endif; ?>
            </td>
            <td class="px-5 py-4 text-start sm:px-6">
              <span class="<?= $ui['badge'] ?> <?= $ui['status'][$u->isActive() ? 'active' : 'deactivated'] ?>"><?= $u->isActive() ? 'Active' : 'Deactivated' ?></span>
            </td>
            <td class="<?= $ui['td'] ?>"><?= e(substr($u->getCreatedAt(), 0, 10)) ?></td>
            <td class="<?= $ui['td'] ?>"><?= e($u->getLastLoginAt() ? substr($u->getLastLoginAt(), 0, 16) : 'Never') ?></td>
            <td class="<?= $ui['td'] ?>">
              <div class="flex flex-wrap items-center gap-3">
                <a class="sky-link text-sm font-medium" href="<?= e(route('admin-user-form', ['id' => $u->getId()])) ?>">Edit</a>
                <?php if ($u->getId() !== $me) : ?>
                  <form method="post" action="<?= e(route('admin-user-toggle', ['id' => $u->getId()])) ?>">
                    <?= csrf_field() ?>
                    <button type="submit" class="sky-link text-sm font-medium"><?= $u->isActive() ? 'Deactivate' : 'Activate' ?></button>
                  </form>
                <?php endif; ?>
              </div>
            </td>
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
