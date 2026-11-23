<?php
/**
 * S1 - Home page. Only the page content: the layout adds head, navbar, footer and scripts.
 * The blocks under the hero come from the modules (slot "home" in their module.php), in module order.
 */
$blocks = slot('home');
?>
    <section class="ds-hero">
        <div class="container">
            <h1>Local shops, in stock right now</h1>
            <p>Find a product in a shop of your district, then pick it up or have it delivered.</p>
            <?php if (has_route('search')) : ?>
                <form class="ds-search" method="get" action="<?= e(route('search')) ?>" role="search">
                    <input type="hidden" name="page" value="search">
                    <label class="visually-hidden" for="homeSearch">Search a product</label>
                    <input class="form-control" type="search" id="homeSearch" name="q" placeholder="Search a product, for example milk" data-search-shortcut>
                    <button class="btn btn-primary" type="submit">Search</button>
                </form>
            <?php endif; ?>
            <?= slot('home-top') ?>
        </div>
    </section>

    <div class="container ds-main pt-0">
        <?php if (trim($blocks) !== '') : ?>
            <?= $blocks ?>
        <?php else : ?>
            <div class="ds-empty">
                <h2 class="h5">The shops are on their way</h2>
                <p class="ds-muted mb-0">Shops, products and offers appear here as soon as their modules are added.</p>
            </div>
        <?php endif; ?>
    </div>
