<?php
/**
 * config/routes.php - every page of SKYDROP in one place.
 *
 * Each section of the site lists:
 *   'label'  => title of its group in the back-office sidebar
 *   'routes' => [[METHOD, '/path/{id}', [Controller::class, 'action'], 'page-key'], ...]
 *               a page opens at index.php?page=<page-key> (path parameters come from the query string)
 *   'menu'   => links of the front navbar and of the two back-office sidebars
 *   'slots'  => views shown inside another page (see slot() in config/helpers.php)
 * Registry (controllers/Registry.php) reads this file; Router (controllers/Router.php) dispatches the request.
 */
return [
    'user' => [
        'label' => 'Accounts',
        'routes' => [
            ['GET', '/', [HomeController::class, 'index'], 'home'],
            ['GET', '/login', [AuthController::class, 'loginForm'], 'login'],
            ['POST', '/login', [AuthController::class, 'login'], 'login'],
            ['GET', '/register', [AuthController::class, 'registerForm'], 'register'],
            ['POST', '/register', [AuthController::class, 'register'], 'register'],
            ['POST', '/logout', [AuthController::class, 'logout'], 'logout'],
            ['GET', '/profile', [AuthController::class, 'profile'], 'profile'],
            ['POST', '/profile', [AuthController::class, 'updateProfile'], 'profile'],
            ['POST', '/profile/password', [AuthController::class, 'changePassword'], 'profile-password'],
            ['GET', '/owner', [OwnerDashboardController::class, 'index'], 'owner-dashboard'],
            ['GET', '/admin', [AdminDashboardController::class, 'index'], 'admin-dashboard'],
            ['POST', '/profile/delete', [AuthController::class, 'deleteAccount'], 'profile-delete'],
            ['GET', '/admin/users', [AdminUserController::class, 'index'], 'admin-users'],
            ['GET', '/admin/users/export', [AdminUserController::class, 'export'], 'admin-users-export'],
            ['GET', '/admin/users/form', [AdminUserController::class, 'form'], 'admin-user-form'],
            ['POST', '/admin/users/form', [AdminUserController::class, 'save'], 'admin-user-form'],
            ['GET', '/admin/users/{id}', [AdminUserController::class, 'show'], 'admin-user'],
            ['POST', '/admin/users/{id}/delete', [AdminUserController::class, 'delete'], 'admin-user-delete'],
            ['POST', '/admin/users/{id}/password', [AdminUserController::class, 'setPassword'], 'admin-user-password'],
            ['POST', '/admin/users/{id}/toggle', [AdminUserController::class, 'toggle'], 'admin-user-toggle'],
            ['POST', '/admin/users/{id}/role', [AdminUserController::class, 'changeRole'], 'admin-user-role'],
        ],
        'menu' => [
            'front' => [],
            'owner' => [['owner-dashboard', 'Overview', 'bi-grid']],
            'admin' => [['admin-dashboard', 'Overview', 'bi-grid'], ['admin-users', 'Users', 'bi-people']],
        ],
        'slots' => [],
    ],
    // Shops and plan tiers (S2 Mili)
    'shop' => [
        'label' => 'Shops',
        'routes' => [
            ['GET', '/shops', [ShopController::class, 'index'], 'shops'],
            ['GET', '/shops/{id}', [ShopController::class, 'show'], 'shop'],
            ['GET', '/owner/shop', [OwnerShopController::class, 'form'], 'owner-shop'],
            ['POST', '/owner/shop', [OwnerShopController::class, 'save'], 'owner-shop'],
            ['GET', '/admin/shops', [AdminShopController::class, 'index'], 'admin-shops'],
            ['GET', '/admin/shops/{id}', [AdminShopController::class, 'show'], 'admin-shop'],
            ['POST', '/admin/shops/{id}/approve', [AdminShopController::class, 'approve'], 'admin-shop-approve'],
            ['POST', '/admin/shops/{id}/reject', [AdminShopController::class, 'reject'], 'admin-shop-reject'],
            ['POST', '/admin/shops/{id}/delete', [AdminShopController::class, 'delete'], 'admin-shop-delete'],
            ['GET', '/admin/plans', [AdminPlanController::class, 'index'], 'admin-plans'],
            ['GET', '/admin/plans/form', [AdminPlanController::class, 'form'], 'admin-plan-form'],
            ['POST', '/admin/plans/form', [AdminPlanController::class, 'save'], 'admin-plan-form'],
            ['POST', '/admin/plans/{id}/delete', [AdminPlanController::class, 'delete'], 'admin-plan-delete'],
        ],
        'menu' => [
            'front' => [['shops', 'Shops']],
            'owner' => [['owner-shop', 'My shop', 'bi-shop', ['shop_owner', 'admin']]],
            'admin' => [['admin-shops', 'Shops', 'bi-shop'], ['admin-plans', 'Plan tiers', 'bi-layers']],
        ],
        'slots' => [
            'owner-dashboard' => ['owner/shop-tile'],
            'admin-dashboard' => ['admin/shop-approvals'],
        ],
    ],
];
