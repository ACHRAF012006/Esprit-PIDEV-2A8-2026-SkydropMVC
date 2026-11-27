<?php
/**
 * S4 Zamel - Checkout: summary of the cart, optional coupon code, note, simulated payment. Only the page content.
 * Variables: $items (OrderItemModel[]), $shopName, $subtotal (computed by the server), $couponsReady (bool: the coupon
 * part is installed, otherwise the field is hidden), $payments [key => label], $errors, $old.
 */
$bannerTitle = 'Checkout';
$bannerParents = [['My cart', 'cart']];
require VIEW_PATH . '/partials/page-banner.php';
$invalid = static fn (string $name): string => isset($errors[$name]) ? 'is-invalid' : '';
$payment = (string) ($old['payment'] ?? 'pickup');
?>
    <div class="container ds-main">
        <form method="post" action="<?= e(route('checkout')) ?>" novalidate data-validate>
            <?= csrf_field() ?>
            <div class="row g-4">
                <div class="col-lg-7">
                    <div class="ds-card mb-4">
                        <div class="ds-card-body">
                            <h2 class="h5 mb-3">Payment <span class="simulation-badge">Simulation</span></h2>
                            <fieldset class="ds-choice-list">
                                <legend class="visually-hidden">How do you pay?</legend>
                                <?php foreach ($payments as $key => $label) : ?>
                                    <label class="ds-choice">
                                        <input type="radio" class="form-check-input" name="payment" value="<?= e($key) ?>" <?= $payment === $key ? 'checked' : '' ?>>
                                        <span class="ds-choice-title"><?= e($label) ?></span>
                                        <span class="ds-choice-detail"><?= $key === 'now'
                                            ? 'No real payment: the order is only marked as paid.'
                                            : 'You pay the shop when you collect the order.' ?></span>
                                    </label>
                                <?php endforeach; ?>
                            </fieldset>
                            <?php if (isset($errors['payment'])) : ?><p class="ds-field-error"><?= e($errors['payment']) ?></p><?php endif; ?>
                        </div>
                    </div>

                    <div class="ds-card">
                        <div class="ds-card-body">
                            <h2 class="h5 mb-3">Details</h2>
                            <div class="row g-3">
                                <?php if ($couponsReady) : ?>
                                    <div class="col-12">
                                        <div class="form-floating">
                                            <input type="text" class="form-control <?= $invalid('coupon_code') ?>" id="coupon_code" name="coupon_code" maxlength="20"
                                                   placeholder="Coupon code" value="<?= e((string) ($old['coupon_code'] ?? '')) ?>" autocomplete="off"
                                                   data-pattern="^[A-Za-z0-9]{3,20}$" data-pattern-message="This coupon code does not exist.">
                                            <label for="coupon_code">Coupon code (optional)</label>
                                            <?php if (isset($errors['coupon_code'])) : ?><div class="invalid-feedback"><?= e($errors['coupon_code']) ?></div><?php endif; ?>
                                        </div>
                                    </div>
                                <?php endif; ?>
                                <div class="col-12">
                                    <div class="form-floating">
                                        <textarea class="form-control order-note <?= $invalid('note') ?>" id="note" name="note" placeholder="Note for the shop"
                                                  data-max-length="300"><?= e((string) ($old['note'] ?? '')) ?></textarea>
                                        <label for="note">Note for the shop (optional, 300 characters at most)</label>
                                        <?php if (isset($errors['note'])) : ?><div class="invalid-feedback"><?= e($errors['note']) ?></div><?php endif; ?>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="col-lg-5">
                    <div class="ds-card">
                        <div class="ds-card-body">
                            <h2 class="h5 mb-3">Your order at <?= e($shopName) ?></h2>
                            <dl class="ds-rows mb-3">
                                <?php foreach ($items as $item) : ?>
                                    <div><dt><?= (int) $item->getQuantity() ?> x <?= e($item->getProductName()) ?></dt><dd><?= e(OrderController::price($item->getLineTotal())) ?></dd></div>
                                <?php endforeach; ?>
                                <div class="ds-total"><dt>Subtotal</dt><dd><?= e(OrderController::price($subtotal)) ?></dd></div>
                            </dl>
                            <p class="ds-muted small"><?= $couponsReady ? 'A coupon discount is computed when you place the order.' : '' ?> The total is computed again by SKYDROP with the current prices.</p>
                            <button type="submit" class="btn btn-primary w-100">Place my order</button>
                            <a class="btn btn-outline w-100 mt-2" href="<?= e(route('cart')) ?>">Back to my cart</a>
                        </div>
                    </div>
                </div>
            </div>
        </form>
    </div>
