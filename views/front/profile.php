<?php
/**
 * S1 - Profile page: my details, my password, and the blocks that modules add for the signed-in user
 * (slot "profile": my orders, my delivery addresses, my favourites, my coupons...).
 * Variables: $user (User\UserModel), $errors (field => message), $old (field => previous value).
 */
$bannerTitle = 'My profile';
require VIEW_PATH . '/partials/page-banner.php';
$invalid = static fn (string $name): string => isset($errors[$name]) ? 'is-invalid' : '';
$blocks = slot('profile', ['user' => $user]);
?>
    <div class="container ds-main">
        <div class="row g-4">
            <div class="col-lg-4">
                <div class="ds-card">
                    <div class="ds-card-body">
                        <h2 class="h4 mb-1"><?= e($user->getFullName()) ?></h2>
                        <p class="ds-muted mb-2"><?= e($user->getEmail()) ?></p>
                        <span class="ds-tag"><?= e($user->getRoleLabel()) ?></span>
                        <dl class="ds-rows mt-3 mb-0">
                            <div><dt>Member since</dt><dd><?= e(substr($user->getCreatedAt(), 0, 10)) ?></dd></div>
                            <div><dt>Last sign-in</dt><dd><?= e($user->getLastLoginAt() ? substr($user->getLastLoginAt(), 0, 16) : 'now') ?></dd></div>
                        </dl>
                        <?php if (in_array($user->getRole(), ['shop_owner', 'advertiser', 'admin'], true)) : ?>
                            <a class="btn btn-outline w-100 mt-3" href="<?= e(route(AuthController::homePageFor($user->getRole()))) ?>">Open my back office</a>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

            <div class="col-lg-8">
                <div class="ds-card mb-4">
                    <div class="ds-card-body">
                        <h2 class="h4 mb-3">My details</h2>
                        <form method="post" action="<?= e(route('profile')) ?>" novalidate data-validate>
                            <?= csrf_field() ?>
                            <div class="row g-3">
                                <div class="col-md-6">
                                    <div class="form-floating">
                                        <input type="text" class="form-control <?= $invalid('full_name') ?>" id="full_name" name="full_name" data-required data-min-length="3" data-max-length="120"
                                               placeholder="Full name" value="<?= e($old['full_name'] ?? $user->getFullName()) ?>" autocomplete="name">
                                        <label for="full_name">Full name</label>
                                        <?php if (isset($errors['full_name'])) : ?><div class="invalid-feedback"><?= e($errors['full_name']) ?></div><?php endif; ?>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="form-floating">
                                        <input type="tel" class="form-control <?= $invalid('phone') ?>" id="phone" name="phone" data-phone
                                               placeholder="Phone" value="<?= e($old['phone'] ?? (string) $user->getPhone()) ?>" autocomplete="tel">
                                        <label for="phone">Phone (optional)</label>
                                        <?php if (isset($errors['phone'])) : ?><div class="invalid-feedback"><?= e($errors['phone']) ?></div><?php endif; ?>
                                    </div>
                                </div>
                                <div class="col-12">
                                    <div class="form-floating">
                                        <input type="email" class="form-control" id="email" value="<?= e($user->getEmail()) ?>" placeholder="Email" disabled>
                                        <label for="email">Email (cannot be changed)</label>
                                    </div>
                                </div>
                                <div class="col-12">
                                    <button class="btn btn-primary" type="submit">Save my details</button>
                                </div>
                            </div>
                        </form>
                    </div>
                </div>

                <div class="ds-card">
                    <div class="ds-card-body">
                        <h2 class="h4 mb-3">My password</h2>
                        <?php if ($user->mustChangePassword()) : ?>
                            <p class="ds-note"><strong>Choose your own password.</strong> An administrator gave you a temporary one: the other pages open again once you change it.</p>
                        <?php endif; ?>
                        <form method="post" action="<?= e(route('profile-password')) ?>" novalidate data-validate>
                            <?= csrf_field() ?>
                            <input type="email" name="username" value="<?= e($user->getEmail()) ?>" autocomplete="username" hidden>

                            <div class="row g-3">
                                <div class="col-12">
                                    <div class="form-floating">
                                        <input type="password" class="form-control <?= $invalid('current_password') ?>" id="current_password" name="current_password" data-required
                                               placeholder="Current password" autocomplete="current-password">
                                        <label for="current_password">Current password</label>
                                        <?php if (isset($errors['current_password'])) : ?><div class="invalid-feedback"><?= e($errors['current_password']) ?></div><?php endif; ?>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="form-floating">
                                        <input type="password" class="form-control <?= $invalid('new_password') ?>" id="new_password" name="new_password" data-required data-min-length="8" data-pattern="^(?=.*[A-Za-z])(?=.*\d).+$" data-pattern-message="Use at least one letter and one digit."
                                               placeholder="New password" autocomplete="new-password">
                                        <label for="new_password">New password</label>
                                        <?php if (isset($errors['new_password'])) : ?><div class="invalid-feedback"><?= e($errors['new_password']) ?></div><?php endif; ?>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="form-floating">
                                        <input type="password" class="form-control <?= $invalid('new_password_confirm') ?>" id="new_password_confirm" name="new_password_confirm" data-required data-match="new_password"
                                               placeholder="Confirm the new password" autocomplete="new-password">
                                        <label for="new_password_confirm">Confirm the new password</label>
                                        <?php if (isset($errors['new_password_confirm'])) : ?><div class="invalid-feedback"><?= e($errors['new_password_confirm']) ?></div><?php endif; ?>
                                    </div>
                                </div>
                                <div class="col-12">
                                    <button class="btn btn-outline" type="submit">Change my password</button>
                                </div>
                            </div>
                        </form>
                    </div>
                </div>

                <div class="ds-card mt-4">
                    <div class="ds-card-body">
                        <h2 class="h4 mb-1">Delete my account</h2>
                        <p class="ds-muted mb-3">Your account and your details are removed and you are logged out. If your orders or your shop still use it, it is closed (deactivated) instead.</p>
                        <form method="post" action="<?= e(route('profile-delete')) ?>" novalidate data-validate
                              data-confirm="Delete your SKYDROP account? This cannot be undone.">
                            <?= csrf_field() ?>
                            <input type="email" name="username" value="<?= e($user->getEmail()) ?>" autocomplete="username" hidden>
                            <div class="row g-3 align-items-start">
                                <div class="col-md-6">
                                    <div class="form-floating">
                                        <input type="password" class="form-control <?= $invalid('delete_password') ?>" id="delete_password" name="delete_password" data-required
                                               placeholder="My password" autocomplete="current-password">
                                        <label for="delete_password">My password, to confirm</label>
                                        <?php if (isset($errors['delete_password'])) : ?><div class="invalid-feedback"><?= e($errors['delete_password']) ?></div><?php endif; ?>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <button class="btn btn-outline" type="submit">Delete my account</button>
                                </div>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>

        <?php if (trim($blocks) !== '') : ?>
            <div class="mt-4"><?= $blocks ?></div>
        <?php endif; ?>
    </div>
