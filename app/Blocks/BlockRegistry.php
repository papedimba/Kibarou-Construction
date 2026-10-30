<?php
declare(strict_types=1);

namespace App\Blocks;

/**
 * Whitelist of block types. A type that is not listed here can never be stored or rendered.
 * Field types: text, textarea, number, decimal, select, url, image, icon, bool, list.
 */
final class BlockRegistry
{
    public const CATEGORIES = ['structure' => 'Structure', 'content' => 'Contenu', 'marketing' => 'Marketing', 'land' => 'Immobilier', 'btp' => 'BTP'];
    private static ?array $defs = null;

    public static function get(string $type): ?array
    {
        return self::all()[$type] ?? null;
    }

    public static function all(): array
    {
        return self::$defs ??= self::build();
    }

    /** Definition shipped to the editor UI: same schema, plus defaults, minus nothing sensitive. */
    public static function toClient(): array
    {
        $out = [];
        foreach (self::all() as $type => $d) {
            $out[$type] = ['label' => $d['label'], 'category' => $d['category'], 'icon' => $d['icon'], 'container' => $d['container'], 'fields' => $d['fields']];
        }
        return $out;
    }

    private static function f(string $type, string $label, array $extra = []): array
    {
        return ['type' => $type, 'label' => $label] + $extra;
    }

    private static function def(string $label, string $category, string $icon, array $fields = [], bool $container = false): array
    {
        return ['label' => $label, 'category' => $category, 'icon' => $icon, 'fields' => $fields, 'container' => $container];
    }

    private static function build(): array
    {
        $f = [self::class, 'f'];
        $align = $f('select', 'Alignement', ['options' => ['left' => 'Gauche', 'center' => 'Centré']]);
        $count = fn(string $l, int $min, int $max) => $f('number', $l, ['min' => $min, 'max' => $max, 'default' => $min]);
        $title = fn(string $l = 'Titre') => $f('text', $l, ['max' => 160]);
        $url = fn(string $l) => $f('url', $l);
        $size = fn(string $l = 'Taille') => $f('select', $l, ['options' => ['sm' => 'Petit', 'md' => 'Moyen', 'lg' => 'Grand', 'xl' => 'Très grand']]);

        return [
            // — Structure
            'section' => self::def('Section', 'structure', 'layers', [
                'bg' => $f('select', 'Fond', ['options' => ['none' => 'Aucun', 'light' => 'Clair', 'dark' => 'Sombre', 'primary' => 'Couleur principale']]),
                'pad' => $f('select', 'Espacement', ['options' => ['sm' => 'Petit', 'md' => 'Moyen', 'lg' => 'Grand'], 'default' => 'md']),
            ], true),
            'container' => self::def('Conteneur', 'structure', 'building', [
                'width' => $f('select', 'Largeur', ['options' => ['narrow' => 'Étroite', 'normal' => 'Normale', 'wide' => 'Large'], 'default' => 'normal']),
            ], true),
            'columns' => self::def('Colonnes', 'structure', 'layers', [
                'count' => $count('Nombre de colonnes', 2, 4),
                'gap' => $f('select', 'Écart', ['options' => ['sm' => 'Petit', 'md' => 'Moyen', 'lg' => 'Grand'], 'default' => 'md']),
            ], true),
            'grid' => self::def('Grille', 'structure', 'layers', ['cols' => $count('Colonnes par ligne', 2, 4)], true),
            'spacer' => self::def('Espace', 'structure', 'ruler', ['size' => $size('Hauteur')]),
            'divider' => self::def('Séparateur', 'structure', 'ruler', ['style' => $f('select', 'Style', ['options' => ['line' => 'Ligne fine', 'thick' => 'Ligne épaisse']])]),
            // — Contenu
            'text' => self::def('Texte', 'content', 'file-text', ['body' => $f('textarea', 'Texte', ['max' => 5000]), 'align' => $align]),
            'heading' => self::def('Titre', 'content', 'file-text', ['text' => $title(), 'level' => $f('select', 'Niveau', ['options' => ['2' => 'Titre 2', '3' => 'Titre 3', '4' => 'Titre 4'], 'default' => '2']), 'align' => $align]),
            'image' => self::def('Image', 'content', 'camera', ['image' => $f('image', 'Image'), 'alt' => $f('text', 'Texte alternatif (ALT)', ['max' => 190]), 'caption' => $f('text', 'Légende', ['max' => 190]), 'link' => $url('Lien (facultatif)')]),
            'gallery' => self::def('Galerie', 'content', 'camera', [
                'cols' => $count('Colonnes', 2, 4),
                'images' => $f('list', 'Images', ['max' => 24, 'item' => ['image' => $f('image', 'Image'), 'alt' => $f('text', 'Texte alternatif (ALT)', ['max' => 190])]]),
            ]),
            'video' => self::def('Vidéo', 'content', 'camera', ['url' => $url('Lien YouTube, Vimeo ou fichier .mp4 (https)'), 'caption' => $f('text', 'Légende', ['max' => 190])]),
            'button' => self::def('Bouton', 'content', 'arrow-right', ['label' => $f('text', 'Libellé', ['max' => 80]), 'url' => $url('Lien'), 'style' => $f('select', 'Style', ['options' => ['primary' => 'Principal', 'secondary' => 'Secondaire', 'outline' => 'Contour']]), 'align' => $align]),
            'icon' => self::def('Icône', 'content', 'star', ['icon' => $f('icon', 'Icône'), 'label' => $f('text', 'Libellé', ['max' => 80]), 'size' => $size()]),
            // — Marketing
            'hero' => self::def('Hero', 'marketing', 'house', [
                'title' => $title(), 'subtitle' => $f('textarea', 'Sous-titre', ['max' => 400]), 'image' => $f('image', 'Image de fond'),
                'primary_label' => $f('text', 'Bouton 1 : libellé', ['max' => 80]), 'primary_url' => $url('Bouton 1 : lien'),
                'secondary_label' => $f('text', 'Bouton 2 : libellé', ['max' => 80]), 'secondary_url' => $url('Bouton 2 : lien'), 'align' => $align,
            ]),
            'services' => self::def('Services', 'marketing', 'hammer', ['title' => $title(), 'items' => $f('list', 'Services', ['max' => 12, 'item' => ['icon' => $f('icon', 'Icône'), 'title' => $title(), 'text' => $f('textarea', 'Description', ['max' => 400])]])]),
            'stats' => self::def('Statistiques', 'marketing', 'award', ['items' => $f('list', 'Chiffres', ['max' => 8, 'item' => ['value' => $f('text', 'Valeur', ['max' => 30]), 'label' => $f('text', 'Libellé', ['max' => 80])]])]),
            'testimonials' => self::def('Témoignages', 'marketing', 'message-circle', [
                'title' => $title(),
                'items' => $f('list', 'Témoignages', ['max' => 12, 'item' => ['quote' => $f('textarea', 'Citation', ['max' => 600]), 'author' => $f('text', 'Auteur', ['max' => 100]), 'role' => $f('text', 'Fonction / lieu', ['max' => 100]), 'verified' => $f('bool', 'Témoignage vérifié (seuls les vérifiés sont publiés)')]]),
            ]),
            'faq' => self::def('FAQ', 'marketing', 'circle-check', ['title' => $title(), 'items' => $f('list', 'Questions', ['max' => 20, 'item' => ['q' => $f('text', 'Question', ['max' => 190]), 'a' => $f('textarea', 'Réponse', ['max' => 1500])]])]),
            'cta' => self::def('Appel à l\'action', 'marketing', 'arrow-right', ['title' => $title(), 'text' => $f('textarea', 'Texte', ['max' => 400]), 'button_label' => $f('text', 'Bouton : libellé', ['max' => 80]), 'button_url' => $url('Bouton : lien'), 'tone' => $f('select', 'Fond', ['options' => ['light' => 'Clair', 'primary' => 'Couleur principale']])]),
            'team' => self::def('Équipe', 'marketing', 'users', ['title' => $title(), 'items' => $f('list', 'Membres', ['max' => 12, 'item' => ['name' => $f('text', 'Nom', ['max' => 100]), 'role' => $f('text', 'Fonction', ['max' => 100]), 'image' => $f('image', 'Photo')]])]),
            'partners' => self::def('Logos partenaires', 'marketing', 'handshake', ['title' => $title(), 'items' => $f('list', 'Partenaires', ['max' => 12, 'item' => ['name' => $f('text', 'Nom', ['max' => 100]), 'image' => $f('image', 'Logo'), 'url' => $url('Lien')]])]),
            // — Immobilier (données lues dans le site courant uniquement)
            'land_search' => self::def('Recherche de terrains', 'land', 'map-pin', ['title' => $title()]),
            'land_featured' => self::def('Terrain vedette', 'land', 'star', ['reference' => $f('text', 'Référence du terrain', ['max' => 40])]),
            'land_catalog' => self::def('Catalogue de terrains', 'land', 'map', ['title' => $title(), 'count' => $count('Nombre de terrains', 1, 12), 'category' => $f('text', 'Catégorie (identifiant, facultatif)', ['max' => 100]), 'sort' => $f('select', 'Tri', ['options' => ['recent' => 'Plus récents', 'price_asc' => 'Prix croissant', 'price_desc' => 'Prix décroissant']])]),
            'land_map' => self::def('Carte de localisation', 'land', 'map-pin', ['title' => $title(), 'lat' => $f('decimal', 'Latitude', ['min' => -90, 'max' => 90]), 'lng' => $f('decimal', 'Longitude', ['min' => -180, 'max' => 180])]),
            'land_gallery' => self::def('Galerie immobilière', 'land', 'camera', ['reference' => $f('text', 'Référence du terrain', ['max' => 40])]),
            // — BTP
            'btp_services' => self::def('Présentation des services', 'btp', 'hammer', ['title' => $title(), 'count' => $count('Nombre de services', 1, 12)]),
            'btp_projects' => self::def('Réalisations', 'btp', 'building-2', ['title' => $title(), 'count' => $count('Nombre de réalisations', 1, 12)]),
            'site_gallery' => self::def('Galerie de chantiers', 'btp', 'camera', ['project' => $f('text', 'Identifiant de la réalisation', ['max' => 190])]),
            'project_showcase' => self::def('Présentation d\'un projet', 'btp', 'drafting-compass', ['project' => $f('text', 'Identifiant de la réalisation', ['max' => 190])]),
        ];
    }
}
