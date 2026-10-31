<?php
declare(strict_types=1);

function e(?string $s): string
{
    return htmlspecialchars((string) $s, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

/** 25000000 -> "25 000 000 FCFA" */
function fcfa(int|string|null $n): string
{
    return number_format((int) $n, 0, ',', "\u{00A0}") . ' FCFA';
}

function csrf_field(string $token): string
{
    return '<input type="hidden" name="_csrf" value="' . e($token) . '">';
}

/** Plain-text body -> safe paragraphs (escaped first; the block editor replaces this in a later lot). */
function text_html(?string $s): string
{
    $out = '';
    foreach (preg_split('/\n{2,}/', trim(str_replace("\r", '', (string) $s))) as $para) {
        if ($para !== '') {
            $out .= '<p>' . nl2br(e($para)) . '</p>';
        }
    }
    return $out;
}

const STATUS_LABELS = [
    'available' => 'Disponible', 'reserving' => 'En cours de réservation', 'reserved' => 'Réservé', 'sold' => 'Vendu', 'unavailable' => 'Indisponible',
    'published' => 'Publié', 'draft' => 'Brouillon', 'active' => 'Actif', 'inactive' => 'Inactif', 'verified' => 'Vérifié', 'unverified' => 'Non vérifié',
    'planned' => 'Prévue', 'done' => 'Effectuée', 'cancelled' => 'Annulée', 'in_progress' => 'En cours', 'completed' => 'Terminé',
    'new' => 'Nouveau', 'to_contact' => 'À contacter', 'contacted' => 'Contact établi', 'visit_scheduled' => 'Visite programmée', 'negotiating' => 'En négociation', 'converted' => 'Converti', 'dropped' => 'Sans suite',
];

function status_label(string $s): string
{
    return STATUS_LABELS[$s] ?? $s;
}

/** Semantic tone of a status; the label always carries the meaning too (colour is never the only signal). */
function status_tone(string $s): string
{
    return match ($s) {
        'available', 'published', 'active', 'verified', 'done', 'completed', 'converted' => 'ok',
        'reserving', 'reserved', 'to_contact', 'negotiating', 'in_progress' => 'warn',
        'new', 'contacted', 'visit_scheduled', 'planned' => 'info',
        default => 'idle',
    };
}

function badge(string $status, ?string $label = null): string
{
    return '<span class="badge b-' . status_tone($status) . '">' . e($label ?? status_label($status)) . '</span>';
}

/**
 * A photo (["url" => …]) or, without one, a drawn scene (1 land, 2 site, 3 buildings). Decorative drawings are aria-hidden.
 */
function picture(?array $photo, int $scene, string $alt = '', string $class = '', bool $eager = false): string
{
    static $scenes, $n = 0;
    if ($photo) {
        return '<img src="' . e($photo['url']) . '" alt="' . e($alt) . '" class="' . e($class) . '"' . ($eager ? ' fetchpriority="high"' : ' loading="lazy"') . '>';
    }
    $scenes ??= require dirname(__DIR__) . '/Views/frontend/_scenes.php';
    $svg = str_replace('{uid}', (string) ++$n, $scenes[$scene] ?? $scenes[1]); // unique pattern ids when several drawings share a page
    return '<span class="drawn ' . e($class) . '" aria-hidden="true">' . $svg . '</span>';
}
