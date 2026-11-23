<?php
/**
 * S1 - Register page (customer, shop owner or advertiser). Only the page content.
 * Variables: $errors (field => message), $old (field => previous value).
 */
$bannerTitle = 'Create an account';
require VIEW_PATH . '/partials/page-banner.php';
$role = $old['role'] ?? 'customer';
$field = static function (string $name) use ($errors): string {
    return isset($errors[$name]) ? 'is-invalid' : '';
};
?>
    <!-- Register Start -->
    <div class="container ds-main">
        <div>
            <div class="row justify-content-center">
                <div class="col-lg-7 col-md-10">
                    <div class="ds-card">
                        <div class="ds-card-body">
                            <h2 class="h3 mb-2">Join SKYDROP</h2>
                            <p class="ds-muted mb-4">Shop owners and advertisers manage their shop or their ads from the back office.</p>
                            <form method="post" action="<?= e(route('register')) ?>" novalidate data-validate>
                                <?= csrf_field() ?>
                                <div class="row g-3">
                                    <div class="col-12">
                                        <span class="d-block mb-2 fw-medium">I am</span>
                                        <div class="form-check form-check-inline">
                                            <input class="form-check-input" type="radio" name="role" id="roleCustomer" value="customer" <?= $role === 'customer' ? 'checked' : '' ?>>
                                            <label class="form-check-label" for="roleCustomer">a customer</label>
                                        </div>
                                        <div class="form-check form-check-inline">
                                            <input class="form-check-input" type="radio" name="role" id="roleOwner" value="shop_owner" <?= $role === 'shop_owner' ? 'checked' : '' ?>>
                                            <label class="form-check-label" for="roleOwner">a shop owner</label>
                                        </div>
                                        <div class="form-check form-check-inline">
                                            <input class="form-check-input" type="radio" name="role" id="roleAdvertiser" value="advertiser" <?= $role === 'advertiser' ? 'checked' : '' ?>>
                                            <label class="form-check-label" for="roleAdvertiser">an advertiser</label>
                                        </div>
                                        <?php if (isset($errors['role'])) : ?>
                                            <div class="text-danger small"><?= e($errors['role']) ?></div>
                                        <?php endif; ?>
                                    </div>
                                    <div class="col-12">
                                        <div class="form-floating">
                                            <input type="text" class="form-control <?= $field('full_name') ?>" id="full_name" name="full_name" data-required data-min-length="3" data-max-length="120"
                                                   placeholder="Full name" value="<?= e($old['full_name'] ?? '') ?>" autocomplete="name" required>
                                            <label for="full_name">Full name</label>
                                            <?php if (isset($errors['full_name'])) : ?>
                                                <div class="invalid-feedback"><?= e($errors['full_name']) ?></div>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                    <div class="col-12">
                                        <div class="form-floating">
                                            <input type="text" class="form-control <?= $field('username') ?>" id="username" name="username"
                                                   placeholder="Username" value="<?= e($old['username'] ?? '') ?>" autocomplete="username"
                                                   data-required data-min-length="3" data-max-length="50" data-pattern="^[a-zA-Z0-9_\-]{3,50}$"
                                                   data-pattern-message="Use 3 to 50 letters, digits, _ or -.">
                                            <label for="username">Username (letters, digits, _ or -)</label>
                                            <?php if (isset($errors['username'])) : ?>
                                                <div class="invalid-feedback"><?= e($errors['username']) ?></div>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                    <div class="col-12">
                                        <div class="form-floating">
                                            <input type="email" class="form-control <?= $field('email') ?>" id="email" name="email" data-required data-email data-max-length="150"
                                                   placeholder="Email" value="<?= e($old['email'] ?? '') ?>" autocomplete="email" required>
                                            <label for="email">Email</label>
                                            <?php if (isset($errors['email'])) : ?>
                                                <div class="invalid-feedback"><?= e($errors['email']) ?></div>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="form-floating">
                                            <input type="password" class="form-control <?= $field('password') ?>" id="password" name="password" data-required data-min-length="8" data-pattern="^(?=.*[A-Za-z])(?=.*\d).+$" data-pattern-message="Use at least one letter and one digit."
                                                   placeholder="Password" autocomplete="new-password" required>
                                            <label for="password">Password (8 characters or more)</label>
                                            <?php if (isset($errors['password'])) : ?>
                                                <div class="invalid-feedback"><?= e($errors['password']) ?></div>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="form-floating">
                                            <input type="password" class="form-control <?= $field('password_confirm') ?>" id="password_confirm" name="password_confirm" data-required data-match="password"
                                                   placeholder="Confirm password" autocomplete="new-password" required>
                                            <label for="password_confirm">Confirm password</label>
                                            <?php if (isset($errors['password_confirm'])) : ?>
                                                <div class="invalid-feedback"><?= e($errors['password_confirm']) ?></div>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                    <div class="col-12">
                                        <button class="btn btn-primary w-100" type="submit">Create my account</button>
                                    </div>
                                    <div class="col-12 text-center">
                                        <span>Already registered?</span>
                                        <a href="<?= e(route('login')) ?>">Login</a>
                                    </div>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <!-- Register End -->
