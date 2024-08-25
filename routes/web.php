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

use Laravel\Lumen\Routing\Router;

$router->get('/', function () {
    return redirect('https://docs.irail.be');
});

$router->get('/health', ['as' => 'status.health', 'uses' => 'StatusController@showStatus']);
$router->get('/maintain', ['as' => 'status.health', 'uses' => 'StatusController@maintain']);
$router->get('/cache/loadGtfs', ['as' => 'cache.warmup', 'uses' => 'StatusController@warmupCache']);
// $router->get('/cache/clear', ['as' => 'cache.clear', 'uses' => 'StatusController@resetCache']);
$router->get('/cache/clean', ['as' => 'cache.clear', 'uses' => 'StatusController@removeOutdatedCacheEntries']);
