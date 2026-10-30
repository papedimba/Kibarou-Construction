<?php
declare(strict_types=1);

namespace App\Core;

final class View
{
    public static function render(string $tpl, array $data = [], ?string $layout = 'layouts/base'): string
    {
        $content = self::capture($tpl, $data);
        return $layout ? self::capture($layout, $data + ['content' => $content]) : $content;
    }

    private static function capture(string $tpl, array $data): string
    {
        extract($data, EXTR_SKIP);
        ob_start();
        require dirname(__DIR__) . "/Views/$tpl.php";
        return (string) ob_get_clean();
    }
}
