<?php
/**
 * Contents of <head> (without the <head> tags), shared by the three offices.
 * Variables: $office (front|owner|admin), $title.
 *
 * Order: template CSS (eLEARNING or TailAdmin), then skydrop.css, then design-system.css, which overrides the templates.
 * No web fonts and no CDN: the system font stack and Bootstrap Icons only, so the app works offline (except map tiles).
 */
$office = $office ?? 'front';
?>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="theme-color" content="#03213d">
    <meta name="color-scheme" content="light dark">
    <script src="<?= e(asset('js/theme-init.js')) ?>"></script>
    <title><?= e($title ?? 'SKYDROP') ?></title>
    <meta name="csrf-token" content="<?= e(Csrf::token()) ?>">
    <link href="<?= e(asset('lib/bootstrap-icons/1.11/bootstrap-icons.css')) ?>" rel="stylesheet">
<?php if ($office === 'front') : ?>
    <!-- Smooth cross-fade between customer pages (browsers that support view transitions; others just load the page). -->
    <style>@media (prefers-reduced-motion: no-preference) { @view-transition { navigation: auto; } }</style>
    <!-- Template stylesheet (eLEARNING), overridden by design-system.css -->
    <link href="<?= e(asset('lib/templates/elearning/css/bootstrap.min.css')) ?>" rel="stylesheet">
    <link href="<?= e(asset('lib/templates/elearning/css/style.css')) ?>" rel="stylesheet">
<?php else : ?>
    <!-- Template stylesheet (TailAdmin), overridden by design-system.css -->
    <link href="<?= e(asset('lib/templates/tailadmin/style.css')) ?>" rel="stylesheet">
<?php endif; ?>
    <link href="<?= e(asset('css/skydrop.css')) ?>" rel="stylesheet">
    <link href="<?= e(asset('css/design-system.css')) ?>" rel="stylesheet">
<?php foreach (($pageStyles ?? []) as $href) : ?>
    <link href="<?= e(asset($href)) ?>" rel="stylesheet">
<?php endforeach; ?>
