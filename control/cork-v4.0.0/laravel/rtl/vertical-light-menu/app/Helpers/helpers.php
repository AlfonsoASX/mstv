<?php


if (!function_exists('getRouterValue')) {
    function getRouterValue() {

        if (config('app.env') === 'production') {

            $__getRoutingValue = '/cork/laravel/rtl/vertical-light-menu/';
            
        } else if (config('app.env') === 'pre_production') {

            $__getRoutingValue = '/cork/laravel_cork_4/rtl/vertical-light-menu/';

        } else {
            
            $__getRoutingValue = '/';

        }
        
        
        return $__getRoutingValue;

    }
}