# Manuel d’utilisation de Compta Artico

Ce manuel accompagne les utilisateurs dans les opérations de gestion financière et comptable de Compta Artico. Il décrit les écrans et procédures courants, les contrôles à effectuer avant validation et les règles de modification des documents. Les menus et boutons visibles dépendent du rôle du compte et des modules activés par l’administrateur.

## Sommaire

1. [Se connecter et se repérer](#1-se-connecter-et-se-repérer)
2. [Rôles et droits d’accès](#2-rôles-et-droits-daccès)
3. [États de besoin](#3-états-de-besoin)
4. [Bons de sortie](#4-bons-de-sortie)
5. [Bons d’entrée](#5-bons-dentrée)
6. [Journaux et trésorerie](#6-journaux-et-trésorerie)
7. [Comptabilité et BRC](#7-comptabilité-et-brc)
8. [Paramètres et administration](#8-paramètres-et-administration)
9. [Recherche, impression et résolution des problèmes](#9-recherche-impression-et-résolution-des-problèmes)

## 1. Se connecter et se repérer

1. Ouvrez l’adresse de l’application fournie par l’administrateur. En environnement local, elle peut être `http://localhost:8000`.
2. Entrez votre identifiant et votre mot de passe.
3. Si une modification du mot de passe est exigée à la première connexion, choisissez un nouveau mot de passe pour continuer.
4. Après connexion, utilisez le menu latéral pour accéder aux modules et le menu du compte pour ouvrir **Mon profil**, **Paramètres** (si disponible) ou **Déconnexion**.

Le tableau de bord présente des indicateurs et raccourcis adaptés aux autorisations du compte. Sur un poste partagé, déconnectez-vous après utilisation. Ne communiquez jamais votre mot de passe.

## 2. Rôles et droits d’accès

Les droits sont contrôlés par l’application. Un bouton absent peut indiquer que le rôle n’autorise pas l’opération, ou que le statut du document verrouille sa modification.

| Rôle | Principales opérations accessibles selon les droits configurés |
| --- | --- |
| Super Admin | Administration, utilisateurs, états de besoin, opérations de caisse, comptabilité, paramètres, audit, corbeille, sauvegardes et réouverture de certains documents. |
| Admin, Gérant, Directeur Général | Fonctions de gestion, consultation et validations autorisées par les règles du module. |
| DAF, Comptable | Fonctions financières, états de besoin, journaux et comptabilité selon les droits associés au module. |
| Chef de Service, Chef de Département, Directeur Technique | Création et suivi d’états de besoin dans le périmètre autorisé. |
| Chargé des finances | Fonctions financières et de caisse affichées pour ce rôle. |
| Caissier, Caissière, Trésorier, Trésorière | Opérations de caisse et accès aux journaux qui leur sont attribués. |

Cette liste donne une orientation générale : les droits exacts varient selon l’installation. Pour une action nécessaire mais inaccessible, contactez le Super Admin en indiquant le module et le document concerné.

## 3. États de besoin

### Créer une demande

1. Dans le menu, ouvrez **États de Besoin**, puis **Nouveau État de Besoin**.
2. Renseignez la date, la direction, la monnaie, le demandeur et le motif.
3. Ajoutez une ou plusieurs lignes. Pour chaque ligne, saisissez une désignation, une quantité et un prix unitaire.
4. Vérifiez le calcul du montant de chaque ligne et le total estimé.
5. Enregistrez la demande. L’application attribue un numéro et crée l’état avec le statut **En attente**.

La désignation décrit chaque article ou service demandé. Le motif explique la raison ou l’objectif de la demande : ces champs sont distincts.

### Rechercher et consulter

Ouvrez **États de Besoin > Liste des États de Besoin**. Les filtres permettent selon les droits de rechercher par numéro, date de début et de fin, statut ou direction. Cliquez sur **Filtrer** pour lancer la recherche et sur **Réinitialiser** pour effacer les critères.

La liste affiche notamment le numéro, la date, la direction, le demandeur, les désignations des lignes, la monnaie, le montant et le statut. Ouvrez la fiche pour consulter le motif, l’observation, les pièces justificatives, le validateur et les informations liées.

### Traiter une demande en attente

Sur une fiche **En attente**, sélectionnez **Traiter** si cette action est autorisée pour votre rôle. Une observation est demandée avant le traitement. Choisissez ensuite l’action proposée :

- **Valider** : l’état passe au statut **Validé**. L’application crée un bon de sortie lié s’il n’en existe pas déjà.
- **Rejeter** : l’état passe au statut **Rejeté**. Le motif de rejet ou l’explication doit être indiqué dans l’observation.

Après validation, vérifiez le statut, le nom du validateur et l’existence du bon de sortie lié. Si un bon de sortie lié est lui-même validé, certaines opérations sur l’état sont verrouillées jusqu’au traitement de ce bon.

### Modifier un état

- **En attente** : les rôles habilités peuvent modifier les informations de la demande et ses lignes, tant que les contrôles du document le permettent.
- **Validé** : le Super Admin peut ouvrir **Modifier** depuis la fiche pour corriger la direction, le demandeur et le motif. Le montant, la monnaie et les désignations des lignes ne sont pas modifiables dans ce parcours.
- **Rejeté** : le Super Admin peut utiliser **Modifier le statut** lorsqu’il est disponible pour remettre l’état en attente et relancer son traitement. Cette action n’est pas proposée si un bon de sortie lié validé bloque l’opération.

Les modifications du demandeur et du motif d’un état validé sont répercutées sur les bons de sortie qui lui sont liés. Le montant des documents liés n’est pas changé par cette correction.

### Pièces justificatives et copies

Depuis la fiche, utilisez la section **Pièce justificative** pour consulter ou ajouter les documents justificatifs lorsque l’action est disponible. La suppression d’une pièce dépend des droits. Utilisez **Imprimer** ou **PDF** lorsque vous avez besoin d’une copie de l’état.

## 4. Bons de sortie

### Consulter ou créer un bon

Ouvrez **Bon de sortie** pour consulter les bons accessibles ou sélectionnez **Nouveau bon de sortie** si votre rôle est autorisé.

Pour créer un bon, renseignez les champs affichés, notamment la date, le bénéficiaire, le type de bon (caisse, banque ou mobile money), la monnaie, le motif, l’observation et les lignes. Chaque ligne comprend une désignation, une quantité et un prix unitaire. Vérifiez le total et le traitement de la TVA avant d’enregistrer.

Un bon créé depuis un état de besoin est lié à cet état. Un bon créé indépendamment peut demander des informations de bénéficiaire et de paiement propres à l’opération.

### Suivre et traiter un bon

Dans la liste, filtrez par numéro, période ou statut. Ouvrez le bon pour vérifier ses détails, ses lignes, les journaux associés et les pièces. Selon les autorisations, les actions peuvent comprendre la modification, la validation, le rejet, la remise en attente, la réouverture, l’impression ou le PDF.

Avant validation, contrôlez au minimum le bénéficiaire, la date, le type de paiement, la monnaie, le motif, les pièces et le montant total. Les bons validés peuvent être verrouillés. Certaines modifications peuvent aussi être refusées si le bon est associé à un journal ou une clôture.

## 5. Bons d’entrée

Ouvrez **Bon d’entrée** pour afficher les entrées, en créer une ou consulter les statistiques si votre rôle le permet.

Lors de la création, renseignez la date, le type de bon (caisse, banque ou mobile money), les informations du partenaire et les lignes d’entrée. Pour chaque ligne, saisissez une désignation, une quantité et un prix unitaire. Le montant total est calculé à partir des lignes. Vérifiez également le mode de paiement et les coordonnées demandées avant d’enregistrer.

Après création, ouvrez la fiche du bon pour revoir les informations enregistrées et utilisez les fonctions d’impression disponibles. En cas d’erreur, relevez le numéro du bon et le message affiché ; la possibilité de modifier ou supprimer dépend du statut et du rôle.

## 6. Journaux et trésorerie

### Créer un journal

Le menu **Journaux** permet aux utilisateurs habilités d’ouvrir un journal de caisse, de banque ou de mobile money. Sélectionnez le type correspondant à l’opération et complétez le formulaire. Les champs dépendent du type de journal ; vérifiez le compte, la date, la référence, le montant, la monnaie et les pièces justificatives.

Les transferts internes de trésorerie sont accessibles par **Transferts internes** lorsque le menu est disponible. Vérifiez les comptes de départ et d’arrivée ainsi que les montants avant de valider.

### Suivre un journal

Utilisez **Relevé Journal** ou la liste des journaux pour retrouver une opération. Ouvrez sa fiche pour consulter le statut, les écritures et les pièces liées. Les journaux validés peuvent être verrouillés. La réouverture ou le rejet est limité aux rôles prévus par l’application.

Le menu **Trésorerie** peut proposer le journal de caisse et d’autres vues selon la configuration. Certains raccourcis ou types de rapport peuvent ne pas être activés dans toutes les installations.

## 7. Comptabilité et BRC

### Écritures et états comptables

Lorsque le module comptable est activé et autorisé, le menu **Comptabilité** propose :

- **Écritures comptables** pour consulter ou gérer les écritures selon les droits ;
- **Grand Livre général** pour consulter les mouvements par compte ;
- **Balance** pour comparer les soldes débiteurs et créditeurs ;
- **Bilan** et **Compte de résultat** pour consulter les états financiers.

Les périodes et filtres des rapports déterminent les données affichées. Avant l’export, vérifiez l’exercice, la plage de dates et les filtres sélectionnés. Une écriture validée ne se modifie pas comme une écriture en attente : les actions correctives dépendent des droits et de la période comptable.

### Importer des données comptables

Les utilisateurs autorisés peuvent ouvrir **Paramètres > Imports comptables**. Préparez un fichier conforme au format demandé, lancez l’aperçu, corrigez les erreurs signalées et contrôlez les lignes reconnues. Confirmez l’import seulement après cette vérification.

### Bon de régulation comptable

Le **BRC** sert à consulter et enregistrer des régularisations comptables. Ouvrez la liste pour rechercher un BRC, puis sa fiche pour revoir son motif, ses écritures et ses justificatifs. La création, la modification et le téléchargement PDF ou Excel varient selon le rôle.

## 8. Paramètres et administration

Ouvrez **Paramètres** depuis le menu du compte lorsque ce lien est visible. Les rubriques disponibles dépendent du rôle.

### Entreprise et utilisateurs

- **Entreprise** : identité, coordonnées et informations légales utilisées par l’application et les documents.
- **Utilisateurs** : création de comptes, affectation d’un rôle, d’une direction ou d’une fonction, consultation de l’activité de connexion et actions administratives autorisées.

Lorsqu’un nouveau compte est créé, transmettez les identifiants temporaires de manière sécurisée. L’utilisateur doit remplacer le mot de passe temporaire si l’application le demande.

### Configuration comptable

Selon le rôle et les modules actifs, les paramètres peuvent proposer le plan comptable, les types de journaux, les taux de change et les paramètres comptables. Une modification de ces référentiels peut influer sur les opérations futures : vérifiez les codes et les comptes associés avant d’enregistrer.

### Audit, corbeille et sauvegardes

- **Suivi et alertes comptables** : consultez les traces et filtres d’audit lorsqu’ils sont disponibles.
- **Corbeille** : recherchez et restaurez les documents supprimés logiquement, selon les droits.
- **Sauvegardes** : création, téléchargement ou restauration de sauvegardes réservés au Super Admin. Vérifiez l’environnement et le fichier avant toute restauration. Une suppression définitive ou la vidange d’un journal d’audit est irréversible.

## 9. Recherche, impression et résolution des problèmes

### Chercher un document efficacement

1. Ouvrez la liste du module concerné.
2. Commencez par saisir le numéro si vous le connaissez.
3. Sinon, réduisez la liste avec une période puis un statut.
4. Cliquez sur **Filtrer** et vérifiez que la période sélectionnée comprend la date du document.
5. Utilisez **Réinitialiser** pour supprimer les filtres et relancer une recherche complète.

Un document peut être absent si le filtre de statut, les dates ou le périmètre de direction ne correspondent pas. Certains utilisateurs ne voient que les documents de leur direction.

### Imprimer ou exporter

Sur les écrans qui le proposent, sélectionnez **Imprimer**, **PDF**, **Excel** ou l’action de rapport correspondante. Vérifiez le document affiché, la période, la monnaie et les filtres avant de lancer l’impression ou le téléchargement.

### Le bouton attendu n’apparaît pas

1. Vérifiez le rôle indiqué dans le menu du compte.
2. Ouvrez la fiche du document : certaines actions n’apparaissent pas dans la liste.
3. Vérifiez le statut du document. Une validation ou un document lié validé peut verrouiller certaines actions.
4. Vérifiez que vous consultez la bonne direction et la bonne période.
5. Si l’action reste absente, demandez au Super Admin de contrôler les droits du compte.

### L’enregistrement est refusé

Lisez le message affiché sur la page et corrigez les champs concernés. Les causes fréquentes sont un champ obligatoire manquant, un montant invalide, une quantité ou un prix non conforme, une pièce obligatoire absente ou un document verrouillé après validation. Si le problème persiste, transmettez à l’administrateur le numéro du document, l’action tentée et le texte exact du message. Ne transmettez pas votre mot de passe.

### Vérification finale avant validation

- Le numéro, la date et la direction correspondent au document attendu.
- Le demandeur ou le bénéficiaire est correctement orthographié.
- Le motif explique clairement l’opération.
- Les désignations, quantités, prix, monnaie et total ont été vérifiés.
- Les justificatifs utiles sont joints.
- Le statut et les documents liés ont été contrôlés après enregistrement.

## Modules activés

La visibilité des fonctions dépend de la configuration installée. Dans la configuration du projet utilisée pour ce manuel, **Comptabilité** est activée et **Budget** est désactivé. Si un menu n’apparaît pas, le module peut être désactivé ou le compte peut ne pas disposer du droit nécessaire.
