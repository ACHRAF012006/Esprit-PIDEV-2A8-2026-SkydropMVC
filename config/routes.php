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
            ['GET', '/password/forgot', [PasswordResetController::class, 'requestForm'], 'password-forgot'],
            ['POST', '/password/forgot', [PasswordResetController::class, 'request'], 'password-forgot'],
            ['GET', '/password/reset', [PasswordResetController::class, 'resetForm'], 'password-reset'],
            ['POST', '/password/reset', [PasswordResetController::class, 'reset'], 'password-reset'],
        ],
        'menu' => [
            'front' => [],
            'owner' => [['owner-dashboard', 'Overview', 'bi-grid']],
            'admin' => [['admin-dashboard', 'Overview', 'bi-grid'], ['admin-users', 'Users', 'bi-people']],
        ],
        'slots' => [],
    ],
    // Ads and placements (S5 Karim)
    'ad' => [
        'label' => 'Ads',
        'routes' => [
            ['GET', '/owner/ads', [OwnerAdController::class, 'index'], 'owner-ads'],
            ['GET', '/owner/ads/form', [OwnerAdController::class, 'form'], 'owner-ad-form'],
            ['POST', '/owner/ads/form', [OwnerAdController::class, 'save'], 'owner-ad-form'],
            ['POST', '/owner/ads/{id}/delete', [OwnerAdController::class, 'delete'], 'owner-ad-delete'],
            ['GET', '/admin/ads', [AdminAdController::class, 'index'], 'admin-ads'],
            ['GET', '/admin/ads/{id}', [AdminAdController::class, 'show'], 'admin-ad'],
            ['POST', '/admin/ads/{id}/approve', [AdminAdController::class, 'approve'], 'admin-ad-approve'],
            ['POST', '/admin/ads/{id}/reject', [AdminAdController::class, 'reject'], 'admin-ad-reject'],
            ['POST', '/admin/ads/{id}/delete', [AdminAdController::class, 'delete'], 'admin-ad-delete'],
            ['GET', '/admin/placements', [AdminPlacementController::class, 'index'], 'admin-placements'],
            ['GET', '/admin/placements/form', [AdminPlacementController::class, 'form'], 'admin-placement-form'],
            ['POST', '/admin/placements/form', [AdminPlacementController::class, 'save'], 'admin-placement-form'],
            ['POST', '/admin/placements/{id}/delete', [AdminPlacementController::class, 'delete'], 'admin-placement-delete'],
        ],
        'menu' => [
            'front' => [],
            'owner' => [['owner-ads', 'My ads', 'bi-megaphone', ['advertiser', 'shop_owner']]],
            'admin' => [['admin-ads', 'Ads', 'bi-megaphone'], ['admin-placements', 'Placements', 'bi-layout-text-window']],
        ],
        'slots' => [
            'home-top' => ['front/ads-home'],
            'admin-dashboard' => ['admin/ad-approvals'],
        ],
    ],
];
