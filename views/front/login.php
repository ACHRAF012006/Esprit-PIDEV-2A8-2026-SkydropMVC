<?php
/**
 * S1 - Login page. Only the page content: the layout adds head, navbar, footer and scripts.
 * Variables: $errors (field => message), $old (field => previous value).
 */
$bannerTitle = 'Login';
require VIEW_PATH . '/partials/page-banner.php';
?>
    <!-- Login Start -->
    <div class="container ds-main">
        <div>
            <div class="row g-4 justify-content-center align-items-center">
                <div class="col-lg-5 d-none d-lg-block">
                    <h2 class="mb-3">Your local shops, in stock right now</h2>
                    <p class="mb-2 ds-muted"><i class="bi bi-check2 me-2" aria-hidden="true"></i>Find a product in the shops of your district</p>
                    <p class="mb-2 ds-muted"><i class="bi bi-check2 me-2" aria-hidden="true"></i>Order and pick it up, or choose a delivery</p>
                    <p class="mb-2 ds-muted"><i class="bi bi-check2 me-2" aria-hidden="true"></i>Follow your order on the map <span class="simulation-badge">Simulation</span></p>
                </div>

                <div class="col-lg-5 col-md-8">
                    <div class="ds-card">
                        <div class="ds-card-body">
                            <h2 class="h3 mb-4">Sign in</h2>
                            <form method="post" action="<?= e(route('login')) ?>" novalidate data-validate>
                                <?= csrf_field() ?>
                                <div class="row g-3">
                                    <div class="col-12">
                                        <div class="form-floating">
                                            <input type="email" class="form-control <?= isset($errors['email']) ? 'is-invalid' : '' ?>" id="email" name="email" data-required data-email
                                                   placeholder="Your email" value="<?= e($old['email'] ?? '') ?>" autocomplete="email" required>
                                            <label for="email">Email</label>
                                            <?php if (isset($errors['email'])) : ?>
                                                <div class="invalid-feedback"><?= e($errors['email']) ?></div>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                    <div class="col-12">
                                        <div class="form-floating">
                                            <input type="password" class="form-control <?= isset($errors['password']) ? 'is-invalid' : '' ?>" id="password" name="password" data-required
                                                   placeholder="Password" autocomplete="current-password" required>
                                            <label for="password">Password</label>
                                            <?php if (isset($errors['password'])) : ?>
                                                <div class="invalid-feedback"><?= e($errors['password']) ?></div>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                    <div class="col-12">
                                        <button class="btn btn-primary w-100" type="submit">Login</button>
                                    </div>
                                    <div class="col-12 text-center">
                                        <span>No account yet?</span>
                                        <a href="<?= e(route('register')) ?>">Create one</a>
                                    </div>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <!-- Login End -->

