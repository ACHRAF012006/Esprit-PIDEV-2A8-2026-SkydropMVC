<?php
/**
 * S5 Karim - "Forgot your password?" page. Only the page content: the layout adds head, navbar, footer and scripts.
 * Variables: $errors (field => message), $old (field => previous value), $resetLink (simulation link or null).
 */
$bannerTitle = 'Forgot your password?';
require VIEW_PATH . '/partials/page-banner.php';
?>
    <!-- Forgot password Start -->
    <div class="container ds-main">
        <div class="row justify-content-center">
            <div class="col-lg-5 col-md-8">
                <div class="ds-card">
                    <div class="ds-card-body">
                        <h2 class="h3 mb-2">Reset your password</h2>
                        <p class="ds-muted mb-4">Type the email of your account. We create a link to choose a new password; it works once, for 30 minutes.</p>

                        <?php if ($resetLink !== null) : ?>
                            <p class="ds-note"><strong>Simulation.</strong> No email is sent: here is the link the email would contain.</p>
                            <p class="mb-4 text-break"><a href="<?= e($resetLink) ?>"><?= e($resetLink) ?></a></p>
                        <?php endif; ?>

                        <form method="post" action="<?= e(route('password-forgot')) ?>" novalidate data-validate>
                            <?= csrf_field() ?>
                            <div class="row g-3">
                                <div class="col-12">
                                    <div class="form-floating">
                                        <input type="email" class="form-control <?= isset($errors['email']) ? 'is-invalid' : '' ?>" id="email" name="email" data-required data-email data-max-length="150"
                                               placeholder="Your email" value="<?= e($old['email'] ?? '') ?>" autocomplete="email" required>
                                        <label for="email">Email</label>
                                        <?php if (isset($errors['email'])) : ?>
                                            <div class="invalid-feedback"><?= e($errors['email']) ?></div>
                                        <?php endif; ?>
                                    </div>
                                </div>
                                <div class="col-12">
                                    <button class="btn btn-primary w-100" type="submit">Create a reset link</button>
                                </div>
                                <div class="col-12 text-center">
                                    <span>Remembered it?</span>
                                    <a href="<?= e(route('login')) ?>">Back to login</a>
                                </div>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <!-- Forgot password End -->
