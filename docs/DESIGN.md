# Design (Lot 6)

Deux surfaces, deux directions, un seul jeu de variables de marque (bleu `#123F78`, rouge `#D20D1A`, gris, bleu clair `#EAF2FA`, tirés du logo officiel).

## Site public — « split stage » (taste-skill)
**Idée** : les deux métiers de Kibarou s'enchaînent, le terrain puis la construction. Une image reste épinglée à gauche et passe du *terrain* au *chantier* puis au *bâtiment* à mesure que le visiteur lit l'offre. (Un concurrent qui ne vend que des terrains, ou ne fait que construire, ne peut pas raconter cette histoire.)
**Chose vivante** : « combien de terrains pour mon budget » — compteur en direct sur le vrai catalogue (`/api/terrains/compte`), avec formulaire GET de repli sans JavaScript.
- Typographie : Literata (serif de lecture), hébergée localement ; une seule famille. Échelle fixe de 5 tailles.
- Règles maison : aplats, angles droits, un seul style de bouton, pas d'ombres, de dégradés, d'ornements ni de bordures de cartes ; séparation par changement de ton ; le rouge du logo est réservé à l'action.
- Accueil : titre sur 2 lignes à toutes les largeurs, une phrase, un bouton ; sections = présentation, terrains (+ recherche), services, réalisations (seulement s'il y en a), « pourquoi nous choisir », contact avec formulaire. **Rien n'est inventé** : une section sans donnée n'est pas affichée ; les services/domaines d'activité viennent du cahier des charges.
- Mouvement : scènes qui se fondent au défilement, apparition échelonnée des parties de chaque section, retours au survol/focus, dérive lente des scènes (`animation-timeline: scroll()`), tout désactivé sous `prefers-reduced-motion`. Contenu visible sans script (`@media (scripting: enabled)`).
- Thèmes : Corporate Premium, Minimaliste Moderne, Élégance & Prestige ne changent que des variables.

### Images
**Aucune photographie réelle n'est incluse** : l'environnement de développement bloquait les banques d'images libres (Wikimedia, Openverse, Picsum). Les trois scènes sont donc **dessinées en code** (terrain, chantier, bâtiment, aux couleurs du logo). Chaque scène est **remplaçable** par une photo : *Administration → Réglages → Accueil, photo « Terrain / Chantier / Bâtiment »* (choix parmi les médias du site). Les photographies professionnelles d'architecture et de développement foncier restent à fournir ; ne jamais présenter une photo trouvée comme une réalisation de Kibarou.

## Administration — « Operate » (Impeccable)
`PRODUCT.md` à la racine fixe la vérité produit. Principes : l'outil disparaît dans la tâche ; l'état se lit d'un coup d'œil ; dense mais respirable ; actions destructives distinctes ; une seule voix typographique.
- Barre latérale groupée selon le §15 (Gestion foncière, BTP, CRM, Contenu, Configuration), icônes Lucide, entrée active marquée, **liens masqués selon les permissions** du rôle ; panneau superposé sur mobile (bouton Menu, Échap, clic extérieur).
- Tableau de bord : lignes « libellé — chiffre » avec lien vers la liste filtrée (pas de cartes de chiffres géants) ; les demandes en attente passent en rouge si > 0.
- Statuts en **pastilles** (couleur + texte, jamais la couleur seule), libellés français partout (`available` → « Disponible »).
- IBM Plex Sans (hébergée localement), échelle rem fixe, boutons : défaut = secondaire, `.btn` = principal, `.danger` = destructif ; focus visible, cibles ≥ 40 px sur mobile ; écran de connexion dédié.
- Éditeur de blocs en plein écran (sans barre latérale).

## Vérifications faites
`inspect.mjs` (taste-skill) sur l'accueil à 1440/768/390 px ; `impeccable detect` sur l'administration : 0 signalement. Playwright : 5 largeurs sans débordement (accueil + 3 écrans d'administration), menus mobiles, console sans violation CSP.
