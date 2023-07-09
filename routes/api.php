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

$router->get('/liveboard', function (Request $request) use ($router) {
    return redirect(route('v1.liveboard', $_GET));
});

$router->get('/connections', function (Request $request) use ($router) {
    return redirect(route('v1.journeyplanning', $_GET));
});

$router->get('/vehicle', function (Request $request) use ($router) {
    return redirect(route('v1.datedvehiclejourney', $_GET));
});

$router->get('/composition', function (Request $request) use ($router) {
    return http_redirect('/v1/composition', $_GET);
});

$router->get('/logs', function (Request $request) use ($router) {
    return http_redirect('/v1/logs', $_GET);
});

$router->group(['prefix' => 'v1'], function () use ($router) {
    $router->get('/liveboard', ['as' => 'v1.liveboard', 'uses' => 'LiveboardV1Controller@getLiveboardById']);
    $router->get('/connections', ['as' => 'v1.journeyplanning', 'uses' => 'JourneyPlanningV1Controller@getJourneyPlanning']);
    $router->get('/vehicle', ['as' => 'v1.datedvehiclejourney', 'uses' => 'DatedVehicleJourneyV1Controller@getVehicleById']);
});

$router->group(['prefix' => 'v2'], function () use ($router) {
    $router->get('/liveboard/{arrdep}/{id}', ['as' => 'v2.liveboard', 'uses' => 'LiveboardV2Controller@getLiveboardById']);
    $router->get('/journeyplanning/{from}/{to}', ['as' => 'v2.journeyplanning', 'uses' => 'JourneyPlanningV2Controller@getJourneyPlanning']);
    $router->get('/journeyplanning/{from}/{to}/{arrdep}/{datetime}', ['as' => 'v2.journeyplanning.withTime', 'uses' => 'JourneyPlanningV2Controller@getJourneyPlanning']);
    $router->get('/vehicle/{id}', ['as' => 'v2.datedVehicleJourney', 'uses' => 'DatedVehicleJourneyV2Controller@getDatedVehicleJourney']);
    $router->get('/vehicle/{id}/{datetime}', ['as' => 'v2.datedVehicleJourney.withTime', 'uses' => 'DatedVehicleJourneyV2Controller@getDatedVehicleJourney']);
});

