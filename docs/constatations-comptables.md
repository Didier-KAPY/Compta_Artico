# Constatations comptables

## Architecture

Les écritures existantes sont des lignes dans `ecritures_comptables`, reliées au bon par `journal_id`. Les rapports comptables utilisent les montants CDF au statut `Validé`. Les mouvements de trésorerie sont issus des journaux et des bons, et ne doivent pas être recréés pour constater une opération.

La table `constatations_comptables` constitue l'en-tête de la constatation. Elle référence l'écriture source, le journal de règlement, le compte de liaison et l'entreprise. Ses lignes comptables portent une référence `CONST-{entreprise}-{source}`, un `constatation_id` et le rôle `constatation`. Elles n'ont pas de journal de trésorerie ; `journal_id` devient donc nullable. Les deux lignes du règlement conservent le journal et la pièce du bon et portent le rôle `reglement`.

Les états comptables existants intègrent ainsi les lignes validées, sans créer un deuxième mouvement de trésorerie. Les pièces justificatives des lignes de constatation sont accessibles à travers l'écriture source.

## Fichiers

Créés :

- `database/migrations/2026_09_18_120000_create_constatations_comptables.php`
- `app/Models/ConstatationComptable.php`
- `app/Services/ConstatationComptableService.php`
- `app/Http/Controllers/ConstatationComptableController.php`
- `resources/views/Comptabilite/ecritures/constatation.blade.php`
- `resources/views/Comptabilite/ecritures/_constatation_action.blade.php`
- `tests/Feature/ConstatationComptableTest.php`

Modifiés : `EcritureComptable`, `Journaux`, `ListeDesComptes`, `PiecesJustificativesService`, `EcritureComptableController`, `routes/web.php`, les vues de liste et de détail des écritures.

## Règles

- Accès en lecture : rôles existants du détail des écritures. Enregistrement : Super Admin ou Comptable.
- Une source admissible est une ligne de trésorerie en attente, issue d'un bon, dans un journal validé contenant encore uniquement cette ligne. Une opération déjà imputée ou constatée n'est pas admissible.
- Les périodes de la source et de la constatation doivent être ouvertes. Le service existant des périodes est conservé ; ses règles de clôture restent globales.
- Le lien explicite à l'entreprise est ajouté aux écritures, journaux et comptes. Les données historiques d'une installation à une seule entreprise sont affectées à celle-ci. Avec plusieurs entreprises, seules les affectations déterminables par un propriétaire unique sont reprises. Une affectation ambiguë est refusée par le nouveau circuit.
- Chaque ligne contient un débit ou un crédit positif, jamais les deux. Les calculs d'équilibre utilisent des centimes entiers.
- Le solde du compte de liaison doit correspondre au paiement. Les écarts, même expliqués, sont bloqués : aucun règlement partiel ni mécanisme d'exception n'est introduit.
- Pour un salaire, les charges sont au débit de comptes de classe 6, les retenues au crédit de comptes distincts et la dette nette au crédit du compte de liaison. Brut moins retenues doit correspondre au paiement.
- Le formulaire n'impose aucun nombre maximal de lignes ou de retenues. Les limites HTTP/PHP restent celles du serveur.
- La transaction verrouille le journal puis la source. Les contraintes uniques sur la source et le journal protègent contre les doublons. Le crédit ou débit de trésorerie initial est conservé sans modification.
- L'audit, la constatation, ses lignes et le complément du règlement sont atomiques. Un échec entraîne leur rollback.
- Les écritures et le règlement liés à une constatation validée sont verrouillés pour empêcher une modification, suppression ou réouverture isolée. Aucun circuit d'annulation ou de contrepassation n'est ajouté.

Le taux affiché est le taux effectif déduit du montant CDF déjà enregistré et du montant USD du bon. Le taux actuel n'est jamais utilisé pour recalculer le paiement historique.

## Vérification

Les tests couvrent les salaires avec et sans retenues, les produits associés à un encaissement, les doublons, l'équilibre, le net, les périodes fermées, les entreprises et comptes étrangers, les droits, le verrouillage et un échec d'audit après création des lignes pour vérifier le rollback intégral. Les tests de trésorerie et d'états financiers sont également exécutés.
