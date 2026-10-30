<?php
/**
 * Web installer. Delete this file after installation (it tries to delete itself on success).
 * It refuses to run if .env or storage/installed.lock exists.
 */
declare(strict_types=1);

$root = dirname(__DIR__);
if (!is_file($root . '/vendor/autoload.php')) {
    http_response_code(500);
    exit('Dossier vendor/ manquant : envoyez l\'archive de livraison complète (elle contient vendor/).');
}
require $root . '/vendor/autoload.php';

use App\Services\Installer;

$installer = new Installer($root);
if ($installer->locked()) {
    http_response_code(404);
    exit('Not found');
}
session_name('kib_install');
session_start();
$_SESSION['csrf'] ??= bin2hex(random_bytes(16));
$e = fn($s) => htmlspecialchars((string) $s, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
$error = null;
$done = false;
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!hash_equals($_SESSION['csrf'], (string) ($_POST['_csrf'] ?? ''))) {
        $error = 'Jeton de sécurité invalide. Rechargez la page.';
    } else {
        try {
            $installer->install($_POST);
            $done = true;
            @unlink(__FILE__);
        } catch (InvalidArgumentException $ex) {
            $error = $ex->getMessage();
        } catch (Throwable $ex) {
            error_log('Install failed: ' . $ex::class);
            $error = 'Erreur inattendue pendant l\'installation (consultez le journal PHP du serveur).';
        }
    }
}
$v = fn(string $k, string $d = '') => $e($_POST[$k] ?? $d);
header('X-Frame-Options: DENY');
header('Content-Security-Policy: default-src \'none\'; style-src \'unsafe-inline\'; form-action \'self\'');
?>
<!doctype html>
<html lang="fr"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1"><meta name="robots" content="noindex">
<title>Installation — KIBAROU CMS</title>
<style>body{font:16px/1.5 system-ui,sans-serif;max-width:720px;margin:2rem auto;padding:0 1rem;color:#16202e}h1{color:#123F78}table{width:100%;border-collapse:collapse}td{padding:.35rem .5rem;border-top:1px solid #dbe2ea}.ok{color:#0f6b3f}.ko{color:#a4121b;font-weight:600}label{display:block;margin:.8rem 0 .2rem;font-weight:600}input{width:100%;padding:.5rem;font:inherit;border:1px solid #b9c4d1;border-radius:6px}fieldset{border:1px solid #dbe2ea;margin:1.2rem 0;padding:.5rem 1rem 1rem}button{background:#123F78;color:#fff;border:0;padding:.7rem 1.4rem;font:inherit;font-weight:600;border-radius:6px;cursor:pointer}.err{background:#fde8ea;color:#a4121b;padding:.7rem 1rem;border-radius:6px}.good{background:#e2f4ea;padding:1rem;border-radius:6px}</style></head><body>
<h1>Installation de KIBAROU CMS</h1>
<?php if ($done): ?>
  <div class="good"><strong>Installation terminée.</strong>
  <p>Le fichier <code>install.php</code> a été supprimé automatiquement. Vérifiez qu'il n'existe plus sur le serveur.</p>
  <p>Connexion à l'administration centrale : <code>https://<?= $e($_POST['platform_host'] ?? '') ?>/platform/login</code></p>
  <p>Étapes suivantes : ajouter le domaine du site, activer le SSL, planifier la sauvegarde (voir DEPLOYMENT_CPANEL.md).</p></div>
<?php else: ?>
  <h2>Prérequis</h2>
  <table><?php foreach ($installer->requirements() as [$label, $ok, $detail, $blocking]): ?>
    <tr><td><?= $e($label) ?></td><td class="<?= $ok ? 'ok' : 'ko' ?>"><?= $ok ? 'OK' : ($blocking ? 'À corriger' : 'Attention') ?> — <?= $e($detail) ?></td></tr>
  <?php endforeach; ?></table>
  <?php if ($error): ?><p class="err" role="alert"><?= $e($error) ?></p><?php endif; ?>
  <?php if ($installer->ready()): ?>
  <form method="post" autocomplete="off">
    <input type="hidden" name="_csrf" value="<?= $e($_SESSION['csrf']) ?>">
    <fieldset><legend>Base de données MySQL / MariaDB</legend>
      <label>Hôte<input name="db_host" value="<?= $v('db_host', 'localhost') ?>"></label>
      <label>Port<input name="db_port" value="<?= $v('db_port', '3306') ?>"></label>
      <label>Nom de la base<input name="db_name" required value="<?= $v('db_name') ?>"></label>
      <label>Utilisateur<input name="db_user" required value="<?= $v('db_user') ?>"></label>
      <label>Mot de passe<input name="db_pass" type="password"></label></fieldset>
    <fieldset><legend>Plateforme</legend>
      <label>Domaine de l'administration centrale (ex. plateforme.mondomaine.com)<input name="platform_host" required value="<?= $v('platform_host') ?>"></label>
      <label>Domaine de base pour les sous-domaines (facultatif, ex. mondomaine.com)<input name="base_domain" value="<?= $v('base_domain') ?>"></label></fieldset>
    <fieldset><legend>Super administrateur</legend>
      <label>Nom<input name="admin_name" required value="<?= $v('admin_name') ?>"></label>
      <label>Email<input name="admin_email" type="email" required value="<?= $v('admin_email') ?>"></label>
      <label>Mot de passe (12 caractères minimum)<input name="admin_password" type="password" required minlength="12" autocomplete="new-password"></label></fieldset>
    <fieldset><legend>Premier site (facultatif, peut être créé plus tard dans /platform)</legend>
      <label>Nom du site<input name="site_name" value="<?= $v('site_name') ?>"></label>
      <label>Identifiant (slug)<input name="site_slug" value="<?= $v('site_slug') ?>"></label>
      <label>Domaine du site<input name="site_host" value="<?= $v('site_host') ?>"></label>
      <label>Administrateur du site : nom<input name="site_admin_name" value="<?= $v('site_admin_name') ?>"></label>
      <label>Administrateur du site : email<input name="site_admin_email" type="email" value="<?= $v('site_admin_email') ?>"></label>
      <label>Administrateur du site : mot de passe (12 caractères minimum)<input name="site_admin_password" type="password" autocomplete="new-password"></label></fieldset>
    <button>Installer</button>
  </form>
  <?php endif; ?>
<?php endif; ?>
</body></html>
