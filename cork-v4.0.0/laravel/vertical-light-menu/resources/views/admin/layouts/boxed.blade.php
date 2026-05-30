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

    <div class="col-xl-3 col-lg-6 col-md-6  mb-4">
        <div class="card bg-primary">
            <div class="card-body pt-3">
                <h5 class="card-title mb-3">Card Title</h5>
                <p class="card-text">Powerful CRM admin dashboard template based on Bootstrap and Sass for all kind of back-end projects.</p>
            </div>
            <div class="card-footer px-4 pt-0 border-0">
                <a href="https://themeforest.net/item/cork-responsive-admin-dashboard-template/25582188" target="_blank">Visit on Themeforest.</a>
            </div>
        </div>
    </div>

    <div class="col-xl-3 col-lg-6 col-md-6  mb-4">
        <div class="card bg-secondary">
            <div class="card-body pt-3">
                <h5 class="card-title mb-3">Card Title</h5>
                <p class="card-text">Powerful CRM admin dashboard template based on Bootstrap and Sass for all kind of back-end projects.</p>
            </div>
            <div class="card-footer px-4 pt-0 border-0">
                <a href="https://themeforest.net/item/cork-responsive-admin-dashboard-template/25582188" target="_blank">Visit on Themeforest.</a>
            </div>
        </div>
    </div>
    
    <div class="col-xl-3 col-lg-6 col-md-6  mb-4">
        <div class="card bg-dark">
            <div class="card-body pt-3">
                <h5 class="card-title mb-3">Card Title</h5>
                <p class="card-text">Powerful CRM admin dashboard template based on Bootstrap and Sass for all kind of back-end projects.</p>
            </div>
            <div class="card-footer px-4 pt-0 border-0">
                <a href="https://themeforest.net/item/cork-responsive-admin-dashboard-template/25582188" target="_blank">Visit on Themeforest.</a>
            </div>
        </div>
    </div>

    <div class="col-xl-3 col-lg-6 col-md-6  mb-4">
        <div class="card bg-danger">
            <div class="card-body pt-3">
                <h5 class="card-title mb-3">Card Title</h5>
                <p class="card-text">Powerful CRM admin dashboard template based on Bootstrap and Sass for all kind of back-end projects.</p>
            </div>
            <div class="card-footer px-4 pt-0 border-0">
                <a href="https://themeforest.net/item/cork-responsive-admin-dashboard-template/25582188" target="_blank">Visit on Themeforest.</a>
            </div>
        </div>
    </div>

    <div class="col-lg-12 col-md-12 layout-spacing">
        <div class="statbox widget box box-shadow">
            <div class="widget-header">
                <div class="row">
                    <div class="col-xl-12 col-md-12 col-sm-12 col-12">
                        <h4>Table</h4>
                    </div>          
                </div>
            </div>
            <div class="widget-content widget-content-area">
                <div class="table-responsive">
                    <table class="table table-bordered mb-4">
                        <thead>
                            <tr>
                                <th>Name</th>
                                <th>Date</th>
                                <th>Sale</th>
                                <th class="text-center">Status</th>
                                <th class="text-center">Action</th>
                            </tr>
                            <tr aria-hidden="true" class="mt-3 d-block table-row-hidden"></tr>
                        </thead>
                        <tbody>
                            <tr>
                                <td>Shaun</td>
                                <td>10/08/2022</td>
                                <td>320</td>
                                <td class="text-center"><span class="badge badge-success">Approved</span></td>
                                <td class="text-center">
                                    
                                    <div class="dropdown custom-dropdown">
                                        <a class="dropdown-toggle" href="#" role="button" id="dropdownMenuLink1" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                                            <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="feather feather-more-horizontal"><circle cx="12" cy="12" r="1"></circle><circle cx="19" cy="12" r="1"></circle><circle cx="5" cy="12" r="1"></circle></svg>
                                        </a>

                                        <div class="dropdown-menu" aria-labelledby="dropdownMenuLink1">
                                            <a class="dropdown-item" href="javascript:void(0);">Download</a>
                                            <a class="dropdown-item" href="javascript:void(0);">Share</a>
                                            <a class="dropdown-item" href="javascript:void(0);">Edit</a>
                                            <a class="dropdown-item" href="javascript:void(0);">Delete</a>
                                        </div>
                                    </div>

                                </td>
                            </tr>
                            <tr>
                                <td>Alma</td>
                                <td>11/08/2022</td>
                                <td>420</td>
                                <td class="text-center"><span class="badge badge-primary">In Progress</span></td>
                                <td class="text-center">
                                    
                                    <div class="dropdown custom-dropdown">
                                        <a class="dropdown-toggle" href="#" role="button" id="dropdownMenuLink2" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                                            <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="feather feather-more-horizontal"><circle cx="12" cy="12" r="1"></circle><circle cx="19" cy="12" r="1"></circle><circle cx="5" cy="12" r="1"></circle></svg>
                                        </a>

                                        <div class="dropdown-menu" aria-labelledby="dropdownMenuLink2">
                                            <a class="dropdown-item" href="javascript:void(0);">Download</a>
                                            <a class="dropdown-item" href="javascript:void(0);">Share</a>
                                            <a class="dropdown-item" href="javascript:void(0);">Edit</a>
                                            <a class="dropdown-item" href="javascript:void(0);">Delete</a>
                                        </div>
                                    </div>
                                </td>
                            </tr>
                            <tr>
                                <td>Kelly</td>
                                <td>12/08/2022</td>
                                <td>130</td>
                                <td class="text-center"><span class="badge badge-warning">Suspended</span></td>
                                <td class="text-center">
                                    
                                    <div class="dropdown custom-dropdown">
                                        <a class="dropdown-toggle" href="#" role="button" id="dropdownMenuLink3" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                                            <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="feather feather-more-horizontal"><circle cx="12" cy="12" r="1"></circle><circle cx="19" cy="12" r="1"></circle><circle cx="5" cy="12" r="1"></circle></svg>
                                        </a>

                                        <div class="dropdown-menu" aria-labelledby="dropdownMenuLink3">
                                            <a class="dropdown-item" href="javascript:void(0);">Download</a>
                                            <a class="dropdown-item" href="javascript:void(0);">Share</a>
                                            <a class="dropdown-item" href="javascript:void(0);">Edit</a>
                                            <a class="dropdown-item" href="javascript:void(0);">Delete</a>
                                        </div>
                                    </div>
                                </td>
                            </tr>
                            <tr>
                                <td>Xavier</td>
                                <td>13/08/2022</td>
                                <td>260</td>
                                <td class="text-center"><span class="badge badge-danger">Blocked</span></td>
                                <td class="text-center">
                                    
                                    <div class="dropdown custom-dropdown">
                                        <a class="dropdown-toggle" href="#" role="button" id="dropdownMenuLink4" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                                            <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="feather feather-more-horizontal"><circle cx="12" cy="12" r="1"></circle><circle cx="19" cy="12" r="1"></circle><circle cx="5" cy="12" r="1"></circle></svg>
                                        </a>

                                        <div class="dropdown-menu" aria-labelledby="dropdownMenuLink4">
                                            <a class="dropdown-item" href="javascript:void(0);">Download</a>
                                            <a class="dropdown-item" href="javascript:void(0);">Share</a>
                                            <a class="dropdown-item" href="javascript:void(0);">Edit</a>
                                            <a class="dropdown-item" href="javascript:void(0);">Delete</a>
                                        </div>
                                    </div>
                                </td>
                            </tr>
                            <tr>
                                <td>Andy</td>
                                <td>14/08/2022</td>
                                <td>99</td>
                                <td class="text-center"><span class="badge badge-secondary">On leave</span></td>
                                <td class="text-center">                                                    
                                    <div class="dropdown custom-dropdown">
                                        <a class="dropdown-toggle" href="#" role="button" id="dropdownMenuLink5" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                                            <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="feather feather-more-horizontal"><circle cx="12" cy="12" r="1"></circle><circle cx="19" cy="12" r="1"></circle><circle cx="5" cy="12" r="1"></circle></svg>
                                        </a>

                                        <div class="dropdown-menu" aria-labelledby="dropdownMenuLink5">
                                            <a class="dropdown-item" href="javascript:void(0);">Download</a>
                                            <a class="dropdown-item" href="javascript:void(0);">Share</a>
                                            <a class="dropdown-item" href="javascript:void(0);">Edit</a>
                                            <a class="dropdown-item" href="javascript:void(0);">Delete</a>
                                        </div>
                                    </div>
                                </td>
                            </tr>
                            <tr>
                                <td>Justin</td>
                                <td>15/08/2022</td>
                                <td>555</td>
                                <td class="text-center"><span class="badge badge-info">Pending</span></td>
                                <td class="text-center">                                                    
                                    <div class="dropdown custom-dropdown">
                                        <a class="dropdown-toggle" href="#" role="button" id="dropdownMenuLink6" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                                            <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="feather feather-more-horizontal"><circle cx="12" cy="12" r="1"></circle><circle cx="19" cy="12" r="1"></circle><circle cx="5" cy="12" r="1"></circle></svg>
                                        </a>

                                        <div class="dropdown-menu" aria-labelledby="dropdownMenuLink6">
                                            <a class="dropdown-item" href="javascript:void(0);">Download</a>
                                            <a class="dropdown-item" href="javascript:void(0);">Share</a>
                                            <a class="dropdown-item" href="javascript:void(0);">Edit</a>
                                            <a class="dropdown-item" href="javascript:void(0);">Delete</a>
                                        </div>
                                    </div>
                                </td>
                            </tr>
                            <tr>
                                <td>Amy</td>
                                <td>16/08/2022</td>
                                <td>300</td>
                                <td class="text-center"><span class="badge badge-dark">Deleted</span></td>
                                <td class="text-center">
                                    
                                    <div class="dropdown custom-dropdown">
                                        <a class="dropdown-toggle" href="#" role="button" id="dropdownMenuLink7" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                                            <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="feather feather-more-horizontal"><circle cx="12" cy="12" r="1"></circle><circle cx="19" cy="12" r="1"></circle><circle cx="5" cy="12" r="1"></circle></svg>
                                        </a>

                                        <div class="dropdown-menu" aria-labelledby="dropdownMenuLink7">
                                            <a class="dropdown-item" href="javascript:void(0);">Download</a>
                                            <a class="dropdown-item" href="javascript:void(0);">Share</a>
                                            <a class="dropdown-item" href="javascript:void(0);">Edit</a>
                                            <a class="dropdown-item" href="javascript:void(0);">Delete</a>
                                        </div>
                                    </div>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

</div>
@endsection

@section('scripts')
{{-- Scripts Here --}}
@endsection