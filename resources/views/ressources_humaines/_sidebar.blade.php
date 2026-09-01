<aside class="sidebar" id="sidebar" aria-label="Navigation des ressources humaines">
    <div class="px-2 mb-3"><a href="{{ route('parametres.rh.index') }}" class="d-flex align-items-center gap-2 text-white text-decoration-none px-2"><span class="d-inline-grid rounded-3 bg-info bg-opacity-25 text-center pt-2" style="width:38px;height:38px"><i class="bi bi-person-workspace text-info"></i></span><span><small class="d-block text-white-50">Module</small><strong>Ressources humaines</strong></span></a></div>
    @php($liens = [['index','speedometer2','Tableau de bord'],['employes','people','Employés'],['contrats','file-earmark-text','Contrats'],['presences','clock-history','Présences'],['conges','calendar2-check','Congés'],['paie','cash-stack','Paie'],['evaluations','clipboard-data','Évaluations'],['rapports','file-earmark-bar-graph','Rapports RH']])
    <ul class="nav flex-column">
        @foreach($liens as [$route,$icone,$libelle])<li><a href="{{ route('parametres.rh.'.$route) }}" class="nav-link {{ request()->routeIs('parametres.rh.'.$route) ? 'active-menu' : '' }}"><i class="bi bi-{{ $icone }} me-2"></i>{{ $libelle }}</a></li>@endforeach
        @can('manageHRSettings')<li><a href="{{route('parametres.rh.settings')}}" class="nav-link {{request()->routeIs('parametres.rh.settings*')?'active-menu':''}}"><i class="bi bi-sliders me-2"></i>Paramètres RH</a></li>@endcan
        <li class="mt-3 pt-3 border-top border-light border-opacity-10"><a href="{{ route('dashboard') }}" class="nav-link"><i class="bi bi-arrow-left-circle me-2"></i>Retour à la comptabilité</a></li>
        <li><a href="{{ route('parametres.parametre') }}" class="nav-link"><i class="bi bi-gear me-2"></i>Paramètres</a></li>
    </ul>
</aside>
