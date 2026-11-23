<?php
/** Generic error page. Variables: $code (int), $message (string, optional). */
$titles = [403 => 'Access denied', 404 => 'Page not found', 405 => 'Method not allowed', 419 => 'Form expired', 500 => 'Server error', 501 => 'Not implemented yet'];
$code = $code ?? 500;
$heading = $titles[$code] ?? 'Error';
?><!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= (int) $code ?> <?= e($heading) ?> | SKYDROP</title>
    <link href="<?= e(asset('lib/bootstrap/5.3/bootstrap.min.css')) ?>" rel="stylesheet">
</head>
<body class="bg-light d-flex align-items-center justify-content-center min-vh-100">
    <main class="text-center p-4">
        <h1 class="display-1 fw-bold text-primary"><?= (int) $code ?></h1>
        <h2 class="h4"><?= e($heading) ?></h2>
        <?php if (!empty($message)) : ?>
            <p class="text-muted"><?= e($message) ?></p>
        <?php endif; ?>
        <a class="btn btn-primary mt-3" href="<?= e(route('home')) ?>">Back to home</a>
    </main>
</body>
</html>
