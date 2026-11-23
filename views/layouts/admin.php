<?php
/**
 * Admin back-office layout (TailAdmin). Receives $title, $currentPage, $content (and optional $pageScripts).
 * Order follows the Atelier Part 2 workshop: head + preloader, sidebar, header, content, footer, scripts.
 */
$office = 'admin';
$pageName = $title ?? 'SKYDROP';
?>
<!DOCTYPE html>
<html lang="en">
<head>
<?php require VIEW_PATH . '/partials/head.php'; ?>
</head>

<body
    class="office-back"
    x-data="{ page: '<?= e($currentPage ?? '') ?>', 'loaded': true, 'darkMode': false, 'stickyMenu': false, 'sidebarToggle': false, 'scrollTop': false }"
    x-init="
        darkMode = JSON.parse(localStorage.getItem('darkMode')) ?? false;
        $watch('darkMode', value => localStorage.setItem('darkMode', JSON.stringify(value)))"
    :class="{'dark bg-gray-900': darkMode === true}"
>
    <?php require VIEW_PATH . '/partials/spinner.php'; ?>

    <div class="flex h-screen overflow-hidden">
        <?php require VIEW_PATH . '/partials/sidebar.php'; ?>

        <div class="relative flex flex-1 flex-col overflow-x-hidden overflow-y-auto">
            <?php require VIEW_PATH . '/partials/header.php'; ?>

            <main id="main-content">
                <div class="mx-auto max-w-(--breakpoint-2xl) p-4 pb-20 md:p-6 md:pb-6">
                    <?php if ($flash = flash('error')) : ?>
                        <div class="mb-4 rounded-lg bg-red-50 p-3 text-sm text-red-600"><?= e($flash) ?></div>
                    <?php endif; ?>
                    <?php if ($flash = flash('success')) : ?>
                        <div class="mb-4 rounded-lg bg-green-50 p-3 text-sm text-green-600"><?= e($flash) ?></div>
                    <?php endif; ?>

                    <?= $content ?>
                </div>
            </main>

            <?php require VIEW_PATH . '/partials/footer.php'; ?>
        </div>
    </div>

    <?php require VIEW_PATH . '/partials/scripts.php'; ?>
</body>
</html>
