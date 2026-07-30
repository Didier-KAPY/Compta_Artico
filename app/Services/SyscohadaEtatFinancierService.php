<?php

namespace App\Services;

use App\Models\EcritureComptable;
use Carbon\CarbonImmutable;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;

class SyscohadaEtatFinancierService
{
    private bool $afficherComptesSansMouvement = false;

    public function generer(CarbonImmutable $debut, CarbonImmutable $fin): array
    {
        $duree = $debut->diffInDays($fin) + 1;
        $finPrecedente = $debut->subDay();
        $debutPrecedente = $finPrecedente->subDays($duree - 1);
        $actuel = $this->mouvements($debut, $fin);
        $precedent = $this->mouvements($debutPrecedente, $finPrecedente);
        $resultat = $this->compteResultat($actuel, $precedent);
        $bilan = $this->bilan($actuel, $precedent, $resultat);

        return [
            'date_debut' => $debut->toDateString(), 'date_fin' => $fin->toDateString(),
            'date_debut_precedente' => $debutPrecedente->toDateString(), 'date_fin_precedente' => $finPrecedente->toDateString(),
            'monnaie' => 'CDF', 'bilan' => $bilan, 'compte_resultat' => $resultat,
            'controles' => [
                'total_debit' => $actuel->sum('debit'), 'total_credit' => $actuel->sum('credit'),
                'ecart_debit_credit' => $actuel->sum('debit') - $actuel->sum('credit'),
                'total_actif' => $bilan['total_actif'], 'total_passif' => $bilan['total_passif'],
                'ecart_actif_passif' => $bilan['ecart'], 'resultat_net' => $resultat['resultat_net']['actuel'],
            ],
            'non_classes' => $this->nonClasses($actuel),
            'anomalies' => $this->anomalies($actuel),
        ];
    }

    private function mouvements(CarbonImmutable $debut, CarbonImmutable $fin): Collection
    {
        $query = EcritureComptable::query()
            ->with('compte')
            ->where('statut', 'Validé')
            ->whereBetween('date', [$debut->toDateString(), $fin->toDateString()]);

        return $query->get()
            ->groupBy(fn (EcritureComptable $e): string => $e->compte ? 'compte-'.$e->liste_des_comptes_id : 'sans-compte-'.$e->liste_des_comptes_id)
            ->map(function (Collection $lignes): array {
                $compte = $lignes->first()->compte;
                $debit = (float) $lignes->sum('debit_cdf');
                $credit = (float) $lignes->sum('credit_cdf');
                $nature = (string) ($compte?->nature ?? '');
                return [
                    'id' => $compte?->id, 'compte' => (string) ($compte?->compte ?? ''),
                    'designation' => (string) ($compte?->designation ?? 'Écriture sans compte'),
                    'nature' => $nature, 'observation' => (string) ($compte?->observation ?? ''),
                    'debit' => $debit, 'credit' => $credit,
                    'solde' => in_array($this->normaliser($nature), ['passif', 'produit'], true) ? $credit - $debit : $debit - $credit,
                    'sans_compte' => $compte === null,
                ];
            })
            ->filter(fn (array $l): bool => $this->afficherComptesSansMouvement || $l['debit'] != 0.0 || $l['credit'] != 0.0 || $l['solde'] != 0.0)
            ->values();
    }

    private function bilan(Collection $actuel, Collection $precedent, array $resultat): array
    {
        $sortie = ['actif' => $this->sectionsBilan('actif'), 'passif' => $this->sectionsBilan('passif')];
        $this->repartir($sortie, $actuel, 'actuel', 'bilan');
        $this->repartir($sortie, $precedent, 'precedent', 'bilan');
        $sortie['passif']['capitaux_propres']['lignes'][] = [
            'cle' => 'resultat_net', 'code' => 'CF', 'label' => 'Résultat net de l’exercice',
            'nature' => 'Passif', 'observation' => 'Bilan',
            'actuel' => $resultat['resultat_net']['actuel'], 'precedent' => $resultat['resultat_net']['precedent'],
        ];
        $this->totaliserSections($sortie['actif']);
        $this->totaliserSections($sortie['passif']);
        $sortie['total_actif'] = collect($sortie['actif'])->sum('total_actuel');
        $sortie['total_passif'] = collect($sortie['passif'])->sum('total_actuel');
        $sortie['ecart'] = $sortie['total_actif'] - $sortie['total_passif'];
        $sortie['equilibre'] = abs($sortie['ecart']) <= (float) config('syscohada.equilibrium_tolerance', 0.01);
        return $sortie;
    }

    private function compteResultat(Collection $actuel, Collection $precedent): array
    {
        $sortie = [];
        foreach (config('syscohada.resultat', []) as $cle => $definition) {
            $sortie[$cle] = ['label' => $definition['label'], 'lignes' => [], 'total_actuel' => 0.0, 'total_precedent' => 0.0];
        }
        $this->repartir($sortie, $actuel, 'actuel', 'resultat');
        $this->repartir($sortie, $precedent, 'precedent', 'resultat');
        $this->totaliserSections($sortie);
        $calculer = function (string $periode) use ($sortie): array {
            $total = fn (string $section): float => (float) ($sortie[$section]['total_'.$periode] ?? 0);
            $exploitation = $total('produits_exploitation') - $total('charges_exploitation');
            $financier = $total('produits_financiers') - $total('charges_financieres');
            $ordinaire = $exploitation + $financier;
            $hao = $total('produits_hao') - $total('charges_hao');
            $impot = $total('impot_resultat');
            return [
                'resultat_exploitation' => $exploitation, 'resultat_financier' => $financier,
                'resultat_activites_ordinaires' => $ordinaire, 'resultat_hao' => $hao,
                'impot_resultat_calcule' => $impot, 'resultat_net' => $ordinaire + $hao - $impot,
            ];
        };
        $n = $calculer('actuel');
        $nMoinsUn = $calculer('precedent');
        foreach ($n as $cle => $montant) {
            $sortie[$cle] = ['actuel' => $montant, 'precedent' => $nMoinsUn[$cle]];
        }
        return $sortie;
    }

    private function repartir(array &$sortie, Collection $mouvements, string $periode, string $etat): void
    {
        foreach ($mouvements as $mouvement) {
            $classement = $this->classer($mouvement);
            if (! $classement || $classement['etat'] !== $etat) continue;
            if ($etat === 'bilan') {
                $section =& $sortie[$classement['sens']][$classement['section']];
            } else {
                $section =& $sortie[$classement['section']];
            }
            $index = collect($section['lignes'])->search(fn (array $ligne): bool => $ligne['cle'] === $mouvement['id']);
            if ($index === false) {
                $section['lignes'][] = [
                    'cle' => $mouvement['id'], 'code' => $mouvement['compte'], 'label' => $mouvement['designation'],
                    'nature' => $mouvement['nature'], 'observation' => $mouvement['observation'],
                    'actuel' => 0.0, 'precedent' => 0.0,
                ];
                $index = array_key_last($section['lignes']);
            }
            $section['lignes'][$index][$periode] = (float) $mouvement['solde'];
            unset($section);
        }
    }

    private function classer(array $mouvement): ?array
    {
        if ($mouvement['sans_compte']) return null;
        $nature = $this->normaliser($mouvement['nature']);
        $observation = $this->normaliser($mouvement['observation']);
        $coherent = ($observation === 'bilan' && in_array($nature, ['actif', 'passif'], true))
            || ($observation === 'gestion' && in_array($nature, ['charge', 'produit'], true));
        if ($coherent && $observation === 'bilan') {
            return ['etat' => 'bilan', 'sens' => $nature, 'section' => $this->sectionBilanParPrefixe($mouvement['compte'], $nature) ?? ($nature === 'actif' ? 'actif_circulant' : 'passif_circulant')];
        }
        if ($coherent && $observation === 'gestion') {
            return ['etat' => 'resultat', 'section' => $this->sectionResultatParPrefixe($mouvement['compte'], $nature) ?? ($nature === 'charge' ? 'charges_exploitation' : 'produits_exploitation')];
        }
        return $this->classementParPrefixe($mouvement['compte']);
    }

    private function sectionsBilan(string $sens): array
    {
        $sortie = [];
        foreach (config('syscohada.bilan.'.$sens, []) as $cle => $definition) {
            $sortie[$cle] = ['label' => $definition['label'], 'lignes' => [], 'total_actuel' => 0.0, 'total_precedent' => 0.0];
        }
        return $sortie;
    }

    private function totaliserSections(array &$sections): void
    {
        foreach ($sections as &$section) {
            if (! isset($section['lignes'])) continue;
            $section['total_actuel'] = collect($section['lignes'])->sum('actuel');
            $section['total_precedent'] = collect($section['lignes'])->sum('precedent');
        }
        unset($section);
    }

    private function sectionBilanParPrefixe(string $compte, string $sens): ?string
    {
        foreach (config('syscohada.bilan.'.$sens, []) as $sectionCle => $section) {
            foreach ($section['rubriques'] as $rubrique) {
                if ($this->correspond($compte, $rubrique['prefixes'])) return $sectionCle;
            }
        }
        return null;
    }

    private function sectionResultatParPrefixe(string $compte, string $nature): ?string
    {
        $sections = $nature === 'charge'
            ? ['charges_exploitation', 'charges_financieres', 'charges_hao', 'impot_resultat']
            : ['produits_exploitation', 'produits_financiers', 'produits_hao'];
        foreach ($sections as $sectionCle) {
            foreach (config('syscohada.resultat.'.$sectionCle.'.rubriques', []) as $rubrique) {
                if ($this->correspond($compte, $rubrique['prefixes'])) return $sectionCle;
            }
        }
        return null;
    }

    private function classementParPrefixe(string $compte): ?array
    {
        foreach (['actif', 'passif'] as $sens) {
            if ($section = $this->sectionBilanParPrefixe($compte, $sens)) return ['etat' => 'bilan', 'sens' => $sens, 'section' => $section];
        }
        foreach (['charge', 'produit'] as $nature) {
            if ($section = $this->sectionResultatParPrefixe($compte, $nature)) return ['etat' => 'resultat', 'section' => $section];
        }
        return null;
    }

    private function anomalies(Collection $mouvements): array
    {
        return $mouvements->filter(function (array $ligne): bool {
            if ($ligne['sans_compte']) return true;
            $nature = $this->normaliser($ligne['nature']);
            $observation = $this->normaliser($ligne['observation']);
            if ($nature === '' && $observation === '') return false;
            return ! (($observation === 'bilan' && in_array($nature, ['actif', 'passif'], true))
                || ($observation === 'gestion' && in_array($nature, ['charge', 'produit'], true)));
        })->map(fn (array $ligne): array => $this->detailAnomalie($ligne, $ligne['sans_compte'] ? 'Écriture sans compte comptable associé' : 'Compte mal paramétré dans liste_des_comptes'))->values()->all();
    }

    private function nonClasses(Collection $mouvements): array
    {
        return $mouvements->filter(fn (array $ligne): bool => ! $ligne['sans_compte'] && $this->classer($ligne) === null)
            ->map(fn (array $ligne): array => $this->detailAnomalie($ligne, 'Compte non associé à une rubrique SYSCOHADA'))->values()->all();
    }

    private function detailAnomalie(array $ligne, string $raison): array
    {
        return [
            'compte' => $ligne['compte'] ?: '-', 'designation' => $ligne['designation'],
            'nature' => $ligne['nature'] ?: '-', 'observation' => $ligne['observation'] ?: '-',
            'debit' => $ligne['debit'], 'credit' => $ligne['credit'], 'solde' => $ligne['solde'], 'raison' => $raison,
        ];
    }

    private function normaliser(?string $valeur): string
    {
        return Str::lower(Str::ascii(trim((string) $valeur)));
    }

    private function correspond(string $compte, array $prefixes): bool
    {
        foreach ($prefixes as $prefixe) if (str_starts_with($compte, (string) $prefixe)) return true;
        return false;
    }
}
