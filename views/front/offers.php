<?php
/**
 * S1 - Offers: the coupons customers can use today. Only the page content.
 * Variables: $coupons (CouponModel[]), $describe [coupon id => what it gives, in plain words],
 * $myUses [coupon id => my number of uses], $signedIn (bool).
 */
$bannerTitle = 'Offers';
require VIEW_PATH . '/partials/page-banner.php';
?>
    <div class="container ds-main">
        <div class="ds-section-head">
            <h2 class="h4">Coupons you can use today</h2>
            <p>Type the code at checkout. The discount is checked again when you order.</p>
        </div>
        <?php if (!$coupons) : ?>
            <div class="ds-empty">
                <h2 class="h5">No offer right now</h2>
                <p class="ds-muted mb-0">New coupons appear here as soon as they start.</p>
            </div>
        <?php else : ?>
            <div class="row g-4">
                <?php foreach ($coupons as $c) :
                    $used = $myUses[$c->getId()] ?? 0;
                    $left = $c->getPerUserLimit() !== null ? max(0, $c->getPerUserLimit() - $used) : null;
                ?>
                    <div class="col-sm-6 col-lg-4">
                        <div class="ds-card ds-card-row">
                            <div class="ds-card-body">
                                <p class="ds-muted mb-1"><?= e($c->getTypeName()) ?></p>
                                <h3 class="ds-card-title"><?= e($c->getCode()) ?></h3>
                                <p class="mb-2"><?= e($describe[$c->getId()] ?? '') ?></p>
                                <dl class="ds-rows mb-0">
                                    <div><dt>Valid until</dt><dd><?= e($c->getEndsAt()) ?></dd></div>
                                    <div><dt>Per customer</dt><dd><?= $c->getPerUserLimit() !== null ? (int) $c->getPerUserLimit() . ' time' . ($c->getPerUserLimit() > 1 ? 's' : '') : 'No limit' ?></dd></div>
                                </dl>
                            </div>
                            <div class="ds-card-footer">
                                <?php if (!$signedIn) : ?>
                                    <a href="<?= e(route('login')) ?>">Log in</a> to use it.
                                <?php elseif ($left === 0) : ?>
                                    <span class="ds-tag ds-tag-muted">Already used</span>
                                <?php elseif ($left !== null) : ?>
                                    <span class="ds-tag ds-tag-ok">You can use it <?= $left ?> more time<?= $left > 1 ? 's' : '' ?></span>
                                <?php else : ?>
                                    <span class="ds-tag ds-tag-ok">You can use it</span>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>
