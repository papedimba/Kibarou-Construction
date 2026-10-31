# Guide utilisateur

## Connexion et rôles
Administration : `https://votre-site/admin`. Chaque personne a un rôle : **Administrateur de site** (tout), **Responsable foncier** (terrains, lotissements, localisations), **Responsable commercial** (prospects, visites), **Responsable BTP** (services, réalisations), **Éditeur** (pages, articles, médias, menus, formulaires). Le menu n'affiche que ce que votre rôle permet. L'administrateur gère l'équipe dans **Utilisateurs** (ajouter, changer le rôle, réinitialiser un mot de passe, retirer l'accès ; le dernier administrateur ne peut pas être retiré).

## Mon compte
Cliquez sur votre nom en haut à droite : **changer mon mot de passe** (12 caractères minimum, mot de passe actuel demandé). À faire dès la première connexion si l'installation a été faite sans HTTPS.

## Terrains
**Terrains → Ajouter** : référence (unique), désignation, catégorie, localisation, superficie (m²), prix (FCFA), statut, GPS (facultatif), caractéristiques, informations foncières (uniquement si confirmées), publication. Le statut commercial (disponible, en cours de réservation, réservé, vendu, indisponible) se change depuis la liste ; un terrain vendu ou non publié disparaît du catalogue.
**Photos/docs** (sur chaque ligne) : photos, **documents fonciers** (ACD, titre foncier, attestations, plans…), fiche **PDF**, **QR code** (SVG pour l'impression, PNG). Un document est « non vérifié » tant que vous ne cliquez pas *Marquer vérifié* ; il n'est publié que s'il est « public ». Les documents sont stockés hors du site public.
**Localisations** : hiérarchie libre (district → quartier) ; le catalogue filtre aussi sur les sous-localisations. **Lotissements** : regroupe des parcelles (numéro, référence, superficie, prix, statut).
Un terrain lié à des demandes de prospects ne peut pas être supprimé : passez-le en « Indisponible ».

## BTP et réalisations
**Services** : créés **inactifs** ; cochez « Actif » seulement quand la prestation est confirmée. **Réalisations** : uniquement de vraies réalisations, avec galerie. Rien n'est pré-rempli.

## Prospects (CRM)
Les demandes du site (informations, visite, rappel, devis, partenariat, formulaires) arrivent dans **Prospects** avec une référence `LD-000123`. Ouvrez une fiche : changer le **statut**, **affecter** un responsable, ajouter des **notes** internes, **programmer une visite** (calendrier dans **Visites**), consulter l'**historique**. Recherche, filtres et **export CSV** (les cellules dangereuses sont neutralisées). Une demande ne constitue jamais une réservation.

## Contenu
**Pages** et **Articles** (titre, adresse, texte simple, SEO, brouillon/publié, versions des pages). **Médias** : images avec texte alternatif (ALT). **Menus** : liens d'en-tête/pied de page et **redirections 301**. **Formulaires** : un champ par ligne `Libellé | type | obligatoire (1/0) | options` ; le formulaire est publié sur `/f/adresse` et les réponses sont dans l'administration. **Réglages** : coordonnées, WhatsApp (le bouton n'apparaît que si renseigné), logo, thème, page d'accueil.

## Plateforme (super administrateur)
`/platform` : créer un site (nom, identifiant, domaine, administrateur), le suspendre/réactiver, ajouter des domaines, voir ses chiffres, le supprimer (taper son identifiant) et gérer les **sauvegardes**.

## Construire une page avec l'éditeur visuel
1. **Administration → Pages → Éditeur visuel** (à côté de la page voulue).
2. **Gauche** : la bibliothèque. Cliquez un bloc pour l'ajouter, ou glissez-le dans la page. Si un bloc *conteneur* (Section, Conteneur, Colonnes, Grille) est sélectionné, le nouveau bloc y entre ; sinon il se place juste après le bloc sélectionné.
3. **Centre** : la page telle que les visiteurs la verront. Cliquez un bloc pour le sélectionner ; barre d'outils : ↑ ↓, Dupliquer, Supprimer. Vous pouvez aussi réordonner en glissant-déposant (y compris dans une Section ou une colonne).
4. **Droite** : les paramètres du bloc sélectionné. Les listes (services, FAQ, statistiques…) se gèrent avec « + Ajouter », ↑ ↓ et « Retirer ».
5. **Ordinateur / Tablette / Mobile** change la largeur de l'aperçu : la mise en page s'adapte comme sur le vrai site.
6. **Enregistrer** garde votre brouillon *sans* changer le site. **Aperçu du brouillon** l'ouvre dans un nouvel onglet. **Publier** met la page en ligne et archive la version précédente.
7. **Versions publiées** (colonne de gauche) : « Restaurer dans le brouillon » récupère une ancienne version ; elle n'est visible des visiteurs qu'après un nouveau « Publier ».

## À savoir
- Les images se choisissent parmi celles déjà ajoutées dans **Médias** : téléversez-les d'abord.
- Les **témoignages** ne sont publiés que s'ils sont cochés « vérifié ». N'inventez ni chiffres, ni références, ni avis.
- Un lien doit commencer par `/` (page du site) ou `https://`. Les autres formes sont ignorées à l'enregistrement.
- Le texte est du texte simple : le HTML saisi s'affiche tel quel, il n'est jamais interprété.
- Pour utiliser une page comme **accueil** : Réglages → « Page d'accueil » → identifiant de la page (elle doit être publiée).
- Blocs « Terrain vedette », « Galerie immobilière » : saisissez la **référence** du terrain publié. « Galerie de chantiers », « Présentation d'un projet » : l'**identifiant** (adresse) de la réalisation publiée.

## Textes et photos du site
**Administration → Textes et photos** regroupe les textes de l'accueil et de la page **À propos** (présentation, démarche, mission, vision, valeurs, engagements, histoire, équipe) et les photos de chaque section.
- Chaque texte porte une pastille : **Provisoire, à remplacer** (texte générique rédigé pour démarrer, sans chiffre ni référence inventés), **Cahier des charges** (repris du cahier), **Vide, section masquée** (Histoire, Équipe : la section n'apparaît pas tant qu'elle est vide) ou **Personnalisé**.
- Vider un champ rétablit le texte par défaut. `{site}` est remplacé par le nom du site.
- Les chiffres de l'accueil (terrains disponibles, lotissements, services, réalisations) sont calculés depuis vos données ; un chiffre à zéro n'est pas affiché.
- Photos : choisissez une image de la **Médiathèque** pour chaque emplacement (accueil, terrains, BTP, appel à l'action, À propos, bandeau des pages). Sans choix, une photo d'illustration (créditée en pied de page) ou un dessin est affiché. Une photo d'illustration ou d'un autre chantier ne doit jamais être présentée comme une réalisation de l'entreprise.
- Pour une page À propos entièrement libre : créez une page publiée d'adresse `a-propos`, elle remplace le modèle.
