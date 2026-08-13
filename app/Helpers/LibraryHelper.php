<?php

use App\Models\User;

if (!function_exists('return_library')) {
    function return_library($object, $key_col, $value_col)
    {
        $data = array();
        foreach ($object as $item) {
            $data[$item->$key_col] = $item->$value_col;
        }
        return $data;
    }
}

if (!function_exists('lib_all_category')) {
    function lib_all_category()
    {
        return [];
    }
}

if (!function_exists('lib_category')) {
    function lib_category()
    {
        return [];
    }
}

if (!function_exists('lib_book_category')) {
    function lib_book_category()
    {
        return [];
    }
}

if (!function_exists('lib_brand')) {
    function lib_brand()
    {
        return [];
    }
}

if (!function_exists('lib_serviceMan')) {
    function lib_serviceMan()
    {
        return return_library(User::where('status', '1')->get(), 'id', 'name');
    }
}

if (!function_exists('lib_salesMan')) {
    function lib_salesMan()
    {
        return return_library(User::where('status', '1')->get(), 'id', 'name');
    }
}
