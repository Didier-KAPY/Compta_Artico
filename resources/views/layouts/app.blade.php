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
</head>

<body>
@include('layouts.topbar')
@include('layouts.sidebar')
<div class="content">
    @yield('content')
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
</script>
</body>

</html>
