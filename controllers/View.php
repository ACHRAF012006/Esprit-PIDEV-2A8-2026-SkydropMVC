<?php

/**
 * View rendering following the Atelier workshop: a page view holds only its own HTML,
 * layouts/<office>.php assembles head + sidebar/navbar + header + $content + footer + scripts.
 */
final class View
{
    private static string $currentPage = '';
    private static string $currentOffice = 'front';

    public static function currentPage(): string
    {
        return self::$currentPage;
    }

    public static function setCurrentPage(string $page): void
    {
        self::$currentPage = $page;
    }

    /** Office of the page being rendered: front | owner | admin (the layout name). */
    public static function currentOffice(): string
    {
        return self::$currentOffice;
    }

    /** File of a view name: "front/shops" -> views/front/shops.php, "layouts/admin" -> views/layouts/admin.php. */
    public static function file(string $view): string
    {
        return VIEW_PATH . '/' . $view . '.php';
    }

    /** Render a view and return its HTML as a string. */
    public static function capture(string $view, array $data = []): string
    {
        $file = self::file($view);
        if (!is_file($file)) {
            throw new RuntimeException("View not found: $view");
        }
        extract($data, EXTR_SKIP);
        ob_start();
        try {
            require $file;
        } catch (Throwable $ex) {
            ob_end_clean();
            throw $ex;
        }
        return (string) ob_get_clean();
    }

    /**
     * Render a view inside a layout (front | owner | admin).
     * The layout receives $title, $currentPage and $content (plus the rest of $data).
     */
    public static function render(string $view, array $data = [], string $layout = 'front'): void
    {
        $currentPage = $data['currentPage'] ?? self::$currentPage;
        self::$currentPage = $currentPage;
        self::$currentOffice = $layout;
        $title = $data['title'] ?? 'SKYDROP';
        $content = self::capture($view, $data);
        extract($data, EXTR_SKIP);
        require VIEW_PATH . '/layouts/' . $layout . '.php';
    }
}
