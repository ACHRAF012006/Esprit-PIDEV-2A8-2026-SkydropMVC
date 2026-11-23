<?php
/**
 * Front-office navbar (eLEARNING navbar markup, restyled by design-system.css) with a dynamic .active link (Atelier Step 5).
 * $currentPage is the page key of the route being displayed (see app/config/routes.php).
 *
 * The links come from each module's module.php ("menu" => ["front" => [[key, label], ...]]), in module order.
 * A module adds its link in its own module.php: nobody edits this file to add a page.
 * A page that belongs to a menu entry without being in the menu itself (a product page under "Products")
 * passes the entry's key from its controller: 'activeMenu' => 'products'.
 */
$currentPage = $currentPage ?? View::currentPage();
$links = [['home', 'Home']];
foreach (Registry::menu('front') as $item) {
    $links[] = $item;
}
$activeKey = $activeMenu ?? $currentPage;
?>
    <a class="visually-hidden-focusable" href="#main">Skip to the content</a>
    <!-- Navbar Start -->
    <nav class="navbar navbar-expand-lg navbar-dark ds-navbar sticky-top" aria-label="Main" style="view-transition-name: ds-navbar;">
        <div class="container">
            <a href="<?= e(route('home')) ?>" class="navbar-brand">
                <span class="fs-4 fw-semibold text-white">SKYDROP</span>
            </a>
            <button type="button" class="navbar-toggler" data-bs-toggle="collapse" data-bs-target="#navbarCollapse"
                    aria-controls="navbarCollapse" aria-expanded="false" aria-label="Open the menu">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarCollapse">
                <div class="navbar-nav ms-auto">
                    <?php foreach ($links as [$key, $label]) : ?>
                        <a href="<?= e(route($key)) ?>" class="nav-item nav-link <?= e(active($key, $activeKey)) ?>"
                           <?= $key === $activeKey ? 'aria-current="page"' : '' ?>><?= e($label) ?></a>
                    <?php endforeach; ?>
                    <?php if (Auth::check()) : ?>
                        <a href="<?= e(route('profile')) ?>" class="nav-item nav-link <?= e(active('profile', $activeKey)) ?>">Profile</a>
                    <?php else : ?>
                        <a href="<?= e(route('register')) ?>" class="nav-item nav-link <?= e(active('register', $activeKey)) ?>">Register</a>
                    <?php endif; ?>
                </div>
                <div class="ds-nav-action d-flex align-items-center gap-2">
                    <button type="button" class="btn btn-sm ds-theme-toggle" data-theme-toggle aria-pressed="false" aria-label="Switch to dark mode">
                        <i class="bi bi-moon-stars" aria-hidden="true"></i><i class="bi bi-sun" aria-hidden="true"></i>
                    </button>
                    <?php if (Auth::check()) : ?>
                        <form method="post" action="<?= e(route('logout')) ?>" class="m-0">
                            <?= csrf_field() ?>
                            <button type="submit" class="btn btn-sm">Log out</button>
                        </form>
                    <?php else : ?>
                        <a href="<?= e(route('login')) ?>" class="btn btn-sm">Log in</a>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </nav>
    <!-- Navbar End -->
