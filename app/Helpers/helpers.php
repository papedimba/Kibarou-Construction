<?php
declare(strict_types=1);

function e(?string $s): string
{
    return htmlspecialchars((string) $s, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

/** 25000000 -> "25 000 000 FCFA" */
function fcfa(int|string|null $n): string
{
    return number_format((int) $n, 0, ',', "\u{202F}") . ' FCFA';
}

function csrf_field(string $token): string
{
    return '<input type="hidden" name="_csrf" value="' . e($token) . '">';
}
