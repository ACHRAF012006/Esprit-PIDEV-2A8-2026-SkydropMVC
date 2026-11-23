<?php

/**
 * Reads config/routes.php and gives the rest of the app its routes, menu links and slots.
 *
 * config/routes.php returns one entry per section of the site:
 *   'label'  => 'Shops',                                    title of the section's group in the back-office sidebar
 *   'routes' => [[METHOD, '/path/{id}', [Controller::class, 'action'], 'page-key'], ...]
 *   'menu'   => ['front' => [['shops', 'Shops']], 'owner' => [['owner-shop', 'My shop', 'bi-shop']], 'admin' => [...]]
 *   'slots'  => ['home' => ['front/home-featured'], ...]    views shown inside other pages (see slot())
 */
final class Registry
{
    private static ?array $all = null;

    /** section => definition, in the order of config/routes.php. */
    public static function all(): array
    {
        if (self::$all === null) {
            self::$all = [];
            foreach (require CONFIG_PATH . '/routes.php' as $name => $def) {
                self::$all[$name] = $def + ['routes' => [], 'menu' => [], 'slots' => []];
            }
        }
        return self::$all;
    }

    /** Every route of the site. */
    public static function routes(): array
    {
        $routes = [];
        foreach (self::all() as $def) {
            foreach ($def['routes'] as $route) {
                $routes[] = $route;
            }
        }
        return $routes;
    }

    /**
     * Front menu: [[key, label], ...]. Back office: [group label => [[key, label, icon], ...]].
     * An item may end with a list of roles (front: [key, label, [roles]]; back: [key, label, icon, [roles]]): it is shown
     * only to those roles. The page itself must still call Auth::requireRole(): hiding a link is not a protection.
     */
    public static function menu(string $office): array
    {
        $out = [];
        foreach (self::all() as $name => $def) {
            $items = array_values(array_filter($def['menu'][$office] ?? [], static function (array $item) use ($office): bool {
                $roles = $item[$office === 'front' ? 2 : 3] ?? null;
                return !is_array($roles) || Auth::hasRole(...$roles);
            }));
            if (!$items) {
                continue;
            }
            if ($office === 'front') {
                array_push($out, ...$items);
            } else {
                $out[$def['label'] ?? $name] = $items;
            }
        }
        return $out;
    }

    /** View names registered for a slot, in section order. */
    public static function slot(string $slot): array
    {
        $views = [];
        foreach (self::all() as $def) {
            foreach ($def['slots'][$slot] ?? [] as $view) {
                $views[] = $view;
            }
        }
        return $views;
    }
}
