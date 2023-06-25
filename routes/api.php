<?php

/** @var \Laravel\Lumen\Routing\Router $router */

/*
|--------------------------------------------------------------------------
| Application Routes
|--------------------------------------------------------------------------
|
| Here is where you can register all of the routes for an application.
| It is a breeze. Simply tell Lumen the URIs it should respond to
| and give it the Closure to call when that URI is requested.
|
*/

use Illuminate\Http\Request;
use Irail\Http\Controllers\LiveboardV1Controller;

$router->get('/liveboard', function (Request $request)  use ($router) {
    return redirect(route('v1.liveboard' , $_GET));
});

$router->get('/connections', function (Request $request)  use ($router) {
    return http_redirect('/v1/connections', $_GET);
});

$router->get('/vehicle', function (Request $request) use ($router) {
    return http_redirect('/v1/vehicle', $_GET);
});

$router->get('/composition', function (Request $request) use ($router) {
    return http_redirect('/v1/composition', $_GET);
});

$router->get('/logs', function (Request $request) use ($router) {
    return http_redirect('/v1/logs', $_GET);
});

$router->group(['prefix' => 'v1'], function () use ($router) {
    $router->get('/liveboard', ['as' => 'v1.liveboard', 'uses' => 'LiveboardV1Controller@getLiveboardById']);
});

$router->group(['prefix' => 'v2'], function () use ($router) {
    $router->get('/liveboard/{arrdep}/{id}', ['as' => 'v2.liveboard', 'uses' => 'LiveboardV2Controller@getLiveboardById']);
});

