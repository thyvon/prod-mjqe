<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8" />
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title inertia>{{ config('app.name', 'Laravel') }}</title>
    <link rel="icon" type="image/x-icon" href="https://www.mjqeducation.edu.kh/FrontEnd/Image/logo/mjq-education-single-logo_1.ico">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <meta name="description" content="">
    <meta name="author" content="">

    <!-- Include Poppins font -->
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet">

        <!-- ================== BEGIN core-css ================== -->
        <link href="/coloradmin/css/vendor.min.css" rel="stylesheet" defer />
        <link href="/coloradmin/css/default/app.min.css" rel="stylesheet" defer />
        <!-- ================== END core-css ================== -->

        <!-- ================== BEGIN plugin-css ================== -->
        <link href="/coloradmin/plugins/datatables.net-bs5/css/dataTables.bootstrap5.min.css" rel="stylesheet" defer />
        <link href="/coloradmin/plugins/datatables.net-responsive-bs5/css/responsive.bootstrap5.min.css" rel="stylesheet" defer />
        <link href="/coloradmin/plugins/datatables.net-select-bs5/css/select.bootstrap5.min.css" rel="stylesheet" defer />
        <link href="/coloradmin/plugins/select2/dist/css/select2.min.css" rel="stylesheet" defer />
        <link href="/coloradmin/plugins/bootstrap-daterangepicker/daterangepicker.css" rel="stylesheet" defer />
        <link href="/coloradmin/plugins/bootstrap-datepicker/dist/css/bootstrap-datepicker.css" rel="stylesheet" defer/>
        <link href="/coloradmin/plugins/dropzone/dist/min/dropzone.min.css" rel="stylesheet" defer />
        <link href="/coloradmin/plugins/jvectormap-next/jquery-jvectormap.css" rel="stylesheet" defer />
        <link href="/coloradmin/plugins/nvd3/build/nv.d3.css" rel="stylesheet" defer />
        <link href="/coloradmin/plugins/summernote/dist/summernote-lite.css" rel="stylesheet" defer />
        <!-- ================== END plugin-css ================== -->

    @inertiaHead
</head>
<body>
        <!-- ================== BEGIN core-js ================== -->
        <script src="/coloradmin/js/vendor.min.js" defer></script>
        <script src="/coloradmin/js/app.min.js" defer></script>
        <!-- ================== END core-js ================== -->

        <!-- ================== BEGIN plugin-js ================== -->
        <script src="/coloradmin/plugins/datatables.net/js/jquery.dataTables.min.js" defer></script>
        <script src="/coloradmin/plugins/datatables.net-bs5/js/dataTables.bootstrap5.min.js" defer></script>
        <script src="/coloradmin/plugins/datatables.net-responsive/js/dataTables.responsive.min.js" defer></script>
        <script src="/coloradmin/plugins/datatables.net-responsive-bs5/js/responsive.bootstrap5.min.js" defer></script>
        <script src="/coloradmin/plugins/datatables.net-select/js/dataTables.select.min.js" defer></script>
        <script src="/coloradmin/plugins/datatables.net-select-bs5/js/select.bootstrap5.min.js" defer></script>
        <script src="/coloradmin/plugins/sweetalert/dist/sweetalert.min.js" defer></script>
        <script src="/coloradmin/plugins/select2/dist/js/select2.min.js" defer></script>
        <script src="/coloradmin/plugins/bootstrap-datepicker/dist/js/bootstrap-datepicker.js" defer></script>
        <script src="/coloradmin/plugins/moment/min/moment.min.js" defer></script>
        <script src="/coloradmin/plugins/bootstrap-daterangepicker/daterangepicker.js" defer></script>
        <script src="/coloradmin/plugins/dropzone/dist/min/dropzone.min.js" defer></script>
        <script src="/coloradmin/plugins/summernote/dist/summernote-lite.min.js" defer></script>
        <!-- ================== END plugin-js ================== -->

        <!-- ================== BEGIN dashboard-js ================== -->
        <script src="/coloradmin/plugins/d3/d3.min.js" defer></script>
        <script src="/coloradmin/plugins/nvd3/build/nv.d3.min.js" defer></script>
        <script src="/coloradmin/plugins/jvectormap-next/jquery-jvectormap.min.js" defer></script>
        <script src="/coloradmin/plugins/jvectormap-content/world-mill.js" defer></script>
        <script src="/coloradmin/plugins/apexcharts/dist/apexcharts.min.js" defer></script>
        <script src="/coloradmin/js/demo/dashboard-v3.js" defer></script>
        <script src="/coloradmin/plugins/chart.js/dist/chart.umd.js" defer></script>
        <!-- ================== END dashboard-js ================== -->

    @vite(['resources/js/app.js'])
    @routes
    @inertia
</body>
</html>
