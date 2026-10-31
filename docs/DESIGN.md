# Design (Lot 6)

Deux surfaces, deux directions, un seul jeu de variables de marque (bleu `#123F78`, rouge `#D20D1A`, gris, bleu clair `#EAF2FA`, tirés du logo officiel).

## Site public — « grand groupe BTP » (v1.1, taste-skill)
**Idée** : l'allure des sites de grands groupes de construction et de promotion foncière — institutionnel pour le BTP, commercial pour les terrains. Deux métiers présentés côte à côte (Foncier / BTP), puis le catalogue.
**Chose vivante** : « combien de terrains pour mon budget » — compteur en direct sur le vrai catalogue (`/api/terrains/compte`) dans la bande de recherche de l'accueil, avec formulaire GET de repli sans JavaScript.
- Typographie : IBM Plex Sans, hébergée localement ; une seule famille.
- Structure : barre supérieure (contacts, masquée s'il n'y en a aucun), en-tête collant avec bouton « Trouver un terrain », hero plein écran sur photo voilée, bandeau photo + fil d'Ariane + `<h1>` unique sur toutes les pages intérieures, pied de page en 4 colonnes avec crédits photo.
- Accueil : hero, recherche, présentation + chiffres **réels** (masqués à zéro), deux métiers, terrains à la une, services BTP, réalisations et actualités (seulement s'il y en a), démarche, bandeau d'appel, contact.
- Catalogue : panneau de filtres latéral + cartes ; fiche terrain : galerie, encadré prix/actions collant, caractéristiques, foncier, documents (vérifiés ou non, jamais présentés comme vérifiés à tort), terrains similaires.
- Page **À propos** complète (présentation, histoire, mission, vision, valeurs, engagements, équipe, identité). Les textes manquants sont **provisoires et signalés** dans *Administration → Textes et photos* ; Histoire et Équipe sont masquées tant qu'elles sont vides. Aucun chiffre, témoignage ni référence inventés.
- Mouvement : apparition des sections au défilement, désactivée sous `prefers-reduced-motion` ; contenu visible sans script.
- Thèmes : Corporate Premium, Minimaliste Moderne, Élégance & Prestige ne changent que des variables.

### Images
Chaque emplacement (hero, terrains, BTP, appel, À propos, bandeau) prend dans l'ordre : la photo choisie dans la médiathèque du site, sinon une photo d'illustration libre de `public/assets/images/illustrations/<emplacement>.jpg` (créditée via `credits.json`), sinon un dessin vectoriel. Les banques d'images étaient bloquées pendant le développement : **aucune photo n'est livrée**, les dessins s'affichent. Ne jamais présenter une photo d'illustration comme une réalisation de Kibarou.

## Administration — « Operate » (Impeccable)
`PRODUCT.md` à la racine fixe la vérité produit. Principes : l'outil disparaît dans la tâche ; l'état se lit d'un coup d'œil ; dense mais respirable ; actions destructives distinctes ; une seule voix typographique.
- Barre latérale groupée selon le §15 (Gestion foncière, BTP, CRM, Contenu, Configuration), icônes Lucide, entrée active marquée, **liens masqués selon les permissions** du rôle ; panneau superposé sur mobile (bouton Menu, Échap, clic extérieur).
- Tableau de bord : lignes « libellé — chiffre » avec lien vers la liste filtrée (pas de cartes de chiffres géants) ; les demandes en attente passent en rouge si > 0.
- Statuts en **pastilles** (couleur + texte, jamais la couleur seule), libellés français partout (`available` → « Disponible »).
- IBM Plex Sans (hébergée localement), échelle rem fixe, boutons : défaut = secondaire, `.btn` = principal, `.danger` = destructif ; focus visible, cibles ≥ 40 px sur mobile ; écran de connexion dédié.
- Éditeur de blocs en plein écran (sans barre latérale).

## Vérifications faites
`inspect.mjs` (taste-skill) sur l'accueil à 1440/768/390 px ; `impeccable detect` sur l'administration : 0 signalement. Playwright : 5 largeurs sans débordement (accueil + 3 écrans d'administration), menus mobiles, console sans violation CSP.
