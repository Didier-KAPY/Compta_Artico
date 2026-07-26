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


    </style>

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
        });
        document.addEventListener('click', function(e){
            if(window.innerWidth <= 991){
                if(!sidebar.contains(e.target)
                && !toggle.contains(e.target)){
                    sidebar.classList.remove('show');
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