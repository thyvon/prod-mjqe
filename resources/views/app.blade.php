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

    <!-- ================== BEGIN core-css ================== -->
    <link href="/coloradmin/css/vendor.min.css" rel="stylesheet" />
    <link href="/coloradmin/css/default/app.min.css" rel="stylesheet" />
    <!-- ================== END core-css ================== -->
    <link href="/coloradmin/plugins/datatables.net-bs5/css/dataTables.bootstrap5.min.css" rel="stylesheet" />
    <link href="/coloradmin/plugins/datatables.net-responsive-bs5/css/responsive.bootstrap5.min.css" rel="stylesheet" />
    @vite(['resources/css/app.css'])
    @inertiaHead
</head>
<body>

    <script src="/coloradmin/js/vendor.min.js"></script>
    <script src="/coloradmin/js/app.min.js"></script>

    <!-- ================== BEGIN page-js ================== -->
    <script src="/coloradmin/plugins/datatables.net/js/jquery.dataTables.min.js" defer></script>
    <script src="/coloradmin/plugins/datatables.net-bs5/js/dataTables.bootstrap5.min.js" defer></script>
    <script src="/coloradmin/plugins/datatables.net-responsive/js/dataTables.responsive.min.js" defer></script>
    <script src="/coloradmin/plugins/datatables.net-responsive-bs5/js/responsive.bootstrap5.min.js" defer></script>
    <script src="/coloradmin/plugins/sweetalert/dist/sweetalert.min.js" defer></script>
    @vite(['resources/js/app.js'])
    @routes
    @inertia
</body>
</html>