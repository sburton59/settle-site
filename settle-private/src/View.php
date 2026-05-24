<?php
declare(strict_types=1);
namespace Settle;

/**
 * Minimal PHP-as-template renderer.
 * - $data is extracted into the template's local scope
 * - A helper closure e() is provided for HTML-escaping
 * - If a $layout is given, the rendered content is wrapped in it via $content
 */
final class View
{
    public static function render(string $template, array $data = [], ?string $layout = null): void
    {
        $templatePath = __DIR__ . '/../templates/' . $template . '.php';
        if (!is_file($templatePath)) {
            throw new \RuntimeException("Template not found: $template");
        }

        $data['e'] = static fn($v) => htmlspecialchars((string)$v, ENT_QUOTES, 'UTF-8');
        extract($data, EXTR_SKIP);

        ob_start();
        require $templatePath;
        $content = ob_get_clean();

        if ($layout) {
            $layoutPath = __DIR__ . '/../templates/layout/' . $layout . '.php';
            if (!is_file($layoutPath)) {
                throw new \RuntimeException("Layout not found: $layout");
            }
            require $layoutPath;
        } else {
            echo $content;
        }
    }
}