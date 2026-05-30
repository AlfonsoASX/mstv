<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1, shrink-to-fit=no">
    <title>
        @isset($title)
            @if ($title !== '')
                {{$title}} | Multipurpose Bootstrap Dashboard Template
            @else
                CORK Admin | Multipurpose Bootstrap Dashboard Template
            @endif
        @endisset
    </title>
    <link rel="icon" type="image/x-icon" href="{{Vite::asset('resources/images/favicon.ico')}}"/>
    @vite(['resources/scss/layouts/semi-dark-menu/light/loader.scss'])
    @vite(['resources/scss/layouts/semi-dark-menu/dark/loader.scss'])
    @vite(['resources/layouts/semi-dark-menu/loader.js'])

    <!-- BEGIN GLOBAL MANDATORY STYLES -->
    <link href="https://fonts.googleapis.com/css?family=Nunito:400,600,700" rel="stylesheet">
    <link rel="stylesheet" href="{{asset('plugins/src/bootstrap/css/bootstrap.min.css')}}">
    @vite(['resources/scss/light/assets/main.scss'])
    @vite(['resources/scss/dark/assets/main.scss'])
    @vite(['resources/scss/light/plugins/perfect-scrollbar/perfect-scrollbar.scss'])
    @vite(['resources/scss/dark/plugins/perfect-scrollbar/perfect-scrollbar.scss'])
    <link rel="stylesheet" href="{{asset('plugins/src/waves/waves.min.css')}}">
    @vite(['resources/scss/layouts/semi-dark-menu/light/structure.scss'])
    @vite(['resources/scss/layouts/semi-dark-menu/dark/structure.scss'])
    <link rel="stylesheet" href="{{asset('plugins/src/highlight/styles/monokai-sublime.css')}}">
    
    <style>
        body:not(.dark) .logo-light {
            display: block;
        }
        body:not(.dark) .logo-dark {
            display: none;
        }
        body.dark .logo-light {
            display: none;
        }
        body.dark .logo-dark {
            display: block;
        }
    </style>
   

    @isset($scrollspy)
        @if ($scrollspy)
            @vite(['resources/scss/light/assets/scrollspyNav.scss'])
            @vite(['resources/scss/dark/assets/scrollspyNav.scss'])
        @endif
    @endisset
    <!-- END GLOBAL MANDATORY STYLES -->

    <!-- BEGIN PAGE LEVEL PLUGINS/CUSTOM STYLES -->
    @yield('styles')
    <!-- END PAGE LEVEL PLUGINS/CUSTOM STYLES -->

</head>
<body class="
    {{ Request::routeIs('error404') ? 'error text-center' : '' }}
    {{ Request::routeIs('maintenance') ? 'maintanence text-center' : '' }}
    {{ 
        (Request::routeIs('boxedSignIn') || 
        Request::routeIs('boxedSignUp') || 
        Request::routeIs('boxedLockscreen') || 
        Request::routeIs('boxedPasswordReset') || 
        Request::routeIs('boxed2sv')) ? 'form' : '' 
    }}

    {{ 
        (Request::routeIs('coverSignIn') || 
        Request::routeIs('coverSignUp') || 
        Request::routeIs('coverLockscreen') || 
        Request::routeIs('coverPasswordReset') || 
        Request::routeIs('cover2sv')) ? 'form' : '' 
    }}
    {{ Request::routeIs('collapsed') ? 'alt-menu' : '' }}
    
    
" layout="{{ Request::routeIs('boxed') ? 'boxed' : 'full-width' }}" >
    <!-- BEGIN LOADER -->
    <div id="load_screen"> <div class="loader"> <div class="loader-content">
        <div class="spinner-grow align-self-center"></div>
    </div></div></div>
    <!--  END LOADER -->
    
    @isset($simplePage)

        @if ($simplePage)

            @yield('content')
            
        @else

        @if (!Request::routeIs('blank'))
            <!--  BEGIN NAVBAR  -->
            @include('layouts.navbar')
            <!--  END NAVBAR  -->
        @endif

            <!--  BEGIN MAIN CONTAINER  -->
            <div class="main-container" id="container">

                <div class="overlay"></div>
                <div class="search-overlay"></div>

                @if (!Request::routeIs('blank'))
                    <!--  BEGIN SIDEBAR  -->
                    @include('layouts.sidebar')
                    <!--  END SIDEBAR  -->                    
                @endif

                <!--  BEGIN CONTENT AREA  -->
                <div id="content" class="main-content {{ Request::routeIs('blank') ? 'ms-0 mt-0' : '' }}">

                    @isset($scrollspy)
                        
                        @if ($scrollspy)
                            <div class="container">
                                <div class="container">                
                                    <div class="middle-content container-xxl p-0">
            
                                        <!--  BEGIN BREADCRUMBS  -->
                                        {{-- @include('layouts.secondaryNav') --}}
                                        <!--  END BREADCRUMBS  -->
                                        
                                        <!--  BEGIN CONTENT  -->
                                        @yield('content')
                                        <!--  END CONTENT  -->

                                    </div>
                                </div>
                            </div>
                        @else
                            <div class="layout-px-spacing">
                                <div class="middle-content {{ Request::routeIs('boxed') ? 'container-xxl' : '' }} p-0">
                                    @if (!Request::routeIs('blank'))
                                        <!--  BEGIN BREADCRUMBS  -->
                                        {{-- @include('layouts.secondaryNav') --}}
                                        <!--  END BREADCRUMBS  -->
                                    @endif
                                                            
                                    <!--  BEGIN CONTENT  -->
                                    @yield('content')
                                    <!--  END CONTENT  -->
                                </div>

                            </div>
                        @endif

                    @endisset
                    
                    @if (!Request::routeIs('blank'))
                        <!--  BEGIN FOOTER  -->
                        @include('layouts.footer')
                        <!--  END FOOTER  -->
                    @endif
                </div>
                <!--  END CONTENT AREA  -->

            </div>
            <!-- END MAIN CONTAINER -->
            
        @endif
        
    @endisset

    <!-- BEGIN GLOBAL MANDATORY SCRIPTS -->
    <script src="{{asset('plugins/src/bootstrap/js/bootstrap.bundle.min.js')}}"></script>
    <script src="{{asset('plugins/src/perfect-scrollbar/perfect-scrollbar.min.js')}}"></script>
    <script src="{{asset('plugins/src/mousetrap/mousetrap.min.js')}}"></script>
    <script src="{{asset('plugins/src/waves/waves.min.js')}}"></script>
    <script src="{{asset('plugins/src/highlight/highlight.pack.js')}}"></script>
    @vite(['resources/layouts/semi-dark-menu/app.js'])
    
    @isset($scrollspy)
        @if ($scrollspy)
            @vite(['resources/js/scrollspyNav.js'])
        @endif
    @endisset
    
    <!-- END GLOBAL MANDATORY SCRIPTS -->

    <!-- BEGIN PAGE LEVEL PLUGINS/CUSTOM SCRIPTS -->
    @yield('scripts')
    <!-- BEGIN PAGE LEVEL PLUGINS/CUSTOM SCRIPTS -->

</body>
</html>