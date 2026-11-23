<?php
/**
 * Front-office page header: breadcrumb and title, on a white band with a hairline (design system, no coloured band).
 * Usage in a page view:
 *     <?php $bannerTitle = 'Shops near me'; $bannerLead = 'Optional one-line description.'; require VIEW_PATH . '/partials/page-banner.php'; ?>
 * Optional $bannerParents = [['Search', 'search'], ['Cart', 'cart', ['sample' => 'light']]] adds steps between Home and the page.
 */
$bannerTitle = $bannerTitle ?? 'SKYDROP';
?>
    <header class="ds-page-header">
        <div class="container">
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb">
                    <li class="breadcrumb-item"><a href="<?= e(route('home')) ?>">Home</a></li>
                    <?php foreach (($bannerParents ?? []) as $parent) : ?>
                        <li class="breadcrumb-item"><a href="<?= e(route($parent[1], $parent[2] ?? [])) ?>"><?= e($parent[0]) ?></a></li>
                    <?php endforeach; ?>
                    <li class="breadcrumb-item active" aria-current="page"><?= e($bannerTitle) ?></li>
                </ol>
            </nav>
            <h1><?= e($bannerTitle) ?></h1>
            <?php if (!empty($bannerLead)) : ?>
                <p class="ds-lead"><?= e($bannerLead) ?></p>
            <?php endif; ?>
        </div>
    </header>
