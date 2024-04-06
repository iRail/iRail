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

$router->get('/health', ['as' => 'status.health', 'uses' => 'StatusController@showStatus']);
$router->get('/cache/loadGtfs', ['as' => 'cache.warmup', 'uses' => 'StatusController@warmupGtfsCache']);
$router->get('/cache/clear', ['as' => 'cache.clear', 'uses' => 'StatusController@resetCache']);
