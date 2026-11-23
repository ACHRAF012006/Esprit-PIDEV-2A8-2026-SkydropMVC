<?php
/**
 * Back-office page title + breadcrumb (TailAdmin "page-breadcrumb" block).
 * Usage in a page view:
 *     <?php $pageTitle = 'Users'; require VIEW_PATH . '/partials/back-title.php'; ?>
 * Variables: $pageTitle, $isSample (optional). The office (owner|admin) comes from View::currentOffice().
 */
$pageTitle = $pageTitle ?? 'SKYDROP';
$homeKey = View::currentOffice() === 'admin' ? 'admin-dashboard' : 'owner-dashboard';
?>
<!-- Breadcrumb Start -->
<div class="mb-6 flex flex-wrap items-center justify-between gap-3">
  <h2 class="text-xl font-semibold text-gray-800 dark:text-white/90"><?= e($pageTitle) ?></h2>
  <nav>
    <ol class="flex items-center gap-1.5">
      <li>
        <a class="inline-flex items-center gap-1.5 text-sm text-gray-500 dark:text-gray-400" href="<?= e(route($homeKey)) ?>">
          Home
          <svg class="stroke-current rtl:rotate-180" width="17" height="16" viewBox="0 0 17 16" fill="none" xmlns="http://www.w3.org/2000/svg">
            <path d="M6.0765 12.667L10.2432 8.50033L6.0765 4.33366" stroke="" stroke-width="1.2" stroke-linecap="round" stroke-linejoin="round" />
          </svg>
        </a>
      </li>
      <li class="text-sm text-gray-800 dark:text-white/90"><?= e($pageTitle) ?></li>
    </ol>
  </nav>
</div>
<!-- Breadcrumb End -->
<?php if (!empty($isSample)) : ?>
  <div class="mb-6 rounded-xl border border-gray-200 bg-white px-5 py-3 text-sm text-gray-500 dark:border-gray-800 dark:bg-white/3 dark:text-gray-400">
    <span class="simulation-badge">Sample data</span>
    These rows are examples. They are read from the database in the next round.
  </div>
<?php endif; ?>
