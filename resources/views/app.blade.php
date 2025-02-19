<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta charset="utf-8" />
    <title inertia>{{ config('app.name', 'Laravel') }}</title>
    <link rel="icon" type="image/x-icon" href="https://www.mjqeducation.edu.kh/FrontEnd/Image/logo/mjq-education-single-logo_1.ico">
    <meta content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no" name="viewport" />
    <meta content="" name="description" />
    <meta content="" name="author" />

    <!-- Include Poppins font -->
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet">

    <!-- ================== BEGIN core-css ================== -->
    <link href="/coloradmin/css/vendor.min.css" rel="stylesheet" defer />
    <link href="/coloradmin/css/default/app.min.css" rel="stylesheet" defer />
    <!-- ================== END core-css ================== -->
    <link href="/coloradmin/plugins/datatables.net-bs5/css/dataTables.bootstrap5.min.css" rel="stylesheet" defer />
    <link href="/coloradmin/plugins/datatables.net-responsive-bs5/css/responsive.bootstrap5.min.css" rel="stylesheet" defer />
    <link href="/coloradmin/plugins/datatables.net-select-bs5/css/select.bootstrap5.min.css" rel="stylesheet" defer />
    <link href="/coloradmin/plugins/select2/dist/css/select2.min.css" rel="stylesheet" defer />
    @inertiaHead
</head>
<body>

    <script src="/coloradmin/js/vendor.min.js" defer></script>
    <script src="/coloradmin/js/app.min.js" defer></script>

    <!-- ================== BEGIN page-js ================== -->

    <script src="/coloradmin/plugins/datatables.net/js/jquery.dataTables.min.js" defer></script>
    <!-- <script src="/coloradmin/plugins/datatables.net/js/dataTables.min.js" defer></script> -->
    <script src="/coloradmin/plugins/datatables.net-bs5/js/dataTables.bootstrap5.min.js" defer></script>
    <script src="/coloradmin/plugins/datatables.net-responsive/js/dataTables.responsive.min.js" defer></script>
    <script src="/coloradmin/plugins/datatables.net-responsive-bs5/js/responsive.bootstrap5.min.js" defer></script>
    <script src="/coloradmin/plugins/datatables.net-select/js/dataTables.select.min.js" defer></script>
    <script src="/coloradmin/plugins/datatables.net-select-bs5/js/select.bootstrap5.min.js" defer></script>
    <script src="/coloradmin/plugins/sweetalert/dist/sweetalert.min.js" defer></script>
    <script src="/coloradmin/plugins/select2/dist/js/select2.min.js" defer></script>
    @vite(['resources/js/app.js'])
    @routes
    @inertia
</body>
</html>
