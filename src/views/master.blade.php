<!DOCTYPE html>
<html lang="{{App::getLocale()}}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width">
    <meta content="mardok9185" name="author">
    <meta content="IE=edge,chrome=1" http-equiv="X-UA-Compatible">
    <meta name="viewport" content="width=device-width">
    <!-- CSRF Token -->
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Admin</title>
    <link type="text/css" rel="stylesheet"
          href="{{asset('vendor/basemodelfields/assets/bootstrap-3.3.7-dist/css/bootstrap-theme-3.min.css')}}">
    <link type="text/css" rel="stylesheet"
          href="{{asset('vendor/basemodelfields/assets/jquery-ui-1.12.1.custom/jquery-ui.min.css')}}">
    <link rel="stylesheet" type="text/css"
          href="{{asset('vendor/basemodelfields/assets/DataTables/datatables.min.css')}}"/>
    <link rel="stylesheet" type="text/css"
          href="{{asset('vendor/basemodelfields/assets/cropper-master/dist/cropper.min.css')}}"/>


    <link type="text/css" rel="stylesheet" href="{{asset('vendor/basemodelfields/css/base.css')}}">
    <!-- Scripts -->
    <script>
        window.Laravel = {!! json_encode([
            'csrfToken' => csrf_token(),
        ]) !!};
    </script>
</head>
<body>

@include('basemodelfields::menu')
@include('basemodelfields::alerts')

<div class="container-fluid admin-cont">
    @yield('content')
</div>


<script src="{{asset('vendor/basemodelfields/assets/jquery-3.3.1.min.js')}}" type="text/javascript"></script>
<script src="{{asset('vendor/basemodelfields/assets/jquery-ui-1.12.1.custom/jquery-ui.min.js')}}"
        type="text/javascript"></script>
<script src="{{asset('vendor/basemodelfields/assets/bootstrap-3.3.7-dist/js/bootstrap.min.js')}}"
        type="text/javascript"></script>
<script src="{{asset('vendor/basemodelfields/assets/ckeditor/ckeditor.js')}}" type="text/javascript"></script>
<script src="{{asset('vendor/basemodelfields/assets/cropper-master/dist/cropper.min.js')}}" type="text/javascript"></script>
<script src="{{asset('vendor/basemodelfields/assets/DataTables/datatables.min.js')}}" type="text/javascript"></script>
<script src="{{asset('vendor/basemodelfields/assets/xlsx.full.min.js')}}" type="text/javascript"></script>

<script src="{{asset('vendor/basemodelfields/js/core.js')}}" type="text/javascript"></script>
<script src="{{asset('vendor/basemodelfields/js/forms.js')}}" type="text/javascript"></script>
<script src="{{asset('vendor/basemodelfields/js/elements_list.js')}}" type="text/javascript"></script>
<script type="text/javascript">
    $('.dropdown-toggle').dropdown();
    b4b.cfg.DOMAIN_SAFE_ASSETS_CDN_URL = "{{asset('assets')}}";
    b4b.cfg.BASE_DOMAIN = "{{asset('assets')}}";
</script>
</body>
</html>
