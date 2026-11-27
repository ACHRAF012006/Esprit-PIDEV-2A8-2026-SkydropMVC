<?php
/**
 * S5 Karim - Text ads of the placement "Home banner" inside the home page (slot "home-top", under the search box).
 * A slot view has no controller of its own: it asks AdController::running() for the approved ads that run today
 * (no SQL here). Nothing is printed when no ad runs.
 */
$homeAds = (new AdController())->running(AdController::HOME_BANNER);
?>
<?php if ($homeAds) : ?>
    <div class="row g-3 mt-2 text-start" aria-label="Sponsored">
        <?php foreach ($homeAds as $ad) : ?>
            <div class="col-md-6">
                <div class="ds-card h-100">
                    <div class="ds-card-body">
                        <span class="ds-tag mb-2">Sponsored</span>
                        <h2 class="ds-card-title"><?= e($ad->getTitle()) ?></h2>
                        <div class="ds-muted small"><?= e($ad->getBody()) ?></div>
                        <?php if ($ad->getLink() !== null) : ?>
                            <a class="d-inline-block mt-2 small" href="<?= e($ad->getLink()) ?>" rel="sponsored noopener nofollow">Visit the offer</a>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        <?php endforeach; ?>
    </div>
<?php endif; ?>
