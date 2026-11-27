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
    // Cart and orders (S4 Zamel)
    'order' => [
        'label' => 'Orders',
        'routes' => [
            ['GET', '/cart', [CartController::class, 'index'], 'cart'],
            ['POST', '/cart/add', [CartController::class, 'add'], 'cart-add'],
            ['POST', '/cart/update', [CartController::class, 'update'], 'cart-update'],
            ['POST', '/cart/remove', [CartController::class, 'remove'], 'cart-remove'],
            ['POST', '/cart/empty', [CartController::class, 'clear'], 'cart-empty'],
            ['GET', '/checkout', [OrderController::class, 'checkoutForm'], 'checkout'],
            ['POST', '/checkout', [OrderController::class, 'checkout'], 'checkout'],
            ['GET', '/orders', [OrderController::class, 'index'], 'my-orders'],
            ['GET', '/orders/{id}', [OrderController::class, 'show'], 'order'],
            ['POST', '/orders/{id}/cancel', [OrderController::class, 'cancel'], 'order-cancel'],
            ['GET', '/owner/orders', [OwnerOrderController::class, 'index'], 'owner-orders'],
            ['GET', '/owner/orders/{id}', [OwnerOrderController::class, 'show'], 'owner-order'],
            ['POST', '/owner/orders/{id}/status', [OwnerOrderController::class, 'advance'], 'owner-order-status'],
            ['GET', '/admin/orders', [AdminOrderController::class, 'index'], 'admin-orders'],
            ['GET', '/admin/orders/{id}', [AdminOrderController::class, 'show'], 'admin-order'],
            ['POST', '/admin/orders/{id}/delete', [AdminOrderController::class, 'delete'], 'admin-order-delete'],
        ],
        'menu' => [
            'front' => [['cart', 'Cart', ['customer']], ['my-orders', 'My orders', ['customer']]],
            'owner' => [['owner-orders', 'Orders', 'bi-receipt', ['shop_owner']]],
            'admin' => [['admin-orders', 'Orders', 'bi-receipt']],
        ],
        'slots' => [
            'profile' => ['front/profile-orders'],
        ],
    ],
];
