# Manuel d’utilisation de Compta Artico

Ce manuel décrit le fonctionnement des modules de gestion financière et comptable de Compta Artico : demandes de dépenses, bons d’entrée et de sortie, trésorerie, journaux, écritures et rapports. Il suit le circuit d’un document depuis sa création jusqu’à son contrôle et à son archivage. Les menus et actions visibles dépendent du rôle, des droits accordés et de la configuration de l’entreprise.

Le module Ressources humaines est hors du périmètre de ce manuel. Le module Budget est présent dans le code, mais désactivé dans la configuration actuelle (`features.budget = false`) ; ses écrans ne sont donc pas disponibles dans cette installation.

## Sommaire

1. [Accès, navigation et droits](#1-accès-navigation-et-droits)
2. [Comprendre le circuit comptable](#2-comprendre-le-circuit-comptable)
3. [Tableau de bord et notifications](#3-tableau-de-bord-et-notifications)
4. [États de besoin](#4-états-de-besoin)
5. [Bons de sortie](#5-bons-de-sortie)
6. [Bons d’entrée](#6-bons-dentrée)
7. [Journaux de trésorerie](#7-journaux-de-trésorerie)
8. [Relevé journalier des mouvements](#8-relevé-journalier-des-mouvements)
9. [Situation de trésorerie](#9-situation-de-trésorerie)
10. [Transferts internes de trésorerie](#10-transferts-internes-de-trésorerie)
11. [Comptabilité](#11-comptabilité)
12. [Bons de régulation comptable](#12-bons-de-régulation-comptable)
13. [États financiers et rapports](#13-états-financiers-et-rapports)
14. [Paramètres de l’application](#14-paramètres-de-lapplication)
15. [Clôtures journalières et périodes comptables](#15-clôtures-journalières-et-périodes-comptables)
16. [Corbeille, audit et sauvegardes](#16-corbeille-audit-et-sauvegardes)
17. [Boutons courants et résolution des problèmes](#17-boutons-courants-et-résolution-des-problèmes)

## 1. Accès, navigation et droits

### Se connecter

1. Ouvrez l’adresse fournie par l’administrateur. En environnement local, elle peut être `http://localhost:8000`.
2. Saisissez votre identifiant et votre mot de passe.
3. Si le changement de mot de passe est exigé, choisissez-en un nouveau avant de continuer.
4. Pour quitter, ouvrez le menu de votre compte en haut de page et cliquez sur **Déconnexion**.

Sur un poste partagé, déconnectez-vous après usage et ne partagez pas votre mot de passe. La page **Mon profil** permet de consulter ou modifier les informations personnelles proposées à votre compte.

### Menus principaux

Le menu latéral donne accès, selon le rôle, au **Tableau de bord**, aux **États de besoin**, aux **Bons de sortie**, aux **Bons d’entrée**, aux **Journaux**, à la **Comptabilité** et aux **BRC**. Le menu du compte donne accès au profil et, pour les rôles habilités, aux paramètres. La cloche de notification présente les opérations en attente et permet d’ouvrir les documents signalés.

Les rubriques **Journaux** et **Comptabilité** sont liées : les journaux décrivent les opérations de trésorerie et les écritures comptables traduisent leur impact sur les comptes. Selon l’installation, certaines rubriques peuvent être masquées.

### Rôles et autorisations

Les rôles usuels comprennent Super Admin, Admin, Directeur Général, Gérant, DAF, Comptable, Chargé des finances, Caissier, Trésorier, Directeur Technique, Chef de Service et Chef de Département. Les autorisations sont appliquées au niveau des écrans et de chaque action. Un utilisateur peut donc voir une fiche sans pouvoir la modifier, la valider ou la supprimer.

Les menus et boutons décrits ci-dessous apparaissent uniquement lorsque le rôle et le statut de l’objet le permettent. Si une action manque, vérifiez votre rôle, la société ou direction sélectionnée, le statut du document et l’état de la période comptable avant de contacter le Super Admin.

## 2. Comprendre le circuit comptable

Le circuit courant suit ces étapes :

1. Un demandeur habilité crée un **État de besoin** avec sa justification et ses lignes de dépense.
2. Un utilisateur autorisé examine la demande, ajoute une observation et la valide ou la rejette.
3. La validation d’un état de besoin peut générer le **Bon de sortie** associé. Une sortie peut également être créée directement lorsqu’elle ne provient pas d’un état de besoin.
4. Le bon est contrôlé puis validé. Le système prépare le ou les **journaux de trésorerie** liés au paiement, selon le type de bon, le compte et la monnaie.
5. Les journaux sont vérifiés et validés. La validation comptabilise les écritures correspondantes en partie double.
6. Les utilisateurs habilités rapprochent les opérations avec le **Relevé journalier** ou la **Situation de trésorerie**, puis consultent les écritures, le Grand Livre, la Balance et les états financiers.

Les recettes suivent un parcours comparable : création ou saisie d’un **Bon d’entrée**, contrôle/validation, journal de trésorerie associé, puis écritures comptables. Les opérations qui ne viennent pas d’un bon peuvent être saisies directement dans un journal. Les opérations diverses et les régularisations suivent les parcours comptables dédiés.

Un document **En attente** est encore à examiner. **Validé** signifie que l’action de validation a été exécutée ; les modifications deviennent alors limitées. **Rejeté** indique que l’opération n’a pas été approuvée. Certaines fiches proposent une remise en attente ou une réouverture réservée à des rôles précis. Lisez le motif avant de reprendre un traitement.

## 3. Tableau de bord et notifications

Le **Tableau de bord** affiche des indicateurs et des raccourcis correspondant aux données et fonctions visibles par l’utilisateur. Lorsque le sélecteur de mois est présent :

- **Afficher** recharge les indicateurs pour le mois choisi ;
- **Mois en cours** revient à la période courante.

La cloche **Notifications** résume les opérations à examiner, avec leur module, leur statut et leur date. Cliquez sur une notification pour ouvrir l’élément associé. Le nombre affiché signale des éléments en attente ; il ne remplace pas la consultation des listes.

## 4. États de besoin

Un état de besoin formalise une demande avant son exécution.

### Créer une demande

1. Ouvrez **États de Besoin > Nouveau État de Besoin**.
2. Remplissez la date, la direction, la monnaie, le demandeur et le motif.
3. Ajoutez une ou plusieurs lignes en indiquant la désignation, la quantité et le prix unitaire.
4. Vérifiez les sous-totaux et le montant estimé, puis enregistrez.

Le **motif** explique l’objet de la demande ; les **désignations** détaillent les biens ou services. Le système attribue un numéro et place généralement la demande **En attente**.

### Liste et fiche

Dans **Liste des États de Besoin**, utilisez les filtres proposés (numéro, dates, statut ou direction), puis **Filtrer**. **Réinitialiser** efface les critères de recherche. Ouvrez le numéro ou l’action de consultation pour afficher la fiche, les lignes, les pièces jointes, les observations, l’historique et les documents liés.

### Traiter, modifier et imprimer

- **Traiter** ouvre le choix de décision pour une demande admissible. Saisissez l’observation demandée, puis choisissez **Valider** ou **Rejeter**.
- **Valider** approuve la demande et crée ou rattache le bon de sortie prévu par le circuit.
- **Rejeter** refuse la demande ; indiquez clairement le motif dans l’observation.
- **Modifier** permet de corriger les champs autorisés avant verrouillage. Les demandes validées ou liées à un bon déjà validé sont soumises à des restrictions.
- **Ajouter une pièce justificative**, **Consulter**, **Télécharger** et, si autorisé, **Supprimer** gèrent les justificatifs.
- **Imprimer** ouvre une version imprimable ; **PDF** télécharge une copie.
- **Réouvrir** est réservé au rôle prévu (notamment Super Admin) et peut être bloqué par un document lié déjà validé.

Après validation, vérifiez le statut, le validateur et la présence du bon de sortie lié.

## 5. Bons de sortie

Un bon de sortie (BSC, BSB ou BSM selon le type de paiement affiché) documente une dépense payée en caisse, par banque ou par Mobile Money.

### Créer ou consulter

Ouvrez **Bon de sortie > Nouveau bon de sortie**. Selon le parcours, le formulaire demande la date, le type de bon, le bénéficiaire, le motif, la monnaie, les lignes de dépense, les informations de paiement et une observation. Une ligne comprend une désignation, une quantité et un prix unitaire. Le formulaire peut aussi demander si la TVA s’applique ; vérifiez la base, le taux et les montants calculés avant d’enregistrer.

Un bon peut être créé depuis un état de besoin validé ou indépendamment. La fiche affiche son origine, ses lignes, pièces, statut et journaux associés.

### Boutons de la fiche

- **Modifier** corrige les champs encore ouverts à la modification.
- **Valider** confirme le bon après vérification du bénéficiaire, des montants, de la monnaie, du type de paiement et des justificatifs. Le système met à jour le circuit de dépense et les journaux associés.
- **Rejeter** refuse le bon ; un motif est requis.
- **Remettre en attente** replace un bon rejeté dans le circuit lorsque l’action est autorisée.
- **Réouvrir** permet à certains rôles de reprendre un bon validé sous conditions. Un journal ou une clôture liée peut empêcher la réouverture.
- **Imprimer** et **PDF** produisent une copie du bon.
- Les actions des pièces jointes permettent de les **consulter**, **télécharger**, **ajouter** ou **supprimer** selon les droits.
- **Supprimer** déplace normalement le document dans la corbeille si la suppression logique est autorisée ; les dépendances comptables peuvent bloquer l’action.

Dans la liste, filtrez par numéro, dates ou statut. Après validation, ouvrez le journal lié pour suivre le paiement jusqu’à la comptabilisation.

## 6. Bons d’entrée

Un bon d’entrée (BEC) constate une recette ou une entrée de fonds.

### Créer et valider

1. Ouvrez **Bon d’entrée > Nouvelle Entrée**.
2. Renseignez la date, le type de bon ou de trésorerie, le partenaire, la monnaie, le mode de paiement et les champs complémentaires demandés.
3. Ajoutez les lignes : désignation, quantité et prix unitaire.
4. Contrôlez le total et les coordonnées, puis enregistrez.
5. Ouvrez la fiche et utilisez **Valider** après contrôle, si votre rôle le permet.

La validation prépare ou rattache le mouvement de trésorerie selon le paramétrage. **Rejeter** refuse l’entrée avec un motif. **Réouvrir** est une action restreinte et conditionnée par les dépendances. **Modifier** et **Supprimer** ne sont proposés que si le statut et les droits l’autorisent.

La liste permet de rechercher les entrées ; **Statistiques** ouvre les agrégats disponibles. Depuis la fiche, **Imprimer** ou **PDF** produit une copie. Consultez le journal associé pour vérifier l’impact de trésorerie.

## 7. Journaux de trésorerie

Les journaux enregistrent les recettes, dépenses, achats, ventes ou opérations diverses affectant un compte de caisse, banque ou Mobile Money. Ils servent de support au relevé et aux écritures.

### Créer un journal

Dans **Journaux**, choisissez **Journal Caisse**, **Journal Banque** ou **Journal Mobile Money**. Sur **Nouveau journal**, remplissez :

- le journal / compte de trésorerie ;
- la date, le type d’opération et la monnaie (CDF ou USD) ;
- le libellé et les informations du tiers demandées ;
- les montants d’entrée ou de sortie, et les détails de TVA s’ils sont applicables ;
- la référence et la pièce justificative lorsqu’elles sont demandées.

Le système peut afficher le taux de change enregistré. Si le journal est prérempli depuis un bon, contrôlez que la référence et le montant correspondent au document source. **Enregistrer** crée le journal dans le circuit comptable ; cela ne signifie pas nécessairement qu’il est validé.

### Liste, fiche et actions

La liste des journaux permet de parcourir ou filtrer les opérations. Les vues **Banque**, **Mobile** et **Trésorerie** sont des accès par nature ou état. Ouvrez une référence pour voir les informations, le document lié, les écritures, le statut et les pièces.

- **Modifier** corrige un journal qui n’est pas verrouillé.
- **Valider** contrôle puis comptabilise le journal. La validation applique les règles configurées, crée les écritures en partie double et peut finaliser les documents liés.
- **Rejeter** retire l’opération du flux normal ; indiquez le motif demandé.
- **Réouvrir** remet un journal validé en traitement uniquement si le rôle, la période et les dépendances le permettent.
- **Supprimer** est soumis aux droits, aux liens et au statut.
- **Pièce justificative** permet de consulter ou télécharger la pièce liée.
- **Reçu** ouvre la preuve de l’opération ; **PDF** télécharge le reçu lorsque cette action est disponible.
- **Journal des opérations diverses** donne accès aux opérations diverses et aux journaux en attente d’imputation.

Avant validation, rapprochez la date, le compte, la monnaie, les entrées/sorties, le libellé, les pièces et la référence au bon source. La validation d’un journal est une étape distincte de la simple création du bon.

## 8. Relevé journalier des mouvements

Le menu **Journaux > Relevé Journal** ouvre le relevé détaillé des mouvements validés. Il sert à contrôler l’activité sur une journée ou sur une plage de dates.

### Filtres et boutons

- **Du** et **Au** définissent les bornes de la période. Vérifiez qu’elles incluent bien la journée à contrôler.
- **Journal / compte** restreint le relevé à un compte de trésorerie ; **Tous les journaux** affiche tous les comptes accessibles.
- **Appliquer** recharge les mouvements et les soldes avec les filtres choisis.
- Le bouton avec la croix réinitialise les filtres.
- **Imprimer** lance l’impression du relevé ; les filtres et la période sélectionnés sont repris dans le document.
- **PDF** ou **Excel**, lorsque le groupe d’export est visible, télécharge le rapport dans le format choisi.
- **Situation** ouvre l’écran de synthèse de trésorerie pour la même période.
- En cliquant sur une référence, les utilisateurs ayant le droit de gérer les journaux ouvrent la fiche du journal.
- Les liens de pagination affichent les autres lignes du relevé.

### Lire le relevé

Si un compte est sélectionné, le panneau de synthèse présente le solde d’ouverture, les entrées, les sorties et le solde de clôture, séparément en CDF et en USD. Le calcul est :

**Solde de clôture = solde d’ouverture + entrées de la période − sorties de la période.**

Le tableau détaillé présente, pour chaque mouvement, la date, la pièce/référence, le code journal, le compte, le libellé, les entrées, les sorties et le solde progressif dans les deux monnaies. La première ligne donne le solde d’ouverture ; la dernière ligne de total récapitule la période.

Le relevé compte les mouvements **validés** uniquement. Un journal en attente ou rejeté ne constitue donc pas une ligne validée du relevé. Par défaut, la période va du premier jour du mois à aujourd’hui. Pour rapprocher le solde progressif d’un compte précis, sélectionnez ce compte dans le filtre **Journal / compte** ; **Tous les journaux** sert à parcourir une vue globale. Si aucun mouvement n’apparaît, contrôlez les dates, le compte choisi et le statut du journal. Le relevé n’est pas la clôture comptable de fin de journée : c’est un état de consultation et de rapprochement.

## 9. Situation de trésorerie

Ouvrez **Journaux > Situation trésorerie** si le droit de consultation est accordé. Cette page résume les soldes de trésorerie sur la période.

- **Du** et **Au** définissent la période ; **Actualiser** recharge la situation.
- La croix remet les filtres à zéro.
- Les cartes affichent le disponible à la date de fin pour la caisse, la banque et Mobile Money, avec des totaux distincts en CDF et USD.
- Le tableau **Position par compte de trésorerie** donne le code journal, le compte, la désignation, la nature, l’ouverture, les entrées, les sorties et le solde de chaque devise.
- Le clic sur un code de journal ouvre le relevé détaillé du compte pour la même période.
- **Relevé détaillé** revient au relevé des mouvements ; **Imprimer**, **PDF** et **Excel** sont disponibles selon le rôle et l’écran.

Les montants agrègent des mouvements validés. Un solde négatif est signalé visuellement et doit être rapproché avec les pièces et opérations concernées. Les vues historiques du menu Trésorerie peuvent dépendre de l’installation ; utilisez les pages **Situation trésorerie** et **Relevé Journal** pour les états consolidés actifs.

## 10. Transferts internes de trésorerie

Le menu **Journaux > Transferts internes de trésorerie** permet d’enregistrer un déplacement de fonds entre comptes de trésorerie. Sélectionnez le compte source, le compte destinataire, la date, les monnaies et montants demandés, puis indiquez la référence et le motif. Vérifiez que les deux côtés du transfert sont cohérents avant d’enregistrer.

La liste permet de retrouver les transferts. **Consulter le bon** ouvre le document justificatif généré ; **Supprimer** annule le transfert lorsque l’action est autorisée et que les journaux concernés ne bloquent pas la suppression. Un transfert déplace les fonds entre comptes : il ne doit pas être enregistré une seconde fois comme une recette ou une dépense externe.

## 11. Comptabilité

### Écritures comptables

Dans **Comptabilité > Écritures Comptables**, filtrez la liste selon les critères disponibles, puis ouvrez une écriture pour voir le journal source, la pièce, le compte, les débits/crédits et le statut.

- **Valider** comptabilise une écriture admissible après contrôle des imputations.
- **Modifier** ouvre les champs encore modifiables.
- **Réouvrir** permet une reprise si le statut, le rôle et la période le permettent.
- **Ajouter une pièce**, **Consulter**, **Télécharger** et **Supprimer** gèrent les justificatifs selon les droits.
- **Supprimer** l’écriture est soumis au rôle et aux dépendances ; un document lié peut interdire la suppression.
- Le lien vers le journal ou le bon source aide à vérifier l’origine du mouvement.

Une écriture comptable doit être équilibrée en partie double. Vérifiez que le total des débits correspond au total des crédits et que les comptes utilisés conviennent à l’opération.

### Journal des opérations diverses et imputation

Dans **Journaux > Journal des opérations diverses**, les utilisateurs habilités peuvent créer une opération diverse ou consulter les journaux à traiter. **Nouvelle opération diverse** ouvre la saisie manuelle : indiquez la date, la référence, le libellé, la monnaie, les comptes, les débits et crédits ainsi que le justificatif demandé. Enregistrez seulement après avoir vérifié l’équilibre.

La vue d’imputation réunit les journaux comptabilisés qui attendent l’affectation finale à des comptes. Ouvrez une ligne, choisissez les comptes et montants proposés, puis utilisez **Traiter** pour enregistrer l’imputation. Contrôlez la pièce, les montants et l’équilibre avant de traiter. Une ligne déjà traitée ou liée à une période fermée peut ne plus être modifiable.

### Constatations comptables

Lorsqu’une écriture admissible comporte un écart entre le paiement et son imputation (par exemple un salaire ou une recette dont les lignes doivent être détaillées), l’action **Constatation** ouvre un formulaire de ventilation. Sélectionnez les comptes et saisissez les lignes de débit/crédit et les explications demandées. Le total doit être équilibré et correspondre au montant de règlement attendu ; la pièce et le journal source doivent être corrects.

**Enregistrer la constatation** crée les écritures de régularisation liées à la source et au règlement, sans créer un nouveau mouvement de trésorerie. Les périodes doivent être ouvertes et l’opération ne doit pas déjà être imputée/constatée. Une constatation validée verrouille les écritures associées ; contactez le responsable comptable en cas d’erreur.

### Grand Livre et Balance

- **Grand Livre général** présente les mouvements des comptes sur la période. Sélectionnez le compte et les bornes de dates, appliquez les filtres puis contrôlez les soldes et lignes. Les actions d’export, PDF ou Excel reprennent la sélection courante.
- **Balance** récapitule les soldes et mouvements des comptes. Choisissez l’exercice ou la période, lancez l’affichage puis vérifiez l’égalité globale des débits et des crédits. Les exports disponibles produisent une copie du résultat.

Ces états utilisent les écritures enregistrées et validées selon les règles du module. Ils ne remplacent pas le rapprochement avec les bons et les journaux.

### Diagnostic comptable

Le Super Admin peut ouvrir `/diagnostic-comptable` pour obtenir une réponse technique JSON sur la disponibilité de quelques écrans comptables, des tables et colonnes liées aux constatations. Ce diagnostic aide l’administrateur à repérer un problème de schéma ou de rendu ; ce n’est ni un rapport financier ni une correction automatique. En cas d’erreur, transmettre le résultat au responsable technique.

## 12. Bons de régulation comptable

Le **BRC** documente une régularisation. Dans **Bon de régulation comptable > BRC**, utilisez les filtres pour rechercher une référence et ouvrez sa fiche pour contrôler le motif, les montants, les lignes, le statut et les justificatifs.

- **Nouveau BRC** ouvre la saisie d’une régularisation ; renseignez la date, le motif, la monnaie, les comptes et les montants requis.
- **Modifier** est réservé aux utilisateurs autorisés et aux dossiers encore modifiables.
- **Valider** confirme la régularisation et génère/valide les journaux comptables liés selon le circuit.
- **PDF** et **Excel** téléchargent le document dans le format choisi.
- **Pièce justificative** permet de consulter ou télécharger le justificatif.
- **Supprimer** dépend des droits et de l’absence de dépendances bloquantes.

Avant validation, confirmez le motif, le sens de la régularisation, les comptes et l’équilibre des écritures. Une régularisation ne doit pas servir à masquer une erreur de saisie dans le document source.

## 13. États financiers et rapports

### Bilan

Dans **Comptabilité > Bilan**, sélectionnez l’exercice ou les dates proposés puis affichez l’état. Le bilan regroupe les soldes des comptes par rubrique. **PDF** télécharge l’état et **Imprimer** lance sa mise en page imprimable.

Le bouton de bilan initial permet d’archiver une situation d’ouverture. Renseignez les comptes et soldes, contrôlez l’équilibre, puis enregistrez. Les bilans initiaux archivés peuvent être consultés, exportés en PDF et, selon les droits, supprimés.

### Compte de résultat

Dans **Comptabilité > Compte de résultat**, choisissez la période et affichez le résultat calculé à partir des comptes de produits et charges. Utilisez **PDF** ou **Imprimer** pour produire une copie. Vérifiez les dates et le statut des écritures avant interprétation.

### Exports périodiques

Les boutons **PDF** et **Excel** apparaissent sur plusieurs rapports (écritures, journaux, opérations diverses, entrées, sorties, états de besoin, Grand Livre, trésorerie, relevé, Balance, bilan et compte de résultat). Ils utilisent les filtres affichés à l’écran. Avant le téléchargement, confirmez la période, le compte, le statut et les autres critères sélectionnés.

## 14. Paramètres de l’application

Ouvrez **Paramètres** depuis le menu de compte. Les rubriques visibles dépendent du rôle.

### Entreprise et utilisateurs

- **Entreprise** : met à jour l’identité, les coordonnées et informations légales qui apparaissent sur les documents.
- **Utilisateurs** : le bouton **Créer** déplie le formulaire du nouvel utilisateur. Renseignez les champs obligatoires, choisissez un rôle et une direction, puis cliquez **Enregistrer**. Le tableau permet de modifier un rôle avec le sélecteur et la coche, et de **Réinitialiser** le mot de passe d’un utilisateur. Les dernières connexions sont affichées pour le suivi.
- **Mon profil** : consulte et met à jour les informations personnelles proposées.

Lors de la création d’un compte, communiquez les identifiants temporaires par un canal sécurisé. N’accordez que les droits nécessaires.

### Référentiels comptables

- **Plan comptable** : crée, modifie ou consulte les comptes généraux et leurs désignations. La suppression est restreinte et peut être refusée si le compte est utilisé.
- **Types de journaux** : configure le code, la nature (caisse, banque, Mobile Money), la monnaie et le compte de trésorerie associés.
- **Taux de change** : enregistre les taux utilisés pour les opérations multidevises, avec leur date et leurs monnaies. Sélectionnez la date et vérifiez le sens du taux avant sauvegarde.
- **Paramétrage comptable** : associe les règles et comptes utilisés automatiquement par les opérations.
- **Imports comptables** : choisissez un fichier CSV conforme, cliquez sur **Aperçu**, corrigez les erreurs et vérifiez les lignes. Cliquez sur **Confirmer l’import** seulement après validation de l’aperçu.

Ces paramètres influencent les opérations futures. Une mauvaise association de compte ou un taux erroné peut fausser les journaux et les rapports.

### Paramètres réservés aux rôles habilités

Les utilisateurs autorisés peuvent aussi administrer les clôtures journalières, les périodes comptables, l’audit, la corbeille et les sauvegardes (voir chapitres suivants). Le module **Budget** est désactivé dans la configuration actuelle ; ses menus ne sont pas actifs.

## 15. Clôtures journalières et périodes comptables

### Clôturer une journée

La clôture journalière est une fonction comptable réservée au Super Admin dans l’installation actuelle. Elle diffère du relevé : le relevé consulte les mouvements ; la clôture crée un regroupement et verrouille les journaux concernés.

1. Ouvrez **Paramètres > Clôtures journalières**.
2. Saisissez la **Journée comptable**, puis cliquez **Simuler la clôture**.
3. Examinez le nombre de journaux admissibles, les recettes/dépenses par devise, les journaux rejetés et la liste détaillée des opérations.
4. Si les résultats sont corrects, cliquez **Lancer la clôture**. Une clôture complémentaire exige de cocher **Clôture complémentaire** et de saisir son motif.
5. Ouvrez la clôture générée et consultez les bons et journaux rattachés.
6. Cliquez **Vérifier la clôture** pour contrôler les totaux et rattachements.

Une clôture est enregistrée dans l’historique avec un numéro, une révision, son statut, l’utilisateur, les totaux, le nombre de pièces et les éventuels rejets. Les journaux regroupés sont verrouillés. **Rouvrir** exige un motif détaillé et peut être refusé si la chaîne contient un document ou une écriture validée. Les rattachements restent conservés pour préserver la traçabilité.

### Gérer les périodes comptables

Dans **Paramètres > Périodes comptables**, le Super Admin peut consulter les périodes, lancer la clôture selon le calendrier de l’entreprise, imprimer ou télécharger le document de période, et **Réouvrir** une période lorsque les conditions l’autorisent. Une période fermée peut bloquer la création, la modification, la validation ou la réouverture de pièces datées dans cette période. Vérifiez la période avant de corriger une opération passée.

## 16. Corbeille, audit et sauvegardes

### Corbeille

Les rôles disposant du droit d’accès ouvrent **Corbeille** pour rechercher les documents supprimés logiquement et consulter leur fiche.

- **Restaurer** remet un document dans son module d’origine si ses dépendances le permettent.
- **Supprimer définitivement** efface définitivement l’élément et ne peut pas être annulé.
- **Vider la corbeille** supprime définitivement son contenu accessible ; vérifiez soigneusement avant confirmation.

### Suivi et alertes comptables

Le Super Admin peut consulter le journal d’audit pour filtrer les actions sensibles, les suppressions, restaurations et changements. Le filtre par référence facilite le suivi d’un document ou d’une clôture. **Vider** le journal d’audit est une opération irréversible et réservée au rôle autorisé.

### Sauvegardes

Le menu **Sauvegardes** est réservé au Super Admin. **Créer une sauvegarde** produit une copie de la base ; **Télécharger** récupère le fichier. Les fonctions **Exporter le dossier**, **Importer**, **Importer un dossier** et **Restaurer la base** servent aux opérations de migration ou de récupération.

Avant toute restauration, confirmez l’environnement et le fichier, assurez-vous de disposer d’une sauvegarde récente et suivez la procédure interne de l’organisation. La restauration remplace des données et peut affecter l’ensemble des utilisateurs.

## 17. Boutons courants et résolution des problèmes

### Boutons récurrents

| Bouton | Effet habituel |
| --- | --- |
| **Nouveau / Créer** | Ouvre un formulaire vierge pour enregistrer un document. |
| **Enregistrer** | Sauvegarde les champs saisis ; cela ne vaut pas toujours validation comptable. |
| **Filtrer / Appliquer / Actualiser** | Recharge la liste ou le rapport selon les critères choisis. |
| **Réinitialiser** (croix) | Efface les filtres et revient à la vue par défaut. |
| **Consulter / Voir** | Ouvre la fiche d’un document ou d’un rapport. |
| **Modifier** | Ouvre les champs modifiables selon le statut et les droits. |
| **Valider** | Confirme l’opération et déclenche l’étape suivante du circuit. Lire le récapitulatif avant confirmation. |
| **Rejeter** | Refuse l’opération ; un motif peut être obligatoire. |
| **Remettre en attente / Réouvrir** | Reprend un traitement sous conditions et avec les droits nécessaires. |
| **Imprimer** | Ouvre la version imprimable dans le navigateur. |
| **PDF / Excel** | Télécharge une copie ou un rapport. Les filtres actifs peuvent être repris. |
| **Consulter / Télécharger** (pièce) | Affiche ou récupère le justificatif associé. |
| **Supprimer** | Supprime logiquement lorsque c’est permis ; la suppression définitive de la corbeille est irréversible. |
| **Annuler / Retour** | Ferme une fenêtre ou revient à la page précédente sans lancer l’action de confirmation. |

### Un bouton ou un menu n’apparaît pas

1. Vérifiez le rôle affiché sous votre nom et les menus accessibles.
2. Ouvrez la fiche : certaines actions sont présentes uniquement dans le détail.
3. Vérifiez le statut. Une pièce validée, rejetée, regroupée ou liée à une autre pièce peut restreindre les actions.
4. Vérifiez la période comptable et, si applicable, la direction ou le compte sélectionné.
5. Si l’action devrait être disponible, demandez au Super Admin de vérifier les droits et le paramétrage.

### Un document ou un mouvement est introuvable

Effacez les filtres, recherchez d’abord par numéro/référence, puis élargissez les dates. Vérifiez que vous consultez le bon module et le bon compte. Le relevé journalier et la situation de trésorerie n’affichent que les mouvements validés ; un document encore en attente doit être recherché dans sa liste d’origine.

### Une validation est refusée

Lisez le message affiché et contrôlez les champs obligatoires, le montant, la monnaie, le compte, les justificatifs, l’équilibre débit/crédit et le statut des documents liés. Pour une opération ancienne, vérifiez que la période est ouverte. Ne répétez pas la validation sans comprendre l’erreur.

### Préparation avant validation

- Le numéro, la date, le compte et la monnaie correspondent à l’opération.
- Le demandeur, le bénéficiaire ou le partenaire est correctement renseigné.
- Le motif explique l’opération et les lignes ont des désignations utiles.
- Les quantités, prix, TVA, entrées, sorties et totaux ont été recalculés.
- Les justificatifs requis sont lisibles et rattachés au bon document.
- Le document source, le journal associé et le statut final ont été vérifiés.

Si le problème persiste, transmettez à l’administrateur le numéro du document, le nom du module, l’action tentée et le texte exact de l’erreur. Ne transmettez jamais votre mot de passe.

## Modules actifs dans cette installation

La configuration du projet active **Comptabilité** et désactive **Budget**. Les fonctions du module Ressources humaines ne sont pas décrites dans ce manuel. La visibilité d’un écran reste conditionnée par le rôle de l’utilisateur et les paramètres de l’entreprise.
