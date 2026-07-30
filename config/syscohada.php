<?php

return [
    // Les préfixes les plus longs restent prioritaires et sont modifiables ici.
    'bilan' => [
        'actif' => [
            'actif_immobilise' => ['label' => 'Actif immobilisé', 'rubriques' => [
                'immobilisations_incorporelles' => ['code' => 'AD', 'label' => 'Immobilisations incorporelles', 'prefixes' => ['21']],
                'immobilisations_corporelles' => ['code' => 'AE', 'label' => 'Immobilisations corporelles', 'prefixes' => ['22', '23', '24']],
                'immobilisations_financieres' => ['code' => 'AF', 'label' => 'Immobilisations financières', 'prefixes' => ['25', '26', '27']],
                'amortissements_depreciations' => ['code' => 'AG', 'label' => 'Amortissements et dépréciations', 'prefixes' => ['28', '29'], 'orientation' => 'credit'],
            ]],
            'actif_circulant' => ['label' => 'Actif circulant', 'rubriques' => [
                'stocks' => ['code' => 'BA', 'label' => 'Stocks et en-cours', 'prefixes' => ['3']],
                'clients' => ['code' => 'BB', 'label' => 'Créances clients', 'prefixes' => ['41']],
                'autres_creances' => ['code' => 'BG', 'label' => 'Autres créances', 'prefixes' => ['42', '45', '46', '470', '471', '472', '473', '474', '475', '476', '477', '48']],
                'ecarts_conversion_actif' => ['code' => 'BH', 'label' => 'Écarts de conversion actif', 'prefixes' => ['478']],
            ]],
            'tresorerie_actif' => ['label' => 'Trésorerie actif', 'rubriques' => [
                'titres_placement' => ['code' => 'BQ', 'label' => 'Titres de placement', 'prefixes' => ['50']],
                'valeurs_encaisser' => ['code' => 'BR', 'label' => 'Valeurs à encaisser', 'prefixes' => ['51']],
                'banques_caisses' => ['code' => 'BS', 'label' => 'Banques, établissements financiers, monnaie électronique, caisses et régies', 'prefixes' => ['52', '53', '54', '55', '57', '58']],
            ]],
        ],
        'passif' => [
            'capitaux_propres' => ['label' => 'Capitaux propres et ressources assimilées', 'rubriques' => [
                'capital' => ['code' => 'CA', 'label' => 'Capital', 'prefixes' => ['10']],
                'reserves' => ['code' => 'CB', 'label' => 'Réserves', 'prefixes' => ['11']],
                'report_nouveau' => ['code' => 'CD', 'label' => 'Report à nouveau', 'prefixes' => ['12']],
                'resultat_net' => ['code' => 'CF', 'label' => 'Résultat net de l’exercice', 'prefixes' => ['13'], 'calculated' => true],
                'subventions_provisions' => ['code' => 'CL', 'label' => 'Subventions et provisions réglementées', 'prefixes' => ['14', '15']],
            ]],
            'dettes_financieres' => ['label' => 'Dettes financières et ressources assimilées', 'rubriques' => [
                'emprunts_dettes' => ['code' => 'DA', 'label' => 'Emprunts et dettes financières', 'prefixes' => ['16', '17', '18']],
            ]],
            'passif_circulant' => ['label' => 'Passif circulant', 'rubriques' => [
                'fournisseurs' => ['code' => 'DH', 'label' => 'Fournisseurs et comptes rattachés', 'prefixes' => ['40']],
                'dettes_sociales' => ['code' => 'DI', 'label' => 'Dettes sociales', 'prefixes' => ['42', '43']],
                'dettes_fiscales' => ['code' => 'DJ', 'label' => 'Dettes fiscales', 'prefixes' => ['44']],
                'autres_dettes' => ['code' => 'DK', 'label' => 'Autres dettes', 'prefixes' => ['45', '46', '470', '471', '472', '473', '474', '475', '476', '477', '48']],
                'ecarts_conversion_passif' => ['code' => 'DM', 'label' => 'Écarts de conversion passif', 'prefixes' => ['479']],
            ]],
            'tresorerie_passif' => ['label' => 'Trésorerie passif', 'rubriques' => [
                'credits_tresorerie' => ['code' => 'DQ', 'label' => 'Banques, crédits de trésorerie et d’escompte', 'prefixes' => ['56']],
            ]],
        ],
    ],
    'resultat' => [
        'produits_exploitation' => ['label' => 'Produits d’exploitation', 'orientation' => 'credit', 'rubriques' => [
            'ventes_marchandises' => ['code' => 'TA', 'label' => 'Ventes de marchandises', 'prefixes' => ['701']],
            'ventes_produits' => ['code' => 'TB', 'label' => 'Ventes de produits fabriqués', 'prefixes' => ['702', '703']],
            'travaux_services' => ['code' => 'TC', 'label' => 'Travaux et services vendus', 'prefixes' => ['704', '705', '706']],
            'produits_accessoires' => ['code' => 'TD', 'label' => 'Produits accessoires', 'prefixes' => ['707']],
            'production_stockee' => ['code' => 'TE', 'label' => 'Production stockée', 'prefixes' => ['71']],
            'production_immobilisee' => ['code' => 'TF', 'label' => 'Production immobilisée', 'prefixes' => ['72']],
            'subventions_exploitation' => ['code' => 'TH', 'label' => 'Subventions d’exploitation', 'prefixes' => ['73']],
            'autres_produits' => ['code' => 'TK', 'label' => 'Autres produits d’exploitation', 'prefixes' => ['75']],
            'reprises_transferts' => ['code' => 'TL', 'label' => 'Reprises et transferts de charges', 'prefixes' => ['78', '79']],
        ]],
        'charges_exploitation' => ['label' => 'Charges d’exploitation', 'orientation' => 'debit', 'rubriques' => [
            'achats_marchandises' => ['code' => 'RA', 'label' => 'Achats de marchandises', 'prefixes' => ['601']],
            'variation_stocks' => ['code' => 'RB', 'label' => 'Variation de stocks', 'prefixes' => ['603']],
            'achats_matieres' => ['code' => 'RC', 'label' => 'Achats de matières et fournitures', 'prefixes' => ['602', '604', '605', '608']],
            'transports' => ['code' => 'RD', 'label' => 'Transports', 'prefixes' => ['61']],
            'services_exterieurs' => ['code' => 'RE', 'label' => 'Services extérieurs', 'prefixes' => ['62', '63']],
            'impots_taxes' => ['code' => 'RF', 'label' => 'Impôts et taxes', 'prefixes' => ['64']],
            'autres_charges' => ['code' => 'RG', 'label' => 'Autres charges', 'prefixes' => ['65']],
            'charges_personnel' => ['code' => 'RH', 'label' => 'Charges de personnel', 'prefixes' => ['66']],
            'dotations' => ['code' => 'RI', 'label' => 'Dotations aux amortissements et provisions', 'prefixes' => ['68']],
        ]],
        'produits_financiers' => ['label' => 'Produits financiers', 'orientation' => 'credit', 'rubriques' => [
            'produits_financiers' => ['code' => 'UA', 'label' => 'Revenus financiers et assimilés', 'prefixes' => ['77']],
        ]],
        'charges_financieres' => ['label' => 'Charges financières', 'orientation' => 'debit', 'rubriques' => [
            'charges_financieres' => ['code' => 'SA', 'label' => 'Frais financiers et charges assimilées', 'prefixes' => ['67']],
        ]],
        'produits_hao' => ['label' => 'Produits hors activités ordinaires', 'orientation' => 'credit', 'rubriques' => [
            'produits_hao' => ['code' => 'HAO-P', 'label' => 'Produits hors activités ordinaires', 'prefixes' => ['82', '84']],
        ]],
        'charges_hao' => ['label' => 'Charges hors activités ordinaires', 'orientation' => 'debit', 'rubriques' => [
            'charges_hao' => ['code' => 'HAO-C', 'label' => 'Charges hors activités ordinaires', 'prefixes' => ['81', '83', '85']],
        ]],
        'impot_resultat' => ['label' => 'Impôt sur le résultat', 'orientation' => 'debit', 'rubriques' => [
            'participation_travailleurs' => ['code' => 'XA', 'label' => 'Participation des travailleurs', 'prefixes' => ['86']],
            'impot_resultat' => ['code' => 'XB', 'label' => 'Impôt sur le résultat', 'prefixes' => ['87', '89']],
        ]],
    ],
    'equilibrium_tolerance' => 0.01,
];
