<?php
/**
 * S5 Karim - "Choose a new password" page, opened from a reset link. Only the page content.
 * Variables: $token (from the link, sent back in a hidden field), $errors (field => message).
 */
$bannerTitle = 'Choose a new password';
require VIEW_PATH . '/partials/page-banner.php';
$field = static function (string $name) use ($errors): string {
    return isset($errors[$name]) ? 'is-invalid' : '';
};
?>
    <!-- Reset password Start -->
    <div class="container ds-main">
        <div class="row justify-content-center">
            <div class="col-lg-5 col-md-8">
                <div class="ds-card">
                    <div class="ds-card-body">
                        <h2 class="h3 mb-2">New password</h2>
                        <p class="ds-muted mb-4">8 characters or more, with at least one letter and one digit.</p>
                        <form method="post" action="<?= e(route('password-reset')) ?>" novalidate data-validate>
                            <?= csrf_field() ?>
                            <input type="hidden" name="token" value="<?= e($token) ?>">
                            <div class="row g-3">
                                <div class="col-12">
                                    <div class="form-floating">
                                        <input type="password" class="form-control <?= $field('password') ?>" id="password" name="password" data-required data-min-length="8" data-pattern="^(?=.*[A-Za-z])(?=.*\d).+$" data-pattern-message="Use at least one letter and one digit."
                                               placeholder="New password" autocomplete="new-password" required>
                                        <label for="password">New password (8 characters or more)</label>
                                        <?php if (isset($errors['password'])) : ?>
                                            <div class="invalid-feedback"><?= e($errors['password']) ?></div>
                                        <?php endif; ?>
                                    </div>
                                </div>
                                <div class="col-12">
                                    <div class="form-floating">
                                        <input type="password" class="form-control <?= $field('password_confirm') ?>" id="password_confirm" name="password_confirm" data-required data-match="password"
                                               placeholder="Confirm the new password" autocomplete="new-password" required>
                                        <label for="password_confirm">Confirm the new password</label>
                                        <?php if (isset($errors['password_confirm'])) : ?>
                                            <div class="invalid-feedback"><?= e($errors['password_confirm']) ?></div>
                                        <?php endif; ?>
                                    </div>
                                </div>
                                <div class="col-12">
                                    <button class="btn btn-primary w-100" type="submit">Save my new password</button>
                                </div>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <!-- Reset password End -->
