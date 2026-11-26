<?php
/**
 * S2 Mili - Front office: one approved shop with its plan, hours, phone and a small map (ShopController::show).
 * Only the page content. The map is drawn by assets/js/shops.js from the data- attributes of #shopMap.
 * Phone and position are optional: their rows and the map appear only when the owner gave them.
 * Variables: $shop (ShopModel).
 */
$bannerTitle = $shop->getName();
$bannerParents = [['Shops', 'shops']];
require VIEW_PATH . '/partials/page-banner.php';
$hasPosition = $shop->getLat() !== null && $shop->getLng() !== null;
?>
    <div class="container ds-main">
        <div class="row g-4">
            <div class="col-lg-7">
                <div class="ds-card">
                    <div class="ds-card-body">
                        <p class="mb-2">
                            <span class="ds-tag"><?= e($shop->getPlanName()) ?> plan</span>
                            <?php if ($shop->isOpenNow()) : ?>
                                <span class="ds-tag ds-tag-ok">Open now</span>
                            <?php else : ?>
                                <span class="ds-tag ds-tag-muted">Closed now</span>
                            <?php endif; ?>
                        </p>
                        <?php if ($shop->getDescription() !== null) : ?>
                            <p><?= e($shop->getDescription()) ?></p>
                        <?php endif; ?>
                        <dl class="ds-rows mb-0">
                            <div><dt>Opening hours</dt><dd><?= e(substr($shop->getOpensAt(), 0, 5)) ?> to <?= e(substr($shop->getClosesAt(), 0, 5)) ?>, every day</dd></div>
                            <?php if ($shop->getPhone() !== null) : ?>
                                <div><dt>Phone</dt><dd><a href="tel:<?= e($shop->getPhone()) ?>"><?= e($shop->getPhone()) ?></a></dd></div>
                            <?php endif; ?>
                            <?php if ($shop->getAddress() !== null) : ?>
                                <div><dt>Address</dt><dd><?= e($shop->getAddress()) ?></dd></div>
                            <?php endif; ?>
                        </dl>
                    </div>
                </div>
                <a class="btn btn-outline mt-3" href="<?= e(route('shops')) ?>">Back to the shops</a>
            </div>

            <?php if ($hasPosition) : ?>
                <div class="col-lg-5">
                    <div class="ds-card">
                        <div class="ds-card-body">
                            <div class="d-flex flex-wrap align-items-center justify-content-between gap-2 mb-2">
                                <h2 class="h5 mb-0">On the map</h2>
                                <span class="simulation-badge">Simulation</span>
                            </div>
                            <div class="ds-map shop-map" id="shopMap" data-lat="<?= e((string) $shop->getLat()) ?>" data-lng="<?= e((string) $shop->getLng()) ?>"
                                 data-name="<?= e($shop->getName()) ?>" role="img" aria-label="Map around <?= e($shop->getName()) ?>">
                                <p class="shop-map-fallback">
                                    Position <?= e(number_format($shop->getLat(), 4)) ?>, <?= e(number_format($shop->getLng(), 4)) ?>.
                                    <a href="<?= e('https://www.openstreetmap.org/?mlat=' . $shop->getLat() . '&mlon=' . $shop->getLng() . '#map=17/' . $shop->getLat() . '/' . $shop->getLng()) ?>" rel="noopener noreferrer" target="_blank">Open it on OpenStreetMap</a>
                                </p>
                            </div>
                            <p class="ds-muted small mt-2 mb-0">The position is the one typed by the shop owner.</p>
                        </div>
                    </div>
                </div>
            <?php endif; ?>
        </div>
    </div>
