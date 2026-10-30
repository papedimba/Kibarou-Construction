# Déploiement sur cPanel

Testé sur Apache 2.4 + PHP 8.3 (`tests/deploy-smoke.sh` : 41 contrôles, dont installation par l'assistant web). Non testé sur un cPanel réel : les noms de menus peuvent varier légèrement selon la version.

## 1. PHP
cPanel → **MultiPHP Manager** : choisir PHP 8.2 ou 8.3 pour le domaine. **MultiPHP INI Editor** : `upload_max_filesize = 12M`, `post_max_size = 16M` (documents fonciers jusqu'à 10 Mo), `memory_limit = 256M`. Activer **OPcache**. Extensions (**Select PHP Version → Extensions**) : `pdo_mysql`, `mbstring`, `gd`, `intl`, `fileinfo`, `zip`.

## 2. Où mettre les fichiers
Ordre de préférence :
1. **Domaine pointé sur `public/` (recommandé).** Décompresser l'archive dans `~/kibarou/`, puis cPanel → **Domains** → *Manage* → *Document Root* = `kibarou/public`. Seul `public/` est alors exposé ; `.env`, `app/`, `storage/` sont hors web.
2. **Racine web imposée (`public_html`).** Décompresser l'archive dans `public_html/`. Le `.htaccess` de la racine interdit `app/`, `storage/`, `vendor/`, `.env`, etc. et redirige tout vers `public/`. À vérifier après installation : `https://domaine/.env` doit répondre **403**.
3. **Application hors `public_html` avec lien.** Décompresser dans `~/kibarou/` et remplacer `public_html` par un lien symbolique vers `~/kibarou/public` (si l'hébergeur le permet).

## 3. Base MySQL
cPanel → **MySQL® Database Wizard** : créer la base (ex. `cpuser_kibarou`), l'utilisateur et un mot de passe fort ; cocher **ALL PRIVILEGES**. Notez le nom complet préfixé (`cpuser_…`). Hôte : `localhost`.

## 4. Installation
Ouvrir `https://plateforme.mondomaine.com/install.php` (voir `INSTALLATION.md`). Après succès, **vérifier que `public/install.php` a disparu** ; sinon le supprimer à la main.

## 5. Permissions
| Élément | Droits |
|---|---|
| Dossiers | `755` |
| Fichiers | `644` |
| `.env` | `600` (posé par l'installateur) |
| `storage/` et sous-dossiers, `public/uploads/` | écriture pour l'utilisateur PHP (`755` suffit avec PHP-FPM/suPHP ; `775` si PHP tourne sous un autre utilisateur) |

Ne jamais mettre `777`. Le dossier racine n'a besoin d'être accessible en écriture que pendant l'installation.

## 6. DNS
- **Un seul domaine** : l'hôte de la plateforme peut être le domaine du site lui-même (ex. `kibarouconstruction.com`) : `/platform/…` reste l'administration centrale, tout le reste sert le site. Pour plusieurs clients, préférez un hôte dédié (`plateforme.mondomaine.com`).
- **Plateforme** : enregistrement `A` de `plateforme.mondomaine.com` vers l'IP du serveur (cPanel → **Zone Editor**).
- **Chaque site client** : enregistrement `A` de son domaine (et de `www`) vers la même IP. Dans cPanel → **Domains**, ajouter le domaine avec **le même Document Root** que l'application.
- **Sous-domaines** (`client.mondomaine.com`) : un enregistrement `A` joker `*.mondomaine.com` vers l'IP, ou un enregistrement par sous-domaine ; renseigner `PLATFORM_BASE_DOMAIN=mondomaine.com`.
- Dans **/platform → Sites**, associer chaque domaine au site. Un domaine inconnu de la plateforme répond 404 : c'est voulu.
- La propagation DNS peut prendre jusqu'à 24 h.

## 7. SSL
cPanel → **SSL/TLS Status** → *Run AutoSSL* (Let's Encrypt / cPanel). Il couvre chaque domaine ajouté dans cPanel. Un certificat **joker** (`*.mondomaine.com`) demande une validation DNS que AutoSSL n'offre pas toujours : préférez ajouter chaque client comme domaine. Une fois le HTTPS actif :
1. décommenter le bloc *Pour forcer HTTPS* de `public/.htaccess` (ou du `.htaccess` racine selon l'option choisie) ;
2. l'en-tête HSTS est envoyé automatiquement en HTTPS, et les cookies de session passent en `Secure`.

## 8. E-mails
`MAIL_DRIVER=mail` utilise le service d'envoi du serveur. `MAIL_FROM` doit être une adresse **de votre domaine** et le domaine doit avoir SPF/DKIM (cPanel → **Email Deliverability**), sinon les notifications arrivent en spam. Tester avec une demande depuis le formulaire de contact.

## 9. Sauvegardes
- **Manuelle** : `/platform/backups` → *Créer une sauvegarde* puis *Télécharger* (super administrateur).
- **Automatique** : cPanel → **Cron Jobs**, tous les jours à 3 h : `0 3 * * * /usr/local/bin/php /home/UTILISATEUR/kibarou/bin/console.php backup --keep=14`
- Contenu : base de données de **tous** les sites + images + documents fonciers privés. Archives dans `storage/backups/` (non accessibles par le web). **Copiez-les hors du serveur** (poste, stockage distant) : une sauvegarde sur le même disque ne protège pas d'une panne du serveur.

## 10. Restauration
```bash
php bin/console.php restore /chemin/kibarou-AAAAMMJJ-HHMMSS-xxxxxx.zip --yes
```
Remplace **toute** la base et réécrit les fichiers de l'archive ; l'archive est validée entièrement avant la moindre modification (chemins hors dossier autorisé, scripts, `.htaccess` refusés). Procédure sûre : (1) faire une sauvegarde de l'état actuel, (2) restaurer, (3) `php bin/console.php doctor`, (4) vérifier un site et une connexion. **Testez une restauration sur un environnement de recette avant d'en avoir besoin.**

## 11. Mise à jour
1. Sauvegarde (§9). 2. Décompresser la nouvelle archive **par-dessus** (elle ne contient ni `.env` ni données : `.env`, `storage/` et `public/uploads/` sont conservés). **Supprimer ensuite `public/install.php`** qu'elle remet en place (il reste inactif tant que `.env` existe, mais n'a rien à faire en production). 3. `php bin/console.php migrate`. 4. `php bin/console.php doctor`.

## 12. Suppression d'un site
`/platform/sites` → *Supprimer* (taper l'identifiant) : le site disparaît (404) mais ses données sont conservées. Purge définitive, irréversible : `php bin/console.php site:purge <slug> --yes` (efface données et fichiers du site, pas ceux des autres).

## 13. Dépannage
| Symptôme | Piste |
|---|---|
| Erreur 500 | `storage/logs/app.log` ; mettre temporairement `APP_DEBUG=1` ; vérifier la version de PHP |
| 403 partout | `AllowOverride` désactivé pour le domaine, ou `.htaccess` non envoyé (fichier caché dans le gestionnaire de fichiers : activer « Show Hidden Files ») |
| 404 sur toutes les pages sauf l'accueil | `mod_rewrite` / `.htaccess` ignoré |
| Page sans style | domaine pointé sur la racine alors que le `.htaccess` racine est absent |
| Connexion impossible / session perdue | `storage/sessions` non inscriptible |
| Téléversement refusé | `upload_max_filesize`, `post_max_size`, droits de `public/uploads` |
| Aucun e-mail reçu | SPF/DKIM, `MAIL_FROM`, dossier spam |

## 14. Limites connues
Pas de redimensionnement automatique des images (téléverser des images raisonnables), pas de cache de pages, sauvegarde téléchargée en mémoire (au-delà de ~100 Mo, la récupérer par FTP).
