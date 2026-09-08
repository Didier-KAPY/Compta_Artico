<aside class="sidebar" id="sidebar" aria-label="Navigation des ressources humaines">
    <div class="px-2 mb-3"><a href="{{ route('parametres.rh.index') }}" class="d-flex align-items-center gap-2 text-white text-decoration-none px-2"><span class="d-inline-grid rounded-3 bg-info bg-opacity-25 text-center pt-2" style="width:38px;height:38px"><i class="bi bi-person-workspace text-info"></i></span><span><small class="d-block text-white-50">Module</small><strong>Ressources humaines</strong></span></a></div>
    @php($liens = [['index','speedometer2','Tableau de bord',null],['employes','people','Employés',null],['contrats','file-earmark-text','Contrats / Engagements',null],['presences.scanner','qr-code-scan','Pointages','manageAttendance'],['presences','calendar-check','Présences',null],['syntheses','calendar3','Synthèses mensuelles',null],['conges','calendar2-check','Absences & Congés',null],['periodes','calendar-range','Périodes de paie',null],['paie','cash-stack','Paie',null],['evaluations','clipboard-data','Évaluations',null],['rapports','file-earmark-bar-graph','Rapports RH',null]])
    <ul class="nav flex-column">
        @foreach($liens as [$route,$icone,$libelle,$permission])
            @if(!$permission || auth()->user()->can($permission))<li><a href="{{ route('parametres.rh.'.$route) }}" class="nav-link {{ request()->routeIs('parametres.rh.'.$route) ? 'active-menu' : '' }}"><i class="bi bi-{{ $icone }} me-2"></i>{{ $libelle }}</a></li>@endif
        @endforeach
        @can('manageDepartments')<li><a href="{{route('parametres.departements')}}" class="nav-link {{request()->routeIs('parametres.departements*')?'active-menu':''}}"><i class="bi bi-diagram-3 me-2"></i>Directions et fonctions</a></li>@endcan
        @can('manageServiceCards')<li><a href="{{route('parametres.cartes-service.index')}}" class="nav-link {{request()->routeIs('parametres.cartes-service.*')?'active-menu':''}}"><i class="bi bi-person-badge me-2"></i>Cartes de service</a></li>@endcan
        @can('manageHRSettings')<li><a href="{{route('parametres.rh.settings')}}" class="nav-link {{request()->routeIs('parametres.rh.settings*')?'active-menu':''}}"><i class="bi bi-sliders me-2"></i>Paramètres RH</a></li>@endcan
        <li class="mt-3 pt-3 border-top border-light border-opacity-10"><a href="{{ route('dashboard') }}" class="nav-link"><i class="bi bi-arrow-left-circle me-2"></i>Retour à la comptabilité</a></li>
        <li><a href="{{ route('parametres.parametre') }}" class="nav-link"><i class="bi bi-gear me-2"></i>Paramètres</a></li>
    </ul>
</aside>
