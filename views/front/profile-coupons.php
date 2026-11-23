<?php
/**
 * S1 - Slot "profile": the coupons I used and what I saved. A block of the profile page, not a page.
 * Variables: $user (UserModel, the signed-in user), passed by the profile page. The data comes from
 * CouponController::profileBlock() (the SQL stays in the controller).
 */
['uses' => $uses, 'saved' => $saved] = (new CouponController())->profileBlock($user->getId());
?>
<div class="ds-card mb-4" id="my-coupons">
    <div class="ds-card-body">
        <div class="ds-section-head">
            <h2 class="h4">My coupons</h2>
            <a href="<?= e(route('offers')) ?>">See the offers</a>
        </div>
        <?php if (!$uses) : ?>
            <p class="ds-muted mb-0">You have not used a coupon yet. The current offers are on the Offers page.</p>
        <?php else : ?>
            <p class="mb-3">You saved <strong><?= e(money($saved)) ?></strong> with <?= count($uses) ?> coupon<?= count($uses) > 1 ? 's' : '' ?>.</p>
            <div class="table-responsive">
                <table class="ds-table">
                    <thead><tr><th>Code</th><th>Date</th><th class="ds-right">Saved</th></tr></thead>
                    <tbody>
                        <?php foreach ($uses as $u) : ?>
                            <tr>
                                <td><?= e($u->getCode()) ?></td>
                                <td><?= e(substr($u->getUsedAt(), 0, 10)) ?></td>
                                <td class="ds-right"><?= e(money($u->getDiscount())) ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </div>
</div>
