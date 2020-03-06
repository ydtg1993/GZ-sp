<?php

use Illuminate\Routing\Router;

Admin::routes();

Route::group([
    'prefix'        => config('admin.route.prefix'),
    'namespace'     => config('admin.route.namespace'),
    'middleware'    => config('admin.route.middleware'),
], function (Router $router) {

    $router->get('/', 'HomeController@index')->name('admin.home');
    $router->resource('/task', 'TaskController');
    $router->resource('/tag', 'TagController');
    $router->resource('/video', 'VideoController');
    $router->get('/publish/{id}','VideoController@publish');
    $router->post('/publishToBj','VideoController@publishToBj');
    $router->resource('/account', 'AccountController');

    $router->resource('/accountStatistic','AccountStatisticController');
});
