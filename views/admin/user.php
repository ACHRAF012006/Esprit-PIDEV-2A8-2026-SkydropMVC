<?php
/**
 * S1 - Administrator: one account in detail, its quick actions, a new password, and the blocks other modules add
 * for this account (slot "admin-user": shop, orders, ads...). Only the page content.
 * Variables: $user (User\UserModel), $me (my id), $errors, $activity (UserActivityModel[], last 10 actions on this account, S3 Islem).
 */
$pageTitle = $user->getFullName();
require VIEW_PATH . '/partials/back-title.php';
require View::file('partials/user-ui');
$mine = $user->getId() === $me;
$blocks = slot('admin-user', ['user' => $user]);
?>
<div class="grid grid-cols-12 gap-6">
  <div class="<?= $ui['card'] ?> p-5 sm:p-6 col-span-12 xl:col-span-7">
    <div class="flex flex-wrap items-start justify-between gap-3">
      <div>
        <h3 class="text-lg font-semibold text-gray-800 dark:text-white/90"><?= e($user->getFullName()) ?></h3>
        <p class="<?= $ui['small'] ?>">@<?= e($user->getUsername()) ?></p>
      </div>
      <span class="<?= $ui['badge'] ?> <?= $ui['status'][$user->isActive() ? 'active' : 'deactivated'] ?>"><?= $user->isActive() ? 'Active' : 'Deactivated' ?></span>
    </div>
    <dl class="mt-5 grid grid-cols-1 gap-4 sm:grid-cols-2">
      <div><dt class="<?= $ui['small'] ?>">Email</dt><dd class="<?= $ui['strong'] ?>"><?= e($user->getEmail()) ?></dd></div>
      <div><dt class="<?= $ui['small'] ?>">Phone</dt><dd class="<?= $ui['strong'] ?>"><?= e($user->getPhone() ?? 'Not given') ?></dd></div>
      <div><dt class="<?= $ui['small'] ?>">Role</dt><dd class="<?= $ui['strong'] ?>"><?= e($user->getRoleLabel()) ?></dd></div>
      <div><dt class="<?= $ui['small'] ?>">Registered</dt><dd class="<?= $ui['strong'] ?>"><?= e(substr($user->getCreatedAt(), 0, 16)) ?></dd></div>
      <div><dt class="<?= $ui['small'] ?>">Last login</dt><dd class="<?= $ui['strong'] ?>"><?= e($user->getLastLoginAt() ? substr($user->getLastLoginAt(), 0, 16) : 'Never') ?></dd></div>
      <div><dt class="<?= $ui['small'] ?>">Password</dt><dd class="<?= $ui['strong'] ?>"><?= $user->mustChangePassword() ? 'Temporary: changed at the next login' : 'Chosen by the user' ?></dd></div>
    </dl>
    <div class="mt-6 flex flex-wrap items-center gap-3">
      <a class="<?= $ui['primary'] ?>" href="<?= e(route('admin-user-form', ['id' => $user->getId()])) ?>">Edit</a>
      <?php if (!$mine) : ?>
        <form method="post" action="<?= e(route('admin-user-toggle', ['id' => $user->getId()])) ?>">
          <?= csrf_field() ?>
          <input type="hidden" name="back" value="admin-user">
          <input type="hidden" name="back_id" value="<?= (int) $user->getId() ?>">
          <button type="submit" class="<?= $ui['outline'] ?>"><?= $user->isActive() ? 'Deactivate' : 'Activate' ?></button>
        </form>
        <form method="post" action="<?= e(route('admin-user-delete', ['id' => $user->getId()])) ?>"
              data-confirm="Delete the account of <?= e($user->getFullName()) ?>? This cannot be undone. An account still used by a shop or orders is deactivated instead.">
          <?= csrf_field() ?>
          <button type="submit" class="<?= $ui['outline'] ?>">Delete</button>
        </form>
      <?php else : ?>
        <span class="<?= $ui['small'] ?>">This is your account: you cannot delete, deactivate or demote it here.</span>
      <?php endif; ?>
    </div>
  </div>

  <div class="<?= $ui['card'] ?> col-span-12 p-5 sm:p-6 xl:col-span-5">
    <h3 class="text-lg font-semibold text-gray-800 dark:text-white/90">Give a new password</h3>
    <p class="<?= $ui['help'] ?>">For a user who forgot theirs. Tell it to them: they must choose their own at the next login.</p>
    <form class="mt-4" method="post" action="<?= e(route('admin-user-password', ['id' => $user->getId()])) ?>" novalidate data-validate>
      <?= csrf_field() ?>
      <input type="email" name="username" value="<?= e($user->getEmail()) ?>" autocomplete="username" hidden>
      <label class="<?= $ui['label'] ?>" for="new_password">New password</label>
      <input class="<?= $field('new_password') ?>" type="password" id="new_password" name="new_password" data-required data-min-length="8"
             data-pattern="^(?=.*[A-Za-z])(?=.*\d).+$" data-pattern-message="Use at least one letter and one digit." autocomplete="new-password">
      <?= $error('new_password') ?>
      <label class="<?= $ui['label'] ?> mt-4" for="new_password_confirm">Type it again</label>
      <input class="<?= $ui['input'] ?>" type="password" id="new_password_confirm" name="new_password_confirm" data-required data-match="new_password" autocomplete="new-password">
      <button type="submit" class="<?= $ui['outline'] ?> mt-4">Save the new password</button>
    </form>
  </div>
</div>

<!-- S3 Islem: the last actions of administrators on this account (table user_activity). -->
<div class="<?= $ui['card'] ?> mt-6">
  <div class="flex flex-wrap items-center justify-between gap-3 px-5 py-4 sm:px-6">
    <h3 class="text-lg font-semibold text-gray-800 dark:text-white/90">Recent activity</h3>
    <a class="sky-link text-sm font-medium" href="<?= e(route('admin-activity')) ?>">See all activity</a>
  </div>
  <div class="max-w-full overflow-x-auto">
    <table class="min-w-full">
      <thead class="border-y border-gray-100 dark:border-white/5">
        <tr>
          <th class="<?= $ui['th'] ?>">Date</th>
          <th class="<?= $ui['th'] ?>">Administrator</th>
          <th class="<?= $ui['th'] ?>">Action</th>
          <th class="<?= $ui['th'] ?>">Details</th>
        </tr>
      </thead>
      <tbody class="divide-y divide-gray-100 dark:divide-white/5">
        <?php if (empty($activity)) : ?>
          <tr><td colspan="4" class="px-5 py-6 text-center text-sm text-gray-500 sm:px-6 dark:text-gray-400">No administrator has changed this account yet.</td></tr>
        <?php endif; ?>
        <?php foreach ($activity ?? [] as $item) : ?>
          <tr>
            <td class="<?= $ui['td'] ?> whitespace-nowrap"><?= e(substr($item->getCreatedAt(), 0, 16)) ?></td>
            <td class="<?= $ui['td'] ?>"><?= e($item->getActorUsername() !== null ? '@' . $item->getActorUsername() : 'Deleted account') ?></td>
            <td class="px-5 py-4 text-start sm:px-6"><span class="<?= $ui['strong'] ?>"><?= e($item->getActionLabel()) ?></span></td>
            <td class="<?= $ui['td'] ?>"><?= e($item->getDetails() !== '' ? $item->getDetails() : '-') ?></td>
          </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>
</div>

<?php if (trim($blocks) !== '') : ?>
  <div class="mt-6"><?= $blocks ?></div>
<?php endif; ?>
