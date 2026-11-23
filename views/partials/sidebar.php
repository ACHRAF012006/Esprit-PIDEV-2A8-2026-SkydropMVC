<?php
/**
 * Back-office sidebar (owner | admin), from the TailAdmin template, with a dynamic active link
 * (Atelier Part 2, Step 4): $currentPage is compared with each link.
 * Variables: $office (owner|admin), $currentPage (page key from app/config/routes.php).
 *
 * The links come from each module's module.php ("menu" => ["owner" => [[key, label, icon], ...], "admin" => [...]]),
 * one group per module, in module order. A module adds its links in its own module.php: nobody edits this file.
 */
$office = $office ?? 'owner';
$currentPage = $currentPage ?? View::currentPage();
$activeKey = $activeMenu ?? $currentPage;
$menus = [$office => Registry::menu($office)];
$homeKey = $office === 'admin' ? 'admin-dashboard' : 'owner-dashboard';
if (!has_route($homeKey)) {
    $homeKey = 'home';
}
?>
<!-- ===== Sidebar Start ===== -->
<a class="sr-only" href="#main-content">skip to the main content</a>
<!-- Sidebar Backdrop (Mobile) -->
<div
  x-cloak
  x-show="sidebarToggle"
  class="fixed inset-0 z-99 bg-gray-900/50 backdrop-blur-xs xl:hidden"
  aria-hidden="true"
  @click="sidebarToggle = false"
  x-transition.opacity
></div>

<!-- The sidebar always uses the template's dark styles so it matches the navy SKYDROP logo. -->
<div class="dark" style="display: contents">
<aside
  x-cloak
  :class="sidebarToggle ? 'translate-x-0 xl:w-22.5' : '-translate-x-full rtl:translate-x-full'"
  class="sidebar skydrop-sidebar fixed top-0 left-0 z-9999 flex h-screen w-72.5 flex-col border-r border-gray-200 bg-white px-5 transition-all duration-300 xl:static xl:translate-x-0 rtl:right-0 rtl:left-auto rtl:border-r-0 rtl:border-l dark:border-gray-800 dark:bg-black"
  @click.outside="if (window.innerWidth < 1280) sidebarToggle = false"
  aria-label="sidebar navigation"
>
  <!-- SIDEBAR HEADER -->
  <div
    :class="sidebarToggle ? 'xl:justify-center' : 'justify-between'"
    class="sidebar-header flex items-center gap-2 py-8"
  >
    <a href="<?= e(route($homeKey)) ?>">
      <span class="sr-only">SKYDROP back office</span>
      <span class="logo" :class="sidebarToggle ? 'xl:hidden' : ''">
        <span class="text-xl font-semibold text-white">SKYDROP</span>
      </span>
      <span class="logo-icon text-xl font-semibold text-white" :class="sidebarToggle ? 'xl:block hidden' : 'hidden'">S</span>
    </a>
  </div>
  <!-- SIDEBAR HEADER -->

  <div class="no-scrollbar flex flex-1 flex-col overflow-y-auto duration-300 ease-linear">
    <!-- Sidebar Menu -->
    <nav aria-label="sidebar navigation">
      <?php foreach ($menus[$office] as $group => $items) : ?>
        <div>
          <h3 class="mb-4 text-xs leading-5 text-gray-400 uppercase">
            <span class="menu-group-title" :class="sidebarToggle ? 'xl:hidden' : ''"><?= e($group) ?></span>
          </h3>

          <ul class="mb-6 flex flex-col gap-1">
            <?php foreach ($items as [$key, $label, $icon]) : ?>
              <?php $isActive = ($key === $activeKey); ?>
              <li>
                <a
                  href="<?= e(route($key)) ?>"
                  class="group menu-item <?= $isActive ? 'menu-item-active' : 'menu-item-inactive' ?>"
                  <?= $isActive ? 'aria-current="page"' : '' ?>
                >
                  <i class="bi <?= e($icon) ?> <?= $isActive ? 'menu-item-icon-active' : 'menu-item-icon-inactive' ?>" style="font-size: 20px; line-height: 1"></i>
                  <span class="menu-item-text" :class="sidebarToggle ? 'xl:hidden' : ''"><?= e($label) ?></span>
                </a>
              </li>
            <?php endforeach; ?>
          </ul>
        </div>
      <?php endforeach; ?>

      <div>
        <h3 class="mb-4 text-xs leading-5 text-gray-400 uppercase">
          <span class="menu-group-title" :class="sidebarToggle ? 'xl:hidden' : ''">Site</span>
        </h3>
        <ul class="mb-6 flex flex-col gap-1">
          <li>
            <a href="<?= e(route('home')) ?>" class="group menu-item menu-item-inactive">
              <i class="bi bi-box-arrow-up-right menu-item-icon-inactive" style="font-size: 20px; line-height: 1"></i>
              <span class="menu-item-text" :class="sidebarToggle ? 'xl:hidden' : ''">Back to the shop front</span>
            </a>
          </li>
        </ul>
      </div>
    </nav>
  </div>
</aside>
</div>
<!-- ===== Sidebar End ===== -->
