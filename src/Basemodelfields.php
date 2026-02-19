<?php

namespace Mardok9185\Basemodelfields;

use Route;
use App;
use Illuminate\Support\Str;

class Basemodelfields
{
    public static $routes_name = array();
    public static $route_prefix = '';

    public static function route($sections = array())
    {
        $config = App::make('config');
        self::$route_prefix = $config->get('basemodelfields.route_prefix');
        foreach ($sections as $section) {
            $section = strtolower($section);
            $controller = '\Mardok9185\Basemodelfields\Http\Controllers\BasemodelfieldsController';
            $url = str_replace('_', '-', $section);
            self::$routes_name[] = self::$route_prefix . '.' . $section;

            if ($section == 'audit') {
                Route::get('audit', '\Mardok9185\Basemodelfields\Http\Controllers\BasemodelfieldsAuditController@index')->name(self::$route_prefix . '.' . $section);
            } else {
                Route::get('/' . $url . '/form/{id?}', $controller . '@form')->name(self::$route_prefix . '.' . $section . '.form');
                Route::post('/' . $url . '/{id?}', $controller . '@store')->name(self::$route_prefix . '.' . $section . '.post');
                Route::post('/' . $url . '/delete/{id}', $controller . '@delete')->name(self::$route_prefix . '.' . $section . '.delete');
                Route::get('/' . $url, $controller . '@index')->name(self::$route_prefix . '.' . $section);
            }
        }
    }

    public static function get_route($section = '')
    {
        if (!$section) {
            $section = self::getSectionFromRouteName();
        }
        $route = (strpos($section, self::$route_prefix) !== false) ? $section : self::$route_prefix . '.' . $section;
        return $route;
    }

    public static function post_route($section = '')
    {
        return self::get_route($section) . '.post';
    }

    public static function form_route($section = '')
    {
        return self::get_route($section) . '.form';
    }

    public static function delete_route($section = '')
    {
        return self::get_route($section) . '.delete';
    }

    public static function labelFromRouteName($section)
    {
        $label = str_replace(self::$route_prefix . '.', '', $section);
        $label = str_replace('_', ' ', $label);
        return ucfirst($label);
    }

    public static function getRoutesName()
    {
        return self::$routes_name;
    }

    public static function getSectionFromRouteName()
    {
        $section = str_replace(self::$route_prefix . '.', '', Route::currentRouteName());
        $section = explode('.', $section);
        return $section[0];
    }

    public static function getResourceAsset($type = 'IMAGE')
    {
        $section = self::getSectionFromRouteName();
        $subdir = Str::plural(strtolower($type));
        $dir = (env('STORE_' . $type . '_ASSET')) ?
            env('STORE_' . $type . '_ASSET') . '/' . $subdir . '/' . $section :
            asset('/res' . '/' . $subdir . '/' . $section);

        return $dir;

    }

}
