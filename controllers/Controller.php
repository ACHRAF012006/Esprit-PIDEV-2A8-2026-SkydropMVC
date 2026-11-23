<?php

/**
 * Base controller: the bridge between a page view and its layout (Atelier Part 2, Step 8).
 * capture() renders the page view to a string ($content); render() wraps it in layouts/<layout>.php.
 */
abstract class Controller
{
    /** Layout used by render(): front | owner | admin. Override in <Module>\Owner\* / <Module>\Admin\* controllers. */
    protected string $layout = 'front';

    /** Render a view ("<Module>/<office>/<page>") and return the HTML ($content). */
    protected function capture(string $view, array $data = []): string
    {
        return View::capture($view, $data);
    }

    /** Render a view ("<Module>/<office>/<page>") inside the layout: $title and $currentPage are read from $data. */
    protected function render(string $view, array $data = [], ?string $layout = null): void
    {
        View::render($view, $data, $layout ?? $this->layout);
    }

    protected function redirect(string $location): never
    {
        redirect($location);
    }

    /** JSON response for autocomplete / tracking endpoints. */
    protected function json(mixed $payload, int $status = 200): never
    {
        http_response_code($status);
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode($payload, JSON_UNESCAPED_UNICODE);
        exit;
    }
}
