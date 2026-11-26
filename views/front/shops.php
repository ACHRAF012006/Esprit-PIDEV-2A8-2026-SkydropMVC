<?php
/**
 * S2 Mili - Front office: the approved shops, higher plan first (ShopController::index). Only the page content.
 * Variables: $shops (ShopModel[]), $q (search text).
 */
$bannerTitle = 'Shops';
$bannerLead = 'Local shops approved on SKYDROP. Shops with a higher plan are shown first.';
require VIEW_PATH . '/partials/page-banner.php';
?>
    <div class="container ds-main">
        <form class="ds-search mb-4" method="get" action="<?= e(route('shops')) ?>" role="search" novalidate data-validate>
            <input type="hidden" name="page" value="shops">
            <label class="visually-hidden" for="shopSearch">Search a shop by name</label>
            <input class="form-control" type="search" id="shopSearch" name="q" value="<?= e($q) ?>" placeholder="Search a shop by name" data-max-length="80">
            <button class="btn btn-primary" type="submit">Search</button>
            <?php if ($q !== '') : ?>
                <a class="btn btn-outline" href="<?= e(route('shops')) ?>">Clear</a>
            <?php endif; ?>
        </form>

        <div class="ds-section-head">
            <h2 class="h5"><?= count($shops) ?> shop<?= count($shops) === 1 ? '' : 's' ?><?= $q !== '' ? ' for "' . e($q) . '"' : '' ?></h2>
        </div>

        <?php if (!$shops) : ?>
            <div class="ds-empty">
                <h2 class="h5">No shop found</h2>
                <p class="ds-muted mb-0"><?= $q !== '' ? 'No approved shop has this name. Try another word.' : 'No shop is approved yet.' ?></p>
            </div>
        <?php else : ?>
            <div class="row g-3">
                <?php foreach ($shops as $shop) : ?>
                    <div class="col-md-6 col-lg-4">
                        <div class="ds-card ds-card-row">
                            <div class="ds-card-body">
                                <h3 class="ds-card-title"><a href="<?= e(route('shop', ['id' => $shop->getId()])) ?>"><?= e($shop->getName()) ?></a></h3>
                                <p class="mb-2">
                                    <span class="ds-tag"><?= e($shop->getPlanName()) ?></span>
                                    <?php if ($shop->isOpenNow()) : ?>
                                        <span class="ds-tag ds-tag-ok">Open now</span>
                                    <?php else : ?>
                                        <span class="ds-tag ds-tag-muted">Closed now</span>
                                    <?php endif; ?>
                                </p>
                                <ul class="ds-meta">
                                    <li><i class="bi bi-geo-alt" aria-hidden="true"></i><?= e($shop->getAddress()) ?></li>
                                    <li><i class="bi bi-clock" aria-hidden="true"></i><?= e(substr($shop->getOpensAt(), 0, 5)) ?> to <?= e(substr($shop->getClosesAt(), 0, 5)) ?></li>
                                </ul>
                            </div>
                            <div class="ds-card-footer">
                                <a href="<?= e(route('shop', ['id' => $shop->getId()])) ?>">See the shop</a>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>
