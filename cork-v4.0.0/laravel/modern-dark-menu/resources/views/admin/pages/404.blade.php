@extends('layouts.app')

@section('styles')
{{-- Style Here --}}
    <!--  BEGIN CUSTOM STYLE FILE  -->
    @vite(['resources/scss/light/assets/pages/error/error.scss'])
    @vite(['resources/scss/dark/assets/pages/error/error.scss'])
    <!--  END CUSTOM STYLE FILE  -->

    <style>
        body.layout-dark .theme-logo.dark-element {
            display: inline-block;
        }
        .theme-logo.dark-element {
            display: none;
        }
        body.layout-dark .theme-logo.light-element {
            display: none;
        }
        .theme-logo.light-element {
            display: inline-block;
        }
    </style>
@endsection

@section('content')
<div class="container-fluid">
    <div class="row"> 
        <div class="col-md-4 mr-auto mt-5 text-md-left text-center">
            <a href="{{getRouterValue()}}dashboard/analytics" class="ml-md-5">
                <img alt="image-404" src="{{Vite::asset('resources/images/logo.svg')}}" class="dark-element theme-logo">
                <img alt="image-404" src="{{Vite::asset('resources/images/logo2.svg')}}" class="light-element theme-logo">
            </a>
        </div>
    </div>
</div>
<div class="container-fluid error-content">
    <div class="">
        <h1 class="error-number">404</h1>
        <p class="mini-text">Ooops!</p>
        <p class="error-text mb-5 mt-1">The page you requested was not found!</p>
        <img src="{{Vite::asset('resources/images/error.svg')}}" alt="cork-admin-404" class="error-img">
        <a href="{{getRouterValue()}}dashboard/analytics" class="btn btn-dark mt-5">Go Back</a>
    </div>
</div>  
@endsection

@section('scripts')
{{-- Scripts Here --}}
@endsection