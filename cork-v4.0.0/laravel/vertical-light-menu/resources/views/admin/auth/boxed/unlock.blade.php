@extends('layouts.app')

@section('styles')
{{-- Style Here --}}
@vite(['resources/scss/light/assets/authentication/auth-boxed.scss'])
@vite(['resources/scss/dark/assets/authentication/auth-boxed.scss'])
@endsection

@section('content')
{{-- Content Here --}}
<div class="auth-container d-flex h-100">

    <div class="container mx-auto align-self-center">

        <div class="row">

            <div class="col-xxl-4 col-xl-5 col-lg-5 col-md-8 col-12 d-flex flex-column align-self-center mx-auto">
                <div class="card mt-3 mb-3">
                    <div class="card-body">

                        <div class="row">
                            <div class="col-md-12 mb-3">
                                
                                <div class="media mb-4">
                                    
                                    <div class="avatar avatar-lg me-3">
                                        <img alt="avatar" src="{{Vite::asset('resources/images/profile-7.jpeg')}}" class="rounded-circle">
                                    </div>

                                    <div class="media-body align-self-center">

                                        <h3 class="mb-0">Shaun Park</h3>
                                        <p class="mb-0">Enter your password to unlock your ID</p>

                                    </div>
                                    
                                </div>
                                
                            </div>
                            <div class="col-12">
                                <div class="mb-4">
                                    <label class="form-label">Password</label>
                                    <input type="text" class="form-control">
                                </div>
                            </div>
                            <div class="col-12">
                                <div class="mb-4">
                                    <button class="btn btn-secondary w-100">UNLOCK</button>
                                </div>
                            </div>
                            
                        </div>
                        
                    </div>
                </div>
            </div>
            
        </div>
        
    </div>

</div>
@endsection

@section('scripts')
{{-- Scripts Here --}}
@endsection