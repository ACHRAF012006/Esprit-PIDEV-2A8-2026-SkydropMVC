<?php
/**
 * Script tags common to every page (end of <body>).
 * Variables: $office (front|owner|admin); optional $pageScripts = list of extra asset paths for one page.
 *
 * The template's animation scripts (wow, waypoints, easing, owl carousel, main.js) are not loaded: the design system
 * has no scroll or entrance animation (CLAUDE.md section 5).
 */
$office = $office ?? 'front';
?>
<?php if ($office === 'front') : ?>
    <script src="<?= e(asset('lib/jquery/3.7/jquery-3.7.0.min.js')) ?>"></script>
    <script src="<?= e(asset('lib/bootstrap/5.3/bootstrap.bundle.min.js')) ?>"></script>
    <script src="<?= e(asset('lib/leaflet/1.9/leaflet.js')) ?>"></script>
    <script src="<?= e(asset('js/customer-ui.js')) ?>"></script>
<?php else : ?>
    <script src="<?= e(asset('lib/chart.js/4/chart.umd.js')) ?>"></script>
    <script src="<?= e(asset('lib/leaflet/1.9/leaflet.js')) ?>"></script>
    <script defer src="<?= e(asset('lib/templates/tailadmin/bundle.js')) ?>"></script>
<?php endif; ?>
    <script src="<?= e(asset('js/theme-toggle.js')) ?>"></script>
    <script src="<?= e(asset('js/forms.js')) ?>"></script>
<?php foreach (($pageScripts ?? []) as $src) : ?>
    <script src="<?= e(asset($src)) ?>"></script>
<?php endforeach; ?>
