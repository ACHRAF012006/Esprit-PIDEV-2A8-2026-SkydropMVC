<?php
/**
 * Front-office layout. Receives $title, $currentPage, $content (and optional $pageScripts).
 * Order follows the Atelier workshop: head, spinner, navbar, content, footer, scripts.
 */
$office = 'front';
?>
<!DOCTYPE html>
<html lang="en">
<head>
<?php require VIEW_PATH . '/partials/head.php'; ?>
</head>

<body class="office-front">
    <?php require VIEW_PATH . '/partials/spinner.php'; ?>
    <?php require VIEW_PATH . '/partials/front-navbar.php'; ?>

    <?php if ($flash = flash('error') ?? null) : ?>
        <div class="container pt-3"><div class="alert alert-danger ds-message mb-0" role="alert" data-dismissible><i class="bi bi-exclamation-circle" aria-hidden="true"></i><span><?= e($flash) ?></span></div></div>
    <?php endif; ?>
    <?php if ($flash = flash('success') ?? null) : ?>
        <div class="container pt-3"><div class="alert alert-success ds-message mb-0" role="status" data-dismissible><i class="bi bi-check-circle" aria-hidden="true"></i><span><?= e($flash) ?></span></div></div>
    <?php endif; ?>

    <main id="main">
    <?= $content ?>
    </main>

    <?php require VIEW_PATH . '/partials/footer.php'; ?>
    <?php require VIEW_PATH . '/partials/scripts.php'; ?>
</body>
</html>
