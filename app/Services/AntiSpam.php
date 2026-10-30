<?php
declare(strict_types=1);

namespace App\Services;

use App\Core\{Env, Request};
use App\Repositories\LeadRepository;

/**
 * Three cheap layers for public forms: a honeypot field, a signed timestamp (too fast / too old = bot or replay),
 * and a per-IP hourly cap. CSRF is enforced separately by the middleware.
 */
final class AntiSpam
{
    private const MIN_SECONDS = 3;
    private const MAX_SECONDS = 21600;
    private const MAX_PER_HOUR = 5;

    public function __construct(private LeadRepository $leads)
    {
    }

    public static function token(?int $ts = null): string
    {
        $ts ??= time();
        return $ts . '.' . hash_hmac('sha256', (string) $ts, self::key());
    }

    private static function key(): string
    {
        $k = Env::get('APP_KEY');
        if (!$k) {
            if (Env::get('APP_ENV') === 'production') {
                throw new \RuntimeException('APP_KEY is required in production');
            }
            $k = 'dev-only-insecure-key';
        }
        return $k;
    }

    /** Hidden inputs to embed in a public form. */
    public function fields(): string
    {
        return '<div class="hp" aria-hidden="true"><label>Ne pas remplir <input type="text" name="website" tabindex="-1" autocomplete="off"></label></div>'
            . '<input type="hidden" name="_t" value="' . e(self::token()) . '">';
    }

    /** @return string|null error message, null when the submission looks human */
    public function check(Request $req): ?string
    {
        if (trim((string) $req->input('website', '')) !== '') {
            return 'Envoi refusé.';
        }
        [$ts, $sig] = array_pad(explode('.', (string) $req->input('_t', ''), 2), 2, '');
        if (!ctype_digit($ts) || !hash_equals(hash_hmac('sha256', $ts, self::key()), $sig)) {
            return 'Formulaire expiré, rechargez la page.';
        }
        $age = time() - (int) $ts;
        if ($age < self::MIN_SECONDS) {
            return 'Envoi trop rapide, veuillez réessayer.';
        }
        if ($age > self::MAX_SECONDS) {
            return 'Formulaire expiré, rechargez la page.';
        }
        if ($this->leads->recentFromIp($req->ip) >= self::MAX_PER_HOUR) {
            return 'Trop de demandes depuis votre connexion. Réessayez plus tard.';
        }
        return null;
    }
}
