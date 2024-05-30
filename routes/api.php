<?php

/** @var Router $router */

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
use Laravel\Lumen\Routing\Router;

$router->get('/stations{suffix:.*}', function (Request $request) use ($router) {
    return redirect(route('v1.stations', $_GET, $request->isSecure()), 301, [
        'Access-Control-Allow-Origin'   => '*',
        'Access-Control-Allow-Headers'  => '*',
        'Access-Control-Expose-Headers' => '*',
    ]);
});

$router->get('/liveboard{suffix:.*}', function (Request $request) use ($router) {
    return redirect(route('v1.liveboard', $_GET, $request->isSecure()), 301, [
        'Access-Control-Allow-Origin'   => '*',
        'Access-Control-Allow-Headers'  => '*',
        'Access-Control-Expose-Headers' => '*',
    ]);
});

$router->get('/connections{suffix:.*}', function (Request $request) use ($router) {
    return redirect(route('v1.journeyPlanning', $_GET, $request->isSecure()), 301, [
        'Access-Control-Allow-Origin'   => '*',
        'Access-Control-Allow-Headers'  => '*',
        'Access-Control-Expose-Headers' => '*',
    ]);
});

$router->get('/vehicle{suffix:.*}', function (Request $request) use ($router) {
    return redirect(route('v1.datedVehicleJourney', $_GET, $request->isSecure()), 301, [
        'Access-Control-Allow-Origin'   => '*',
        'Access-Control-Allow-Headers'  => '*',
        'Access-Control-Expose-Headers' => '*',
    ]);
});

$router->get('/disturbances{suffix:.*}', function (Request $request) use ($router) {
    return redirect(route('v1.serviceAlerts', $_GET, $request->isSecure()), 301, [
        'Access-Control-Allow-Origin'   => '*',
        'Access-Control-Allow-Headers'  => '*',
        'Access-Control-Expose-Headers' => '*',
    ]);
});

$router->get('/composition', function (Request $request) use ($router) {
    return redirect(route('v1.composition', $_GET, $request->isSecure()), 301, [
        'Access-Control-Allow-Origin'   => '*',
        'Access-Control-Allow-Headers'  => '*',
        'Access-Control-Expose-Headers' => '*',
    ]);
});

$router->get('/logs', function (Request $request) use ($router) {
    return redirect(route('v1.logs', $_GET, $request->isSecure()), 301, [
        'Access-Control-Allow-Origin'   => '*',
        'Access-Control-Allow-Headers'  => '*',
        'Access-Control-Expose-Headers' => '*',
    ]);
});

// Can't redirect a POST request, so handle this twice
$router->post('/feedback/occupancy.php', ['as' => 'v1.occupancy', 'uses' => 'OccupancyController@store']);

$router->group(['prefix' => 'v1'], function () use ($router) {
    $router->get('/stations{suffix:.*}', ['as' => 'v1.stations', 'uses' => 'StationsV1Controller@list']);
    $router->get('/liveboard{suffix:.*}', ['as' => 'v1.liveboard', 'uses' => 'LiveboardV1Controller@getLiveboardById']);
    $router->get('/connections{suffix:.*}', ['as' => 'v1.journeyPlanning', 'uses' => 'JourneyPlanningV1Controller@getJourneyPlanning']);
    $router->get('/vehicle{suffix:.*}', ['as' => 'v1.datedVehicleJourney', 'uses' => 'DatedVehicleJourneyV1Controller@getVehicleById']);
    $router->get('/disturbances{suffix:.*}', ['as' => 'v1.serviceAlerts', 'uses' => 'ServiceAlertsV1Controller@getServiceAlerts']);
    $router->get('/composition{suffix:.*}', ['as' => 'v1.composition', 'uses' => 'CompositionV1Controller@getVehiclecomposition']);
    $router->get('/logs', ['as' => 'v1.logs', 'uses' => 'LogController@getLogs']);
    $router->post('/feedback/occupancy', ['as' => 'v1.occupancy', 'uses' => 'OccupancyController@store']);
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
    $router->post('/feedback/occupancy', ['as' => 'v2.occupancy', 'uses' => 'OccupancyController@store']);
    // A raw dump endpoint, so these logs can still be published to gtfs.irail.be even if they are stored in a database
    $router->get('/feedback/reports', ['as' => 'v2.occupancy.export', 'uses' => 'OccupancyController@dump']);
});
