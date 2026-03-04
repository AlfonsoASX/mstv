@extends('layouts.app')

@section('styles')
{{-- Style Here --}}
    <!--  BEGIN CUSTOM STYLE FILE  -->
    @vite(['resources/scss/light/assets/elements/alert.scss'])
    @vite(['resources/scss/dark/assets/elements/alert.scss'])
    <!--  END CUSTOM STYLE FILE  -->
@endsection

@section('content')
{{-- Content Here --}}
<div class="row layout-top-spacing">

    <div class="col-12">
        <div class="alert alert-arrow-right alert-icon-right alert-light-success alert-dismissible fade show mb-4" role="alert">
            <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="feather feather-alert-circle"><circle cx="12" cy="12" r="10"></circle><line x1="12" y1="8" x2="12" y2="12"></line><line x1="12" y1="16" x2="12" y2="16"></line></svg>
            <strong>Kick Start you new project with ease!</strong> Learn more about Starter Kit by refering to the <a target="_blank" href="https://designreset.com/cork/documentation/getting-started.html">Documentation</a>
        </div>
    </div>

    <div class="col-md-12">
    </div>
    
</div>
@endsection

@section('scripts')
{{-- Scripts Here --}}
@endsection