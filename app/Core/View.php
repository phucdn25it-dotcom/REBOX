<?php
namespace App\Core;

final class View
{
    public static function render(string $viewName, array $data = []): void
    {
        $viewsDirectory = dirname(__DIR__) . DIRECTORY_SEPARATOR . 'Views';
        $viewFile = $viewsDirectory . DIRECTORY_SEPARATOR
            . str_replace('/', DIRECTORY_SEPARATOR, $viewName) . '.php';
        $layoutFile = $viewsDirectory . DIRECTORY_SEPARATOR . 'layouts'
            . DIRECTORY_SEPARATOR . 'main.php';

        if (!is_file($viewFile)) {
            http_response_code(404);
            echo 'View not found.';
            return;
        }

        if (!is_file($layoutFile)) {
            error_log('ReBox layout not found: ' . $layoutFile);
            http_response_code(500);
            echo 'Giao diện chưa sẵn sàng. Vui lòng kiểm tra app/Views/layouts/main.php.';
            return;
        }

        extract($data, EXTR_SKIP);
        $name = $viewName;
        require $layoutFile;
    }
}
