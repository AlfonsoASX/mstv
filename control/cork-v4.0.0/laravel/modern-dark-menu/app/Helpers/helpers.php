<?php


if (!function_exists('getRouterValue')) {
    function getRouterValue() {

        if (config('app.env') === 'production') {

            $__getRoutingValue = '/cork/laravel/modern-dark-menu/';
            
        } else if (config('app.env') === 'pre_production') {

            $__getRoutingValue = '/cork/laravel_cork_4/modern-dark-menu/';

        } else {
            
            $__getRoutingValue = '/';

        }        
        
        return $__getRoutingValue;

    }
}