<?php
declare(strict_types=1);

namespace App\Services;

use App\Core\{Database, Migrator};
use App\Multisite\{DomainManager, TenantProvisioner};

/**
 * First-run installation (web page public/install.php). Safe by default: it refuses to run when a .env or the
 * lock file already exists, so a deployed site cannot be re-installed or hijacked through it.
 */
final class Installer
{
    public function __construct(private string $root)
    {
    }

    public function locked(): bool
    {
        return is_file($this->root . '/storage/installed.lock') || is_file($this->root . '/.env');
    }

    /** @return list<array{0:string,1:bool,2:string,3:bool}> [label, ok, detail, blocking] */
    public function requirements(): array
    {
        $r = [['PHP 8.2 ou supérieur', PHP_VERSION_ID >= 80200, PHP_VERSION, true]];
        foreach (['pdo_mysql', 'mbstring', 'gd', 'fileinfo', 'openssl', 'intl'] as $ext) {
            $r[] = ["Extension $ext", extension_loaded($ext), extension_loaded($ext) ? 'active' : 'manquante', true];
        }
        $r[] = ['Extension zip (sauvegardes)', extension_loaded('zip'), extension_loaded('zip') ? 'active' : 'manquante : les sauvegardes seront indisponibles', false];
        $r[] = ['Dépendances installées (vendor/)', is_file($this->root . '/vendor/autoload.php'), 'dossier vendor/ présent', true];
        foreach (['storage', 'storage/logs', 'storage/sessions', 'storage/backups', 'storage/private', 'public/uploads'] as $dir) {
            @mkdir("$this->root/$dir", 0750, true);
            $r[] = ["Écriture dans $dir", is_writable("$this->root/$dir"), is_writable("$this->root/$dir") ? 'ok' : 'permissions à corriger (755 ou 775)', true];
        }
        $r[] = ['Écriture du fichier .env', is_writable($this->root), is_writable($this->root) ? 'ok' : 'le dossier racine doit être accessible en écriture pendant l\'installation', true];
        return $r;
    }

    public function ready(): bool
    {
        foreach ($this->requirements() as [, $ok, , $blocking]) {
            if ($blocking && !$ok) {
                return false;
            }
        }
        return true;
    }

    /**
     * @param array<string,string> $in db_host, db_port, db_name, db_user, db_pass, platform_host, base_domain,
     *        admin_email, admin_name, admin_password, optional: site_name, site_slug, site_host, site_admin_email, site_admin_name, site_admin_password
     * @throws \InvalidArgumentException with a message safe to show to the user (never contains a password)
     */
    public function install(array $in): void
    {
        if ($this->locked()) {
            throw new \InvalidArgumentException('Déjà installé : supprimez .env et storage/installed.lock pour réinstaller.');
        }
        if (!$this->ready()) {
            throw new \InvalidArgumentException('Les prérequis ne sont pas satisfaits.');
        }
        $g = fn(string $k) => trim((string) ($in[$k] ?? ''));
        $platform = DomainManager::normalize($g('platform_host'));
        $base = $g('base_domain') === '' ? '' : DomainManager::normalize($g('base_domain'));
        if ($platform === '' || ($g('base_domain') !== '' && $base === '')) {
            throw new \InvalidArgumentException('Nom de domaine invalide (hôte de la plateforme ou domaine de base).');
        }
        if (!filter_var($g('admin_email'), FILTER_VALIDATE_EMAIL) || $g('admin_name') === '' || mb_strlen((string) ($in['admin_password'] ?? '')) < 12) {
            throw new \InvalidArgumentException('Super administrateur : email valide, nom, et mot de passe de 12 caractères minimum.');
        }
        foreach (['db_name', 'db_user'] as $k) {
            if ($g($k) === '' || !preg_match('/^[A-Za-z0-9_$.-]+$/', $g($k))) {
                throw new \InvalidArgumentException('Base de données : nom ou utilisateur invalide.');
            }
        }
        $host = $g('db_host') ?: '127.0.0.1';
        $port = $g('db_port') ?: '3306';
        if (!preg_match('/^[A-Za-z0-9.-]+$/', $host) || !ctype_digit($port)) {
            throw new \InvalidArgumentException('Base de données : hôte ou port invalide.');
        }
        try {
            $pdo = new \PDO("mysql:host=$host;port=$port;dbname={$g('db_name')};charset=utf8mb4", $g('db_user'), (string) ($in['db_pass'] ?? ''), [
                \PDO::ATTR_ERRMODE => \PDO::ERRMODE_EXCEPTION, \PDO::ATTR_DEFAULT_FETCH_MODE => \PDO::FETCH_ASSOC, \PDO::ATTR_EMULATE_PREPARES => false,
            ]);
        } catch (\PDOException) {
            throw new \InvalidArgumentException('Connexion à la base impossible : vérifiez hôte, nom, utilisateur et mot de passe.'); // PDO's message can echo credentials
        }
        $db = new Database($pdo);
        (new Migrator($db, $this->root . '/database/migrations'))->run();
        if (!$db->value('SELECT 1 FROM users WHERE email = ?', [strtolower($g('admin_email'))])) {
            $db->insert('users', ['email' => strtolower($g('admin_email')), 'name' => $g('admin_name'), 'is_super_admin' => 1, 'password_hash' => password_hash((string) $in['admin_password'], PASSWORD_DEFAULT)]);
        }
        if ($g('site_name') !== '') {
            (new TenantProvisioner($db))->create($g('site_name'), $g('site_slug'), $g('site_host'), $g('site_admin_email'), $g('site_admin_name'), (string) ($in['site_admin_password'] ?? ''));
        }
        $env = "APP_ENV=production\nAPP_DEBUG=0\nAPP_KEY=" . bin2hex(random_bytes(24)) . "\nPLATFORM_HOST=$platform\nPLATFORM_BASE_DOMAIN=$base\n"
            . "DB_HOST=$host\nDB_PORT=$port\nDB_NAME={$g('db_name')}\nDB_USER={$g('db_user')}\nDB_PASS=" . str_replace(["\r", "\n"], '', (string) ($in['db_pass'] ?? '')) . "\nMAIL_DRIVER=mail\nMAIL_FROM=no-reply@$platform\n";
        $tmp = $this->root . '/.env.tmp';
        file_put_contents($tmp, $env);
        chmod($tmp, 0600);
        rename($tmp, $this->root . '/.env');
        file_put_contents($this->root . '/storage/installed.lock', date('c') . "\n");
    }
}
