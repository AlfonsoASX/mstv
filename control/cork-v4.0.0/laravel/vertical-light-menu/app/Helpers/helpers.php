<?php


if (!function_exists('getRouterValue')) {
    function getRouterValue() {

        if (config('app.env') === 'production') {

            $__getRoutingValue = '/cork/laravel/vertical-light-menu/';
            
        } else if (config('app.env') === 'pre_production') {

            $__getRoutingValue = '/cork/laravel_cork_4/vertical-light-menu/';

        } else {
            
            $__getRoutingValue = '/';

        }


        // if (config('app.env') === 'production') {

        //     $__getRoutingValue = '/cork/laravel/vertical-light-menu/';
            
        // } else {
            
        //     $__getRoutingValue = '/';

        // }
        
        
        return $__getRoutingValue;

    }
}