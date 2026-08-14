<aside class="sidebar" id="sidebar" aria-label="Navigation de la gestion budgétaire">
    <div class="px-2 mb-3">
        <a href="{{ route('parametres.budgets.index') }}" class="d-flex align-items-center gap-2 text-white text-decoration-none px-2">
            <span class="d-inline-grid rounded-3 bg-primary bg-opacity-25 text-center pt-2" style="width:38px;height:38px"><i class="bi bi-pie-chart-fill text-info"></i></span>
            <span><small class="d-block text-white-50">Module</small><strong>Gestion budgétaire</strong></span>
        </a>
    </div>
    <ul class="nav flex-column">
        <li><a href="{{ route('parametres.budgets.index') }}" class="nav-link {{ request()->routeIs('parametres.budgets.index') ? 'active-menu' : '' }}"><i class="bi bi-speedometer2 me-2"></i> Tableau de bord</a></li>
        <li><a href="{{ route('parametres.budgets.annuels') }}" class="nav-link {{ request()->routeIs('parametres.budgets.annuels') ? 'active-menu' : '' }}"><i class="bi bi-calendar2-range me-2"></i> Budgets annuels</a></li>
        <li><a href="{{ route('parametres.budgets.lignes') }}" class="nav-link {{ request()->routeIs('parametres.budgets.lignes') ? 'active-menu' : '' }}"><i class="bi bi-list-check me-2"></i> Lignes budgétaires</a></li>
        <li><a href="{{ route('parametres.budgets.rubriques') }}" class="nav-link {{ request()->routeIs('parametres.budgets.rubriques*') ? 'active-menu' : '' }}"><i class="bi bi-tags me-2"></i> Rubriques</a></li>
        <li><a href="{{ route('parametres.budgets.recettes') }}" class="nav-link {{ request()->routeIs('parametres.budgets.recettes') ? 'active-menu' : '' }}"><i class="bi bi-graph-up-arrow me-2"></i> Prévisions de recettes</a></li>
        <li><a href="{{ route('parametres.budgets.depenses') }}" class="nav-link {{ request()->routeIs('parametres.budgets.depenses') ? 'active-menu' : '' }}"><i class="bi bi-graph-down-arrow me-2"></i> Prévisions de dépenses</a></li>
        <li><a href="{{ route('parametres.budgets.engagements') }}" class="nav-link {{ request()->routeIs('parametres.budgets.engagements') ? 'active-menu' : '' }}"><i class="bi bi-lock me-2"></i> Engagements</a></li>
        <li><a href="{{ route('parametres.budgets.realisations') }}" class="nav-link {{ request()->routeIs('parametres.budgets.realisations') ? 'active-menu' : '' }}"><i class="bi bi-check2-circle me-2"></i> Réalisations</a></li>
        <li><a href="{{ route('parametres.budgets.execution') }}" class="nav-link {{ request()->routeIs('parametres.budgets.execution') ? 'active-menu' : '' }}"><i class="bi bi-bar-chart-line me-2"></i> Exécution budgétaire</a></li>
        <li><a href="{{ route('parametres.budgets.mouvements') }}" class="nav-link {{ request()->routeIs('parametres.budgets.mouvements') ? 'active-menu' : '' }}"><i class="bi bi-arrow-left-right me-2"></i> Mouvements</a></li>
        <li><a href="{{ route('parametres.budgets.revisions-transferts') }}" class="nav-link {{ request()->routeIs('parametres.budgets.revisions-transferts') ? 'active-menu' : '' }}"><i class="bi bi-shuffle me-2"></i> Révisions et transferts</a></li>
        <li><a href="{{ route('parametres.budgets.etats') }}" class="nav-link {{ request()->routeIs('parametres.budgets.etats') ? 'active-menu' : '' }}"><i class="bi bi-file-earmark-bar-graph me-2"></i> États budgétaires</a></li>
        <li class="mt-3 pt-3 border-top border-light border-opacity-10"><a href="{{ route('dashboard') }}" class="nav-link"><i class="bi bi-arrow-left-circle me-2"></i> Retour à la comptabilité</a></li>
        <li><a href="{{ route('parametres.parametre') }}" class="nav-link"><i class="bi bi-gear me-2"></i> Paramètres</a></li>
    </ul>
</aside>
