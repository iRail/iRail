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
    return redirect(route('v1.journeyPlanning', $_GET));
});

$router->get('/vehicle', function (Request $request) use ($router) {
    return redirect(route('v1.datedVehicleJourney', $_GET));
});

$router->get('/disturbances', function (Request $request) use ($router) {
    return redirect(route('v1.serviceAlerts', $_GET));
});

$router->get('/composition', function (Request $request) use ($router) {
    return redirect(route('v1.composition', $_GET));
});

$router->get('/logs', function (Request $request) use ($router) {
    return redirect(route('v1.logs', $_GET));
});

$router->group(['prefix' => 'v1'], function () use ($router) {
    $router->get('/liveboard', ['as' => 'v1.liveboard', 'uses' => 'LiveboardV1Controller@getLiveboardById']);
    $router->get('/connections', ['as' => 'v1.journeyPlanning', 'uses' => 'JourneyPlanningV1Controller@getJourneyPlanning']);
    $router->get('/vehicle', ['as' => 'v1.datedVehicleJourney', 'uses' => 'DatedVehicleJourneyV1Controller@getVehicleById']);
    $router->get('/disturbances', ['as' => 'v1.serviceAlerts', 'uses' => 'ServiceAlertsV1Controller@getServiceAlerts']);
    $router->get('/composition', ['as' => 'v1.composition', 'uses' => 'CompositionV1Controller@getVehiclecomposition']);
    $router->get('/logs', ['as' => 'v1.logs', 'uses' => 'LogController@getLogs']);
});

$router->group(['prefix' => 'v2'], function () use ($router) {
    $router->get('/liveboard/{departureArrivalMode}/{id}', ['as' => 'v2.liveboard', 'uses' => 'LiveboardV2Controller@getLiveboardById']);
    $router->get('/journeyplanning/{from}/{to}', ['as' => 'v2.journeyPlanning', 'uses' => 'JourneyPlanningV2Controller@getJourneyPlanning']);
    $router->get('/journeyplanning/{from}/{to}/{arrdep}/{datetime}', ['as' => 'v2.journeyPlanning.withTime', 'uses' => 'JourneyPlanningV2Controller@getJourneyPlanning']);
    $router->get('/vehicle/{id}', ['as' => 'v2.datedVehicleJourney', 'uses' => 'DatedVehicleJourneyV2Controller@getDatedVehicleJourney']);
    $router->get('/vehicle/{id}/{datetime}', ['as' => 'v2.datedVehicleJourney.withTime', 'uses' => 'DatedVehicleJourneyV2Controller@getDatedVehicleJourney']);
    $router->get('/servicealerts', ['as' => 'v2.serviceAlerts', 'uses' => 'ServiceAlertsV2Controller@getServiceAlerts']);
    $router->get('/composition', ['as' => 'v2.composition', 'uses' => 'CompositionV2Controller@getVehiclecomposition']);
    $router->get('/logs', ['as' => 'v2.logs', 'uses' => 'LogController@getLogs']);
});

