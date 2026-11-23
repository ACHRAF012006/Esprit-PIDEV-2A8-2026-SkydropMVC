<?php
/**
 * S1 - Administrator: add or edit an account. Only the page content.
 * Variables: $user (?User\UserModel, null = new account), $roles [role => label], $me (my id), $errors, $old.
 */
$pageTitle = $user ? 'Edit ' . $user->getFullName() : 'Add a user';
require VIEW_PATH . '/partials/back-title.php';
require View::file('partials/user-ui');
$value = static fn (string $name, string $current): string => (string) ($old[$name] ?? $current);
$active = $old ? isset($old['is_active']) : ($user ? $user->isActive() : true);
$role = $value('role', $user ? $user->getRole() : 'customer');
?>
<div class="<?= $ui['card'] ?> p-5 sm:p-6">
  <form method="post" action="<?= e(route('admin-user-form')) ?>" novalidate data-validate>
    <?= csrf_field() ?>
    <input type="hidden" name="id" value="<?= $user ? (int) $user->getId() : 0 ?>">
    <div class="grid grid-cols-1 gap-5 sm:grid-cols-2">
      <div>
        <label class="<?= $ui['label'] ?>" for="full_name">Full name</label>
        <input class="<?= $field('full_name') ?>" type="text" id="full_name" name="full_name" data-required data-min-length="3" data-max-length="120"
               value="<?= e($value('full_name', $user ? $user->getFullName() : '')) ?>" maxlength="120" autocomplete="off">
        <?= $error('full_name') ?>
      </div>
      <div>
        <label class="<?= $ui['label'] ?>" for="username">Username</label>
        <input class="<?= $field('username') ?>" type="text" id="username" name="username" data-required data-min-length="3" data-max-length="50"
               data-pattern="^[a-zA-Z0-9_\-]{3,50}$" data-pattern-message="Use 3 to 50 letters, digits, _ or -."
               value="<?= e($value('username', $user ? $user->getUsername() : '')) ?>" maxlength="50" autocomplete="off">
        <?= $error('username') ?>
      </div>
      <div>
        <label class="<?= $ui['label'] ?>" for="email">Email</label>
        <input class="<?= $field('email') ?>" type="email" id="email" name="email" data-required data-email data-max-length="150"
               value="<?= e($value('email', $user ? $user->getEmail() : '')) ?>" maxlength="150" autocomplete="off">
        <?= $error('email') ?>
      </div>
      <div>
        <label class="<?= $ui['label'] ?>" for="phone">Phone (optional)</label>
        <input class="<?= $field('phone') ?>" type="tel" id="phone" name="phone" data-phone
               value="<?= e($value('phone', $user ? (string) $user->getPhone() : '')) ?>" autocomplete="off">
        <?= $error('phone', '8 to 15 digits, for example 20123456.') ?>
      </div>
      <div>
        <label class="<?= $ui['label'] ?>" for="role">Role</label>
        <select class="<?= $field('role') ?>" id="role" name="role" data-required>
          <?php foreach ($roles as $key => $label) : ?>
            <option value="<?= e($key) ?>" <?= $key === $role ? 'selected' : '' ?>><?= e($label) ?></option>
          <?php endforeach; ?>
        </select>
        <?= $error('role') ?>
      </div>
      <div>
        <label class="<?= $ui['label'] ?>" for="password"><?= $user ? 'New password (leave empty to keep it)' : 'First password' ?></label>
        <input class="<?= $field('password') ?>" type="password" id="password" name="password" <?= $user ? '' : 'data-required' ?> data-min-length="8"
               data-pattern="^(?=.*[A-Za-z])(?=.*\d).+$" data-pattern-message="Use at least one letter and one digit." autocomplete="new-password">
        <?= $error('password', 'At least 8 characters with a letter and a digit. The user chooses their own at the next login.') ?>
      </div>
    </div>
    <label class="mt-5 flex items-center gap-3 text-sm text-gray-700 dark:text-gray-400" for="is_active">
      <input type="checkbox" class="size-4" id="is_active" name="is_active" value="1" <?= $active ? 'checked' : '' ?>>
      Active (the account can log in)
    </label>
    <div class="mt-6 flex flex-wrap items-center gap-3">
      <button type="submit" class="<?= $ui['primary'] ?>"><?= $user ? 'Save the account' : 'Add the account' ?></button>
      <a class="<?= $ui['outline'] ?>" href="<?= e($user ? route('admin-user', ['id' => $user->getId()]) : route('admin-users')) ?>">Cancel</a>
    </div>
  </form>
</div>
