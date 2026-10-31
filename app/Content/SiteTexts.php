<?php
declare(strict_types=1);

namespace App\Content;

use App\Core\Database;
use App\Multisite\TenantContext;

/**
 * Editable texts and photo slots of the public site. Every text has a default: either taken from the specification
 * ("cahier") or a provisional wording marked as such in the admin. A section whose text is empty is hidden, so nothing
 * (history, team, figures) is ever invented to fill a gap. Stored in `settings` under "txt." / "img." keys.
 */
final class SiteTexts
{
    public const CAHIER = 'cahier';
    public const PROVISIONAL = 'provisoire';
    public const EMPTY = 'vide';

    /** key => [group, label, default, origin, max length] */
    public const TEXTS = [
        'home_hero_title' => ['Accueil', 'Titre principal', 'Construisons vos projets. Trouvons ensemble votre terrain.', self::CAHIER, 120],
        'home_hero_subtitle' => ['Accueil', 'Sous-titre', "De l'acquisition foncière à la réalisation de vos projets immobiliers, {site} vous accompagne en Côte d'Ivoire.", self::CAHIER, 260],
        'home_intro_title' => ['Accueil', 'Présentation : titre', 'Du terrain à la remise des clés', self::PROVISIONAL, 120],
        'home_intro_text' => ['Accueil', 'Présentation : texte', "{site} réunit deux métiers complémentaires : la commercialisation de terrains et la réalisation de travaux de construction. Un seul interlocuteur vous accompagne, du choix de la parcelle jusqu'à la livraison de votre bâtiment.", self::PROVISIONAL, 700],
        'home_land_text' => ['Accueil', 'Métier foncier : texte', "Terrains résidentiels, commerciaux, agricoles ou en lotissement : chaque parcelle est présentée avec sa superficie, son prix, sa localisation et l'état de ses documents.", self::PROVISIONAL, 400],
        'home_btp_text' => ['Accueil', 'Métier BTP : texte', 'Construction de maisons et de bâtiments, gros œuvre et second œuvre, rénovation, terrassement, travaux publics et génie civil.', self::PROVISIONAL, 400],
        'home_steps' => ['Accueil', 'Notre démarche (une étape par ligne : Titre | texte)', "Choisir le terrain | Nous vous présentons les parcelles adaptées à votre projet et à votre budget.\nVérifier le foncier | Nous indiquons clairement les documents disponibles et leur état de vérification.\nConcevoir et chiffrer | Nous étudions votre projet de construction et vous remettons un devis.\nConstruire et livrer | Nous réalisons les travaux et vous tenons informé jusqu'à la livraison.", self::PROVISIONAL, 1200],
        'home_cta_title' => ['Accueil', 'Bandeau final : titre', 'Un projet de terrain ou de construction ?', self::PROVISIONAL, 120],
        'home_cta_text' => ['Accueil', 'Bandeau final : texte', 'Décrivez-nous votre besoin : notre équipe vous recontacte rapidement.', self::PROVISIONAL, 300],
        'about_intro' => ['À propos', 'Présentation', "{site} est une entreprise ivoirienne de construction et de commercialisation foncière. Elle intervient dans la construction et le bâtiment, les travaux publics, le gros œuvre et le second œuvre, la rénovation, le terrassement et le génie civil, et propose des terrains de différentes catégories.", self::PROVISIONAL, 1500],
        'about_history' => ['À propos', 'Notre histoire (vide = section masquée)', '', self::EMPTY, 3000],
        'about_mission' => ['À propos', 'Mission', 'Faciliter l\'accès à la propriété et réaliser des constructions durables, en accompagnant chaque client de l\'acquisition du terrain jusqu\'à la livraison.', self::PROVISIONAL, 800],
        'about_vision' => ['À propos', 'Vision', 'Devenir un acteur de référence du foncier et de la construction en Côte d\'Ivoire, reconnu pour sa fiabilité et la clarté de ses engagements.', self::PROVISIONAL, 800],
        'about_values' => ['À propos', 'Valeurs (une par ligne : Titre | texte)', "Fiabilité | Tenir nos engagements, sur le terrain comme sur le chantier.\nTransparence | Des informations claires sur chaque parcelle, chaque document et chaque devis.\nProximité | Un interlocuteur disponible tout au long de votre projet.\nExigence | Un travail soigné, des chantiers suivis et sécurisés.", self::PROVISIONAL, 1200],
        'about_commitments' => ['À propos', 'Engagements (un par ligne)', "Présenter chaque terrain avec ses informations essentielles et l'état réel de ses documents.\nNe jamais présenter comme vérifié un document qui ne l'a pas été.\nRemettre un devis clair avant tout engagement de travaux.\nRépondre à chaque demande et en assurer le suivi.", self::PROVISIONAL, 1200],
        'about_team' => ['À propos', 'Équipe dirigeante (vide = section masquée)', '', self::EMPTY, 2000],
    ];

    /** slot => [label, shown where] */
    public const PHOTOS = [
        'hero' => ['Grande photo d\'accueil', 'Accueil, premier écran'],
        'land' => ['Métier foncier', 'Accueil, bloc « Terrains »'],
        'btp' => ['Métier BTP', 'Accueil, bloc « Construction »'],
        'cta' => ['Bandeau final', 'Accueil, bas de page'],
        'about' => ['À propos', 'Page À propos'],
        'banner' => ['Bandeau des pages', 'En-tête des pages intérieures'],
    ];

    private ?array $cache = null;

    public function __construct(private Database $db, private TenantContext $tenant)
    {
    }

    private function stored(): array
    {
        return $this->cache ??= array_column($this->db->all("SELECT `key`, value FROM settings WHERE site_id = ? AND (`key` LIKE 'txt.%' OR `key` LIKE 'img.%')", [$this->tenant->id()]), 'value', 'key');
    }

    public function get(string $key): string
    {
        $s = $this->stored();
        return $s["txt.$key"] ?? str_replace('{site}', (string) $this->tenant->site()['name'], self::TEXTS[$key][2] ?? '');
    }

    /** @return array{0:string,1:string} [origin of the current value, value] — origin is "personnalisé" once edited. */
    public function state(string $key): array
    {
        $s = $this->stored();
        return isset($s["txt.$key"]) ? ['personnalise', $s["txt.$key"]] : [self::TEXTS[$key][3], $this->get($key)];
    }

    /** "Titre | texte" lines → [[title, text], …]; lines without "|" become [line, ''] */
    public function pairs(string $key): array
    {
        $out = [];
        foreach (preg_split('/\R/', $this->get($key)) as $line) {
            if (trim($line) !== '') {
                $p = array_map('trim', explode('|', $line, 2));
                $out[] = [$p[0], $p[1] ?? ''];
            }
        }
        return $out;
    }

    public function mediaId(string $slot): ?int
    {
        $v = $this->stored()["img.$slot"] ?? '';
        return ctype_digit($v) ? (int) $v : null;
    }

    /**
     * Photo of a slot: the site's own media if chosen, else the bundled illustration (credited), else null (drawn fallback).
     * @return array{url:string,credit:?string}|null
     */
    public function photo(string $slot): ?array
    {
        if ($id = $this->mediaId($slot)) {
            $f = $this->db->value('SELECT filename FROM media WHERE site_id = ? AND id = ?', [$this->tenant->id(), $id]);
            if ($f) {
                return ['url' => '/uploads/' . $this->tenant->id() . '/' . basename((string) $f), 'credit' => null];
            }
        }
        $credits = self::illustrationCredits();
        foreach (['jpg', 'webp'] as $ext) {
            if (is_file(self::illustrationDir() . "/$slot.$ext")) {
                return ['url' => "/assets/images/illustrations/$slot.$ext", 'credit' => $credits[$slot] ?? null];
            }
        }
        return null;
    }

    public static function illustrationDir(): string
    {
        return dirname(__DIR__, 2) . '/public/assets/images/illustrations';
    }

    /** @return array<string,string> slot => "Title — author — licence — source" */
    public static function illustrationCredits(): array
    {
        $f = self::illustrationDir() . '/credits.json';
        return is_file($f) ? (json_decode((string) file_get_contents($f), true) ?: []) : [];
    }

    /** Empty value = back to the default (provisional text, illustration photo). @return string|null error */
    public function save(array $texts, array $photos, callable $mediaExists): ?string
    {
        foreach ($texts as $k => $v) {
            if (!isset(self::TEXTS[$k]) || !is_string($v) || mb_strlen($v) > self::TEXTS[$k][4]) {
                return 'Texte trop long ou inconnu : ' . (self::TEXTS[$k][1] ?? $k);
            }
        }
        foreach ($photos as $k => $v) {
            if (!isset(self::PHOTOS[$k]) || ($v !== '' && (!ctype_digit((string) $v) || !$mediaExists((int) $v)))) {
                return 'Photo invalide : ' . (self::PHOTOS[$k][0] ?? $k);
            }
        }
        $sid = $this->tenant->id();
        $this->db->transaction(function (Database $db) use ($texts, $photos, $sid) {
            foreach (['txt' => $texts, 'img' => $photos] as $prefix => $values) {
                foreach ($values as $k => $v) {
                    $v = str_replace("\r", '', trim((string) $v));
                    $default = $prefix === 'txt' ? str_replace('{site}', (string) $this->tenant->site()['name'], self::TEXTS[$k][2]) : '';
                    if ($v === '' || $v === $default) { // identical to the default = not customised
                        $db->run('DELETE FROM settings WHERE site_id = ? AND `key` = ?', [$sid, "$prefix.$k"]);
                    } else {
                        $db->run('INSERT INTO settings (site_id, `key`, value) VALUES (?, ?, ?) ON DUPLICATE KEY UPDATE value = VALUES(value)', [$sid, "$prefix.$k", $v]);
                    }
                }
            }
        });
        $this->cache = null;
        return null;
    }
}
