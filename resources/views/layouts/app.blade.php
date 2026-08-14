<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>COMPTA ARTICO</title>
    <!-- Bootstrap -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Bootstrap Icons -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">
    <!-- Select2 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet">

    <style>

        body{
            background:#f4f6f9;
            overflow-x:hidden;
        }


        .navbar{
            z-index:1100;
        }


        /* ================= SIDEBAR ================= */

        .sidebar{

            width:260px;
            height:100vh;

            background:#1e293b;

            position:fixed;

            top:0;
            left:0;

            padding-top:70px;

            overflow-y:auto;

            transition:.3s;

            z-index:1000;
        }



        .sidebar .nav-link{

            color:#cbd5e1;

            padding:12px 18px;

            display:flex;

            align-items:center;

            text-decoration:none;
        }



        .sidebar .nav-link:hover{

            background:#334155;

            color:#fff;
        }



        /* MENU ACTIF */

        .sidebar .nav-link.active-menu{

            background:#f1f5f9 !important;

            color:#1e293b !important;

            font-weight:600;

            border-left:4px solid #0d6efd;
        }



        .sidebar .nav-link.active-menu i{

            color:#0d6efd;
        }



        /* ================= CONTENT ================= */

        .content{

            margin-left:260px;

            padding:90px 20px 20px;
        }



        @media(max-width:991px){

            .sidebar{

                left:-260px;

            }


            .sidebar.show{

                left:0;

            }


            .content{

                margin-left:0;

            }

        }




        /* ================= SUBMENU ================= */


        .submenu{

            display:none;

            padding-left:15px;
        }



        .submenu.show{

            display:block;
        }



        .submenu a{

            color:#cbd5e1;

            padding:8px 15px;

            display:block;

            text-decoration:none;

            font-size:14px;
        }



        .submenu a:hover{

            background:#334155;

            color:#fff;
        }



        .submenu a.active{

            background:#f1f5f9 !important;

            color:#1e293b !important;

            border-left:4px solid #0d6efd;

            font-weight:600;
        }




        /* ICON ROTATION */

        .submenu-icon{

            transition:.25s;
        }



        .submenu-icon.rotate{

            transform:rotate(180deg);
        }




        /* ================= SELECT2 ================= */


        .select2-container .select2-selection--single{

            height:38px;
        }



        .select2-container--default 
        .select2-selection--single 
        .select2-selection__rendered{

            line-height:38px;
        }



        .select2-container--default 
        .select2-selection--single 
        .select2-selection__arrow{

            height:38px;
        }


        /* ==================================================
           SIDEBAR — DESIGN MODERNE (présentation uniquement)
           ================================================== */
        .sidebar {
            width: 272px;
            padding: 82px 12px 24px;
            background:
                radial-gradient(circle at 15% 8%, rgba(59, 130, 246, .22), transparent 28%),
                linear-gradient(180deg, #101c35 0%, #15243f 52%, #0d172b 100%);
            border-right: 1px solid rgba(255, 255, 255, .08);
            box-shadow: 10px 0 35px rgba(15, 23, 42, .13);
            scrollbar-width: thin;
            scrollbar-color: rgba(148, 163, 184, .35) transparent;
        }

        .sidebar::-webkit-scrollbar {
            width: 5px;
        }

        .sidebar::-webkit-scrollbar-thumb {
            background: rgba(148, 163, 184, .35);
            border-radius: 20px;
        }

        .sidebar > .nav {
            gap: 5px;
        }

        .sidebar .nav-item,
        .sidebar > .nav > li {
            width: 100%;
        }

        .sidebar .nav-link,
        .sidebar .menu-parent {
            min-height: 46px;
            margin: 0;
            padding: 11px 13px;
            color: #c8d4e5;
            border: 1px solid transparent;
            border-radius: 12px;
            font-size: .9rem;
            font-weight: 500;
            letter-spacing: .01em;
            transition: background-color .2s ease, color .2s ease,
                        border-color .2s ease, transform .2s ease,
                        box-shadow .2s ease;
        }

        .sidebar .nav-link > i:first-child,
        .sidebar .menu-parent > div > i:first-child {
            width: 30px;
            height: 30px;
            margin-right: 10px !important;
            display: inline-grid;
            place-items: center;
            flex: 0 0 30px;
            color: #8fb8f5;
            background: rgba(96, 165, 250, .1);
            border-radius: 9px;
            font-size: 1rem !important;
            transition: .2s ease;
        }

        .sidebar .nav-link:hover,
        .sidebar .menu-parent:hover {
            color: #fff;
            background: rgba(255, 255, 255, .075);
            border-color: rgba(255, 255, 255, .07);
            transform: translateX(2px);
        }

        .sidebar .nav-link:hover > i:first-child,
        .sidebar .menu-parent:hover > div > i:first-child {
            color: #fff;
            background: rgba(59, 130, 246, .28);
        }

        .sidebar .nav-link.active-menu,
        .sidebar .submenu a.active {
            color: #fff !important;
            background: linear-gradient(135deg, #2563eb, #3b82f6) !important;
            border: 1px solid rgba(147, 197, 253, .25);
            border-left: 1px solid rgba(147, 197, 253, .25);
            box-shadow: 0 8px 20px rgba(37, 99, 235, .28);
            font-weight: 600;
        }

        .sidebar .nav-link.active-menu > i:first-child,
        .sidebar .submenu a.active > i:first-child {
            color: #fff;
            background: rgba(255, 255, 255, .17);
        }

        .sidebar .submenu {
            position: relative;
            margin: 5px 0 7px 15px !important;
            padding: 3px 0 3px 17px !important;
        }

        .sidebar .submenu::before {
            content: '';
            position: absolute;
            top: 4px;
            bottom: 4px;
            left: 0;
            width: 1px;
            background: linear-gradient(180deg, rgba(96, 165, 250, .55), rgba(96, 165, 250, .08));
        }

        .sidebar .submenu li {
            position: relative;
            margin: 3px 0;
        }

        .sidebar .submenu li::before {
            content: '';
            position: absolute;
            top: 20px;
            left: -17px;
            width: 11px;
            height: 1px;
            background: rgba(96, 165, 250, .4);
        }

        .sidebar .submenu a {
            min-height: 39px;
            padding: 8px 10px;
            color: #aebdd0;
            border-radius: 10px;
            font-size: .82rem;
        }

        .sidebar .submenu a:hover {
            color: #fff;
            background: rgba(255, 255, 255, .07);
        }

        .sidebar .submenu-icon {
            color: #7187a5;
            font-size: .75rem;
        }

        .content {
            margin-left: 272px;
            transition: margin-left .3s ease;
        }

        @media (max-width: 991px) {
            .sidebar {
                left: -286px;
                box-shadow: 18px 0 45px rgba(15, 23, 42, .28);
            }

            .sidebar.show {
                left: 0;
            }

            .content {
                margin-left: 0;
            }
        }
    </style>

    <link href="{{ asset('assets/css/app-theme.css') }}" rel="stylesheet">
    <style>
        .app-action-loading {
            pointer-events: none !important;
            cursor: wait !important;
            opacity: .78;
        }
        .app-action-loading .spinner-border { vertical-align: -.125em; }
        #appLoadingOverlay {
            position: fixed;
            inset: 0;
            z-index: 19980;
            display: none;
            cursor: wait;
            background: transparent;
        }
        #appLoadingOverlay.show { display: block; }
        #appConfirmationModal { z-index: 20000; }
        body:has(#appConfirmationModal.show) .modal-backdrop { z-index: 19990; }
    </style>
</head>

<body>
@include('layouts.topbar')
@hasSection('module-sidebar')
    @yield('module-sidebar')
@else
    @include('layouts.sidebar')
@endif
<div class="content">
    @if(session('warning'))
        <div class="alert alert-warning alert-dismissible fade show" role="alert">
            {{ session('warning') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Fermer"></button>
        </div>
    @endif
    @yield('content')
</div>
<div id="appLoadingOverlay" aria-hidden="true"></div>
<div class="modal fade" id="appConfirmationModal" tabindex="-1" aria-labelledby="appConfirmationTitle" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow">
            <div class="modal-header border-0 pb-0">
                <h5 class="modal-title" id="appConfirmationTitle"><i class="bi bi-question-circle text-warning me-2"></i>Confirmation</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Fermer"></button>
            </div>
            <div class="modal-body"><p id="appConfirmationMessage" class="mb-0"></p></div>
            <div class="modal-footer border-0 pt-0">
                <button type="button" class="btn btn-light border" data-bs-dismiss="modal">Annuler</button>
                <button type="button" class="btn btn-primary" id="appConfirmationAccept"><i class="bi bi-check-lg me-1"></i>Confirmer</button>
            </div>
        </div>
    </div>
</div>
<!-- ================= SCRIPTS ================= -->
<!-- JQuery -->
<script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
<!-- Bootstrap JS -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<!-- Select2 JS -->
<script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
<!-- Scripts propres aux pages -->
@stack('scripts')
@yield('scripts')
<script>
    (function ($) {
        const accountAndJournalSelector = [
            'select[name="liste_des_comptes_id"]',
            'select[name$="[liste_des_comptes_id]"]',
            'select[name="compte_id"]',
            'select[name$="[compte_id]"]',
            'select[name="journal_id"]',
            'select[name$="[journal_id]"]',
            'select[name="journal_type_id"]',
            'select[name$="[journal_type_id]"]',
            'select.compte-search',
            'select.journal-search'
        ].join(',');

        function searchablePlaceholder(select) {
            const name = select.name || '';
            const isJournal = name.includes('journal') || select.classList.contains('journal-search');

            return isJournal ? 'Rechercher un journal' : 'Rechercher un compte';
        }

        function enableAccountAndJournalSearch(root) {
            const candidates = [];

            if (root instanceof Element && root.matches(accountAndJournalSelector)) {
                candidates.push(root);
            }

            if (root.querySelectorAll) {
                candidates.push(...root.querySelectorAll(accountAndJournalSelector));
            }

            candidates.forEach(function (select) {
                const field = $(select);

                if (field.hasClass('select2-hidden-accessible') || select.dataset.noSearch !== undefined) {
                    return;
                }

                const modal = field.closest('.modal');
                const options = {
                    width: '100%',
                    placeholder: searchablePlaceholder(select),
                    allowClear: !select.required,
                    minimumResultsForSearch: 0,
                    language: {
                        noResults: function () { return 'Aucun résultat trouvé'; },
                        searching: function () { return 'Recherche…'; }
                    }
                };

                if (modal.length) {
                    options.dropdownParent = modal;
                }

                field.select2(options);
            });
        }

        $(function () {
            enableAccountAndJournalSearch(document);

            new MutationObserver(function (mutations) {
                mutations.forEach(function (mutation) {
                    mutation.addedNodes.forEach(function (node) {
                        if (node.nodeType === Node.ELEMENT_NODE) {
                            enableAccountAndJournalSearch(node);
                        }
                    });
                });
            }).observe(document.body, { childList: true, subtree: true });
        });
    })(jQuery);
</script>
<script>
    document.addEventListener('DOMContentLoaded', function () {
    const sidebar = document.getElementById('sidebar');
    const toggle = document.getElementById('toggleSidebar');
    /* MOBILE SIDEBAR */
    if(toggle && sidebar){
        toggle.addEventListener('click', function(){
            sidebar.classList.toggle('show');
            toggle.setAttribute('aria-expanded', sidebar.classList.contains('show') ? 'true' : 'false');
        });
        document.addEventListener('click', function(e){
            if(window.innerWidth <= 991){
                if(!sidebar.contains(e.target)
                && !toggle.contains(e.target)){
                    sidebar.classList.remove('show');
                    toggle.setAttribute('aria-expanded', 'false');
                }
            }
        });
    }
    /* SUBMENU */
    document.querySelectorAll('.menu-parent')
    .forEach(menu => {
        menu.addEventListener('click', function(){
            const parent = menu.closest('.nav-item');
            if(!parent) return;
            const submenu = parent.querySelector('.submenu');
            const icon = menu.querySelector('.submenu-icon');
            if(!submenu) return;
            const isOpen =
                submenu.classList.contains('show');
            document.querySelectorAll('.submenu')
            .forEach(s =>
                s.classList.remove('show')
            );
            document.querySelectorAll('.submenu-icon')
            .forEach(i =>
                i.classList.remove('rotate')
            );
            if(!isOpen){
                submenu.classList.add('show');
                if(icon){
                    icon.classList.add('rotate');
                }
            }
        });
    });
    });

    document.addEventListener('DOMContentLoaded', function () {
        const modalElement = document.getElementById('appConfirmationModal');
        const messageElement = document.getElementById('appConfirmationMessage');
        const acceptButton = document.getElementById('appConfirmationAccept');
        const cancelButton = modalElement.querySelector('[data-bs-dismiss="modal"]:not(.btn-close)');
        const overlay = document.getElementById('appLoadingOverlay');
        const confirmationModal = bootstrap.Modal.getOrCreateInstance(modalElement);
        let pendingAction = null;

        const askConfirmation = (message, action) => {
            pendingAction = action;
            cancelButton.classList.remove('d-none');
            acceptButton.innerHTML = '<i class="bi bi-check-lg me-1"></i>Confirmer';
            messageElement.textContent = message;
            confirmationModal.show();
        };
        window.appNotify = (message) => {
            pendingAction = null;
            cancelButton.classList.add('d-none');
            acceptButton.textContent = 'OK';
            messageElement.textContent = message;
            confirmationModal.show();
        };

        acceptButton.addEventListener('click', function () {
            const action = pendingAction;
            pendingAction = null;
            if (action) {
                modalElement.addEventListener('hidden.bs.modal', action, { once: true });
            }
            confirmationModal.hide();
        });
        modalElement.addEventListener('hidden.bs.modal', () => { pendingAction = null; });

        document.addEventListener('click', function (event) {
            const trigger = event.target.closest('[data-confirm]');
            if (!trigger || trigger.closest('form')) return;
            if (trigger.dataset.confirmedClick === 'true') {
                delete trigger.dataset.confirmedClick;
                return;
            }
            event.preventDefault();
            askConfirmation(trigger.dataset.confirm, () => {
                if (trigger.matches('a[href]')) window.location.assign(trigger.href);
                else {
                    trigger.dataset.confirmedClick = 'true';
                    trigger.click();
                }
            });
        });

        document.addEventListener('click', function (event) {
            if (event.defaultPrevented || event.button !== 0 || event.ctrlKey || event.metaKey || event.shiftKey || event.altKey) return;
            const link = event.target.closest('a.btn[href]');
            if (!link || link.target === '_blank' || link.hasAttribute('download') || link.dataset.bsToggle || link.dataset.noLoading !== undefined) return;
            const href = link.getAttribute('href');
            if (!href || href.startsWith('#') || href.startsWith('javascript:')) return;

            link.dataset.originalHtml = link.innerHTML;
            link.classList.add('app-action-loading');
            link.setAttribute('aria-disabled', 'true');
            link.innerHTML = '<span class="spinner-border spinner-border-sm me-2" aria-hidden="true"></span>Chargement...';

            const isDownload = /\/(pdf|excel)(\/|\?|$)|\/exports\//i.test(href);
            if (isDownload) {
                window.setTimeout(() => {
                    if (!link.dataset.originalHtml) return;
                    link.innerHTML = link.dataset.originalHtml;
                    delete link.dataset.originalHtml;
                    link.classList.remove('app-action-loading');
                    link.removeAttribute('aria-disabled');
                }, 2500);
            } else {
                overlay.classList.add('show');
            }
        });

        document.addEventListener('submit', function (event) {
            const form = event.target;
            if (!(form instanceof HTMLFormElement) || form.dataset.noLoading !== undefined) return;
            const submitter = event.submitter || form.querySelector('button[type="submit"], input[type="submit"], button:not([type])');
            const confirmation = submitter?.dataset.confirm || form.dataset.confirm;

            if (confirmation && form.dataset.confirmed !== 'true') {
                event.preventDefault();
                askConfirmation(confirmation, () => {
                    form.dataset.confirmed = 'true';
                    form.requestSubmit(submitter || undefined);
                });
                return;
            }

            delete form.dataset.confirmed;
            if (form.dataset.submitting === 'true') {
                event.preventDefault();
                return;
            }

            form.dataset.submitting = 'true';
            const loadingText = submitter?.dataset.loadingText || form.dataset.loadingText || 'Traitement...';
            if (submitter) {
                submitter.dataset.originalHtml = submitter.innerHTML;
                submitter.classList.add('app-action-loading');
                submitter.setAttribute('aria-disabled', 'true');
                submitter.innerHTML = `<span class="spinner-border spinner-border-sm me-2" aria-hidden="true"></span>${loadingText}`;
            }
            overlay.classList.add('show');
        }, true);

        window.addEventListener('pageshow', function () {
            overlay.classList.remove('show');
            document.querySelectorAll('form[data-submitting="true"]').forEach(form => {
                delete form.dataset.submitting;
                form.querySelectorAll('[data-original-html]').forEach(button => {
                    button.innerHTML = button.dataset.originalHtml;
                    delete button.dataset.originalHtml;
                    button.classList.remove('app-action-loading');
                    button.removeAttribute('aria-disabled');
                });
            });
            document.querySelectorAll('a[data-original-html]').forEach(link => {
                link.innerHTML = link.dataset.originalHtml;
                delete link.dataset.originalHtml;
                link.classList.remove('app-action-loading');
                link.removeAttribute('aria-disabled');
            });
        });
    });
</script>
</body>

</html>
